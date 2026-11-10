# ROBO

ROBO je webový systém pro organizaci robotické soutěže. Obslouží instalaci, přihlášení podle rolí, technickou přejímku, zápis kvalifikačních jízd na dvou drahách, průběžné pořadí, veřejné zobrazení výsledků a exporty pro následné zpracování v Playoff.

## První instalace

1. Nakopírujte obsah repozitáře do adresáře webu, například `https://pihrt.com/robo/`.
2. Zajistěte PHP 8.2 nebo novější s rozšířeními `pdo_mysql`, `zip` a `curl`, databázi MySQL nebo MariaDB a zapnuté HTTPS.
3. Nechte webový server přepisovat požadavky na `index.php`; pravidla pro Apache jsou v `.htaccess`.
4. Otevřete `https://pihrt.com/robo/install.php`, vyplňte správce a přístup k prázdné databázi.
5. Po úspěšném přihlášení ověřte, že vytvořený soubor `config/local.php` není veřejně přístupný, a na serveru odstraňte `install.php`.

Instalátor vytvoří databázovou strukturu, výchozí role, první ročník, šestnáct týmů, dvě výchozí penalizace a účet správce. Soubor `config/local.php` brání dalšímu spuštění instalace.

Před uložením `config/local.php` instalátor ověří, že právě vytvořený účet správce je aktivní, má správnou roli a přijme zadané heslo. Pokud tato kontrola selže, instalace se neoznačí jako dokončená.

## Funkce verze 0.2.0

- bezpečné přihlášení s hashovanými hesly, CSRF tokeny, bezpečnými relacemi, rolovým oprávněním a omezením pokusů z IP adresy;
- technická přejímka se třemi měnitelnými stavy: bílá = nezadáno, zelená = prošlo, červená = neprošlo;
- dvě dráhy rozdělené podle lichých a sudých týmů, šest průjezdních bodů, cíl, pět časů kol, nezastavení a potvrzení;
- průběžné pořadí podle výsledného času včetně penalizací; tým bez platného času se řadí podle nejvzdálenějšího průjezdního bodu a počtu dosažení;
- veřejná obrazovka `/robo/public` s obnovou dat na pozadí;
- export úplných výsledků do XLSX se dvěma listy a samostatný XLSX kompatibilní s aktuálním importem Playoff.

Správce má funkční obrazovky pro aktuální ročník a historii, lokalizované texty stránek, nastavení penalizací, uživatele a role, správu týmů, kontrolovaný import seznamu týmů z XLSX, auditní záznamy a odblokování přístupů. Změny těchto obrazovek se zapisují do databáze a do auditu.

Každá role vidí jen stránky, které může obsluhovat. Technická přejímka vidí pouze svoji tabulku, dráha 1 pouze liché týmy na první dráze a dráha 2 pouze sudé týmy na druhé dráze. Přímý pokus o otevření zakázané stránky vrátí běžnou stránku s odepřeným přístupem; rozhraní API vrací odpověď JSON pouze pro JavaScriptové požadavky.

Záhlaví ukazuje skutečný počet týmů a počet rozpracovaných kontrol nebo jízd. Po načtení čeká na změnu, během zápisu ukazuje ukládání a po potvrzení serverem čas posledního uložení. Technické změny a změny jízd zapisují auditní událost s týmem, polem a původní i novou hodnotou.

Aktualizace v nabídce Systém ověří nejnovější veřejné vydání `pihrt-com/robo`. Instalace aktualizace přijme jen asset `robo-update.zip` se souborem `VERSION`, jehož verze odpovídá tagu, a se SHA-256 zveřejněným GitHubem. Aktualizátor kontroluje HTTPS, podpis otisku souboru, cesty v ZIPu a před zápisem uchová obnovení změněných souborů. `config/local.php` se nikdy nenahrazuje.

Export pro Playoff obsahuje list `Pojmenování týmů` a přesně sloupce `ID týmu`, `Škola / adresa školy`, `Jméno soutěžícího` a `Nejlepší čas jízdy`. Stávající Playoff tento seznam načte, ale automatické nasazení 24 týmů do pavouka bude vyžadovat navazující změnu v projektu Playoff.

## Ověření

Před každou změnou spusťte PHP linter nad všemi soubory, `php tests/run.php` a kontrolu syntaxe `node --check public/assets/app.js`. Ověření instalace, přihlášení a změn administrace proveďte proti skutečné MariaDB/MySQL s ovladačem `pdo_mysql`; test v tomto repozitáři se zapíná proměnnými `ROBO_INTEGRATION_DSN`, `ROBO_INTEGRATION_USER` a `ROBO_INTEGRATION_PASSWORD`.

## Budoucí moduly

Databázová struktura již obsahuje kategorie a kritéria pro nejlepší design, nejlepší prezentaci a playoff. Jejich pracovní obrazovky a závěrečné soutěžní pořadí budou rozšířením nad kvalifikačním systémem této první verze.

