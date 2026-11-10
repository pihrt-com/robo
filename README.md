# ROBO

[![Vydání](https://img.shields.io/github/v/release/pihrt-com/robo?display_name=tag&sort=semver&label=verze&logo=github)](https://github.com/pihrt-com/robo/releases) [![Stažení](https://img.shields.io/github/downloads/pihrt-com/robo/total?label=stažení&logo=github)](https://github.com/pihrt-com/robo/releases) [![Poslední změna](https://img.shields.io/github/last-commit/pihrt-com/robo/main?label=aktualizováno&logo=git)](https://github.com/pihrt-com/robo/commits/main)

ROBO je webová aplikace pro řízení robotické soutěže od instalace až po zveřejnění výsledků.

## Co aplikace dělá

- Přihlašuje uživatele podle rolí a zpřístupňuje jim jen jejich pracovní obrazovky.
- Zapisuje technickou přejímku vozítek, kvalifikační jízdy na dvou drahách, průjezdní body, časy a penalizace.
- Průběžně počítá a veřejně zobrazuje pořadí týmů.
- Spravuje soutěžní ročníky, týmy, školy, soutěžící, uživatele, role, texty rozhraní a auditní záznamy.
- Exportuje výsledky do XLSX a připravuje data pro aplikaci Playoff.

## Poslední změny

- 2026-11-10 · složka `C:\GitHub\robo`: administrátorský navigační pruh má regresní kontrolu a CSS/JavaScript používají verzi odvozenou od změny souborů; nejlepší čas se upravuje běžným kliknutím, průjezdní body se zobrazují s `x`, změny z `?` se propisují bez obnovení stránky, nejlepší čas má bílý text a podrobnosti týmu mají větší netučné písmo; datum s časem je zarovnáno nad značku ROBO.
- 2026-11-10 · složka `C:\GitHub\robo`: technická kontrola nyní okamžitě přepočítává výsledek a penalizaci, řádky s poznámkou jsou označené, jízdy a celkové hodnocení správně používají penalizaci za nezastavení, správa uživatelů obsahuje validaci, přepínače, mazání a změnu vlastního hesla, log zobrazuje podrobnosti a podporuje hledání a systém umí vytvořit i obnovit úplnou zašifrovanou zálohu.

