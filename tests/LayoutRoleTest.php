<?php
declare(strict_types=1);

namespace Robo\Tests;

final class LayoutRoleTest
{
    public static function run(): void
    {
        $html = self::render(['role_code' => 'technical_inspection', 'permissions' => ['technical.view', 'technical.edit'], 'page' => 'technical']);
        self::assert(str_contains($html, 'href="/robo/technical"'), 'Technician must see technical inspection navigation.');
        self::assert(!str_contains($html, 'href="/robo/track-1"'), 'Technician must not see track 1 navigation.');
        self::assert(!str_contains($html, 'href="/robo/standings"'), 'Technician must not see standings navigation.');
        self::assert(!str_contains($html, 'href="/robo/system"'), 'Technician must not see system navigation.');

        $admin = self::render(['role_code' => 'administrator', 'permissions' => ['*'], 'page' => 'system']);
        self::assert(str_contains($admin, 'href="/robo/system"'), 'Administrator must see system navigation.');
        self::assert(str_contains($admin, '<nav class="nav-row" aria-label="Hlavní navigace">'), 'Administrator must receive the visible navigation bar.');
        self::assert(substr_count($admin, 'class="nav-link"') === 5, 'Administrator must receive all five navigation buttons.');
        self::assert(str_contains($admin, 'data-layout-version="0.4.7"'), 'Authenticated pages must use the new uncached layout file.');
        self::assert(str_contains($admin, '/robo/assets/app.css?v='), 'Static assets must be cache-busted after an update.');
        self::assert(!str_contains($admin, 'Týmů 16'), 'System page must not show work counters.');
        self::assert(str_contains($admin, 'Opravdu se chcete odhlásit?'), 'Logout must require confirmation.');
        self::assert(str_contains($html, 'data-completed-count'), 'Work pages must expose completed-work counters.');

        $english = self::render(['locale' => 'en', 'page' => 'technical']);
        self::assert(str_contains($english, 'ROBO 2026 EN'), 'English work pages must show the EN locale marker.');

        $systemFallback = self::render(['page' => 'system']);
        self::assert(str_contains($systemFallback, 'href="/robo/track-1"'), 'System page fallback must retain administrator navigation.');

        $systemPermission = self::render(['role_code' => 'custom_system_role', 'permissions' => ['system.view'], 'page' => 'system']);
        self::assert(str_contains($systemPermission, 'href="/robo/track-1"'), 'A role authorized for system administration must retain the complete navigation.');

        $technicalFallback = self::render(['page' => 'technical']);
        self::assert(str_contains($technicalFallback, 'href="/robo/technical"'), 'Technical fallback must retain technical navigation.');
        self::assert(!str_contains($technicalFallback, 'href="/robo/system"'), 'Technical fallback must not show system navigation.');

        foreach (['admin', 'race', 'settings-competition', 'settings-log', 'settings-penalties', 'settings-texts', 'settings-users', 'standings', 'teams', 'teams-import-preview', 'technical'] as $viewName) {
            $source = (string) file_get_contents(dirname(__DIR__) . '/views/' . $viewName . '.php');
            self::assert(str_contains($source, "app-shell-v047.php"), 'Authenticated view ' . $viewName . ' must bypass the stale layout opcode.');
        }
    }

    private static function render(array $role): string
    {
        $title = 'Test';
        $content = '<p>Obsah</p>';
        $csrf = 'test-token';
        $help = '<p>Nápověda</p>';
        $view = array_replace(['year' => '2026', 'locale' => 'cs', 'user' => 'test', 'role_label' => 'Správce', 'teams' => 16, 'pending' => 3], $role);
        ob_start();
        require dirname(__DIR__) . '/views/app-shell-v047.php';
        return (string) ob_get_clean();
    }

    private static function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }
}
