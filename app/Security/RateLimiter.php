<?php
declare(strict_types=1);

namespace Robo\Security;

use DateInterval;
use DateTimeImmutable;
use PDO;

final class RateLimiter
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{allowed: bool, retry_after_seconds: int, failures: int} */
    public function check(string $ipAddress, DateTimeImmutable $now): array
    {
        $row = $this->find($ipAddress);
        if ($row === null) {
            return ['allowed' => true, 'retry_after_seconds' => 0, 'failures' => 0];
        }

        $lockedUntil = $row['locked_until'] === null ? null : new DateTimeImmutable($row['locked_until']);
        if ($lockedUntil !== null && $lockedUntil > $now) {
            return ['allowed' => false, 'retry_after_seconds' => $lockedUntil->getTimestamp() - $now->getTimestamp(), 'failures' => (int) $row['failure_count']];
        }

        return ['allowed' => true, 'retry_after_seconds' => 0, 'failures' => (int) $row['failure_count']];
    }

    public function registerFailure(string $ipAddress, DateTimeImmutable $now): int
    {
        $existing = $this->find($ipAddress);
        $failures = ($existing === null || new DateTimeImmutable($existing['window_started_at']) < $now->sub(new DateInterval('PT1H')))
            ? 1
            : (int) $existing['failure_count'] + 1;
        $lockSeconds = $failures >= 10 ? 3600 : ($failures >= 5 ? 300 : 0);
        $lockedUntil = $lockSeconds > 0 ? $now->add(new DateInterval('PT' . $lockSeconds . 'S'))->format('Y-m-d H:i:s') : null;

        $statement = $this->pdo->prepare(
            'INSERT INTO login_rate_limits (ip_address, failure_count, window_started_at, locked_until) VALUES (:ip, :failures, :window_started, :locked_until) ON DUPLICATE KEY UPDATE failure_count = VALUES(failure_count), window_started_at = VALUES(window_started_at), locked_until = VALUES(locked_until)'
        );
        $statement->execute(['ip' => $ipAddress, 'failures' => $failures, 'window_started' => $now->format('Y-m-d H:i:s'), 'locked_until' => $lockedUntil]);
        return $lockSeconds;
    }

    public function clear(string $ipAddress): void
    {
        $statement = $this->pdo->prepare('DELETE FROM login_rate_limits WHERE ip_address = :ip');
        $statement->execute(['ip' => $ipAddress]);
    }

    /** @return list<array<string, mixed>> */
    public function activeBlocks(DateTimeImmutable $now): array
    {
        $statement = $this->pdo->prepare('SELECT ip_address, failure_count, locked_until FROM login_rate_limits WHERE locked_until IS NOT NULL AND locked_until > :now ORDER BY locked_until DESC');
        $statement->execute(['now' => $now->format('Y-m-d H:i:s')]);
        return $statement->fetchAll();
    }

    public function clearAll(): int
    {
        return $this->pdo->exec('DELETE FROM login_rate_limits') ?: 0;
    }

    /** @return array<string, mixed>|null */
    private function find(string $ipAddress): ?array
    {
        $statement = $this->pdo->prepare('SELECT ip_address, failure_count, window_started_at, locked_until FROM login_rate_limits WHERE ip_address = :ip');
        $statement->execute(['ip' => $ipAddress]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }
}
