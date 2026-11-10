<?php
declare(strict_types=1);

namespace Robo\Domain;

use PDO;
use Robo\Core\AuditLogger;
use RuntimeException;

final class TeamService
{
    public function __construct(private readonly PDO $pdo, private readonly AuditLogger $audit)
    {
    }

    /** @return list<array<string, mixed>> */
    public function list(int $competitionId): array
    {
        $statement = $this->pdo->prepare('SELECT id, team_number, school_name, contestant_names FROM teams WHERE competition_id = :competition_id ORDER BY team_number');
        $statement->execute(['competition_id' => $competitionId]);
        return $statement->fetchAll();
    }

    /** @param array{team_number:int,school_name:string,contestant_names:string} $input */
    public function save(int $competitionId, ?int $teamId, array $input, int $userId, string $ipAddress): int
    {
        $number = $input['team_number'];
        $school = trim($input['school_name']);
        $contestants = trim($input['contestant_names']);
        if ($number < 1 || $number > 999 || self::length($school) > 255 || self::length($contestants) > 500) {
            throw new RuntimeException('Údaje týmu nemají platný rozsah.');
        }
        $this->assertSchoolLimit($competitionId, $school, $teamId);
        if ($teamId === null) {
            $statement = $this->pdo->prepare('INSERT INTO teams (competition_id, team_number, school_name, contestant_names) VALUES (:competition_id, :team_number, :school_name, :contestant_names)');
            $statement->execute(['competition_id' => $competitionId, 'team_number' => $number, 'school_name' => $school, 'contestant_names' => $contestants]);
            $teamId = (int) $this->pdo->lastInsertId();
            $event = 'team.created';
        } else {
            $statement = $this->pdo->prepare('UPDATE teams SET team_number = :team_number, school_name = :school_name, contestant_names = :contestant_names WHERE id = :id AND competition_id = :competition_id');
            $statement->execute(['team_number' => $number, 'school_name' => $school, 'contestant_names' => $contestants, 'id' => $teamId, 'competition_id' => $competitionId]);
            if ($statement->rowCount() === 0 && !$this->exists($competitionId, $teamId)) {
                throw new RuntimeException('Tým neexistuje.');
            }
            $event = 'team.updated';
        }
        $this->audit->log($userId, $competitionId, $event, 'Údaje týmu byly uloženy.', ['team_id' => $teamId, 'team_number' => $number], $ipAddress);
        return $teamId;
    }

    public function delete(int $competitionId, int $teamId, int $userId, string $ipAddress): void
    {
        $statement = $this->pdo->prepare('DELETE FROM teams WHERE id = :id AND competition_id = :competition_id');
        $statement->execute(['id' => $teamId, 'competition_id' => $competitionId]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Tým neexistuje.');
        }
        $this->audit->log($userId, $competitionId, 'team.deleted', 'Tým byl odstraněn.', ['team_id' => $teamId], $ipAddress);
    }

    /** @param list<array{team_number:int,school_name:string,contestant_names:string}> $rows */
    public function replaceFromImport(int $competitionId, array $rows, int $userId, string $ipAddress): void
    {
        $this->validateRows($rows);
        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM teams WHERE competition_id = :competition_id');
            $delete->execute(['competition_id' => $competitionId]);
            $insert = $this->pdo->prepare('INSERT INTO teams (competition_id, team_number, school_name, contestant_names) VALUES (:competition_id, :team_number, :school_name, :contestant_names)');
            foreach ($rows as $row) {
                $insert->execute(['competition_id' => $competitionId, 'team_number' => $row['team_number'], 'school_name' => $row['school_name'], 'contestant_names' => $row['contestant_names']]);
            }
            $this->audit->log($userId, $competitionId, 'team.imported', 'Seznam týmů byl nahrazen importem.', ['team_count' => count($rows)], $ipAddress);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    /** @param list<array{team_number:int,school_name:string,contestant_names:string}> $rows */
    public function validateRows(array $rows): void
    {
        if ($rows === []) throw new RuntimeException('Import neobsahuje žádný tým.');
        $numbers = [];
        $schools = [];
        foreach ($rows as $row) {
            if ($row['team_number'] < 1 || $row['team_number'] > 999 || self::length($row['school_name']) > 255 || self::length($row['contestant_names']) > 500) throw new RuntimeException('Import obsahuje neplatnou hodnotu.');
            if (isset($numbers[$row['team_number']])) throw new RuntimeException('Import obsahuje duplicitní číslo týmu ' . $row['team_number'] . '.');
            $numbers[$row['team_number']] = true;
            $normalized = self::lower(trim($row['school_name']));
            if ($normalized !== '') {
                $schools[$normalized] = ($schools[$normalized] ?? 0) + 1;
                if ($schools[$normalized] > 2) throw new RuntimeException('Import obsahuje více než dva týmy stejné školy: ' . $row['school_name'] . '.');
            }
        }
    }

    private function assertSchoolLimit(int $competitionId, string $school, ?int $excludedTeamId): void
    {
        if ($school === '') return;
        $sql = 'SELECT COUNT(*) FROM teams WHERE competition_id = :competition_id AND LOWER(TRIM(school_name)) = LOWER(TRIM(:school_name))';
        if ($excludedTeamId !== null) $sql .= ' AND id <> :team_id';
        $statement = $this->pdo->prepare($sql);
        $params = ['competition_id' => $competitionId, 'school_name' => $school];
        if ($excludedTeamId !== null) $params['team_id'] = $excludedTeamId;
        $statement->execute($params);
        if ((int) $statement->fetchColumn() >= 2) throw new RuntimeException('Jedna škola může mít nejvýše dva týmy.');
    }

    private function exists(int $competitionId, int $teamId): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM teams WHERE id = :id AND competition_id = :competition_id');
        $statement->execute(['id' => $teamId, 'competition_id' => $competitionId]);
        return $statement->fetchColumn() !== false;
    }

    private static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private static function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }
}
