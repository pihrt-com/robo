<?php
/** @var string $title */
/** @var string $content */
/** @var array{year?: string, locale?: string, user?: string, teams?: int, pending?: int, saved_at?: string, page?: string} $view */
$view = $view ?? [];
$localeLabel = ($view['locale'] ?? 'cs') === 'cs' ? 'CZ' : strtoupper((string) ($view['locale'] ?? 'en'));
$nav = [
    'technical' => 'TECHNICKÁ',
    'track-1' => 'DRÁHA 1',
    'track-2' => 'DRÁHA 2',
    'standings' => 'POŘADÍ',
    'system' => 'SYSTÉM',
];
?>
<!doctype html>
<html lang="<?= htmlspecialchars($view['locale'] ?? 'cs', ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title><?= htmlspecialchars($title ?? 'ROBO', ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/robo/assets/app.css">
</head>
<body>
<div class="app-shell">
  <header class="app-header">
    <div class="meta-row">
      <strong data-current-date>--.--.----</strong><strong data-current-time>--:--:--</strong>
      <span>Týmů <?= (int) ($view['teams'] ?? 0) ?></span>
      <span class="meta-optional">Chybí <?= (int) ($view['pending'] ?? 0) ?> dokončit</span>
      <span class="meta-optional">Data uložena<?= empty($view['saved_at']) ? '' : ' → '.htmlspecialchars($view['saved_at'], ENT_QUOTES, 'UTF-8') ?></span>
      <span><?= htmlspecialchars($view['user'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
      <form method="post" action="/robo/logout"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>"><button class="button button-primary button-small">Odhlásit se</button></form>
    </div>
    <div class="brand-row">
      <a class="brand" href="/robo/">ROBO <?= htmlspecialchars($view['year'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8') ?></a>
      <h1 class="page-title"><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
      <button class="icon-button" type="button" data-help-modal="page-help" aria-label="Nápověda k této stránce">i</button>
    </div>
    <nav class="nav-row" aria-label="Hlavní navigace">
      <?php foreach ($nav as $path => $label): ?>
        <a class="nav-link" href="/robo/<?= $path ?>"<?= ($view['page'] ?? '') === $path ? ' aria-current="page"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
  </header>
  <main class="app-main"><?= $content ?></main>
</div>
<dialog id="page-help" aria-labelledby="page-help-title">
  <div class="modal-header"><h2 id="page-help-title">Nápověda</h2><button class="modal-close" type="button" data-close-modal aria-label="Zavřít">×</button></div>
  <div class="modal-body"><?= $help ?? '<p>Pro tuto stránku zatím není nápověda připravena.</p>' ?></div>
  <div class="modal-footer"><button class="button button-neutral" type="button" data-close-modal>Zavřít</button></div>
</dialog>
<script src="/robo/assets/app.js" defer></script>
</body>
</html>
