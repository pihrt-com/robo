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
        echo json_encode(['error' => 'Session expired.'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    $pdo = robo_database($config)->pdo();
    $statement = $pdo->prepare('SELECT u.username,u.role_code,r.label_cs,r.label_en,r.permissions_json FROM users u JOIN roles r ON r.code=u.role_code WHERE u.id=:id AND u.is_active=1');
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();
    if ($user === false) {
        http_response_code(401);
        echo json_encode(['error' => 'Account is inactive.'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    $competition = $pdo->query('SELECT id,locale,competition_year FROM competitions WHERE is_active=1 ORDER BY id DESC LIMIT 1')->fetch();
    if ($competition === false) {
        throw new RuntimeException('No active competition.');
    }
    $locale = ($competition['locale'] ?? '') === 'en' ? 'en' : 'cs';
    $labels = $locale === 'en'
        ? ['technical' => 'TECHNICAL', 'track_1' => 'TRACK 1', 'track_2' => 'TRACK 2', 'standings' => 'STANDINGS', 'system' => 'SYSTEM']
        : ['technical' => 'TECHNICKÁ', 'track_1' => 'DRÁHA 1', 'track_2' => 'DRÁHA 2', 'standings' => 'POŘADÍ', 'system' => 'SYSTÉM'];
    $textQuery = $pdo->prepare("SELECT text_key,text_value FROM page_texts WHERE competition_id=:competition_id AND page_key='navigation' AND locale=:locale");
    $textQuery->execute(['competition_id' => $competition['id'], 'locale' => $locale]);
    $labels = array_replace($labels, $textQuery->fetchAll(PDO::FETCH_KEY_PAIR));
    $teamCount = $pdo->prepare('SELECT COUNT(*) FROM teams WHERE competition_id=:competition_id');
    $teamCount->execute(['competition_id' => $competition['id']]);
    $teams = (int) $teamCount->fetchColumn();
    $page = (string) ($_GET['page'] ?? '');
    $pending = 0;
    if ($page === 'technical') {
        $pendingQuery = $pdo->prepare("SELECT COUNT(*) FROM teams t LEFT JOIN technical_inspections i ON i.team_id=t.id AND i.competition_id=t.competition_id WHERE t.competition_id=:competition_id AND (i.id IS NULL OR i.criterion_1='unknown' OR i.criterion_2='unknown' OR i.criterion_3='unknown')");
        $pendingQuery->execute(['competition_id' => $competition['id']]);
        $pending = (int) $pendingQuery->fetchColumn();
    } elseif (preg_match('/^track-([12])$/', $page, $track) === 1) {
        $pendingQuery = $pdo->prepare('SELECT COUNT(*) FROM teams t LEFT JOIN rides r ON r.team_id=t.id AND r.competition_id=t.competition_id WHERE t.competition_id=:competition_id AND MOD(t.team_number,2)=:parity AND (r.id IS NULL OR r.is_confirmed=0)');
        $pendingQuery->execute(['competition_id' => $competition['id'], 'parity' => (int) $track[1] % 2]);
        $pending = (int) $pendingQuery->fetchColumn();
    }
    $permissions = json_decode((string) $user['permissions_json'], true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($permissions)) {
        $permissions = [];
    }
    $roleLabel = (string) $user[$locale === 'cs' ? 'label_cs' : 'label_en'];
    $summary = $locale === 'en'
        ? ['teams' => 'Teams: '.$teams, 'pending' => 'Remaining: '.$pending, 'completed' => 'Completed: '.max(0, $teams - $pending), 'connection' => 'Connection: online', 'save' => 'Waiting for changes']
        : ['teams' => 'Týmů: '.$teams, 'pending' => 'Zbývá dokončit: '.$pending, 'completed' => 'Hotovo: '.max(0, $teams - $pending), 'connection' => 'Spojení: online', 'save' => 'Čeká na změnu'];
    echo json_encode([
        'username' => (string) $user['username'],
        'role' => $roleLabel,
        'locale' => $locale,
        'brand' => 'ROBO '.$competition['competition_year'].' '.strtoupper($locale),
        'navigation' => NavigationMenu::items((string) $user['role_code'], $permissions, $labels),
        'summary' => $summary,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Header could not be loaded.'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}
