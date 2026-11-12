<?php
declare(strict_types=1);

namespace Robo\Tests;

use InvalidArgumentException;
use Robo\Domain\AdminService;
use Robo\Domain\AdminValidation;

final class AdminValidationTest
{
    public static function run(): void
    {
        $competition = AdminValidation::competition(2027, '2027-03-05', 'en', 32);
        self::assert($competition['team_count'] === 32, 'Team count must be retained.');
        self::assert(AdminValidation::username('track-1.operator') === 'track-1.operator', 'Valid username must be retained.');
        self::assert(AdminValidation::password('Dlouhé heslo ! 2026') === 'Dlouhé heslo ! 2026', 'Password must accept spaces and special characters.');
        $penalty = AdminValidation::penalty('not_stopped', 'Nezastavení', 'Did not stop', 5);
        self::assert($penalty['seconds'] === 5, 'Penalty seconds must be retained.');
        self::assert(AdminService::defaultPenalties() === [
            ['code' => 'technical', 'label_cs' => 'Technická přejímka', 'label_en' => 'Technical inspection', 'seconds' => 90],
            ['code' => 'not_stopped', 'label_cs' => 'Nezastavení', 'label_en' => 'Did not stop', 'seconds' => 5],
        ], 'Every competition must receive the required default penalties.');
        self::fails(static fn() => AdminValidation::competition(2019, '2027-03-05', 'cs', 16));
        self::fails(static fn() => AdminValidation::username('bad name'));
        self::fails(static fn() => AdminValidation::password('kratke'));
        self::fails(static fn() => AdminValidation::password("platne-heslo\nse-zalomenim"));
        self::fails(static fn() => AdminValidation::penalty('?', '', 'Penalty', -1));
    }

    private static function fails(callable $callback): void
    {
        try {
            $callback();
        } catch (InvalidArgumentException) {
            return;
        }
        throw new \RuntimeException('Invalid input was accepted.');
    }

    private static function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }
}
