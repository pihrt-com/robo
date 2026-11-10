<?php
declare(strict_types=1);

namespace Robo\Domain;

use RuntimeException;
use ZipArchive;

final class TeamImport
{
    /** @return list<array{team_number:int,school_name:string,contestant_names:string}> */
    public static function xlsx(string $file): array
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('Server nemá rozšíření ZIP potřebné pro import XLSX.');
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) throw new RuntimeException('Nahraný soubor není platný XLSX soubor.');
        try {
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheet === false) throw new RuntimeException('XLSX neobsahuje první list.');
            $strings = self::sharedStrings($zip);
            $xml = @simplexml_load_string($sheet);
            if ($xml === false) throw new RuntimeException('XLSX obsahuje nečitelný první list.');
            $xml->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = $xml->xpath('//s:sheetData/s:row') ?: [];
            $result = [];
            foreach ($rows as $rowNumber => $row) {
                $cells = [];
                foreach ($row->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->c as $cell) {
                    $reference = (string) $cell['r'];
                    $column = preg_replace('/\d+/', '', $reference) ?: '';
                    $cells[$column] = self::cellValue($cell, $strings);
                }
                if ($rowNumber === 0) continue;
                $id = trim($cells['A'] ?? '');
                $school = trim($cells['B'] ?? '');
                $contestants = trim($cells['C'] ?? '');
                if ($id === '' && $school === '' && $contestants === '') continue;
                if (!ctype_digit($id) || (int) $id < 1) throw new RuntimeException('Řádek ' . ($rowNumber + 1) . ' nemá platné ID týmu.');
                $result[] = ['team_number' => (int) $id, 'school_name' => $school, 'contestant_names' => $contestants];
            }
            return $result;
        } finally { $zip->close(); }
    }

    /** @return list<string> */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $content = $zip->getFromName('xl/sharedStrings.xml');
        if ($content === false) return [];
        $xml = @simplexml_load_string($content);
        if ($xml === false) return [];
        $xml->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        return array_map(static fn ($node): string => trim((string) $node), $xml->xpath('//s:si') ?: []);
    }

    /** @param list<string> $strings */
    private static function cellValue(\SimpleXMLElement $cell, array $strings): string
    {
        $type = (string) $cell['t'];
        if ($type === 'inlineStr') return (string) $cell->is->t;
        $value = (string) $cell->v;
        return $type === 's' ? ($strings[(int) $value] ?? '') : $value;
    }
}
