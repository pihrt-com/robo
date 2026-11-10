# Changelog

## 0.3.1 - 2026-11-10

### Fixed

- `GitHub/robo`: aktualizátor po úspěšném nasazení obnoví PHP OPcache, takže se ihned načtou změněné šablony a řídicí soubory.
- `GitHub/robo`: přibyla chráněná stránka `cache-refresh.php` pro jednorázové obnovení PHP OPcache na hostingu, kde po starší aktualizaci zůstala v paměti šablona záhlaví.

## 0.3.0 - 2026-11-10

### Added

- `GitHub/robo`: nastavení textů obsahuje úplný katalog navigace, technické přejímky, drah, pořadí a správy systému v češtině i angličtině; filtruje podle stránky a každá položka obsahuje odkaz na její náhled.
- `GitHub/robo`: README obsahuje pouze účel aplikace a její činnost; přibyly odznaky pro vydání, stažení a poslední změnu.

### Fixed

- `GitHub/robo`: navigace správce se zobrazí také tehdy, když serverová role obsahuje oprávnění `system.view`, ale nemá doslovný kód `administrator`.
- `GitHub/robo`: aktualizátor po dokončení nebo chybě smaže vlastní pracovní adresář, před novým během odstraní zbytky starých pracovních adresářů a nikdy znovu nevloží odstraněný soubor `install.php`.

## 0.2.4 - 2026-11-10

### Fixed

- `GitHub/robo`: stránka Systém vždy zobrazí úplnou administrátorskou navigaci i při neúplném předání role do šablony.
- `GitHub/robo`: záhlaví systému a pořadí nezobrazuje pracovní počítadla týmů, rozpracovaných úkonů ani stav ukládání; tyto údaje zůstávají pouze u technické přejímky a drah.

## 0.2.3 - 2026-11-10

### Fixed

- `GitHub/robo`: po dokončení aktualizace se auditní událost zapisuje se správným ID aktivního ročníku a pojmenovanými argumenty, takže nemůže dojít k záměně typu události a ID ročníku.

## 0.2.2 - 2026-11-10

### Fixed

- `GitHub/robo`: aktualizátor používá správný podpis cURL callbacku pro PHP 8, takže stažení aktualizačního souboru neskončí chybou typu argumentu a bezpečným návratem souborů.

## 0.2.1 - 2026-11-10

### Fixed

- `GitHub/robo`: navigace se řídí oprávněním role; technická přejímka vidí pouze vlastní stránku a přímý zakázaný odkaz již nezobrazuje technickou JSON odpověď.
- `GitHub/robo`: záhlaví počítá týmy a rozpracované kontroly nebo jízdy z uložených dat, průběžně ukazuje čekání, ukládání a potvrzený čas zápisu.
- `GitHub/robo`: každá změna technické kontroly nebo jízdy vytváří auditní záznam s původní a novou hodnotou.
- `GitHub/robo`: celkové pořadí nebarví prázdný výsledný čas zeleně a tým bez výsledku nezobrazuje pořadí `0`.
- `GitHub/robo`: administrace udržuje vlastní rozložení i po zobrazení informační hlášky a pracovní plocha využívá větší šířku.

## 0.2.0 - 2026-11-10

### Added

- `GitHub/robo`: funkční správa ročníků, lokalizovaných textů, penalizací, týmů, uživatelů, rolí, importu XLSX a auditních záznamů.
- `GitHub/robo`: bezpečný aktualizátor z veřejných GitHub Releases s ověřením HTTPS, SHA-256, obsahu ZIPu, verze a zachováním `config/local.php`.
- `GitHub/robo`: lokální integrační test MariaDB pro instalaci, vytvoření správce, přihlášení a zápis nastavení.

### Fixed

- `GitHub/robo`: informační ikona má samostatný prostor vedle nadpisu; administrace a tabulky využívají širší pracovní plochu.
- `GitHub/robo`: průběžné výsledky se po změně dat přepíšou bez obnovy stránky a týmy bez výsledku nemají přidělené pořadí.
- `GitHub/robo`: záhlaví počítá skutečný počet týmů uložených v aktivním ročníku.

## 0.1.1 - 2026-11-10

### Fixed

- `GitHub/robo`: instalátor před vytvořením trvalé konfigurace ověří, že založený správce je aktivní, má roli správce a jeho zadané heslo lze ověřit.

## 0.1.0 - 2026-11-10

### Added

- `GitHub/robo`: první instalovatelná verze systému ROBO s databázovým schématem pro ročníky, týmy, uživatele, technickou přejímku, jízdy, audit, hodnocení a playoff.
- `GitHub/robo`: jednorázová instalace přes `install.php`, bezpečné přihlášení, CSRF ochrana, ochrana proti opakovaným neúspěšným pokusům z IP adresy a role správce, technická přejímka, dráha 1, dráha 2, pořadí, veřejné výsledky a administrace.
- `GitHub/robo`: XLSX export kompletních výsledků a XLSX export kompatibilní s dnešním importem programu Playoff.
- `GitHub/robo`: automatizované kontraktní testy backendu, PHP linter a kontrola syntaxe JavaScriptu.
