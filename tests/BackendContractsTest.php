<?php
declare(strict_types=1);

namespace Robo\Tests;

use Robo\Domain\PageTextCatalog;
use Robo\Security\Authorization;

final class BackendContractsTest
{
    public static function run(): void
    {
        self::assert(Authorization::allows('administrator', [], 'technical.edit'), 'Administrator must have all permissions.');
        self::assert(count(PageTextCatalog::entries()) >= 60, 'The page-text catalog must contain the full administrative text set.');
        self::assert(PageTextCatalog::find('technical', 'condition_1') !== null, 'Technical inspection text must have a catalog entry.');
        self::assert(PageTextCatalog::find('system', 'update_system') !== null, 'System update text must have a catalog entry.');
        self::assert(Authorization::allows('track_1', ['track_1.view', 'track_1.edit'], 'track_1.edit'), 'Track editor permission must work.');
        self::assert(!Authorization::allows('viewer', ['results.view'], 'technical.edit'), 'Viewer must not edit technical inspection.');
        self::assert(!Authorization::allows('guest', ['results.view'], 'track_2.edit'), 'Guest must not edit rides.');
    }

    private static function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }
}
