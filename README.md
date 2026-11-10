# ROBO

ROBO je webový systém pro organizaci robotické soutěže. Obslouží instalaci, přihlášení podle rolí, technickou přejímku, zápis kvalifikačních jízd na dvou drahách, průběžné pořadí, veřejné zobrazení výsledků a exporty pro následné zpracování v Playoff.

## První instalace

1. Nakopírujte obsah repozitáře do adresáře webu, například `https://pihrt.com/robo/`.
2. Zajistěte PHP 8.2 nebo novější s rozšířeními `pdo_mysql` a `zip`, databázi MySQL nebo MariaDB a zapnuté HTTPS.
3. Nechte webový server přepisovat požadavky na `index.php`; pravidla pro Apache jsou v `.htaccess`.
4. Otevřete `https://pihrt.com/robo/install.php`, vyplňte správce a přístup k prázdné databázi.
5. Po úspěšném přihlášení ověřte, že vytvořený soubor `config/local.php` není veřejně přístupný, a na serveru odstraňte `install.php`.

Instalátor vytvoří databázovou strukturu, výchozí role, první ročník, šestnáct týmů, dvě výchozí penalizace a účet správce. Soubor `config/local.php` brání dalšímu spuštění instalace.

## Funkce první verze

- bezpečné přihlášení s hashovanými hesly, CSRF tokeny, bezpečnými relacemi, rolovým oprávněním a omezením pokusů z IP adresy;
- technická přejímka se třemi měnitelnými stavy: bílá = nezadáno, zelená = prošlo, červená = neprošlo;
- dvě dráhy rozdělené podle lichých a sudých týmů, šest průjezdních bodů, cíl, pět časů kol, nezastavení a potvrzení;
- průběžné pořadí podle výsledného času včetně penalizací; tým bez platného času se řadí podle nejvzdálenějšího průjezdního bodu a počtu dosažení;
- veřejná obrazovka `/robo/public` s obnovou dat na pozadí;
- export úplných výsledků do XLSX se dvěma listy a samostatný XLSX kompatibilní s aktuálním importem Playoff.

Export pro Playoff obsahuje list `Pojmenování týmů` a přesně sloupce `ID týmu`, `Škola / adresa školy`, `Jméno soutěžícího` a `Nejlepší čas jízdy`. Stávající Playoff tento seznam načte, ale automatické nasazení 24 týmů do pavouka bude vyžadovat navazující změnu v projektu Playoff.

## Ověření

Před každou změnou spusťte PHP linter nad všemi soubory a `php tests/run.php`. Skutečný instalační test vyžaduje MariaDB/MySQL s ovladačem `pdo_mysql`; tento ovladač nebyl v lokálním testovacím PHP dostupný, proto je potřeba instalaci ověřit na cílovém hostingu před soutěží.

## Budoucí moduly

Databázová struktura již obsahuje kategorie a kritéria pro nejlepší design, nejlepší prezentaci a playoff. Jejich pracovní obrazovky a závěrečné soutěžní pořadí budou rozšířením nad kvalifikačním systémem této první verze.

