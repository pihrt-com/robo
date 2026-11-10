<?php
declare(strict_types=1);

namespace Robo\Core;

use PDO;

final class AuditLogger
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, scalar|null> $context */
    public function log(?int $userId, ?int $competitionId, string $type, string $message, array $context = [], ?string $ipAddress = null): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, competition_id, event_type, message, context_json, ip_address) VALUES (:user_id, :competition_id, :event_type, :message, :context_json, :ip_address)'
        );
        $statement->execute([
            'user_id' => $userId,
            'competition_id' => $competitionId,
            'event_type' => $type,
            'message' => $message,
            'context_json' => json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'ip_address' => $ipAddress,
        ]);
    }
}
