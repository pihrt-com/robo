<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use Robo\Core\Session;
use Robo\Domain\NavigationMenu;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

try {
    $config = robo_config(__DIR__);
    Session::start($config->bool('secure_cookie', true));
    $userId = Session::userId();
    if ($userId === null) {
        http_response_code(401);
        echo json_encode(['error' => 'Přihlášení vypršelo.'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    $pdo = robo_database($config)->pdo();
    $statement = $pdo->prepare('SELECT u.username,u.role_code,r.label_cs,r.label_en,r.permissions_json FROM users u JOIN roles r ON r.code=u.role_code WHERE u.id=:id AND u.is_active=1');
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();
    if ($user === false) {
        http_response_code(401);
        echo json_encode(['error' => 'Uživatelský účet není aktivní.'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    $competition = $pdo->query('SELECT id,locale FROM competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1')->fetch();
    $locale = is_array($competition) && ($competition['locale'] ?? '') === 'en' ? 'en' : 'cs';
    $labels = $locale === 'en'
        ? ['technical' => 'TECHNICAL', 'track_1' => 'TRACK 1', 'track_2' => 'TRACK 2', 'standings' => 'STANDINGS', 'system' => 'SYSTEM']
        : ['technical' => 'TECHNICKÁ', 'track_1' => 'DRÁHA 1', 'track_2' => 'DRÁHA 2', 'standings' => 'POŘADÍ', 'system' => 'SYSTÉM'];
    if (is_array($competition)) {
        $textQuery = $pdo->prepare("SELECT text_key,text_value FROM page_texts WHERE competition_id=:competition_id AND page_key='navigation' AND locale=:locale");
        $textQuery->execute(['competition_id' => $competition['id'], 'locale' => $locale]);
        $labels = array_replace($labels, $textQuery->fetchAll(PDO::FETCH_KEY_PAIR));
    }
    $permissions = json_decode((string) $user['permissions_json'], true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($permissions)) {
        $permissions = [];
    }
    $roleLabel = (string) $user[$locale === 'cs' ? 'label_cs' : 'label_en'];
    echo json_encode([
        'username' => (string) $user['username'],
        'role' => $roleLabel,
        'navigation' => NavigationMenu::items((string) $user['role_code'], $permissions, $labels),
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Navigaci se nepodařilo načíst.'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}
