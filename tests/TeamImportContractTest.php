<?php
declare(strict_types=1);

namespace Robo\Tests;

use ReflectionClass;
use Robo\Domain\TeamService;

final class TeamImportContractTest
{
    public static function run(): void
    {
        $service = (new ReflectionClass(TeamService::class))->newInstanceWithoutConstructor();
        $service->validateRows([
            ['team_number' => 1, 'school_name' => 'SPŠE Plzeň', 'contestant_names' => 'Jan Novák, Petr Novák'],
            ['team_number' => 2, 'school_name' => 'SPŠE Plzeň', 'contestant_names' => 'Eva Nová, Iva Nová'],
        ]);
        self::mustFail($service, [
            ['team_number' => 1, 'school_name' => 'A', 'contestant_names' => 'A'],
            ['team_number' => 1, 'school_name' => 'B', 'contestant_names' => 'B'],
        ]);
        self::mustFail($service, [
            ['team_number' => 1, 'school_name' => 'A', 'contestant_names' => 'A'],
            ['team_number' => 2, 'school_name' => 'A', 'contestant_names' => 'B'],
            ['team_number' => 3, 'school_name' => 'A', 'contestant_names' => 'C'],
        ]);
    }

    /** @param list<array{team_number:int,school_name:string,contestant_names:string}> $rows */
    private static function mustFail(TeamService $service, array $rows): void
    {
        try {
            $service->validateRows($rows);
        } catch (\RuntimeException) {
            return;
        }
        throw new \RuntimeException('Neplatný import byl přijat.');
    }
}
