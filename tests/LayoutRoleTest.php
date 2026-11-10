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
    }

    private static function render(array $role): string
    {
        $title = 'Test';
        $content = '<p>Obsah</p>';
        $csrf = 'test-token';
        $help = '<p>Nápověda</p>';
        $view = ['year' => '2026', 'locale' => 'cs', 'user' => 'test', 'teams' => 16, 'pending' => 3] + $role;
        ob_start();
        require dirname(__DIR__) . '/views/layout.php';
        return (string) ob_get_clean();
    }

    private static function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }
}
