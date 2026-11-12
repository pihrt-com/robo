<?php
/** @var string|null $error */
$labels = $labels ?? [];
$text = static fn (string $key, string $fallback): string => (string) ($labels[$key] ?? $fallback);
$locale = ($locale ?? 'cs') === 'en' ? 'en' : 'cs';
$localeLabel = $locale === 'cs' ? 'CZ' : 'EN';
?>
<!doctype html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= htmlspecialchars($text('title', 'ROBO — Přihlášení'), ENT_QUOTES, 'UTF-8') ?></title><link rel="icon" type="image/svg+xml" href="/robo/assets/favicon.svg?v=0.5.3"><link rel="stylesheet" href="/robo/assets/app.css"></head>
<body class="login-page" data-l10n-password-show="<?= htmlspecialchars($text('show_password', 'Zobrazit heslo'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-password-hide="<?= htmlspecialchars($text('hide_password', 'Skrýt heslo'), ENT_QUOTES, 'UTF-8') ?>">
  <main class="login-card">
    <h1>ROBO <small><?= htmlspecialchars($year ?? '', ENT_QUOTES, 'UTF-8') ?> <?= $localeLabel ?></small></h1>
    <h2><?= htmlspecialchars($text('heading', 'Přihlášení do systému'), ENT_QUOTES, 'UTF-8') ?></h2>
    <?php if (!empty($error)): ?><p class="status status-warning" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <form class="form-stack" method="post" action="/robo/login" novalidate>
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <label class="form-field"><?= htmlspecialchars($text('username', 'Uživatel'), ENT_QUOTES, 'UTF-8') ?><input name="username" type="text" autocomplete="username" required maxlength="80"></label>
      <label class="form-field"><?= htmlspecialchars($text('password', 'Heslo'), ENT_QUOTES, 'UTF-8') ?><span class="password-field"><input id="login-password" name="password" type="password" autocomplete="current-password" required><button class="password-toggle" type="button" data-password-toggle="login-password" aria-label="<?= htmlspecialchars($text('show_password', 'Zobrazit heslo'), ENT_QUOTES, 'UTF-8') ?>">◉</button></span></label>
      <button class="button button-primary" type="submit"><?= htmlspecialchars($text('submit', 'Přihlásit se'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>
    <p class="login-public-link"><a class="button button-neutral" href="/robo/public"><?= htmlspecialchars($text('public_results', 'Veřejné průběžné pořadí'), ENT_QUOTES, 'UTF-8') ?></a></p>
    <p><button class="icon-button" type="button" data-help-modal="login-help" aria-label="<?= htmlspecialchars($text('help_aria', 'Nápověda k přihlášení'), ENT_QUOTES, 'UTF-8') ?>">i</button></p>
  </main>
  <dialog id="login-help"><div class="modal-header"><h2><?= htmlspecialchars($text('help_title', 'Přihlášení'), ENT_QUOTES, 'UTF-8') ?></h2><button class="modal-close" type="button" data-close-modal aria-label="<?= htmlspecialchars($text('close', 'Zavřít'), ENT_QUOTES, 'UTF-8') ?>">×</button></div><div class="modal-body"><p><?= htmlspecialchars($text('help', 'Zadejte přidělené uživatelské jméno a heslo. Při potížích kontaktujte správce soutěže.'), ENT_QUOTES, 'UTF-8') ?></p></div><div class="modal-footer"><button class="button" type="button" data-close-modal><?= htmlspecialchars($text('close', 'Zavřít'), ENT_QUOTES, 'UTF-8') ?></button></div></dialog>
  <script src="/robo/assets/app.js" defer></script>
</body>
</html>
