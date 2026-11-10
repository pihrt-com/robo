<?php
/** @var string $title */
/** @var string $content */
/** @var array{year?: string, locale?: string, user?: string, teams?: int, pending?: int, saved_at?: string, page?: string, role_code?: string, permissions?: list<string>} $view */
$view = $view ?? [];
$localeLabel = ($view['locale'] ?? 'cs') === 'cs' ? 'CZ' : strtoupper((string) ($view['locale'] ?? 'en'));
$nav = [
    ['path' => 'technical', 'label' => 'TECHNICKÁ', 'permission' => 'technical.view'],
    ['path' => 'track-1', 'label' => 'DRÁHA 1', 'permission' => 'track_1.view'],
    ['path' => 'track-2', 'label' => 'DRÁHA 2', 'permission' => 'track_2.view'],
    ['path' => 'standings', 'label' => 'POŘADÍ', 'permission' => 'results.view'],
    ['path' => 'system', 'label' => 'SYSTÉM', 'permission' => 'system.view'],
];
$permissions = is_array($view['permissions'] ?? null) ? $view['permissions'] : [];
$administrator = ($view['role_code'] ?? '') === 'administrator' || in_array('*', $permissions, true);
$page = (string) ($view['page'] ?? '');
if (!$administrator && $permissions === []) {
    $permissions = match ($page) {
        'technical' => ['technical.view'],
        'track-1' => ['track_1.view'],
        'track-2' => ['track_2.view'],
        'standings' => ['results.view'],
        'system' => ['*'],
        default => [],
    };
    $administrator = in_array('*', $permissions, true);
}
$workPage = in_array($page, ['technical', 'track-1', 'track-2'], true);
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
    <div class="meta-row<?= $workPage ? '' : ' meta-row-static' ?>">
      <strong data-current-date>--.--.----</strong><strong data-current-time>--:--:--</strong>
      <?php if ($workPage): ?>
      <span>Týmů <?= (int) ($view['teams'] ?? 0) ?></span>
      <span class="meta-optional" data-pending-count>Chybí <?= (int) ($view['pending'] ?? 0) ?> dokončit</span>
      <span class="meta-optional" data-save-status aria-live="polite">Čeká na změnu</span>
      <?php endif; ?>
      <span><?= htmlspecialchars($view['user'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
      <form method="post" action="/robo/logout"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>"><button class="button button-primary button-small">Odhlásit se</button></form>
    </div>
    <div class="brand-row">
      <a class="brand" href="/robo/">ROBO <?= htmlspecialchars($view['year'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8') ?></a>
      <h1 class="page-title"><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
      <button class="icon-button" type="button" data-help-modal="page-help" aria-label="Nápověda k této stránce">i</button>
    </div>
    <nav class="nav-row" aria-label="Hlavní navigace">
      <?php foreach ($nav as $item): ?>
        <?php if ($administrator || in_array($item['permission'], $permissions, true)): ?>
        <a class="nav-link" href="/robo/<?= htmlspecialchars($item['path'], ENT_QUOTES, 'UTF-8') ?>"<?= $page === $item['path'] ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
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
