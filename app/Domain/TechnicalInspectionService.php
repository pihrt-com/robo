<?php
declare(strict_types=1);

namespace Robo\Domain;

use PDO;
use Robo\Core\AuditLogger;
use RuntimeException;

final class TechnicalInspectionService
{
    public function __construct(private readonly PDO $pdo, private readonly AuditLogger $audit)
    {
    }

    /** @param array{criterion_1: string, criterion_2: string, criterion_3: string, note?: string} $values */
    public function save(int $competitionId, int $teamId, array $values, int $userId, string $ipAddress): void
    {
        foreach (['criterion_1', 'criterion_2', 'criterion_3'] as $field) {
            if (!in_array($values[$field], ['unknown', 'passed', 'failed'], true)) {
                throw new RuntimeException('Neplatný stav technické podmínky.');
            }
        }
        $failed = in_array('failed', [$values['criterion_1'], $values['criterion_2'], $values['criterion_3']], true);
        $complete = !in_array('unknown', [$values['criterion_1'], $values['criterion_2'], $values['criterion_3']], true);
        $penalty = $failed ? $this->penalty($competitionId) : 0;
        $statement = $this->pdo->prepare(
            'INSERT INTO technical_inspections (competition_id, team_id, criterion_1, criterion_2, criterion_3, note, penalty_seconds, is_passed, completed_at, updated_by) VALUES (:competition_id, :team_id, :criterion_1, :criterion_2, :criterion_3, :note, :penalty_seconds, :is_passed, :completed_at, :updated_by) ON DUPLICATE KEY UPDATE criterion_1=VALUES(criterion_1), criterion_2=VALUES(criterion_2), criterion_3=VALUES(criterion_3), note=VALUES(note), penalty_seconds=VALUES(penalty_seconds), is_passed=VALUES(is_passed), completed_at=VALUES(completed_at), updated_by=VALUES(updated_by)'
        );
        $statement->execute([
            'competition_id' => $competitionId,
            'team_id' => $teamId,
            'criterion_1' => $values['criterion_1'],
            'criterion_2' => $values['criterion_2'],
            'criterion_3' => $values['criterion_3'],
            'note' => $values['note'] ?? '',
            'penalty_seconds' => $penalty,
            'is_passed' => $complete && !$failed ? 1 : 0,
            'completed_at' => $complete ? date('Y-m-d H:i:s') : null,
            'updated_by' => $userId,
        ]);
        $this->audit->log($userId, $competitionId, 'technical_inspection.saved', 'Technická přejímka byla uložena.', ['team_id' => $teamId, 'penalty_seconds' => $penalty], $ipAddress);
    }

    private function penalty(int $competitionId): int
    {
        $statement = $this->pdo->prepare("SELECT seconds_value FROM penalties WHERE competition_id = :competition_id AND code = 'technical' LIMIT 1");
        $statement->execute(['competition_id' => $competitionId]);
        return (int) ($statement->fetchColumn() ?: 0);
    }
}
