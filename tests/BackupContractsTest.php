<?php
declare(strict_types=1);

namespace Robo\Tests;

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
