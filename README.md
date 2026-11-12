# ROBO

[![Vydání](https://img.shields.io/github/v/release/pihrt-com/robo?display_name=tag&sort=semver&label=verze&logo=github)](https://github.com/pihrt-com/robo/releases) [![Stažení](https://img.shields.io/github/downloads/pihrt-com/robo/total?label=stažení&logo=github)](https://github.com/pihrt-com/robo/releases) [![Poslední změna](https://img.shields.io/github/last-commit/pihrt-com/robo/main?label=aktualizováno&logo=git)](https://github.com/pihrt-com/robo/commits/main)

ROBO je samostatná webová aplikace pro řízení robotické soutěže od přípravy ročníku přes technickou kontrolu a kvalifikační jízdy až po zveřejnění výsledků. Přepnutí jazyka ročníku lokalizuje všechny pracovní a veřejné obrazovky pro uživatele; správa systému zůstává v administrativním jazyce.

## Co aplikace dělá

- Přihlašuje uživatele podle rolí a zpřístupňuje jim jen jejich pracovní obrazovky.
- Zapisuje technickou přejímku vozítek, kvalifikační jízdy na dvou drahách, průjezdní body, časy a penalizace.
- Průběžně počítá a veřejně zobrazuje pořadí týmů.
- Spravuje soutěžní ročníky, týmy, školy, soutěžící, uživatele, role, texty rozhraní a auditní záznamy.
- Exportuje výsledky do XLSX a připravuje data pro aplikaci Playoff.

## Role a pracovní obrazovky

- Správce má přístup k technické kontrole, oběma drahám, celkovému pořadí a správě systému.
- Technická přejímka pracuje jen s kontrolou rozměrů a konstrukčních podmínek vozítek, poznámkami, výsledkem a technickou penalizací.
- Obsluha dráhy 1 a dráhy 2 zapisuje průjezdní body, pět časů kompletních kol, nezastavení a potvrzení výsledku pouze pro týmy své dráhy.
- Divák má přístup k průběžnému pořadí bez možnosti měnit data.
- Záhlaví zobrazuje přihlášené jméno, roli a jen ty položky navigace, které odpovídají oprávnění účtu.

## Průběh soutěže

Technická kontrola používá tři tří stavová pole: nevyplněno, splněno a nesplněno. Jakmile je některá podmínka nesplněna, aplikace použije nastavenou technickou penalizaci. Poznámku lze uložit samostatně a její přítomnost je viditelná v řádku týmu.

Týmy s lichým číslem patří na dráhu 1 a týmy se sudým číslem na dráhu 2. Krátké kliknutí zvýší počet průjezdů, podržení otevře přesnou opravu hodnoty a běžné kliknutí na čas otevře zadání kompletního kola. Po potvrzení jízdy aplikace vybere nejlepší platný čas a připočte penalizaci za nezastavení i technickou kontrolu.

Celkové pořadí řadí týmy s platným časem podle výsledného času včetně penalizací. Týmy bez platného času porovnává podle nejvzdálenějšího dosaženého bodu a počtu jeho průjezdů. Tabulka se průběžně obnovuje bez nutnosti načíst celou stránku znovu.

## Veřejná obrazovka

Veřejné průběžné výsledky jsou dostupné bez přihlášení na adrese `/robo/public`. Obrazovka zobrazuje pořadí, číslo týmu, školu, soutěžící a výsledný čas a načítá aktuální data z `/robo/api/standings` každých 10 sekund. Je určená pro projektor, informační monitor nebo samostatný prohlížeč pro návštěvníky.

## Správa soutěže

Správce nastavuje aktivní ročník, datum soutěže, jazyk a počet týmů. Samostatně upravuje texty českého a anglického rozhraní, hodnoty penalizací, uživatelské účty a role, školy, soutěžící a systémový log. Správcovský rozcestník používá tlačítka pro jednotlivé oblasti bez opakování slova „Nastavení“.

Číslo existujícího týmu nelze ručně změnit. Nový tým dostane na serveru další volné číslo. Import XLSX zobrazí před nahrazením dat náhled, rozpozná běžné české i anglické názvy sloupců a přijme také soubor, ve kterém číslo týmu nebo soutěžící chybí. Chybějící čísla doplní podle pořadí neprázdných řádků, chybějící soutěžící ponechá prázdné a nesouvisející sloupce ignoruje.

## Exporty a Playoff

Celkové pořadí lze exportovat do XLSX pro další zpracování. Export pro Playoff vytváří list `Pojmenování týmů` se sloupci `ID týmu`, `Škola / adresa školy`, `Jméno soutěžícího` a `Nejlepší čas jízdy`; oba soutěžící jsou uloženi v jednom textovém poli a čas používá formát `MM:SS:MMM`.

## Instalace

1. Nahrajte obsah repozitáře do webového adresáře `/robo/`.
2. V prohlížeči otevřete `/robo/install.php`.
3. Zadejte připojení k MySQL nebo MariaDB a vytvořte první účet správce.
4. Po dokončení instalace vznikne místní konfigurace `config/local.php`; její přítomnost zablokuje opakované spuštění instalátoru.

Server musí poskytovat PHP 8.2 nebo novější, PDO s ovladačem MySQL, rozšíření ZIP a databázi MySQL nebo MariaDB. Pro šifrované úplné zálohy musí ZIP podporovat AES-256.

## Aktualizace a zálohy

Správce může ověřit nejnovější veřejné vydání na GitHubu a nainstalovat pouze ověřený soubor `robo-update.zip`. Aktualizátor kontroluje novější verzi, HTTPS adresu, SHA-256, bezpečné cesty v archivu a zachovává `config/local.php`. Při neúspěchu obnoví původní změněné soubory.

Úplná záloha obsahuje databázi, účty, nastavení, místní konfiguraci a spravované soubory aplikace. Archiv je chráněn heslem a šifrováním AES-256. Obnova před nahrazením současných dat ověřuje heslo, strukturu archivu, velikosti a kontrolní součty.

## Bezpečnost a audit

Každý zápis vyžaduje platnou relaci, oprávnění role a CSRF token. Hesla jsou ukládána přes `password_hash`, přihlášení omezuje opakované neúspěšné pokusy z jedné IP adresy a změny soutěžních dat i správy systému se zapisují do auditního logu s uživatelem, časem a podrobnostmi operace.

## Historie verzí

Podrobný přehled vydání a oprav je v souboru [CHANGELOG.md](CHANGELOG.md).

Dokumentace odpovídá verzi 0.4.5 a byla ověřena 2026-11-12 pro pracovní složku `C:\Users\marti\OneDrive\Dokumenty\GitHub\robo`.

