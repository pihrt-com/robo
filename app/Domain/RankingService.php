<?php
declare(strict_types=1);

namespace Robo\Domain;

use PDO;

/**
 * Builds the running qualification ranking. Teams with a confirmed valid lap are ranked first by
 * final time. Teams without a valid lap remain ranked by their furthest checkpoint, then by how
 * often they reached it, as required by the ROBO competition workflow.
 */
final class RankingService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, int|string|null>> */
    public function qualification(int $competitionId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.id AS team_id, t.team_number, t.school_name, t.contestant_names,
                r.checkpoint_1, r.checkpoint_2, r.checkpoint_3, r.checkpoint_4, r.checkpoint_5, r.checkpoint_6, r.finish_count,
                r.best_time_ms, r.stop_penalty_seconds, r.is_confirmed,
                COALESCE(i.penalty_seconds, 0) AS technical_penalty_seconds
             FROM teams t
             LEFT JOIN rides r ON r.team_id = t.id AND r.competition_id = t.competition_id
             LEFT JOIN technical_inspections i ON i.team_id = t.id AND i.competition_id = t.competition_id
             WHERE t.competition_id = :competition_id
             ORDER BY t.team_number'
        );
        $statement->execute(['competition_id' => $competitionId]);
        $rows = $statement->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $bestTime = $row['best_time_ms'] === null ? null : (int) $row['best_time_ms'];
            $confirmed = (int) ($row['is_confirmed'] ?? 0) === 1;
            $valid = $confirmed && $bestTime !== null && $bestTime > 0;
            $furthest = 0;
            $furthestCount = 0;
            for ($checkpoint = 6; $checkpoint >= 1; $checkpoint--) {
                $count = (int) ($row['checkpoint_' . $checkpoint] ?? 0);
                if ($count > 0) {
                    $furthest = $checkpoint;
                    $furthestCount = $count;
                    break;
                }
            }
            $finalMs = $valid
                ? $bestTime + ((int) $row['stop_penalty_seconds'] + (int) $row['technical_penalty_seconds']) * 1000
                : null;
            $row['has_valid_time'] = $valid ? 1 : 0;
            $row['final_time_ms'] = $finalMs;
            $row['furthest_checkpoint'] = $furthest;
            $row['furthest_checkpoint_count'] = $furthestCount;
            $result[] = $row;
        }

        usort($result, static function (array $left, array $right): int {
            if ($left['has_valid_time'] !== $right['has_valid_time']) {
                return $right['has_valid_time'] <=> $left['has_valid_time'];
            }
            if ($left['has_valid_time'] === 1) {
                $timeOrder = $left['final_time_ms'] <=> $right['final_time_ms'];
                return $timeOrder !== 0 ? $timeOrder : $left['team_number'] <=> $right['team_number'];
            }
            $checkpointOrder = $right['furthest_checkpoint'] <=> $left['furthest_checkpoint'];
            if ($checkpointOrder !== 0) {
                return $checkpointOrder;
            }
            $countOrder = $right['furthest_checkpoint_count'] <=> $left['furthest_checkpoint_count'];
            return $countOrder !== 0 ? $countOrder : $left['team_number'] <=> $right['team_number'];
        });

        $rank = 0;
        foreach ($result as &$row) {
            $hasResult = $row['has_valid_time'] === 1 || $row['furthest_checkpoint'] > 0;
            $row['rank'] = $hasResult ? ++$rank : null;
        }
        unset($row);
        return $result;
    }
}
