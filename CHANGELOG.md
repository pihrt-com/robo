# Changelog

## 0.1.1 - 2026-11-10

### Fixed

- `GitHub/robo`: instalátor před vytvořením trvalé konfigurace ověří, že založený správce je aktivní, má roli správce a jeho zadané heslo lze ověřit.

## 0.1.0 - 2026-11-10

### Added

- `GitHub/robo`: první instalovatelná verze systému ROBO s databázovým schématem pro ročníky, týmy, uživatele, technickou přejímku, jízdy, audit, hodnocení a playoff.
- `GitHub/robo`: jednorázová instalace přes `install.php`, bezpečné přihlášení, CSRF ochrana, ochrana proti opakovaným neúspěšným pokusům z IP adresy a role správce, technická přejímka, dráha 1, dráha 2, pořadí, veřejné výsledky a administrace.
- `GitHub/robo`: XLSX export kompletních výsledků a XLSX export kompatibilní s dnešním importem programu Playoff.
- `GitHub/robo`: automatizované kontraktní testy backendu, PHP linter a kontrola syntaxe JavaScriptu.
