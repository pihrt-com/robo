<?php
declare(strict_types=1);

namespace Robo\Security;

use DateTimeImmutable;
use PDO;
use Robo\Core\AuditLogger;
use Robo\Core\Session;

final class AuthService
{
    public function __construct(private readonly PDO $pdo, private readonly RateLimiter $rateLimiter, private readonly AuditLogger $audit)
    {
    }

    /** @return array{ok: bool, reason: string, user: array<string, mixed>|null} */
    public function attempt(string $username, string $password, string $ipAddress, DateTimeImmutable $now): array
    {
        $limit = $this->rateLimiter->check($ipAddress, $now);
        if (!$limit['allowed']) {
            $this->audit->log(null, null, 'security.login_blocked', 'Přihlášení bylo zablokováno omezením pokusů.', [], $ipAddress);
            return ['ok' => false, 'reason' => 'login_failed', 'user' => null];
        }

        $statement = $this->pdo->prepare('SELECT id, username, password_hash, role_code, is_active FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();
        $valid = $user !== false && (int) $user['is_active'] === 1 && password_verify($password, (string) $user['password_hash']);
        if (!$valid) {
            $lockSeconds = $this->rateLimiter->registerFailure($ipAddress, $now);
            $this->audit->log(null, null, 'security.login_failed', 'Neúspěšný pokus o přihlášení.', ['username' => substr($username, 0, 80), 'lock_seconds' => $lockSeconds], $ipAddress);
            return ['ok' => false, 'reason' => 'login_failed', 'user' => null];
        }

        $this->rateLimiter->clear($ipAddress);
        $update = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $update->execute(['id' => $user['id']]);
        Session::login((int) $user['id']);
        $this->audit->log((int) $user['id'], null, 'security.login_succeeded', 'Uživatel se přihlásil.', [], $ipAddress);
        unset($user['password_hash']);
        return ['ok' => true, 'reason' => 'ok', 'user' => $user];
    }
}
