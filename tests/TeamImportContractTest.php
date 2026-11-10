<?php
declare(strict_types=1);

namespace Robo\Tests;

use ReflectionClass;
use Robo\Domain\TeamEditorV044;
use Robo\Domain\TeamImportV044;
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

        $editor = (new ReflectionClass(TeamEditorV044::class))->newInstanceWithoutConstructor();
        $editor->validateRows([
            ['team_number' => 1, 'school_name' => 'SOUE Plzeň', 'contestant_names' => ''],
            ['team_number' => 2, 'school_name' => 'Jiná škola', 'contestant_names' => 'Jan, Eva'],
        ]);

        $suppliedFormat = TeamImportV044::fromXml(self::sheet([
            ['ID týmu', 'Popis týmu', 'Nejlepší čas'],
            ['1', 'SOUE Plzeň', '00:20:100'],
            ['2', 'SPŠE Plzeň', '00:21:200'],
        ]));
        self::assert($suppliedFormat === [
            ['team_number'=>1,'school_name'=>'SOUE Plzeň','contestant_names'=>''],
            ['team_number'=>2,'school_name'=>'SPŠE Plzeň','contestant_names'=>''],
        ], 'Import musí rozpoznat dodaný formát ID týmu a Popis týmu.');

        $renamedColumns = TeamImportV044::fromXml(self::sheet([
            ['Číslo družstva', 'Organizace', 'Členové'],
            ['7', 'Škola A', 'A, B'],
            ['8', 'Škola B', 'C, D'],
        ]));
        self::assert($renamedColumns[0] === ['team_number'=>7,'school_name'=>'Škola A','contestant_names'=>'A, B'], 'Import musí rozpoznat přejmenované sloupce.');

        $missingNumber = TeamImportV044::fromXml(self::sheet([
            ['Organizace'],
            ['Škola A'],
            ['Škola B'],
        ]));
        self::assert(array_column($missingNumber, 'team_number') === [1, 2], 'Při chybějícím čísle se musí čísla doplnit podle pořadí řádků.');
        self::assert(array_column($missingNumber, 'contestant_names') === ['', ''], 'Chybějící sloupec soutěžících musí zůstat prázdný.');
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

    /** @param list<list<string>> $rows */
    private static function sheet(array $rows): string
    {
        $body = '';
        foreach ($rows as $rowIndex => $row) {
            $body .= '<row r="'.($rowIndex + 1).'">';
            foreach ($row as $columnIndex => $value) {
                $column = chr(65 + $columnIndex);
                $body .= '<c r="'.$column.($rowIndex + 1).'" t="inlineStr"><is><t>'.htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
            }
            $body .= '</row>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$body.'</sheetData></worksheet>';
    }

    private static function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }
}
