# Changelog

## 0.5.2 - 2026-11-12

### Added

- u každé uložené zálohy je nyní tlačítko pro smazání s potvrzením, kontrolou názvu souboru na serveru, oprávněním správce, CSRF ochranou a záznamem do auditu.

### Changed

- poznámky k vydání ROBO jsou celé česky a neobsahují samostatnou část o interním ověřování ani obecné oznámení o aktualizačním archivu.

## 0.5.1 - 2026-11-12

### Added

- ve správě ročníků lze s potvrzením smazat neaktivní historický ročník včetně jeho týmů, jízd a hodnocení; aktivní ročník smazat nelze.
- systémový log nabízí mazání jednotlivých záznamů i hromadné vymazání po opsání textu `SMAZAT LOGY`; hromadné vymazání uloží do nového logu vlastní záznam.

### Changed

- změna mazání ročníku i logu je zapsána ve stejné databázové transakci, takže při chybě nedojde k částečně dokončené operaci.

## 0.5.0 - 2026-11-12

### Added

- nová stránka Bezpečnost a audit ve správě systému zobrazuje bezpečnostní metriky za 24 hodin, aktivní blokace a poslední bezpečnostní události s odkazem na úplný auditní log.

### Changed

- relace používají přísný režim identifikátorů, HTTP-only cookies, osmihodinový limit nečinnosti a pravidelné obnovení identifikátoru relace.
- web posílá ochranné hlavičky proti vložení do cizí stránky, podvrženému typu obsahu, sdílení refereru mimo web a zneužití kamery, mikrofonu nebo polohy.

### Fixed

- odmítnutý CSRF požadavek při přihlášení se nyní zaznamená do auditu a vrátí stejnou bezpečnou přihlašovací chybu místo technické chyby.

## 0.4.9 - 2026-11-12

### Changed

- horní pracovní lišta je rozdělena na datum a čas vlevo, jednotně formátovaný souhrn soutěže uprostřed a uživatele, odhlášení a nápovědu vpravo; informační tlačítko se již nemůže zalomit do samostatného řádku.

## 0.4.8 - 2026-11-12

### Fixed

- pracovní záhlaví se nyní po každém načtení stránky vždy sestaví z JavaScriptu a aktuálních dat necachovaného endpointu; už nezávisí na značce verze uložené ve staré PHP šabloně.

## 0.4.7 - 2026-11-12

### Changed

- pracovní záhlaví znovu zobrazuje počet týmů, zbývající a dokončené záznamy, stav spojení a ukládání; při anglickém ročníku vždy uvádí značku `ROBO <rok> EN`.
- hlavní navigace má nižší zaoblené záložky s mezerami, takže horní panel zabírá méně svislého prostoru.

### Fixed

- nový necachovaný endpoint a šablona záhlaví obnoví správný jazyk, ročník, navigaci a pracovní stav i na hostingu, který nadále spouští dřívější verzi PHP šablony.

## 0.4.6 - 2026-11-12

### Fixed

- přihlašovací obrazovka nyní při anglickém ročníku používá anglické nadpisy, formulář, nápovědu, dialogové tlačítko, chybové hlášení i značku `ROBO … EN`; načítá novou šablonu, aby hosting s uloženou starší PHP verzí nemohl ponechat předchozí češtinu.
- aktivní ročník bez řádků penalizací při otevření správy penalizací automaticky obnoví výchozí technickou penalizaci a penalizaci za nezastavení; nové ročníky je dostanou už při vytvoření.

## 0.4.5 - 2026-11-12

### Fixed

- přepnutí aktivního ročníku do angličtiny nyní přeloží záhlaví tabulek, dialogy, nápovědu, stav ukládání a automaticky obnovované výsledky na technické kontrole, obou drahách, v pořadí i na veřejné obrazovce; všechny tyto texty jsou dostupné v katalogu Texty stránek.

## 0.4.4 - 2026-11-10

### Added

- nové endpointy `teams-save-v044.php` a `teams-import-v044.php` obcházejí staré PHP soubory ponechané v OPcache a zajišťují ukládání týmů, náhled i potvrzení importu.
- XLSX import rozpoznává běžné české a anglické názvy sloupců i jejich varianty; při chybějícím čísle přidělí čísla podle pořadí neprázdných řádků, chybějící soutěžící ponechá prázdné a ostatní sloupce ignoruje.

### Changed

- číslo existujícího týmu již nelze upravit a nový tým získá další volné číslo na serveru bez ohledu na odeslanou hodnotu formuláře.
- pole školy a soutěžících jsou širší, sloupec akcí užší a tlačítko kontroly importu nemá šířku celé stránky.
- správcovský rozcestník používá tlačítka se stručnými názvy bez opakovaného slova „Nastavení“.
- datum a čas mají velikost značky ROBO a tlačítko nápovědy je umístěno vedle odhlášení.

### Fixed

- klient doplní přihlášené jméno a roli i tehdy, když stará šablona vytvořila prázdný prvek místo jeho úplného vynechání.
- dodaný XLSX se sloupci `ID týmu` a `Popis týmu` se načte jako seznam týmů se školou a prázdným polem soutěžících.

## 0.4.3 - 2026-11-10

### Added

- nový endpoint `navigation-v043.php` načítá přihlášeného uživatele, lokalizovanou roli a navigaci filtrovanou podle oprávnění přímo z aktivní relace a databáze.

### Fixed

- pokud hosting stále spouští starou zkompilovanou šablonu bez uživatele a menu, JavaScript chybějící údaje a navigační pruh doplní bez závislosti na této šabloně; při chybě zobrazí viditelné upozornění.
- `.htaccess` nastavuje pro CSS a JavaScript opětovné ověření při načtení stránky, aby prohlížeč nepoužíval starou verzi opravy navigace.

## 0.4.2 - 2026-11-10

### Changed

- Celkové pořadí zobrazuje počty průjezdních bodů s příponou `x`, číslo týmu má stejnou velikost jako pořadí a mezi exportními tlačítky a tabulkou je mezera.
- výsledný čas v Celkovém pořadí používá stejné pozadí a font jako ostatní datové buňky.

### Fixed

- všech 11 přihlášených obrazovek načítá záhlaví z nového souboru `app-shell-v042.php`, takže stará zkompilovaná kopie `layout.php` v PHP OPcache již nemůže skrýt přihlášeného uživatele, jeho roli ani pět tlačítek hlavní navigace.
- automatické obnovení Celkového pořadí zachovává příponu `x`, velikost týmu a nezvýrazněný výsledný čas.

## 0.4.1 - 2026-11-10

### Changed

- průjezdní body zobrazují počet průjezdů s příponou `x`; přesná oprava bodů nadále vyžaduje podržení, ale okno pro zápis času se otevře běžným kliknutím.
- dialog týmu zobrazuje školu a soutěžící větším netučným písmem a datum s časem začíná na stejné svislé ose jako značka ROBO.

### Fixed

- první i další kliknutí na průjezdní bod se po potvrzení serverem ihned zobrazí a klient již změněnou hodnotu nevrací na `?`.
- tlačítko uvnitř zelené buňky nejlepšího času dědí bílou barvu textu.
- šablona kontroluje administrátorský navigační pruh a přidává k CSS a JavaScriptu verzi podle změny souborů, aby prohlížeč po aktualizaci načetl nové rozhraní.

## 0.4.0 - 2026-11-10

### Added

- systém vytváří heslem chráněnou úplnou zálohu databáze, účtů, nastavení, tajné konfigurace a systémových souborů; nahraný archiv lze po dvojím potvrzení obnovit i na čisté instalaci a současný stav se nahradí stavem ze zálohy.
- správa uživatelů obsahuje samostatnou změnu vlastního hesla s ověřením současného hesla, možnost smazat účet a přepínač zapnuto/vypnuto; vlastní účet, posledního aktivního správce a autora bodového hodnocení nelze nebezpečně odstranit.
- záhlaví vedle odhlášení zobrazuje uživatelské jméno a lokalizovaný název role a odhlášení vyžaduje potvrzení.
- log nyní zobrazuje ID uživatele a ročníku i rozbalitelné podrobnosti změn a eviduje odhlášení, zálohy, obnovy a neúspěšné akce správy.

### Changed

- pravidla uživatelského jména a hesla jsou viditelná přímo ve správě účtů; formulář i server uvádějí přesně povolené skupiny znaků a srozumitelně oznamují duplicitu nebo chybnou hodnotu.
- akční tlačítka na všech stránkách používají společné zaoblení, tučný řez, velikost písma a výšku; informace o PHP a systému je přesunuta do nenápadného textu na konci stránky.
- přihlašovací stránka používá pro češtinu stejnou zkratku `CZ` jako ostatní stránky.

### Fixed

- technická kontrola obnoví hodnotu Prošel a technickou penalizaci ihned po kliknutí na bílý, zelený nebo červený stav; uložení poznámky už nevyžaduje obnovení stránky a tlačítko označuje řádek s uloženou poznámkou.
- potvrzený řádek jízdy se zelenou fajfkou nebo červeným křížkem nezobrazuje šedé otazníky v prázdných polích.
- celkové hodnocení počítá penalizaci za nezastavení přímo z aktuálního nastavení soutěže, takže se pět sekund propíše i u staršího záznamu jízdy s příznakem nezastavení.
- hledání v logu používá samostatné SQL parametry a funguje i bez rozšíření `mbstring`, takže dotaz jako `?q=pepik` již nekončí chybou 500; hledání zahrnuje také podrobnosti události.

## 0.3.2 - 2026-11-10

### Fixed

- přibyla nová chráněná oprava `cache-repair.php`, která samostatně zneplatní uložené PHP soubory záhlaví a navigace; pokud ji hosting odmítne, zobrazí konkrétní pokyn k restartu PHP místo návratu na výchozí formulář.

## 0.3.1 - 2026-11-10

### Fixed

- aktualizátor po úspěšném nasazení obnoví PHP OPcache, takže se ihned načtou změněné šablony a řídicí soubory.
- přibyla chráněná stránka `cache-refresh.php` pro jednorázové obnovení PHP OPcache na hostingu, kde po starší aktualizaci zůstala v paměti šablona záhlaví.

## 0.3.0 - 2026-11-10

### Added

- nastavení textů obsahuje úplný katalog navigace, technické přejímky, drah, pořadí a správy systému v češtině i angličtině; filtruje podle stránky a každá položka obsahuje odkaz na její náhled.
- README obsahuje pouze účel aplikace a její činnost; přibyly odznaky pro vydání, stažení a poslední změnu.

### Fixed

- navigace správce se zobrazí také tehdy, když serverová role obsahuje oprávnění `system.view`, ale nemá doslovný kód `administrator`.
- aktualizátor po dokončení nebo chybě smaže vlastní pracovní adresář, před novým během odstraní zbytky starých pracovních adresářů a nikdy znovu nevloží odstraněný soubor `install.php`.

## 0.2.4 - 2026-11-10

### Fixed

- stránka Systém vždy zobrazí úplnou administrátorskou navigaci i při neúplném předání role do šablony.
- záhlaví systému a pořadí nezobrazuje pracovní počítadla týmů, rozpracovaných úkonů ani stav ukládání; tyto údaje zůstávají pouze u technické přejímky a drah.

## 0.2.3 - 2026-11-10

### Fixed

- po dokončení aktualizace se auditní událost zapisuje se správným ID aktivního ročníku a pojmenovanými argumenty, takže nemůže dojít k záměně typu události a ID ročníku.

## 0.2.2 - 2026-11-10

### Fixed

- aktualizátor používá správný podpis cURL callbacku pro PHP 8, takže stažení aktualizačního souboru neskončí chybou typu argumentu a bezpečným návratem souborů.

## 0.2.1 - 2026-11-10

### Fixed

- navigace se řídí oprávněním role; technická přejímka vidí pouze vlastní stránku a přímý zakázaný odkaz již nezobrazuje technickou JSON odpověď.
- záhlaví počítá týmy a rozpracované kontroly nebo jízdy z uložených dat, průběžně ukazuje čekání, ukládání a potvrzený čas zápisu.
- každá změna technické kontroly nebo jízdy vytváří auditní záznam s původní a novou hodnotou.
- celkové pořadí nebarví prázdný výsledný čas zeleně a tým bez výsledku nezobrazuje pořadí `0`.
- administrace udržuje vlastní rozložení i po zobrazení informační hlášky a pracovní plocha využívá větší šířku.

## 0.2.0 - 2026-11-10

### Added

- funkční správa ročníků, lokalizovaných textů, penalizací, týmů, uživatelů, rolí, importu XLSX a auditních záznamů.
- bezpečný aktualizátor z veřejných GitHub Releases s ověřením HTTPS, SHA-256, obsahu ZIPu, verze a zachováním `config/local.php`.
- lokální integrační test MariaDB pro instalaci, vytvoření správce, přihlášení a zápis nastavení.

### Fixed

- informační ikona má samostatný prostor vedle nadpisu; administrace a tabulky využívají širší pracovní plochu.
- průběžné výsledky se po změně dat přepíšou bez obnovy stránky a týmy bez výsledku nemají přidělené pořadí.
- záhlaví počítá skutečný počet týmů uložených v aktivním ročníku.

## 0.1.1 - 2026-11-10

### Fixed

- instalátor před vytvořením trvalé konfigurace ověří, že založený správce je aktivní, má roli správce a jeho zadané heslo lze ověřit.

## 0.1.0 - 2026-11-10

### Added

- první instalovatelná verze systému ROBO s databázovým schématem pro ročníky, týmy, uživatele, technickou přejímku, jízdy, audit, hodnocení a playoff.
- jednorázová instalace přes `install.php`, bezpečné přihlášení, CSRF ochrana, ochrana proti opakovaným neúspěšným pokusům z IP adresy a role správce, technická přejímka, dráha 1, dráha 2, pořadí, veřejné výsledky a administrace.
- XLSX export kompletních výsledků a XLSX export kompatibilní s dnešním importem programu Playoff.
- automatizované kontraktní testy backendu, PHP linter a kontrola syntaxe JavaScriptu.
