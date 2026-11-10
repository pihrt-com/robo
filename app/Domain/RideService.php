<?php
declare(strict_types=1);

namespace Robo\Domain;

use PDO;
use Robo\Core\AuditLogger;
use RuntimeException;

final class RideService
{
    public function __construct(private readonly PDO $pdo, private readonly AuditLogger $audit)
    {
    }

    /** @param array{checkpoint_1:int,checkpoint_2:int,checkpoint_3:int,checkpoint_4:int,checkpoint_5:int,checkpoint_6:int,finish_count:int,laps:list<int|null>,did_not_stop:bool,confirmed:bool} $input */
    public function save(int $competitionId, int $teamId, int $trackNumber, array $input, int $userId, string $ipAddress): void
    {
        if (!in_array($trackNumber, [1, 2], true) || count($input['laps']) !== 5) {
            throw new RuntimeException('Neplatná data jízdy.');
        }
        $counts = [$input['checkpoint_1'], $input['checkpoint_2'], $input['checkpoint_3'], $input['checkpoint_4'], $input['checkpoint_5'], $input['checkpoint_6'], $input['finish_count']];
        foreach (array_merge($counts, $input['laps']) as $number) {
            if ($number !== null && (!is_int($number) || $number < 0 || $number > 999999)) {
                throw new RuntimeException('Zadaná hodnota jízdy není platná.');
            }
        }
        $times = array_values(array_filter($input['laps'], static fn ($value): bool => $value !== null && $value > 0));
        $bestTime = $times === [] ? null : min($times);
        $penalty = $input['did_not_stop'] ? $this->penalty($competitionId) : 0;
        $statement = $this->pdo->prepare(
            'INSERT INTO rides (competition_id, team_id, track_number, checkpoint_1, checkpoint_2, checkpoint_3, checkpoint_4, checkpoint_5, checkpoint_6, finish_count, lap_1_ms, lap_2_ms, lap_3_ms, lap_4_ms, lap_5_ms, best_time_ms, did_not_stop, stop_penalty_seconds, is_confirmed, confirmed_at, updated_by) VALUES (:competition_id,:team_id,:track_number,:checkpoint_1,:checkpoint_2,:checkpoint_3,:checkpoint_4,:checkpoint_5,:checkpoint_6,:finish_count,:lap_1_ms,:lap_2_ms,:lap_3_ms,:lap_4_ms,:lap_5_ms,:best_time_ms,:did_not_stop,:stop_penalty_seconds,:is_confirmed,:confirmed_at,:updated_by) ON DUPLICATE KEY UPDATE checkpoint_1=VALUES(checkpoint_1),checkpoint_2=VALUES(checkpoint_2),checkpoint_3=VALUES(checkpoint_3),checkpoint_4=VALUES(checkpoint_4),checkpoint_5=VALUES(checkpoint_5),checkpoint_6=VALUES(checkpoint_6),finish_count=VALUES(finish_count),lap_1_ms=VALUES(lap_1_ms),lap_2_ms=VALUES(lap_2_ms),lap_3_ms=VALUES(lap_3_ms),lap_4_ms=VALUES(lap_4_ms),lap_5_ms=VALUES(lap_5_ms),best_time_ms=VALUES(best_time_ms),did_not_stop=VALUES(did_not_stop),stop_penalty_seconds=VALUES(stop_penalty_seconds),is_confirmed=VALUES(is_confirmed),confirmed_at=VALUES(confirmed_at),updated_by=VALUES(updated_by)'
        );
        $statement->execute([
            'competition_id' => $competitionId, 'team_id' => $teamId, 'track_number' => $trackNumber,
            'checkpoint_1' => $input['checkpoint_1'], 'checkpoint_2' => $input['checkpoint_2'], 'checkpoint_3' => $input['checkpoint_3'], 'checkpoint_4' => $input['checkpoint_4'], 'checkpoint_5' => $input['checkpoint_5'], 'checkpoint_6' => $input['checkpoint_6'], 'finish_count' => $input['finish_count'],
            'lap_1_ms' => $input['laps'][0], 'lap_2_ms' => $input['laps'][1], 'lap_3_ms' => $input['laps'][2], 'lap_4_ms' => $input['laps'][3], 'lap_5_ms' => $input['laps'][4],
            'best_time_ms' => $bestTime, 'did_not_stop' => $input['did_not_stop'] ? 1 : 0, 'stop_penalty_seconds' => $penalty,
            'is_confirmed' => $input['confirmed'] ? 1 : 0, 'confirmed_at' => $input['confirmed'] ? date('Y-m-d H:i:s') : null, 'updated_by' => $userId,
        ]);
        $this->audit->log($userId, $competitionId, 'ride.saved', 'Zápis jízdy byl uložen.', ['team_id' => $teamId, 'track' => $trackNumber, 'best_time_ms' => $bestTime], $ipAddress);
    }

    private function penalty(int $competitionId): int
    {
        $statement = $this->pdo->prepare("SELECT seconds_value FROM penalties WHERE competition_id = :competition_id AND code = 'not_stopped' LIMIT 1");
        $statement->execute(['competition_id' => $competitionId]);
        return (int) ($statement->fetchColumn() ?: 0);
    }
}
