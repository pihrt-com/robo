<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use Robo\Core\Config;
use Robo\Core\Csrf;
use Robo\Core\Database;
use Robo\Core\Session;

if (!is_file(__DIR__ . '/config/local.php')) {
    http_response_code(404);
    exit;
}

$config = Config::fromFile(__DIR__ . '/config/local.php');
Session::start($config->bool('secure_cookie', true));
$userId = Session::userId();
if ($userId === null) {
    http_response_code(403);
    exit('Přihlaste se jako správce systému a otevřete tuto adresu znovu.');
}

$pdo = (new Database($config))->pdo();
$query = $pdo->prepare('SELECT u.role_code, r.permissions_json FROM users u JOIN roles r ON r.code=u.role_code WHERE u.id=:id AND u.is_active=1');
$query->execute(['id' => $userId]);
$user = $query->fetch();
$permissions = is_array($user) ? json_decode((string) $user['permissions_json'], true) : [];
if (!is_array($permissions) || (($user['role_code'] ?? '') !== 'administrator' && !in_array('*', $permissions, true) && !in_array('system.view', $permissions, true))) {
    http_response_code(403);
    exit('Tato údržbová akce je dostupná pouze správci systému.');
}

$attempted = false;
$reset = false;
$invalidated = 0;
$targets = [__DIR__ . '/index.php', __DIR__ . '/views/layout.php', __DIR__ . '/views/admin.php', __DIR__ . '/app/Update/UpdateService.php'];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    Csrf::verify($_POST['_csrf'] ?? null);
    $attempted = true;
    if (function_exists('opcache_invalidate')) {
        foreach ($targets as $target) {
            if (is_file($target) && @opcache_invalidate($target, true)) {
                $invalidated++;
            }
        }
    }
    $reset = function_exists('opcache_reset') && @opcache_reset();
}
$success = $reset || $invalidated > 0;
?>
<!doctype html>
<html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ROBO — Oprava PHP cache</title><link rel="stylesheet" href="/robo/assets/app.css"></head>
<body class="login-page"><main class="login-card"><h1>ROBO</h1><h2>Oprava PHP cache</h2><?php if ($attempted && $success): ?><p class="status status-success">PHP cache byla obnovena pro <?= (int) $invalidated ?> souborů. Nyní přejděte zpět do správy systému.</p><p><a class="button button-primary" href="/robo/system">Přejít do správy systému</a></p><?php elseif ($attempted): ?><p class="status status-error">Hosting odmítl obnovit PHP cache. V administraci hostingu restartujte PHP nebo PHP-FPM pro tento web a potom znovu otevřete správu systému.</p><?php else: ?><p>Akce obnoví PHP soubory, které řídí záhlaví a navigaci systému.</p><form method="post"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"><button class="button button-primary" type="submit">Opravit PHP cache</button></form><?php endif; ?></main></body></html>
