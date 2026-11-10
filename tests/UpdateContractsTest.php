<?php
declare(strict_types=1);

namespace Robo\Tests;

use Robo\Update\UpdateException;
use Robo\Update\UpdateService;
use Robo\Update\Version;

final class UpdateContractsTest
{
    public static function run(): void
    {
        self::assert(Version::compare('1.2.3', '1.2.2') > 0, 'Newer stable version must compare higher.');
        self::assert(Version::compare('1.2.3-rc.2', '1.2.3-rc.10') < 0, 'Numeric prerelease identifiers must use numeric order.');
        self::assert(Version::compare('1.2.3', '1.2.3-rc.1') > 0, 'Stable version must compare higher than prerelease.');
        self::assert(UpdateService::assertSafeArchivePath('app/Update/Version.php') === 'app/Update/Version.php', 'Safe paths must be accepted.');
        self::assertThrows(static fn () => UpdateService::assertSafeArchivePath('../config/local.php'), 'Traversal path must be rejected.');
        self::assertThrows(static fn () => UpdateService::assertSafeArchivePath('config/local.php'), 'Persistent configuration must be protected.');
        self::assert(UpdateService::isProtectedRuntimePath('config/local.php'), 'Persistent configuration must not be replaced by an update.');
        self::assert(UpdateService::isProtectedRuntimePath('install.php'), 'A removed installer must not be restored by an update.');
        self::assert(!UpdateService::isProtectedRuntimePath('index.php'), 'Application files must remain updateable.');
    }

    private static function assertThrows(callable $callback, string $message): void
    {
        try {
            $callback();
        } catch (UpdateException) {
            return;
        }
        throw new \RuntimeException($message);
    }

    private static function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }
}
