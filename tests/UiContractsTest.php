<?php
declare(strict_types=1);

namespace Robo\Tests;

use RuntimeException;

final class UiContractsTest
{
    public static function run(): void
    {
        $technical = self::render('technical', ['teams' => [[
            'id' => 1, 'number' => 1, 'school' => '', 'members' => '', 'condition_1' => 'fail', 'condition_2' => 'pass', 'condition_3' => 'pass', 'note' => 'Kontrola držáku', 'technical_penalty' => 90, 'passed' => 0, 'complete' => true,
        ]], 'labels' => []]);
        self::assert(str_contains($technical, 'data-note-button'), 'Technical note button must expose its saved state.');
        self::assert(str_contains($technical, 'note-button has-note'), 'Saved technical note must be visibly marked.');
        self::assert(str_contains($technical, 'data-computed="technical-result" class="result-failed"'), 'Technical result must be updateable in place.');

        $race = self::render('race', ['track' => 1, 'teams' => [[
            'id' => 1, 'number' => 1, 'school' => '', 'members' => '', 'confirmed' => 1, 'best_time' => null, 'did_not_stop' => 1,
        ]]]);
        self::assert(str_contains($race, 'class="row-complete"'), 'Confirmed ride must mark its complete row.');
        self::assert(str_contains($race, 'data-field="checkpoint_1" data-empty-value="?"></button>'), 'Confirmed ride must hide unanswered placeholders.');
        self::assert(str_contains($race, 'data-state="invalid"'), 'Confirmed ride without a lap must show the invalid marker.');
    }

    private static function render(string $viewName, array $variables): string
    {
        $title = 'Test';
        $csrf = 'test-token';
        $view = ['year' => '2026', 'locale' => 'cs', 'user' => 'test', 'role_label' => 'Správce', 'role_code' => 'administrator', 'permissions' => ['*'], 'page' => $viewName === 'race' ? 'track-1' : $viewName, 'teams' => 1, 'pending' => 0];
        extract($variables, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__) . '/views/' . $viewName . '.php';
        return (string) ob_get_clean();
    }

    private static function assert(bool $condition, string $message): void
    {
        if (!$condition) throw new RuntimeException($message);
    }
}
