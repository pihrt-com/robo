<?php
declare(strict_types=1);

namespace Robo\Tests;

use ReflectionClass;
use Robo\Domain\BackupService;
use RuntimeException;

final class BackupContractsTest
{
    public static function run(): void
    {
        self::assert(BackupService::assertSafeRelativePath('config/local.php') === 'config/local.php', 'Complete backup must allow the encrypted runtime configuration.');
        self::assert(BackupService::assertSafeRelativePath('app/Domain/AdminService.php') === 'app/Domain/AdminService.php', 'Application file path must be accepted.');
        self::fails(static fn () => BackupService::assertSafeRelativePath('../config/local.php'));
        self::fails(static fn () => BackupService::assertSafeRelativePath('.git/config'));
        self::fails(static fn () => BackupService::assertSafeRelativePath('C:\\Windows\\system.ini'));
        $reflection = new ReflectionClass(BackupService::class);
        $directories = $reflection->getConstant('DIRECTORIES');
        $rootFiles = $reflection->getConstant('ROOT_FILES');
        self::assert(is_array($directories) && !in_array('design', $directories, true), 'Removed design files must not return in new backups.');
        self::assert(is_array($rootFiles) && in_array('teams-import-v044.php', $rootFiles, true) && in_array('teams-save-v044.php', $rootFiles, true), 'Versioned team endpoints must be included in complete backups.');
    }

    private static function fails(callable $callback): void
    {
        try {
            $callback();
        } catch (RuntimeException) {
            return;
        }
        throw new RuntimeException('Unsafe backup path was accepted.');
    }

    private static function assert(bool $condition, string $message): void
    {
        if (!$condition) throw new RuntimeException($message);
    }
}
