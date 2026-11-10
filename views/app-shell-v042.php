<?php
/** @var string $title */
/** @var string $content */
/** @var array{year?: string, locale?: string, user?: string, role_label?: string, teams?: int, pending?: int, saved_at?: string, page?: string, role_code?: string, permissions?: list<string>} $view */
$view = $view ?? [];
$localeLabel = ($view['locale'] ?? 'cs') === 'cs' ? 'CZ' : strtoupper((string) ($view['locale'] ?? 'en'));
$text = static fn (string $page, string $key, string $fallback): string => function_exists('ui_text') ? ui_text($page, $key, $fallback) : $fallback;
$nav = [
    ['path' => 'technical', 'label' => $text('navigation', 'technical', 'TECHNICKÁ'), 'permission' => 'technical.view'],
    ['path' => 'track-1', 'label' => $text('navigation', 'track_1', 'DRÁHA 1'), 'permission' => 'track_1.view'],
    ['path' => 'track-2', 'label' => $text('navigation', 'track_2', 'DRÁHA 2'), 'permission' => 'track_2.view'],
    ['path' => 'standings', 'label' => $text('navigation', 'standings', 'POŘADÍ'), 'permission' => 'results.view'],
    ['path' => 'system', 'label' => $text('navigation', 'system', 'SYSTÉM'), 'permission' => 'system.view'],
];
$permissions = is_array($view['permissions'] ?? null) ? $view['permissions'] : [];
$administrator = ($view['role_code'] ?? '') === 'administrator' || in_array('*', $permissions, true) || in_array('system.view', $permissions, true);
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
$assetVersion = max(
    (int) (@filemtime(dirname(__DIR__) . '/public/assets/app.css') ?: 0),
    (int) (@filemtime(dirname(__DIR__) . '/public/assets/app.js') ?: 0)
);
?>
<!doctype html>
<html lang="<?= htmlspecialchars($view['locale'] ?? 'cs', ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <title><?= htmlspecialchars($title ?? 'ROBO', ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="/robo/assets/app.css?v=<?= $assetVersion ?>">
</head>
<body>
<div class="app-shell">
  <header class="app-header" data-layout-version="0.4.2">
    <div class="meta-row<?= $workPage ? '' : ' meta-row-static' ?>">
      <span class="current-clock"><strong data-current-date>--.--.----</strong><strong data-current-time>--:--:--</strong></span>
      <?php if ($workPage): ?>
      <span>Týmů <?= (int) ($view['teams'] ?? 0) ?></span>
      <span class="meta-optional" data-pending-count>Chybí <?= (int) ($view['pending'] ?? 0) ?> dokončit</span>
      <span class="meta-optional" data-save-status aria-live="polite">Čeká na změnu</span>
      <?php endif; ?>
      <span class="signed-user"><strong><?= htmlspecialchars($view['user'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($view['role_label'] ?? $view['role_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></span>
      <form method="post" action="/robo/logout"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>"><button class="button button-primary button-small" data-confirm="Opravdu se chcete odhlásit?"><?= htmlspecialchars($text('navigation', 'logout', 'Odhlásit se'), ENT_QUOTES, 'UTF-8') ?></button></form>
      <button class="icon-button" type="button" data-help-modal="page-help" aria-label="Nápověda k této stránce">i</button>
    </div>
    <div class="brand-row">
      <a class="brand" href="/robo/">ROBO <?= htmlspecialchars($view['year'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8') ?></a>
      <h1 class="page-title"><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
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
<script src="/robo/assets/app.js?v=<?= $assetVersion ?>" defer></script>
</body>
</html>
