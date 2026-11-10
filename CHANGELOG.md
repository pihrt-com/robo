# Changelog

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
