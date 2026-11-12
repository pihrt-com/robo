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
        $technicalEnglish = self::render('technical', ['teams' => [], 'labels' => ['title' => 'Technical inspection', 'team' => 'Team', 'condition_1' => 'Dimensions 20x20x20 (cm)', 'condition_2' => 'Cup holder 1 (mm)', 'condition_3' => 'Cup space 2.5 (cm)', 'note' => 'Note', 'penalty' => 'Penalty (sec)', 'passed' => 'Passed']]);
        self::assert(str_contains($technicalEnglish, 'Dimensions 20x20x20 (cm)') && str_contains($technicalEnglish, '>Team</th>'), 'Technical inspection table must render its English catalog labels.');

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
        $raceEnglish = self::render('race', ['track' => 1, 'teams' => [], 'labels' => ['team' => 'Team', 'checkpoints' => 'Checkpoints', 'laps' => 'Complete lap times (ms)', 'did_not_stop' => 'Did not stop', 'confirmed' => 'Confirmed', 'finish' => 'Finish', 'time_1' => 'Time 1', 'time_2' => 'Time 2', 'time_3' => 'Time 3', 'time_4' => 'Time 4', 'time_5' => 'Time 5']]);
        self::assert(str_contains($raceEnglish, '>Checkpoints</th>') && str_contains($raceEnglish, '>Did not stop</th>'), 'Track table must render its English catalog labels.');

        $javascript = (string) file_get_contents(dirname(__DIR__) . '/public/assets/app.js');
        $stylesheet = (string) file_get_contents(dirname(__DIR__) . '/public/assets/app.css');
        self::assert(str_contains($javascript, 'if (field === "is_confirmed")'), 'A regular checkpoint response must not restore missing placeholders.');
        self::assert(str_contains($javascript, 'document.documentElement.lang || "cs"'), 'Client-side date and save-state formatting must follow the selected locale.');
        self::assert(str_contains($javascript, 'header-v047.php?page=${encodeURIComponent(activePath)}'), 'Stale authenticated layouts must repair the complete header through the new endpoint.');
        self::assert(str_contains($javascript, 'const needsHeaderRefresh = true;'), 'Header synchronization must run on every authenticated page load.');
        self::assert(str_contains($javascript, 'function organizeHeader(meta)'), 'Header metadata must be grouped into stable visual regions.');
        self::assert(str_contains($javascript, 'navigation.dataset.navigationRepair = "0.4.7"'), 'Repaired navigation must expose its client-side version marker.');
        self::assert(str_contains($javascript, 'existingSignedUser.textContent.trim() === ""'), 'An empty user label in a stale layout must be filled from the navigation endpoint.');
        self::assert(str_contains($javascript, 'data-completed-count'), 'The header repair must restore completed-work counters.');
        self::assert(str_contains($javascript, 'repairSystemHome();'), 'The cached system home must receive the redesigned settings buttons.');
        self::assert(str_contains($stylesheet, '.best-time .team-button { color: var(--white); }'), 'The best-time button text must remain white.');
        self::assert(str_contains($stylesheet, '.settings-link { display: flex;'), 'System settings links must be presented as buttons.');
        self::assert(str_contains($stylesheet, '.nav-link { min-width: 148px;'), 'Navigation tabs must use the compact rounded treatment.');
        self::assert(str_contains($stylesheet, '.work-summary-row { display: inline-flex;'), 'Competition counters must use one centered summary row.');
        self::assert(str_contains($stylesheet, '.header-actions { justify-self: end;'), 'User controls and help must stay together on the right.');
        $security = self::render('settings-security', ['overview' => ['failed_logins'=>1, 'blocked_logins'=>2, 'csrf_rejections'=>3, 'expired_sessions'=>4, 'recent'=>[]], 'blocks' => [], 'base' => '/robo']);
        self::assert(str_contains($security, 'Stav ochrany za posledních 24 hodin') && str_contains($security, 'Odmítnuté CSRF požadavky'), 'Security dashboard must display current audit metrics.');
        $competitionSettings = self::render('settings-competition', ['activeCompetition' => ['id'=>1,'competition_year'=>2027,'competition_date'=>'2027-01-01','locale'=>'en','team_count'=>8], 'competitions' => [['id'=>1,'competition_year'=>2027,'competition_date'=>'2027-01-01','locale'=>'en','team_count'=>8,'is_active'=>1], ['id'=>2,'competition_year'=>2026,'competition_date'=>'2026-01-01','locale'=>'cs','team_count'=>8,'is_active'=>0]], 'base'=>'/robo']);
        self::assert(str_contains($competitionSettings, '/system/competition/delete') && str_contains($competitionSettings, 'Aktivní ročník nelze smazat.'), 'Only historical competitions must expose deletion controls.');
        $auditLog = self::render('settings-log', ['search'=>'', 'audit'=>['rows'=>[['id'=>1, 'created_at'=>'2026-11-12 12:00:00', 'username'=>'admin', 'user_id'=>1, 'competition_id'=>1, 'event_type'=>'system.test', 'message'=>'Test', 'context_json'=>'{}', 'ip_address'=>'127.0.0.1']], 'total'=>1, 'page'=>1, 'per_page'=>100], 'base'=>'/robo']);
        self::assert(str_contains($auditLog, '/system/log/delete') && str_contains($auditLog, 'SMAZAT LOGY'), 'Audit log must expose individual and explicitly confirmed bulk deletion.');

        $standings = self::render('standings', ['rows' => [[
            'team_id' => 3, 'number' => 6, 'school' => 'Škola', 'members' => 'Jméno', 'checkpoint_1' => 6, 'furthest_checkpoint' => 'checkpoint_1', 'best_time' => 1000, 'did_not_stop' => 0, 'technical_penalty' => 0, 'total_time' => 1000, 'rank' => 1,
        ]]]);
        self::assert(str_contains($standings, 'class="display-team">6</td>'), 'Standings team numbers must use the same size treatment as rank.');
        self::assert(str_contains($standings, 'class="progress-mark">6x</td>'), 'Standings checkpoint counts must include the x suffix.');
        self::assert(str_contains($standings, 'class="form-actions standings-actions"'), 'Standings export actions must keep spacing above the table.');
        self::assert(!str_contains($standings, 'class="best-time">1000</td>'), 'Standings total time must not use the green highlight.');
        $standingsEnglish = self::render('standings', ['rows' => [], 'labels' => ['team' => 'Team', 'checkpoints' => 'Checkpoints', 'best_time' => 'Best time (ms)', 'did_not_stop' => 'Did not stop', 'technical' => 'Technical', 'total' => 'Total (ms)', 'rank' => 'Rank', 'finish' => 'Finish', 'teams_contestants' => 'Teams and contestants', 'school' => 'School', 'contestants' => 'Contestants']]);
        self::assert(str_contains($standingsEnglish, '>Checkpoints</th>') && str_contains($standingsEnglish, '>Teams and contestants</h2>'), 'Standings must render its English catalog labels.');

        $public = self::render('public-display', ['locale' => 'en', 'year' => '2026', 'rows' => [], 'labels' => ['title' => 'ROBO {year} — Live standings', 'rank' => 'Rank', 'team' => 'Team', 'school' => 'School', 'contestants' => 'Contestants', 'best_time' => 'Best time']]);
        self::assert(str_contains($public, 'ROBO 2026 — Live standings') && str_contains($public, '>Best time</th>'), 'Public standings must render its English catalog labels.');

        $login = self::render('login-v045', ['locale' => 'en', 'year' => '2026', 'labels' => ['title' => 'ROBO — Sign in', 'heading' => 'Sign in to the system', 'username' => 'Username', 'password' => 'Password', 'show_password' => 'Show password', 'submit' => 'Sign in', 'help_aria' => 'Sign-in help', 'help_title' => 'Sign in', 'help' => 'Enter your assigned username and password.', 'close' => 'Close'], 'error' => null]);
        self::assert(str_contains($login, '<small>2026 EN</small>') && str_contains($login, '>Sign in to the system</h2>') && str_contains($login, 'Enter your assigned username and password.'), 'Login screen must render English labels, help and the EN locale marker.');

        $teams = self::render('teams', ['teams' => [[
            'id'=>1,'team_number'=>1,'school_name'=>'Škola','contestant_names'=>'Jan, Eva',
        ]], 'base'=>'/robo']);
        self::assert(str_contains($teams, '<td class="team-number-static">1</td>'), 'Existing team numbers must be read-only.');
        self::assert(!str_contains($teams, 'name="team_number" type="number"'), 'Team numbers must not be editable number inputs.');
        self::assert(str_contains($teams, '/teams-save-v044.php'), 'Team changes must use the versioned endpoint.');
        self::assert(str_contains($teams, '/teams-import-v044.php'), 'Team import must use the versioned endpoint.');

        $admin = self::render('admin', ['settingsLinks'=>[
            ['href'=>'/robo/system/competition','label'=>'Ročník soutěže a jazyk'],
        ], 'blocks'=>[], 'backups'=>[], 'base'=>'/robo']);
        self::assert(str_contains($admin, 'class="settings-link"'), 'System administration links must use button styling.');
        self::assert(!str_contains($admin, 'Nastavení —'), 'System administration labels must omit the repeated settings prefix.');
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
