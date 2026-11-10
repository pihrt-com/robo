<?php
declare(strict_types=1);

namespace Robo\Domain;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class TeamImportV044
{
    /** @return list<array{team_number:int,school_name:string,contestant_names:string}> */
    public static function xlsx(string $file): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Server nemá rozšíření ZIP potřebné pro import XLSX.');
        }
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException('Nahraný soubor není platný XLSX soubor.');
        }
        try {
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheet === false) {
                throw new RuntimeException('XLSX neobsahuje první list.');
            }
            $sharedStrings = $zip->getFromName('xl/sharedStrings.xml');
            return self::fromXml($sheet, $sharedStrings === false ? null : $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /** @return list<array{team_number:int,school_name:string,contestant_names:string}> */
    public static function fromXml(string $sheetXml, ?string $sharedStringsXml = null): array
    {
        $sheet = @simplexml_load_string($sheetXml);
        if (!$sheet instanceof SimpleXMLElement) {
            throw new RuntimeException('XLSX obsahuje nečitelný první list.');
        }
        $strings = self::sharedStrings($sharedStringsXml);
        $nodes = $sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [];
        $rows = [];
        foreach ($nodes as $rowNode) {
            $cells = [];
            foreach ($rowNode->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                $reference = (string) $cell['r'];
                $column = strtoupper((string) preg_replace('/[^A-Za-z].*$/', '', $reference));
                if ($column !== '') {
                    $cells[$column] = trim(self::cellValue($cell, $strings));
                }
            }
            if ($cells !== []) {
                $rows[] = $cells;
            }
        }
        return self::mapRows($rows);
    }

    /** @param list<array<string,string>> $rows
     *  @return list<array{team_number:int,school_name:string,contestant_names:string}>
     */
    private static function mapRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }
        $aliases = [
            'number' => ['idtymu', 'cislotymu', 'tym', 'team', 'teamid', 'teamnumber', 'idteamu'],
            'school' => ['skola', 'skolaadresaskoly', 'adresaskoly', 'popistymu', 'nazevtymu', 'school', 'schoolname', 'teamdescription', 'description'],
            'contestants' => ['soutezici', 'jmenosouteziciho', 'jmenasoutezicich', 'clenovetymu', 'jmena', 'contestants', 'contestant', 'members', 'teammembers', 'names'],
        ];
        $first = $rows[0];
        $columns = ['number' => null, 'school' => null, 'contestants' => null];
        $recognized = false;
        foreach ($first as $column => $value) {
            $header = self::normalize($value);
            $field = self::headerField($header, $aliases);
            if ($field !== null) {
                $columns[$field] = $column;
                $recognized = true;
            }
        }
        $unknownHeader = !$recognized && count($rows) > 1 && self::looksLikeHeader($first, array_slice($rows, 1));
        $dataRows = ($recognized || $unknownHeader) ? array_slice($rows, 1) : $rows;
        $allColumns = self::allColumns($dataRows === [] ? $rows : $dataRows);
        if (!$recognized) {
            $columns['number'] = self::numericColumn($dataRows, $allColumns);
            $columns['school'] = self::firstAvailableColumn($allColumns, [$columns['number']]);
            $columns['contestants'] = self::secondTextColumn($allColumns, [$columns['number'], $columns['school']]);
        }

        $result = [];
        $nextNumber = 1;
        $used = [];
        foreach ($dataRows as $row) {
            $rawNumber = self::columnValue($row, $columns['number']);
            $school = self::columnValue($row, $columns['school']);
            $contestants = self::columnValue($row, $columns['contestants']);
            if ($rawNumber === '' && $school === '' && $contestants === '') {
                continue;
            }
            $number = ctype_digit($rawNumber) && (int) $rawNumber > 0 ? (int) $rawNumber : 0;
            if ($number === 0 || isset($used[$number])) {
                while (isset($used[$nextNumber])) {
                    $nextNumber++;
                }
                $number = $nextNumber;
            }
            $used[$number] = true;
            $nextNumber = max($nextNumber, $number + 1);
            $result[] = ['team_number' => $number, 'school_name' => $school, 'contestant_names' => $contestants];
        }
        return $result;
    }

    /** @return list<string> */
    private static function sharedStrings(?string $content): array
    {
        if ($content === null || $content === '') {
            return [];
        }
        $xml = @simplexml_load_string($content);
        if (!$xml instanceof SimpleXMLElement) {
            return [];
        }
        $result = [];
        foreach ($xml->xpath('//*[local-name()="si"]') ?: [] as $item) {
            $parts = [];
            foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                $parts[] = (string) $text;
            }
            $result[] = trim(implode('', $parts));
        }
        return $result;
    }

    /** @param list<string> $strings */
    private static function cellValue(SimpleXMLElement $cell, array $strings): string
    {
        $type = (string) $cell['t'];
        if ($type === 'inlineStr') {
            $parts = [];
            foreach ($cell->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                $parts[] = (string) $text;
            }
            return implode('', $parts);
        }
        $valueNodes = $cell->xpath('./*[local-name()="v"]') ?: [];
        $value = isset($valueNodes[0]) ? (string) $valueNodes[0] : '';
        return $type === 's' ? ($strings[(int) $value] ?? '') : $value;
    }

    /** @param list<array<string,string>> $rows
     *  @return list<string>
     */
    private static function allColumns(array $rows): array
    {
        $columns = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $column) {
                $columns[$column] = true;
            }
        }
        $result = array_keys($columns);
        usort($result, static fn (string $left, string $right): int => strlen($left) <=> strlen($right) ?: strcmp($left, $right));
        return $result;
    }

    /** @param list<array<string,string>> $rows
     *  @param list<string> $columns
     */
    private static function numericColumn(array $rows, array $columns): ?string
    {
        foreach ($columns as $column) {
            $values = array_values(array_filter(array_map(static fn (array $row): string => trim($row[$column] ?? ''), $rows), static fn (string $value): bool => $value !== ''));
            if ($values !== [] && count(array_filter($values, static fn (string $value): bool => ctype_digit($value) && (int) $value > 0)) === count($values)) {
                return $column;
            }
        }
        return null;
    }

    /** @param list<string> $columns
     *  @param list<?string> $excluded
     */
    private static function firstAvailableColumn(array $columns, array $excluded): ?string
    {
        foreach ($columns as $column) {
            if (!in_array($column, $excluded, true)) {
                return $column;
            }
        }
        return null;
    }

    /** @param list<string> $columns
     *  @param list<?string> $excluded
     */
    private static function secondTextColumn(array $columns, array $excluded): ?string
    {
        $available = array_values(array_filter($columns, static fn (string $column): bool => !in_array($column, $excluded, true)));
        return count($available) >= 1 ? $available[0] : null;
    }

    /** @param array<string,string> $row */
    private static function columnValue(array $row, ?string $column): string
    {
        return $column === null ? '' : trim($row[$column] ?? '');
    }

    private static function normalize(string $value): string
    {
        $value = strtr(self::lower(trim($value)), ['á'=>'a','č'=>'c','ď'=>'d','é'=>'e','ě'=>'e','í'=>'i','ň'=>'n','ó'=>'o','ř'=>'r','š'=>'s','ť'=>'t','ú'=>'u','ů'=>'u','ý'=>'y','ž'=>'z','Á'=>'a','Č'=>'c','Ď'=>'d','É'=>'e','Ě'=>'e','Í'=>'i','Ň'=>'n','Ó'=>'o','Ř'=>'r','Š'=>'s','Ť'=>'t','Ú'=>'u','Ů'=>'u','Ý'=>'y','Ž'=>'z']);
        return (string) preg_replace('/[^a-z0-9]+/', '', $value);
    }

    /** @param array<string,list<string>> $aliases */
    private static function headerField(string $header, array $aliases): ?string
    {
        foreach ($aliases as $field => $names) {
            if (in_array($header, $names, true)) {
                return $field;
            }
        }
        if ((str_contains($header, 'cislo') || str_starts_with($header, 'id')) && (str_contains($header, 'tym') || str_contains($header, 'druz') || str_contains($header, 'team'))) {
            return 'number';
        }
        if (str_contains($header, 'skol') || str_contains($header, 'organiz') || str_contains($header, 'instituc') || str_contains($header, 'popistym') || str_contains($header, 'nazevtym')) {
            return 'school';
        }
        if (str_contains($header, 'soutez') || str_contains($header, 'clen') || str_contains($header, 'contest') || str_contains($header, 'member') || str_contains($header, 'jmen')) {
            return 'contestants';
        }
        return null;
    }

    /** @param array<string,string> $first
     *  @param list<array<string,string>> $remaining
     */
    private static function looksLikeHeader(array $first, array $remaining): bool
    {
        foreach ($first as $column => $value) {
            $header = self::normalize($value);
            if ($header === '') {
                continue;
            }
            if (preg_match('/(id|cislo|tym|druz|team|skol|organiz|instituc|nazev|popis|soutez|clen|jmen|contest|member)/', $header) === 1) {
                return true;
            }
            $below = array_values(array_filter(array_map(static fn (array $row): string => trim($row[$column] ?? ''), $remaining), static fn (string $item): bool => $item !== ''));
            if (!ctype_digit(trim($value)) && $below !== [] && count(array_filter($below, static fn (string $item): bool => ctype_digit($item))) === count($below)) {
                return true;
            }
        }
        return false;
    }

    private static function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }
}
