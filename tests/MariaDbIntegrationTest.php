<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use Robo\Core\AuditLogger;
use Robo\Core\Session;
use Robo\Domain\AdminService;
use Robo\Domain\CompetitionRepository;
use Robo\Installer\SchemaInstaller;
use Robo\Security\AuthService;
use Robo\Security\RateLimiter;

$dsn = getenv('ROBO_INTEGRATION_DSN');
if (!is_string($dsn) || $dsn === '') {
    fwrite(STDOUT, "MariaDB integration test skipped (ROBO_INTEGRATION_DSN is not set).\n");
    exit(0);
}

$user = (string) getenv('ROBO_INTEGRATION_USER');
$password = (string) getenv('ROBO_INTEGRATION_PASSWORD');
$pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
Session::start(false);
(new SchemaInstaller($pdo, dirname(__DIR__) . '/migrations'))->install();
$pdo->exec("INSERT INTO competitions (competition_year, competition_date, locale, team_count, is_active) VALUES (2026, '2026-11-10', 'cs', 16, 1)");
$competitionId = (int) $pdo->lastInsertId();
$adminPassword = 'RoboIntegrationPassword!2026';
$insertUser = $pdo->prepare("INSERT INTO users (username, password_hash, role_code, is_active) VALUES ('integration-admin', :hash, 'administrator', 1)");
$insertUser->execute(['hash' => password_hash($adminPassword, PASSWORD_DEFAULT)]);
$adminId = (int) $pdo->lastInsertId();
$team = $pdo->prepare('INSERT INTO teams (competition_id, team_number, school_name, contestant_names) VALUES (:competition_id, :team_number, :school_name, :contestant_names)');
for ($number = 1; $number <= 16; $number++) {
    $team->execute(['competition_id' => $competitionId, 'team_number' => $number, 'school_name' => 'Škola ' . $number, 'contestant_names' => 'Soutěžící ' . $number]);
}
$penalty = $pdo->prepare('INSERT INTO penalties (competition_id, code, label_cs, label_en, seconds_value) VALUES (:competition_id, :code, :cs, :en, :seconds)');
$penalty->execute(['competition_id' => $competitionId, 'code' => 'technical', 'cs' => 'Technická přejímka', 'en' => 'Technical inspection', 'seconds' => 90]);
$penalty->execute(['competition_id' => $competitionId, 'code' => 'not_stopped', 'cs' => 'Nezastavení', 'en' => 'Did not stop', 'seconds' => 5]);

$auth = new AuthService($pdo, new RateLimiter($pdo), new AuditLogger($pdo));
$login = $auth->attempt('integration-admin', $adminPassword, '127.0.0.1', new DateTimeImmutable());
if ($login['ok'] !== true || (int) $login['user']['id'] !== $adminId) {
    throw new RuntimeException('Freshly installed administrator cannot log in.');
}
$active = (new CompetitionRepository($pdo))->active();
if ((int) $active['id'] !== $competitionId || (int) $active['team_count'] !== 16) {
    throw new RuntimeException('Active competition was not created correctly.');
}
$settings = new AdminService($pdo, new AuditLogger($pdo));
$settings->savePenalty($competitionId, 'technical', 'Technická kontrola', 'Technical control', 120, $adminId, '127.0.0.1');
if ((int) $settings->penalties($competitionId)[0]['seconds_value'] !== 120) {
    throw new RuntimeException('Settings persistence failed.');
}
fwrite(STDOUT, "MariaDB integration test passed.\n");
