<?php
declare(strict_types=1);

namespace Robo\Domain;

final class PageTextCatalog
{
    /** @return list<array{page_key:string,page_label:string,preview_path:string,text_key:string,text_cs:string,text_en:string}> */
    public static function entries(): array
    {
        return [
            ...self::page('navigation', 'Hlavní navigace', '/technical', [
                ['technical', 'Technická', 'Technical'], ['track_1', 'Dráha 1', 'Track 1'], ['track_2', 'Dráha 2', 'Track 2'], ['standings', 'Pořadí', 'Standings'], ['system', 'Systém', 'System'], ['logout', 'Odhlásit se', 'Log out'], ['logout_confirm', 'Opravdu se chcete odhlásit?', 'Do you really want to log out?'], ['help', 'Nápověda', 'Help'], ['close', 'Zavřít', 'Close'], ['main_navigation', 'Hlavní navigace', 'Main navigation'], ['help_page', 'Nápověda k této stránce', 'Help for this page'], ['help_unavailable', 'Pro tuto stránku zatím není nápověda připravena.', 'Help is not available for this page yet.'], ['teams_count', 'Týmů: {count}', 'Teams: {count}'], ['pending_count', 'Zbývá dokončit: {count}', 'Remaining: {count}'], ['save_waiting', 'Čeká na změnu', 'Waiting for changes'], ['save_saving', 'Ukládám…', 'Saving…'], ['save_saved', 'Uloženo {time}', 'Saved {time}'], ['save_error', 'Uložení se nezdařilo', 'Saving failed'], ['navigation_error', 'Navigaci se nepodařilo načíst. Obnovte stránku pomocí Ctrl+F5.', 'Navigation could not be loaded. Refresh the page with Ctrl+F5.'],
            ]),
            ...self::page('technical', 'Technická přejímka', '/technical', [
                ['title', 'Technická kontrola vozítek', 'Technical inspection'], ['team', 'Tým', 'Team'], ['condition_1', 'Rozměry 20x20x20 (cm)', 'Dimensions 20x20x20 (cm)'], ['condition_2', 'Držák kelímku 1 (mm)', 'Cup holder 1 (mm)'], ['condition_3', 'Prostor kelímku 2,5 (cm)', 'Cup space 2.5 (cm)'], ['note', 'Poznámka', 'Note'], ['note_saved', 'Poznámka ✓', 'Note ✓'], ['note_team', 'Poznámka týmu {team}', 'Team {team} note'], ['penalty', 'Penalizace (sec)', 'Penalty (sec)'], ['passed', 'Prošel', 'Passed'], ['yes', 'ANO', 'YES'], ['no', 'NE', 'NO'], ['unfinished', 'Nezadáno', 'Not entered'], ['state_pending', 'Zatím nevyplněno', 'Not entered yet'], ['state_pass', 'Podmínka splněna', 'Condition met'], ['state_fail', 'Podmínka nesplněna', 'Condition not met'], ['save', 'Uložit', 'Save'], ['team_details', 'Podrobnosti týmu', 'Team details'], ['team_title', 'Tým {team}', 'Team {team}'], ['school', 'Škola', 'School'], ['contestants', 'Soutěžící', 'Contestants'], ['help', 'Vyberte každou ze tří kontrolních podmínek. První stisk změní stav na zelený, druhý na červený a třetí jej vrátí do bílého nevyplněného stavu. Pokud je alespoň jedna podmínka červená, systém dopočítá technickou penalizaci.', 'Select each of the three inspection conditions. The first press changes the state to green, the second to red, and the third returns it to the unfilled white state. If at least one condition is red, the system calculates the technical penalty.'],
            ]),
            ...self::page('track', 'Nejlepší jízda — dráhy', '/track-1', [
                ['title', 'Nejlepší jízda', 'Best drive'], ['track_1_title', 'Nejlepší jízda — Dráha 1', 'Best drive — Track 1'], ['track_2_title', 'Nejlepší jízda — Dráha 2', 'Best drive — Track 2'], ['team', 'Tým', 'Team'], ['checkpoints', 'Průjezdní body', 'Checkpoints'], ['checkpoint_1', 'Bod 1', 'Point 1'], ['checkpoint_2', 'Bod 2', 'Point 2'], ['checkpoint_3', 'Bod 3', 'Point 3'], ['checkpoint_4', 'Bod 4', 'Point 4'], ['checkpoint_5', 'Bod 5', 'Point 5'], ['checkpoint_6', 'Bod 6', 'Point 6'], ['finish', 'Cíl', 'Finish'], ['laps', 'Časy kompletních kol (ms)', 'Complete lap times (ms)'], ['time_1', 'Čas 1', 'Time 1'], ['time_2', 'Čas 2', 'Time 2'], ['time_3', 'Čas 3', 'Time 3'], ['time_4', 'Čas 4', 'Time 4'], ['time_5', 'Čas 5', 'Time 5'], ['did_not_stop', 'Nezastavení', 'Did not stop'], ['confirmed', 'Potvrzeno', 'Confirmed'], ['edit_value', 'Upravit hodnotu', 'Edit value'], ['pass_count', 'Počet průjezdů', 'Number of passes'], ['time_milliseconds', 'Čas v milisekundách', 'Time in milliseconds'], ['save', 'Uložit', 'Save'], ['team_details', 'Podrobnosti týmu', 'Team details'], ['team_title', 'Tým {team}', 'Team {team}'], ['school', 'Škola', 'School'], ['contestants', 'Soutěžící', 'Contestants'], ['result_confirmed', 'Výsledek potvrzen', 'Result confirmed'], ['result_invalid', 'Potvrzeno bez platného času', 'Confirmed without a valid time'], ['result_pending', 'Rozpracovaný výsledek', 'Result in progress'], ['help', 'Krátký stisk průjezdního bodu hodnotu zvýší. Podržením otevřete přesnou úpravu. Zadejte nejvýše pět časů kompletních kol. Nejnižší platný čas se zvýrazní zeleně. Potvrzení bez platného času se označí červeným křížkem.', 'A short press increases a checkpoint value. Hold it to open precise editing. Enter at most five complete lap times. The lowest valid time is highlighted in green. Confirmation without a valid time is marked with a red cross.'],
            ]),
            ...self::page('standings', 'Celkové pořadí', '/standings', [
                ['title', 'Celkové pořadí', 'Overall ranking'], ['export_xls', 'Export XLS', 'Export XLS'], ['export_playoff', 'XLS pro Playoff', 'XLS for Playoff'], ['print_pdf', 'Tisk do PDF', 'Print to PDF'], ['team', 'Tým', 'Team'], ['checkpoints', 'Průjezdní body', 'Checkpoints'], ['finish', 'Cíl', 'Finish'], ['best_time', 'Nejlepší čas (ms)', 'Best time (ms)'], ['did_not_stop', 'Nezastavení', 'Did not stop'], ['technical', 'Technická', 'Technical'], ['total', 'Celkem (ms)', 'Total (ms)'], ['rank', 'Pořadí', 'Rank'], ['teams_contestants', 'Týmy a soutěžící', 'Teams and contestants'], ['school', 'Škola', 'School'], ['contestants', 'Soutěžící', 'Contestants'], ['help', 'Pořadí se obnovuje automaticky. Týmy s platným časem se řadí podle výsledného času včetně penalizací. Týmy bez času se řadí podle nejvzdálenějšího dosaženého průjezdního bodu a počtu jeho dosažení.', 'The ranking refreshes automatically. Teams with a valid time are ordered by final time including penalties. Teams without a time are ordered by their furthest checkpoint and the number of times they reached it.'],
            ]),
            ...self::page('public', 'Veřejné pořadí', '/public', [
                ['title', 'ROBO {year} — Průběžné pořadí', 'ROBO {year} — Live standings'], ['rank', 'Pořadí', 'Rank'], ['team', 'Tým', 'Team'], ['school', 'Škola', 'School'], ['contestants', 'Soutěžící', 'Contestants'], ['best_time', 'Nejlepší čas', 'Best time'],
            ]),
            ...self::page('login', 'Přihlášení', '/login', [
                ['title', 'ROBO — Přihlášení', 'ROBO — Sign in'], ['heading', 'Přihlášení do systému', 'Sign in to the system'], ['username', 'Uživatel', 'Username'], ['password', 'Heslo', 'Password'], ['show_password', 'Zobrazit heslo', 'Show password'], ['hide_password', 'Skrýt heslo', 'Hide password'], ['submit', 'Přihlásit se', 'Sign in'], ['help_aria', 'Nápověda k přihlášení', 'Sign-in help'], ['help_title', 'Přihlášení', 'Sign in'], ['help', 'Zadejte přidělené uživatelské jméno a heslo. Při potížích kontaktujte správce soutěže.', 'Enter your assigned username and password. If you have trouble signing in, contact the competition administrator.'], ['close', 'Zavřít', 'Close'], ['error', 'Přihlášení se nezdařilo. Zkontrolujte zadané údaje nebo kontaktujte správce.', 'Sign-in failed. Check your credentials or contact the competition administrator.'],
            ]),
            ...self::page('system', 'Správa a nastavení systému', '/system', [
                ['title', 'Správa systému', 'System administration'], ['competition', 'Ročník soutěže a jazyk', 'Competition year and language'], ['texts', 'Texty stránek (CZ/EN)', 'Page texts (CZ/EN)'], ['penalties', 'Penalizace soutěže', 'Competition penalties'], ['teams', 'Jména soutěžících a škol', 'Contestants and schools'], ['users', 'Uživatelé a role', 'Users and roles'], ['log', 'Záznamy systému', 'System log'], ['blocks', 'Aktuální blokace přístupů', 'Current access blocks'], ['unblock_all', 'Odblokovat vše', 'Unblock all'], ['updates', 'Aktualizace systému z GitHub Releases', 'System updates from GitHub Releases'], ['check_version', 'Ověřit verzi', 'Check version'], ['update_system', 'Aktualizovat systém', 'Update system'], ['backup', 'Záloha dat databáze a systému', 'Database and system backup'],
            ]),
        ];
    }

    /** @return array{page_key:string,page_label:string,preview_path:string,text_key:string,text_cs:string,text_en:string}|null */
    public static function find(string $pageKey, string $textKey): ?array
    {
        foreach (self::entries() as $entry) {
            if ($entry['page_key'] === $pageKey && $entry['text_key'] === $textKey) {
                return $entry;
            }
        }

        return null;
    }

    /** @return list<array{key:string,label:string,preview_path:string}> */
    public static function pages(): array
    {
        $pages = [];
        foreach (self::entries() as $entry) {
            $pages[$entry['page_key']] = ['key' => $entry['page_key'], 'label' => $entry['page_label'], 'preview_path' => $entry['preview_path']];
        }

        return array_values($pages);
    }

    /** @param list<array{0:string,1:string,2:string}> $texts @return list<array{page_key:string,page_label:string,preview_path:string,text_key:string,text_cs:string,text_en:string}> */
    private static function page(string $pageKey, string $pageLabel, string $previewPath, array $texts): array
    {
        return array_map(static fn (array $text): array => ['page_key' => $pageKey, 'page_label' => $pageLabel, 'preview_path' => $previewPath, 'text_key' => $text[0], 'text_cs' => $text[1], 'text_en' => $text[2]], $texts);
    }
}
