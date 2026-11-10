<?php
declare(strict_types=1);

namespace Robo\Domain;

use PDO;
use RuntimeException;

final class CompetitionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function active(): array
    {
        $row = $this->pdo->query('SELECT * FROM competitions WHERE is_active = 1 LIMIT 1')->fetch();
        if ($row === false) {
            throw new RuntimeException('Není vytvořen aktivní ročník soutěže.');
        }
        return $row;
    }

    /** @return list<array<string,mixed>> */
    public function teamsForTrack(int $competitionId, int $trackNumber): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM teams WHERE competition_id = :competition_id AND MOD(team_number, 2) = :parity ORDER BY team_number');
        $statement->execute(['competition_id' => $competitionId, 'parity' => $trackNumber % 2]);
        return $statement->fetchAll();
    }
}
