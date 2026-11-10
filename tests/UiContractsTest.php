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
        self::assert(str_contains($technical, 'class="modal-body team-details"'), 'Team details must use the larger regular-weight presentation.');

        $race = self::render('race', ['track' => 1, 'teams' => [[
            'id' => 1, 'number' => 1, 'school' => '', 'members' => '', 'confirmed' => 1, 'best_time' => null, 'did_not_stop' => 1,
        ]]]);
        self::assert(str_contains($race, 'class="row-complete"'), 'Confirmed ride must mark its complete row.');
        self::assert(str_contains($race, 'data-field="checkpoint_1" data-value="0" data-empty-value="?"></button>'), 'Confirmed ride must hide unanswered placeholders.');
        self::assert(str_contains($race, 'data-state="invalid"'), 'Confirmed ride without a lap must show the invalid marker.');

        $activeRace = self::render('race', ['track' => 1, 'teams' => [[
            'id' => 2, 'number' => 3, 'school' => 'Škola', 'members' => 'Jméno', 'checkpoint_1' => 6, 'time_1' => 12345, 'best_time' => 12345, 'confirmed' => 0, 'did_not_stop' => 0,
        ]]]);
        self::assert(str_contains($activeRace, 'data-value="6" data-empty-value="?">6x</button>'), 'Checkpoint counts must include the x suffix and retain their numeric value.');
        self::assert(str_contains($activeRace, 'data-open-modal="edit-2-time_1"'), 'Lap time editing must open on a regular click.');
        self::assert(!str_contains($activeRace, 'data-long-press="edit-2-time_1"'), 'Lap time editing must not require a long press.');

        $javascript = (string) file_get_contents(dirname(__DIR__) . '/public/assets/app.js');
        $stylesheet = (string) file_get_contents(dirname(__DIR__) . '/public/assets/app.css');
        self::assert(str_contains($javascript, 'if (field === "is_confirmed")'), 'A regular checkpoint response must not restore missing placeholders.');
        self::assert(str_contains($javascript, 'fetch("/robo/navigation-v043.php"'), 'Stale authenticated layouts must repair navigation through the new endpoint.');
        self::assert(str_contains($javascript, 'navigation.dataset.navigationRepair = "0.4.3"'), 'Repaired navigation must expose its client-side version marker.');
        self::assert(str_contains($stylesheet, '.best-time .team-button { color: var(--white); }'), 'The best-time button text must remain white.');

        $standings = self::render('standings', ['rows' => [[
            'team_id' => 3, 'number' => 6, 'school' => 'Škola', 'members' => 'Jméno', 'checkpoint_1' => 6, 'furthest_checkpoint' => 'checkpoint_1', 'best_time' => 1000, 'did_not_stop' => 0, 'technical_penalty' => 0, 'total_time' => 1000, 'rank' => 1,
        ]]]);
        self::assert(str_contains($standings, 'class="display-team">6</td>'), 'Standings team numbers must use the same size treatment as rank.');
        self::assert(str_contains($standings, 'class="progress-mark">6x</td>'), 'Standings checkpoint counts must include the x suffix.');
        self::assert(str_contains($standings, 'class="form-actions standings-actions"'), 'Standings export actions must keep spacing above the table.');
        self::assert(!str_contains($standings, 'class="best-time">1000</td>'), 'Standings total time must not use the green highlight.');
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
