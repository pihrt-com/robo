(() => {
  "use strict";

  const longPressMs = 650;
  let longPressTimer;

  function openModal(id) {
    const dialog = document.getElementById(id);
    if (dialog && typeof dialog.showModal === "function") dialog.showModal();
  }

  async function saveChange(element, field, value) {
    const panel = element.closest("[data-endpoint]");
    const row = element.closest("[data-team-id]");
    const csrf = document.querySelector('input[name="_csrf"]')?.value;
    if (!panel || !row || !csrf) return;
    const response = await fetch(panel.dataset.endpoint, {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json", "X-CSRF-Token": csrf, Accept: "application/json" },
      body: JSON.stringify({ team_id: Number(row.dataset.teamId), field, value }),
    });
    if (!response.ok) throw new Error("Uložení se nezdařilo. Obnovte stránku a zkuste akci znovu.");
  }

  document.addEventListener("click", (event) => {
    const help = event.target.closest("[data-help-modal]");
    if (help) { openModal(help.dataset.helpModal); return; }
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
      const completed = completion.dataset.state !== "confirmed";
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
        const current = Number(increment.textContent.trim());
        increment.textContent = String(Number.isFinite(current) ? current + 1 : 1);
        increment.dispatchEvent(new CustomEvent("robo:state-change", { bubbles: true, detail: { value: Number(increment.textContent), field } }));
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
      if (input && row) saveChange(row, "note", input.value).then(() => window.location.reload()).catch((error) => window.alert(error.message));
    }
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

  function tableCell(value, className = "") {
    const cell = document.createElement("td");
    if (className) cell.className = className;
    cell.textContent = value === null || value === undefined || value === "" ? "—" : String(value);
    return cell;
  }

  function renderQualificationRows(body, rows) {
    const fragment = document.createDocumentFragment();
    rows.forEach((row) => {
      const tr = document.createElement("tr");
      tr.dataset.teamId = String(row.team_id);
      tr.append(tableCell(row.number));
      ["checkpoint_1", "checkpoint_2", "checkpoint_3", "checkpoint_4", "checkpoint_5", "checkpoint_6", "finish"].forEach((key) => {
        tr.append(tableCell(Number(row[key] || 0) || "", row.furthest_checkpoint === key ? "progress-mark" : ""));
      });
      tr.append(tableCell(row.best_time));
      tr.append(tableCell(Number(row.did_not_stop) ? row.stop_penalty : ""));
      tr.append(tableCell(row.technical_penalty || ""));
      tr.append(tableCell(row.total_time, "best-time"));
      tr.append(tableCell(row.rank, "display-rank"));
      fragment.append(tr);
    });
    body.replaceChildren(fragment);
  }

  function renderParticipantsRows(body, rows) {
    const fragment = document.createDocumentFragment();
    rows.forEach((row) => {
      const tr = document.createElement("tr");
      tr.append(tableCell(row.number), tableCell(row.school || ""), tableCell(row.members || ""), tableCell(row.rank, "display-rank"));
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
