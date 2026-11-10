<?php /** @var string|null $error */ ?>
<!doctype html>
<html lang="cs">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ROBO — Přihlášení</title><link rel="stylesheet" href="/robo/assets/app.css"></head>
<body class="login-page">
  <main class="login-card">
    <h1>ROBO <small><?= htmlspecialchars($year ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($locale ?? 'CZ', ENT_QUOTES, 'UTF-8') ?></small></h1>
    <h2>Přihlášení do systému</h2>
    <?php if (!empty($error)): ?><p class="status status-warning" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form class="form-stack" method="post" action="/robo/login" novalidate>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <label class="form-field">Uživatel<input name="username" type="text" autocomplete="username" required maxlength="80"></label>
      <label class="form-field">Heslo<span class="password-field"><input id="login-password" name="password" type="password" autocomplete="current-password" required><button class="password-toggle" type="button" data-password-toggle="login-password" aria-label="Zobrazit heslo">◉</button></span></label>
      <button class="button button-primary" type="submit">Přihlásit se</button>
    </form>
    <p><button class="icon-button" type="button" data-help-modal="login-help" aria-label="Nápověda k přihlášení">i</button></p>
  </main>
  <dialog id="login-help"><div class="modal-header"><h2>Přihlášení</h2><button class="modal-close" type="button" data-close-modal>×</button></div><div class="modal-body"><p>Zadejte přidělené uživatelské jméno a heslo. Při potížích kontaktujte správce soutěže.</p></div><div class="modal-footer"><button class="button" type="button" data-close-modal>Zavřít</button></div></dialog>
  <script src="/robo/assets/app.js" defer></script>
</body>
</html>
