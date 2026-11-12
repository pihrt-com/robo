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
$format = static fn (string $value, int $count): string => str_replace('{count}', (string) $count, $value);
$pageLabels = is_array($labels ?? null) ? $labels : [];
$pageText = static fn (string $key, string $fallback): string => (string) ($pageLabels[$key] ?? $fallback);
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
<body data-l10n-save-waiting="<?= htmlspecialchars($text('navigation', 'save_waiting', 'Čeká na změnu'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-save-saving="<?= htmlspecialchars($text('navigation', 'save_saving', 'Ukládám…'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-save-saved="<?= htmlspecialchars($text('navigation', 'save_saved', 'Uloženo {time}'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-save-error="<?= htmlspecialchars($text('navigation', 'save_error', 'Uložení se nezdařilo'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-pending="<?= htmlspecialchars($text('navigation', 'pending_count', 'Zbývá dokončit: {count}'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-completed="<?= htmlspecialchars($text('navigation', 'completed_count', 'Hotovo: {count}'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-connection-online="<?= htmlspecialchars($text('navigation', 'connection_online', 'Spojení: online'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-connection-offline="<?= htmlspecialchars($text('navigation', 'connection_offline', 'Spojení: bez připojení'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-navigation="<?= htmlspecialchars($text('navigation', 'main_navigation', 'Hlavní navigace'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-navigation-error="<?= htmlspecialchars($text('navigation', 'navigation_error', 'Navigaci se nepodařilo načíst. Obnovte stránku pomocí Ctrl+F5.'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-yes="<?= htmlspecialchars($pageText('yes', 'ANO'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-no="<?= htmlspecialchars($pageText('no', 'NE'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-note="<?= htmlspecialchars($pageText('note', 'Poznámka'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-note-saved="<?= htmlspecialchars($pageText('note_saved', 'Poznámka ✓'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-state-pending="<?= htmlspecialchars($pageText('state_pending', 'Zatím nevyplněno'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-state-pass="<?= htmlspecialchars($pageText('state_pass', 'Podmínka splněna'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-state-fail="<?= htmlspecialchars($pageText('state_fail', 'Podmínka nesplněna'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-result-confirmed="<?= htmlspecialchars($pageText('result_confirmed', 'Výsledek potvrzen'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-result-invalid="<?= htmlspecialchars($pageText('result_invalid', 'Potvrzeno bez platného času'), ENT_QUOTES, 'UTF-8') ?>" data-l10n-result-pending="<?= htmlspecialchars($pageText('result_pending', 'Rozpracovaný výsledek'), ENT_QUOTES, 'UTF-8') ?>">
<div class="app-shell">
  <header class="app-header" data-layout-version="0.4.7">
    <div class="meta-row<?= $workPage ? '' : ' meta-row-static' ?>">
      <span class="current-clock"><strong data-current-date>--.--.----</strong><strong data-current-time>--:--:--</strong></span>
      <?php if ($workPage): ?>
      <span><?= htmlspecialchars($format($text('navigation', 'teams_count', 'Týmů: {count}'), (int) ($view['teams'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="meta-optional" data-pending-count><?= htmlspecialchars($format($text('navigation', 'pending_count', 'Zbývá dokončit: {count}'), (int) ($view['pending'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="meta-optional" data-completed-count><?= htmlspecialchars($format($text('navigation', 'completed_count', 'Hotovo: {count}'), max(0, (int) ($view['teams'] ?? 0) - (int) ($view['pending'] ?? 0))), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="meta-optional connection-status" data-connection-status aria-live="polite"><?= htmlspecialchars($text('navigation', 'connection_online', 'Spojení: online'), ENT_QUOTES, 'UTF-8') ?></span>
      <span class="meta-optional" data-save-status aria-live="polite"><?= htmlspecialchars($text('navigation', 'save_waiting', 'Čeká na změnu'), ENT_QUOTES, 'UTF-8') ?></span>
      <?php endif; ?>
      <span class="signed-user"><strong><?= htmlspecialchars($view['user'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars($view['role_label'] ?? $view['role_code'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></span>
      <form method="post" action="/robo/logout"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>"><button class="button button-primary button-small" data-confirm="<?= htmlspecialchars($text('navigation', 'logout_confirm', 'Opravdu se chcete odhlásit?'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($text('navigation', 'logout', 'Odhlásit se'), ENT_QUOTES, 'UTF-8') ?></button></form>
      <button class="icon-button" type="button" data-help-modal="page-help" aria-label="<?= htmlspecialchars($text('navigation', 'help_page', 'Nápověda k této stránce'), ENT_QUOTES, 'UTF-8') ?>">i</button>
    </div>
    <div class="brand-row">
      <a class="brand" href="/robo/">ROBO <?= htmlspecialchars($view['year'] ?? '', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($localeLabel, ENT_QUOTES, 'UTF-8') ?></a>
      <h1 class="page-title"><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
    </div>
    <nav class="nav-row" aria-label="<?= htmlspecialchars($text('navigation', 'main_navigation', 'Hlavní navigace'), ENT_QUOTES, 'UTF-8') ?>">
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
  <div class="modal-header"><h2 id="page-help-title"><?= htmlspecialchars($text('navigation', 'help', 'Nápověda'), ENT_QUOTES, 'UTF-8') ?></h2><button class="modal-close" type="button" data-close-modal aria-label="<?= htmlspecialchars($text('navigation', 'close', 'Zavřít'), ENT_QUOTES, 'UTF-8') ?>">×</button></div>
  <div class="modal-body"><?= $help ?? '<p>' . htmlspecialchars($text('navigation', 'help_unavailable', 'Pro tuto stránku zatím není nápověda připravena.'), ENT_QUOTES, 'UTF-8') . '</p>' ?></div>
  <div class="modal-footer"><button class="button button-neutral" type="button" data-close-modal><?= htmlspecialchars($text('navigation', 'close', 'Zavřít'), ENT_QUOTES, 'UTF-8') ?></button></div>
</dialog>
<script src="/robo/assets/app.js?v=<?= $assetVersion ?>" defer></script>
</body>
</html>
