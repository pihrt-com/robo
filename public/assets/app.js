(() => {
  "use strict";

  const longPressMs = 650;
  let longPressTimer;

  function setSaveStatus(state) {
    const status = document.querySelector("[data-save-status]");
    if (!status) return;
    if (state === "saving") status.textContent = "Ukládám…";
    else if (state === "saved") status.textContent = `Uloženo ${new Intl.DateTimeFormat("cs-CZ", { hour: "2-digit", minute: "2-digit", second: "2-digit" }).format(new Date())}`;
    else if (state === "error") status.textContent = "Uložení se nezdařilo";
    else status.textContent = "Čeká na změnu";
  }

  function setPendingCount(value) {
    const pending = document.querySelector("[data-pending-count]");
    if (pending && Number.isInteger(value) && value >= 0) pending.textContent = `Chybí ${value} dokončit`;
  }

  function openModal(id) {
    const dialog = document.getElementById(id);
    if (dialog && typeof dialog.showModal === "function") dialog.showModal();
  }

  async function repairNavigation() {
    const header = document.querySelector(".app-header");
    if (!header) return;
    const existingNavigation = header.querySelector(".nav-row");
    const needsUser = !header.querySelector(".signed-user");
    const needsNavigation = !existingNavigation || existingNavigation.children.length === 0;
    if (!needsUser && !needsNavigation) return;
    try {
      const response = await fetch("/robo/navigation-v043.php", {
        credentials: "same-origin",
        cache: "no-store",
        headers: { Accept: "application/json" },
      });
      if (response.status === 401) {
        window.location.assign("/robo/login");
        return;
      }
      if (!response.ok) throw new Error("Navigation endpoint failed");
      const payload = await response.json();
      if (needsUser) {
        const meta = header.querySelector(".meta-row");
        const logout = meta?.querySelector('form[action$="/logout"]');
        if (meta && logout) {
          const signedUser = document.createElement("span");
          signedUser.className = "signed-user";
          const username = document.createElement("strong");
          username.textContent = String(payload.username || "");
          const role = document.createElement("small");
          role.textContent = String(payload.role || "");
          signedUser.append(username, role);
          meta.insertBefore(signedUser, logout);
        }
      }
      if (needsNavigation && Array.isArray(payload.navigation)) {
        const navigation = existingNavigation || document.createElement("nav");
        navigation.className = "nav-row";
        navigation.setAttribute("aria-label", "Hlavní navigace");
        const requestPath = window.location.pathname.replace(/^\/robo\/?/, "").replace(/\/$/, "");
        const activePath = requestPath.startsWith("system") ? "system" : (requestPath || "standings");
        const links = payload.navigation.map((item) => {
          const link = document.createElement("a");
          link.className = "nav-link";
          link.href = `/robo/${String(item.path || "")}`;
          link.textContent = String(item.label || "");
          if (item.path === activePath) link.setAttribute("aria-current", "page");
          return link;
        });
        navigation.replaceChildren(...links);
        navigation.dataset.navigationRepair = "0.4.3";
        if (!existingNavigation) header.append(navigation);
      }
    } catch (error) {
      if (!header.querySelector(".navigation-error")) {
        const message = document.createElement("div");
        message.className = "navigation-error";
        message.setAttribute("role", "alert");
        message.textContent = "Navigaci se nepodařilo načíst. Obnovte stránku pomocí Ctrl+F5.";
        header.append(message);
      }
      console.error("ROBO navigation repair failed", error);
    }
  }

  async function saveChange(element, field, value) {
    const panel = element.closest("[data-endpoint]");
    const row = element.closest("[data-team-id]");
    const csrf = document.querySelector('input[name="_csrf"]')?.value;
    if (!panel || !row || !csrf) return;
    setSaveStatus("saving");
    try {
      const response = await fetch(panel.dataset.endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf, Accept: "application/json" },
        body: JSON.stringify({ team_id: Number(row.dataset.teamId), field, value }),
      });
      if (!response.ok) throw new Error("Uložení se nezdařilo. Obnovte stránku a zkuste akci znovu.");
      const payload = await response.json();
      setPendingCount(payload.pending);
      updateSavedRow(panel, row, field, payload);
      setSaveStatus("saved");
      return payload;
    } catch (error) {
      setSaveStatus("error");
      throw error;
    }
  }

  function updateSavedRow(panel, row, field, payload) {
    if (panel.dataset.page === "technical") {
      const penalty = row.querySelector('[data-computed="technical-penalty"]');
      const result = row.querySelector('[data-computed="technical-result"]');
      if (penalty) penalty.textContent = Number(payload.penalty) > 0 ? String(payload.penalty) : "—";
      if (result) {
        result.textContent = payload.complete ? (payload.passed ? "ANO" : "NE") : "?";
        result.classList.toggle("result-passed", Boolean(payload.complete && payload.passed));
        result.classList.toggle("result-failed", Boolean(payload.complete && !payload.passed));
      }
      const note = row.querySelector("[data-note-button]");
      if (note && field === "note") {
        note.classList.toggle("has-note", Boolean(payload.has_note));
        note.textContent = payload.has_note ? "Poznámka ✓" : "Poznámka";
      }
    }
    if (panel.dataset.page === "race") {
      const completion = row.querySelector(".completion");
      if (completion && typeof payload.confirmed === "boolean") {
        const state = payload.confirmed ? (payload.best_time ? "confirmed" : "invalid") : "pending";
        completion.dataset.state = state;
        completion.textContent = state === "confirmed" ? "✓" : state === "invalid" ? "×" : "?";
        completion.setAttribute("aria-label", payload.confirmed ? (payload.best_time ? "Výsledek potvrzen" : "Potvrzeno bez platného času") : "Rozpracovaný výsledek");
        row.classList.toggle("row-complete", payload.confirmed);
        if (field === "is_confirmed") row.querySelectorAll(".value-missing[data-empty-value]").forEach((value) => { value.textContent = payload.confirmed ? "" : value.dataset.emptyValue; });
      }
    }
  }

  document.addEventListener("click", (event) => {
    const confirmAction = event.target.closest("[data-confirm]");
    if (confirmAction && !window.confirm(confirmAction.dataset.confirm)) {
      event.preventDefault();
      return;
    }
    const help = event.target.closest("[data-help-modal]");
    if (help) { openModal(help.dataset.helpModal); return; }
    const modalTrigger = event.target.closest("[data-open-modal]");
    if (modalTrigger) {
      openModal(modalTrigger.dataset.openModal);
      document.getElementById(modalTrigger.dataset.openModal)?.querySelector("input")?.focus();
      return;
    }
    const close = event.target.closest("[data-close-modal]");
    if (close) { close.closest("dialog")?.close(); return; }
    const toggle = event.target.closest("[data-password-toggle]");
    if (toggle) {
      const input = document.getElementById(toggle.dataset.passwordToggle);
      if (!input) return;
      input.type = input.type === "password" ? "text" : "password";
      toggle.setAttribute("aria-label", input.type === "password" ? "Zobrazit heslo" : "Skrýt heslo");
      return;
    }
    const tri = event.target.closest(".tri-state[data-state]");
    if (tri) {
      const states = ["pending", "pass", "fail"];
      const next = states[(states.indexOf(tri.dataset.state) + 1) % states.length];
      tri.dataset.state = next;
      tri.setAttribute("aria-label", { pending: "Zatím nevyplněno", pass: "Podmínka splněna", fail: "Podmínka nesplněna" }[next]);
      tri.dispatchEvent(new CustomEvent("robo:state-change", { bubbles: true, detail: { value: next } }));
      return;
    }
    const check = event.target.closest(".check-action");
    if (check) {
      const pressed = check.getAttribute("aria-pressed") === "true";
      check.setAttribute("aria-pressed", String(!pressed));
      check.textContent = pressed ? "" : "✓";
      check.dispatchEvent(new CustomEvent("robo:state-change", { bubbles: true, detail: { value: !pressed } }));
      return;
    }
    const completion = event.target.closest(".completion");
    if (completion) {
      const completed = completion.dataset.state === "pending";
      completion.dataset.state = completed ? "confirmed" : "pending";
      completion.textContent = completed ? "✓" : "?";
      completion.setAttribute("aria-label", completed ? "Výsledek potvrzen" : "Rozpracovaný výsledek");
      completion.dispatchEvent(new CustomEvent("robo:state-change", { bubbles: true, detail: { value: completed } }));
      return;
    }
    const increment = event.target.closest("[data-long-press][data-field]");
    if (increment && increment.dataset.longPressHandled !== "true") {
      const field = increment.dataset.field || "";
      if (field.startsWith("checkpoint_") || field === "finish") {
        const current = Number(increment.dataset.value || 0);
        const next = Number.isFinite(current) ? current + 1 : 1;
        increment.dataset.value = String(next);
        increment.textContent = `${next}x`;
        increment.classList.remove("value-missing");
        increment.dispatchEvent(new CustomEvent("robo:state-change", { bubbles: true, detail: { value: next, field } }));
      }
    }
    delete increment?.dataset.longPressHandled;
  });

  document.addEventListener("pointerdown", (event) => {
    const target = event.target.closest("[data-long-press]");
    if (!target) return;
    longPressTimer = window.setTimeout(() => {
      openModal(target.dataset.longPress);
      target.dataset.longPressHandled = "true";
    }, longPressMs);
  });
  ["pointerup", "pointercancel", "pointerleave"].forEach((type) => document.addEventListener(type, () => {
    window.clearTimeout(longPressTimer);
  }));

  document.addEventListener("robo:state-change", (event) => {
    const element = event.target;
    let field = event.detail.field || element.dataset.field;
    if (!field) return;
    if (field === "finish") field = "finish_count";
    if (field === "confirmed") field = "is_confirmed";
    if (/^time_[1-5]$/.test(field)) field = field.replace("time_", "lap_") + "_ms";
    saveChange(element, field, event.detail.value).catch((error) => window.alert(error.message));
  });

  document.addEventListener("click", (event) => {
    const save = event.target.closest("[data-save-field]");
    if (save) {
      const dialog = save.closest("dialog");
      const input = dialog?.querySelector("[data-field]");
      const row = document.querySelector(`[data-team-id="${save.dataset.saveField}"]`);
      if (input && row) {
        let field = input.dataset.field;
        if (field === "finish") field = "finish_count";
        if (field.startsWith("time_")) field = field.replace("time_", "lap_") + "_ms";
        saveChange(row, field, input.value).then(() => window.location.reload()).catch((error) => window.alert(error.message));
      }
    }
    const note = event.target.closest("[data-save-note]");
    if (note) {
      const input = note.closest("dialog")?.querySelector("[data-field='note']");
      const row = document.querySelector(`[data-team-id="${note.dataset.saveNote}"]`);
      if (input && row) saveChange(row, "note", input.value).then(() => note.closest("dialog")?.close()).catch((error) => window.alert(error.message));
    }
  });

  document.addEventListener("change", (event) => {
    const toggle = event.target.closest(".switch-control input[type='checkbox']");
    if (!toggle) return;
    const label = toggle.closest(".switch-control")?.querySelector(".switch-label");
    if (label && label.textContent !== "Aktivní účet") label.textContent = toggle.checked ? "Zapnuto" : "Vypnuto";
  });

  document.querySelectorAll("dialog").forEach((dialog) => dialog.addEventListener("click", (event) => {
    const rect = dialog.getBoundingClientRect();
    if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
  }));

  function updateClock() {
    const now = new Date();
    const date = document.querySelector("[data-current-date]");
    const time = document.querySelector("[data-current-time]");
    if (date) date.textContent = new Intl.DateTimeFormat("cs-CZ").format(now);
    if (time) time.textContent = new Intl.DateTimeFormat("cs-CZ", { hour: "2-digit", minute: "2-digit", second: "2-digit" }).format(now);
  }
  updateClock();
  window.setInterval(updateClock, 1000);
  repairNavigation();

  function tableCell(value, className = "") {
    const cell = document.createElement("td");
    if (className) cell.className = className;
    cell.textContent = value === null || value === undefined || value === "" ? "—" : String(value);
    return cell;
  }

  function checkpointCell(value, className = "") {
    const cell = document.createElement("td");
    if (className) cell.className = className;
    const count = Number(value || 0);
    cell.textContent = count > 0 ? `${count}x` : "";
    return cell;
  }

  function renderQualificationRows(body, rows) {
    const fragment = document.createDocumentFragment();
    rows.forEach((row) => {
      const tr = document.createElement("tr");
      tr.dataset.teamId = String(row.team_id);
      tr.append(tableCell(row.number, "display-team"));
      ["checkpoint_1", "checkpoint_2", "checkpoint_3", "checkpoint_4", "checkpoint_5", "checkpoint_6", "finish"].forEach((key) => {
        tr.append(checkpointCell(row[key], row.furthest_checkpoint === key ? "progress-mark" : ""));
      });
      tr.append(tableCell(row.best_time));
      tr.append(tableCell(Number(row.did_not_stop) ? row.stop_penalty : ""));
      tr.append(tableCell(row.technical_penalty || ""));
      tr.append(tableCell(row.total_time));
      tr.append(tableCell(row.rank, "display-rank"));
      fragment.append(tr);
    });
    body.replaceChildren(fragment);
  }

  function renderParticipantsRows(body, rows) {
    const fragment = document.createDocumentFragment();
    rows.forEach((row) => {
      const tr = document.createElement("tr");
      tr.append(tableCell(row.number, "display-team"), tableCell(row.school || ""), tableCell(row.members || ""), tableCell(row.rank, "display-rank"));
      fragment.append(tr);
    });
    body.replaceChildren(fragment);
  }

  function renderPublicRows(body, rows) {
    const fragment = document.createDocumentFragment();
    rows.forEach((row) => {
      const tr = document.createElement("tr");
      const finalTime = row.total_time ? `${row.total_time} ms` : "—";
      tr.append(tableCell(row.rank, "display-rank"), tableCell(row.number), tableCell(row.school || ""), tableCell(row.members || ""), tableCell(finalTime));
      fragment.append(tr);
    });
    body.replaceChildren(fragment);
  }

  document.addEventListener("robo:data-refresh", (event) => {
    const root = event.target;
    const rows = event.detail?.rows;
    if (!Array.isArray(rows)) return;
    if (root.matches(".display-page")) {
      const publicBody = root.querySelector(".data-table tbody");
      if (publicBody) renderPublicRows(publicBody, rows);
      return;
    }
    if (root.dataset.page !== "standings") return;
    const bodies = root.querySelectorAll(".data-table tbody");
    if (bodies[0]) renderQualificationRows(bodies[0], rows);
    if (bodies[1]) renderParticipantsRows(bodies[1], rows);
  });

  document.querySelectorAll("[data-endpoint][data-refresh-ms]").forEach((root) => {
    const interval = Number(root.dataset.refreshMs);
    const endpoint = root.dataset.endpoint;
    if (!endpoint || !Number.isFinite(interval) || interval < 1000) return;
    window.setInterval(async () => {
      try {
        const response = await fetch(endpoint, { headers: { Accept: "application/json" }, credentials: "same-origin" });
        if (!response.ok) return;
        root.dispatchEvent(new CustomEvent("robo:data-refresh", { bubbles: true, detail: await response.json() }));
      } catch (_) {
        // A temporary connection failure must not disrupt time-critical data entry.
      }
    }, interval);
  });
})();
