<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use Robo\Installer\SchemaInstaller;

$configFile = __DIR__ . '/config/local.php';
if (is_file($configFile)) {
    http_response_code(403);
    exit('Instalace je již dokončena. Instalační skript je zablokovaný.');
}

function installer_config(string $path, string $dsn, string $user, string $password, bool $secure): void {
    $temp = $path . '.tmp';
    $data = ['db_dsn' => $dsn, 'db_user' => $user, 'db_password' => $password, 'app_secret' => bin2hex(random_bytes(32)), 'secure_cookie' => $secure, 'base_path' => '/robo'];
    if (file_put_contents($temp, "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($data, true) . ";\n", LOCK_EX) === false || !rename($temp, $path)) {
        @unlink($temp);
        throw new RuntimeException('Konfigurační soubor nelze uložit.');
    }
    @chmod($path, 0640);
}
function installer_seed(PDO $pdo, string $admin, string $password): void {
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO competitions (competition_year, competition_date, locale, team_count, is_active) VALUES (:year, :date, \'cs\', 16, 1)')->execute(['year' => (int) date('Y'), 'date' => date('Y-m-d')]);
        $competitionId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO users (username, password_hash, role_code) VALUES (:username, :password_hash, \'administrator\')')->execute(['username' => $admin, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        $team = $pdo->prepare('INSERT INTO teams (competition_id, team_number) VALUES (:competition_id, :team_number)');
        for ($number = 1; $number <= 16; $number++) $team->execute(['competition_id' => $competitionId, 'team_number' => $number]);
        $penalty = $pdo->prepare('INSERT INTO penalties (competition_id, code, label_cs, label_en, seconds_value) VALUES (:competition_id, :code, :cs, :en, :seconds)');
        $penalty->execute(['competition_id' => $competitionId, 'code' => 'technical', 'cs' => 'Technická přejímka', 'en' => 'Technical inspection', 'seconds' => 90]);
        $penalty->execute(['competition_id' => $competitionId, 'code' => 'not_stopped', 'cs' => 'Nezastavení', 'en' => 'Did not stop', 'seconds' => 5]);
        $pdo->commit();
    } catch (Throwable $exception) { $pdo->rollBack(); throw $exception; }
}

$error = null;
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin = trim((string) ($_POST['admin_username'] ?? ''));
    $adminPassword = (string) ($_POST['admin_password'] ?? '');
    $host = trim((string) ($_POST['db_host'] ?? ''));
    $database = trim((string) ($_POST['db_name'] ?? ''));
    $dbUser = trim((string) ($_POST['db_user'] ?? ''));
    $dbPassword = (string) ($_POST['db_password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $admin) || strlen($adminPassword) < 12 || !preg_match('/^[A-Za-z0-9_.-]+$/', $database) || $host === '' || $dbUser === '') {
        $error = 'Zkontrolujte údaje. Heslo správce musí mít nejméně 12 znaků a název databáze může obsahovat pouze písmena, čísla, podtržítko, tečku a pomlčku.';
    } else {
        try {
            $dsn = 'mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $dbUser, $dbPassword, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
            (new SchemaInstaller($pdo, __DIR__ . '/migrations'))->install();
            installer_seed($pdo, $admin, $adminPassword);
            $verification = $pdo->prepare('SELECT password_hash, role_code, is_active FROM users WHERE username = :username LIMIT 1');
            $verification->execute(['username' => $admin]);
            $createdAdmin = $verification->fetch();
            if ($createdAdmin === false || $createdAdmin['role_code'] !== 'administrator' || (int) $createdAdmin['is_active'] !== 1 || !password_verify($adminPassword, (string) $createdAdmin['password_hash'])) {
                throw new RuntimeException('Vytvořený účet správce nelze ověřit.');
            }
            installer_config($configFile, $dsn, $dbUser, $dbPassword, !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            $done = true;
        } catch (Throwable) { $error = 'Instalaci se nepodařilo dokončit. Ověřte údaje databáze, oprávnění databázového účtu a oprávnění složky config.'; }
    }
}
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ROBO — Instalace systému</title><link rel="icon" type="image/svg+xml" href="/robo/assets/favicon.svg?v=0.5.3"><link rel="stylesheet" href="/robo/assets/app.css"></head><body class="login-page"><main class="login-card"><h1>ROBO</h1><h2>Instalace systému</h2><?php if ($done): ?><p class="status status-ok">Instalace je dokončena a další spuštění instalátoru je zablokováno. Po ověření přihlášení zkontrolujte a odstraňte ze serveru soubor <code>install.php</code>.</p><p><a class="button button-primary" href="/robo/login">Přejít na přihlášení</a></p><?php else: ?><?php if ($error): ?><p class="status status-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?><p>Systém vytvoří tabulky, výchozí role, 16 týmů a penalizace. Po úspěšné instalaci bude další spuštění instalační stránky zablokováno.</p><form class="form-stack" method="post"><label class="form-field">Správce systému ROBO<input name="admin_username" required maxlength="80"></label><label class="form-field">Heslo správce<input name="admin_password" type="password" required minlength="12" autocomplete="new-password"></label><label class="form-field">Databázový server<input name="db_host" required maxlength="255" placeholder="localhost"></label><label class="form-field">Název databáze<input name="db_name" required maxlength="64"></label><label class="form-field">Uživatel databáze<input name="db_user" required maxlength="80"></label><label class="form-field">Heslo databáze<input name="db_password" type="password" required autocomplete="new-password"></label><button class="button button-primary" type="submit">Instalovat</button></form><?php endif; ?></main></body></html>
