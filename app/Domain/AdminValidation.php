<?php
declare(strict_types=1);

namespace Robo\Domain;

use InvalidArgumentException;

final class AdminValidation
{
    public static function competition(int $year, string $date, string $locale, int $teamCount): array
    {
        if ($year < 2020 || $year > 2100 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !in_array($locale, ['cs', 'en'], true) || $teamCount < 1 || $teamCount > 200) {
            throw new InvalidArgumentException('Nastavení ročníku není platné.');
        }
        return ['year' => $year, 'date' => $date, 'locale' => $locale, 'team_count' => $teamCount];
    }

    public static function username(string $username): string
    {
        $username = trim($username);
        if (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username)) {
            throw new InvalidArgumentException('Uživatelské jméno musí mít 3 až 80 znaků a může obsahovat písmena, čísla, tečku, podtržítko a pomlčku.');
        }
        return $username;
    }

    public static function penalty(string $code, string $cs, string $en, int $seconds): array
    {
        if (!preg_match('/^[a-z0-9_]{2,40}$/', $code) || trim($cs) === '' || trim($en) === '' || $seconds < 0 || $seconds > 3600) {
            throw new InvalidArgumentException('Nastavení penalizace není platné.');
        }
        return ['code' => $code, 'label_cs' => trim($cs), 'label_en' => trim($en), 'seconds' => $seconds];
    }
}
