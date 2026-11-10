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
                ['technical', 'Technická', 'Technical'], ['track_1', 'Dráha 1', 'Track 1'], ['track_2', 'Dráha 2', 'Track 2'], ['standings', 'Pořadí', 'Standings'], ['system', 'Systém', 'System'], ['logout', 'Odhlásit se', 'Log out'], ['help', 'Nápověda', 'Help'],
            ]),
            ...self::page('technical', 'Technická přejímka', '/technical', [
                ['title', 'Technická kontrola vozítek', 'Technical inspection'], ['team', 'Tým', 'Team'], ['condition_1', 'Rozměry 20x20x20 (cm)', 'Dimensions 20x20x20 (cm)'], ['condition_2', 'Držák kelímku 1 (mm)', 'Cup holder 1 (mm)'], ['condition_3', 'Prostor kelímku 2,5 (cm)', 'Cup space 2.5 (cm)'], ['note', 'Poznámka', 'Note'], ['penalty', 'Penalizace (sec)', 'Penalty (sec)'], ['passed', 'Prošel', 'Passed'], ['yes', 'ANO', 'YES'], ['no', 'NE', 'NO'], ['unfinished', 'Nezadáno', 'Not entered'], ['save', 'Uložit', 'Save'], ['team_details', 'Podrobnosti týmu', 'Team details'], ['school', 'Škola', 'School'], ['contestants', 'Soutěžící', 'Contestants'],
            ]),
            ...self::page('track', 'Nejlepší jízda — dráhy', '/track-1', [
                ['title', 'Nejlepší jízda', 'Best drive'], ['track_1_title', 'Nejlepší jízda — Dráha 1', 'Best drive — Track 1'], ['track_2_title', 'Nejlepší jízda — Dráha 2', 'Best drive — Track 2'], ['team', 'Tým', 'Team'], ['checkpoints', 'Průjezdní body', 'Checkpoints'], ['checkpoint_1', 'Bod 1', 'Point 1'], ['checkpoint_2', 'Bod 2', 'Point 2'], ['checkpoint_3', 'Bod 3', 'Point 3'], ['checkpoint_4', 'Bod 4', 'Point 4'], ['checkpoint_5', 'Bod 5', 'Point 5'], ['checkpoint_6', 'Bod 6', 'Point 6'], ['finish', 'Cíl', 'Finish'], ['laps', 'Časy kompletních kol (ms)', 'Complete lap times (ms)'], ['time_1', 'Čas 1', 'Time 1'], ['time_2', 'Čas 2', 'Time 2'], ['time_3', 'Čas 3', 'Time 3'], ['time_4', 'Čas 4', 'Time 4'], ['time_5', 'Čas 5', 'Time 5'], ['did_not_stop', 'Nezastavení', 'Did not stop'], ['confirmed', 'Potvrzeno', 'Confirmed'], ['edit_value', 'Upravit hodnotu', 'Edit value'], ['save', 'Uložit', 'Save'], ['team_details', 'Podrobnosti týmu', 'Team details'], ['school', 'Škola', 'School'], ['contestants', 'Soutěžící', 'Contestants'],
            ]),
            ...self::page('standings', 'Celkové pořadí', '/standings', [
                ['title', 'Celkové pořadí', 'Overall ranking'], ['export_xls', 'Export XLS', 'Export XLS'], ['export_playoff', 'XLS pro Playoff', 'XLS for Playoff'], ['print_pdf', 'Tisk do PDF', 'Print to PDF'], ['team', 'Tým', 'Team'], ['checkpoints', 'Průjezdní body', 'Checkpoints'], ['finish', 'Cíl', 'Finish'], ['best_time', 'Nejlepší čas (ms)', 'Best time (ms)'], ['did_not_stop', 'Nezastavení', 'Did not stop'], ['technical', 'Technická', 'Technical'], ['total', 'Celkem (ms)', 'Total (ms)'], ['rank', 'Pořadí', 'Rank'], ['teams_contestants', 'Týmy a soutěžící', 'Teams and contestants'], ['school', 'Škola', 'School'], ['contestants', 'Soutěžící', 'Contestants'],
            ]),
            ...self::page('system', 'Správa a nastavení systému', '/system', [
                ['title', 'Správa a nastavení systému', 'System administration'], ['competition', 'Nastavení — Rok aktuální soutěže a jazyk', 'Settings — Current competition year and language'], ['texts', 'Nastavení — Texty stránek (CZ/EN)', 'Settings — Page texts (CZ/EN)'], ['penalties', 'Nastavení — Penalizace soutěže', 'Settings — Competition penalties'], ['teams', 'Nastavení — Jména soutěžících a škol', 'Settings — Contestants and schools'], ['users', 'Nastavení — Uživatelé a role', 'Settings — Users and roles'], ['log', 'Nastavení — Log', 'Settings — Log'], ['blocks', 'Aktuální blokace přístupů', 'Current access blocks'], ['unblock_all', 'Odblokovat vše', 'Unblock all'], ['updates', 'Aktualizace systému z GitHub Releases', 'System updates from GitHub Releases'], ['check_version', 'Ověřit verzi', 'Check version'], ['update_system', 'Aktualizovat systém', 'Update system'], ['backup', 'Záloha dat databáze a systému', 'Database and system backup'],
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
