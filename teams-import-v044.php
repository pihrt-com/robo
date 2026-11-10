<?php
declare(strict_types=1);

require __DIR__.'/app/bootstrap.php';

use Robo\Core\AuditLogger;
use Robo\Core\Csrf;
use Robo\Core\Session;
use Robo\Domain\TeamEditorV044;
use Robo\Domain\TeamImportV044;
use Robo\Security\Authorization;

$redirect = static function (): never {
    header('Location: /robo/system/teams', true, 303);
    exit;
};

try {
    $config = robo_config(__DIR__);
    Session::start($config->bool('secure_cookie', true));
    Csrf::verify($_POST['_csrf'] ?? null);
    $userId = Session::userId();
    if ($userId === null) {
        header('Location: /robo/login', true, 303);
        exit;
    }
    $pdo = robo_database($config)->pdo();
    $query = $pdo->prepare('SELECT u.id,u.username,u.role_code,r.label_cs,r.label_en,r.permissions_json FROM users u JOIN roles r ON r.code=u.role_code WHERE u.id=:id AND u.is_active=1');
    $query->execute(['id'=>$userId]);
    $user = $query->fetch();
    if ($user === false) {
        throw new RuntimeException('Uživatelský účet není aktivní.');
    }
    $permissions = json_decode((string)$user['permissions_json'], true, 512, JSON_THROW_ON_ERROR);
    $permissions = is_array($permissions) ? $permissions : [];
    Authorization::require((string)$user['role_code'], $permissions, 'system.view');
    $competition = $pdo->query('SELECT * FROM competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1')->fetch();
    if ($competition === false) {
        throw new RuntimeException('Není vytvořen aktivní ročník soutěže.');
    }
    $editor = new TeamEditorV044($pdo, new AuditLogger($pdo));
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    if (($_POST['action'] ?? '') === 'confirm') {
        $rows = $_SESSION['_robo_team_import_v044'] ?? null;
        if (!is_array($rows)) {
            throw new RuntimeException('Náhled importu vypršel. Nahrajte soubor znovu.');
        }
        $editor->replaceFromImport((int)$competition['id'], $rows, $userId, $ip);
        unset($_SESSION['_robo_team_import_v044']);
        $_SESSION['_robo_flash'] = ['kind'=>'ok','message'=>'Seznam týmů byl nahrazen importem.'];
        $redirect();
    }
    $upload = $_FILES['teams_file'] ?? null;
    if (!is_array($upload) || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$upload['tmp_name'])) {
        throw new RuntimeException('Vyberte platný soubor XLSX.');
    }
    $rows = TeamImportV044::xlsx((string)$upload['tmp_name']);
    $editor->validateRows($rows);
    $_SESSION['_robo_team_import_v044'] = $rows;
    $locale = (string)$competition['locale'];
    $view = [
        'year'=>(string)$competition['competition_year'],
        'locale'=>$locale,
        'user'=>(string)$user['username'],
        'role_label'=>(string)$user[$locale === 'en' ? 'label_en' : 'label_cs'],
        'role_code'=>(string)$user['role_code'],
        'permissions'=>$permissions,
        'teams'=>(int)$competition['team_count'],
        'pending'=>0,
        'page'=>'system',
    ];
    $csrf = Csrf::token();
    require __DIR__.'/views/teams-import-preview-v044.php';
    exit;
} catch (Throwable $exception) {
    $_SESSION['_robo_flash'] = ['kind'=>'error','message'=>$exception->getMessage()];
    $redirect();
}
