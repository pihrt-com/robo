<?php
declare(strict_types=1);

namespace Robo\Tests;

use Robo\Domain\PageTextCatalog;
use Robo\Domain\NavigationMenu;
use Robo\Security\Authorization;

final class BackendContractsTest
{
    public static function run(): void
    {
        self::assert(Authorization::allows('administrator', [], 'technical.edit'), 'Administrator must have all permissions.');
        self::assert(count(PageTextCatalog::entries()) >= 60, 'The page-text catalog must contain the full administrative text set.');
        self::assert(PageTextCatalog::find('technical', 'condition_1') !== null, 'Technical inspection text must have a catalog entry.');
        self::assert(PageTextCatalog::find('system', 'update_system') !== null, 'System update text must have a catalog entry.');
        self::assert(PageTextCatalog::find('navigation', 'connection_online') !== null, 'Header connection state must have a catalog entry.');
        self::assert(Authorization::allows('track_1', ['track_1.view', 'track_1.edit'], 'track_1.edit'), 'Track editor permission must work.');
        self::assert(!Authorization::allows('viewer', ['results.view'], 'technical.edit'), 'Viewer must not edit technical inspection.');
        self::assert(!Authorization::allows('guest', ['results.view'], 'track_2.edit'), 'Guest must not edit rides.');
        self::assert(count(NavigationMenu::items('administrator', [], [])) === 5, 'Administrator navigation repair must expose all five destinations.');
        $technicalMenu = NavigationMenu::items('technical_inspection', ['technical.view'], ['technical' => 'Přejímka']);
        self::assert($technicalMenu === [['path' => 'technical', 'label' => 'Přejímka']], 'Navigation repair must keep role permissions and stored labels.');
        $navigationEndpoint = (string) file_get_contents(dirname(__DIR__) . '/navigation-v043.php');
        self::assert(str_contains($navigationEndpoint, 'Session::userId()'), 'Navigation endpoint must require the signed-in session.');
        self::assert(str_contains($navigationEndpoint, "header('Cache-Control: no-store')"), 'Navigation endpoint must not be cached.');
        $headerEndpoint = (string) file_get_contents(dirname(__DIR__) . '/header-v047.php');
        self::assert(str_contains($headerEndpoint, 'Session::userId()'), 'Header repair endpoint must require the signed-in session.');
        self::assert(str_contains($headerEndpoint, "header('Cache-Control: no-store')"), 'Header repair endpoint must not be cached.');
        self::assert(str_contains($headerEndpoint, "'brand' => 'ROBO '"), 'Header repair endpoint must return the localized ROBO locale marker.');
        $session = (string) file_get_contents(dirname(__DIR__) . '/app/Core/Session.php');
        self::assert(str_contains($session, 'IDLE_TIMEOUT_SECONDS = 28800'), 'Sessions must expire after the configured inactivity period.');
        self::assert(str_contains($session, "ini_set('session.use_strict_mode', '1')"), 'Sessions must reject uninitialized identifiers.');
        $adminService = (string) file_get_contents(dirname(__DIR__) . '/app/Domain/AdminService.php');
        self::assert(str_contains($adminService, 'function deleteCompetition'), 'Historical competitions must have a protected delete operation.');
        self::assert(str_contains($adminService, 'Aktivní ročník nelze smazat'), 'The active competition must remain protected from deletion.');
        self::assert(str_contains($adminService, 'function clearAudit'), 'Administrators must be able to clear audit records with an explicit workflow.');
        $rewriteRules = (string) file_get_contents(dirname(__DIR__) . '/.htaccess');
        self::assert(str_contains($rewriteRules, 'RewriteRule ^public/?$ index.php [L,QSA]'), 'Public standings must be routed before Apache treats the public asset directory as a directory listing.');
    }

    private static function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }
}
