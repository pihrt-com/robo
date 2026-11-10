<?php
declare(strict_types=1);

namespace Robo\Update;

/** Semver validation and comparison without invoking an external program. */
final class Version
{
    private const PATTERN = '/^v?(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z.-]+)?$/D';

    public static function normalize(string $value): string
    {
        $value = trim($value);
        if (!preg_match(self::PATTERN, $value, $parts)) {
            throw new UpdateException('Neplatná verze. Je vyžadován formát SemVer, například 1.2.3.');
        }

        return ($parts[1] . '.' . $parts[2] . '.' . $parts[3]) . (isset($parts[4]) && $parts[4] !== '' ? '-' . $parts[4] : '');
    }

    /** Returns -1 when $left is older, 0 when equal and 1 when newer. */
    public static function compare(string $left, string $right): int
    {
        $left = self::normalize($left);
        $right = self::normalize($right);

        [$leftCore, $leftPre] = array_pad(explode('-', $left, 2), 2, null);
        [$rightCore, $rightPre] = array_pad(explode('-', $right, 2), 2, null);
        $coreComparison = version_compare($leftCore, $rightCore);
        if ($coreComparison !== 0) {
            return $coreComparison <=> 0;
        }
        if ($leftPre === null || $rightPre === null) {
            return $leftPre === $rightPre ? 0 : ($leftPre === null ? 1 : -1);
        }

        $leftIdentifiers = explode('.', $leftPre);
        $rightIdentifiers = explode('.', $rightPre);
        foreach (range(0, max(count($leftIdentifiers), count($rightIdentifiers)) - 1) as $index) {
            if (!array_key_exists($index, $leftIdentifiers)) {
                return -1;
            }
            if (!array_key_exists($index, $rightIdentifiers)) {
                return 1;
            }
            $a = $leftIdentifiers[$index];
            $b = $rightIdentifiers[$index];
            if ($a === $b) {
                continue;
            }
            $aNumeric = ctype_digit($a);
            $bNumeric = ctype_digit($b);
            if ($aNumeric && $bNumeric) {
                return ((int) $a) <=> ((int) $b);
            }
            if ($aNumeric !== $bNumeric) {
                return $aNumeric ? -1 : 1;
            }

            return strcmp($a, $b) <=> 0;
        }

        return 0;
    }
}
