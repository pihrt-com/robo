<?php
declare(strict_types=1);

require __DIR__.'/app/bootstrap.php';

use Robo\Core\AuditLogger;
use Robo\Core\Csrf;
use Robo\Core\Session;
use Robo\Domain\TeamEditorV044;
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
    $query = $pdo->prepare('SELECT u.id,u.role_code,r.permissions_json FROM users u JOIN roles r ON r.code=u.role_code WHERE u.id=:id AND u.is_active=1');
    $query->execute(['id'=>$userId]);
    $user = $query->fetch();
    if ($user === false) {
        throw new RuntimeException('Uživatelský účet není aktivní.');
    }
    $permissions = json_decode((string)$user['permissions_json'], true, 512, JSON_THROW_ON_ERROR);
    Authorization::require((string)$user['role_code'], is_array($permissions) ? $permissions : [], 'system.view');
    $competition = $pdo->query('SELECT id FROM competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1')->fetch();
    if ($competition === false) {
        throw new RuntimeException('Není vytvořen aktivní ročník soutěže.');
    }
    $editor = new TeamEditorV044($pdo, new AuditLogger($pdo));
    $teamId = isset($_POST['team_id']) && $_POST['team_id'] !== '' ? (int)$_POST['team_id'] : null;
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
    if (($_POST['action'] ?? 'save') === 'delete') {
        if ($teamId === null) {
            throw new RuntimeException('Tým neexistuje.');
        }
        $editor->delete((int)$competition['id'], $teamId, $userId, $ip);
        $_SESSION['_robo_flash'] = ['kind'=>'ok','message'=>'Tým byl odstraněn.'];
    } else {
        $editor->save((int)$competition['id'], $teamId, (string)($_POST['school_name'] ?? ''), (string)($_POST['contestant_names'] ?? ''), $userId, $ip);
        $_SESSION['_robo_flash'] = ['kind'=>'ok','message'=>$teamId === null ? 'Nový tým byl přidán s dalším volným číslem.' : 'Údaje týmu byly uloženy.'];
    }
} catch (Throwable $exception) {
    $_SESSION['_robo_flash'] = ['kind'=>'error','message'=>$exception->getMessage()];
}
$redirect();
