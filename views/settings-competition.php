<?php ob_start(); ?>
<section class="panel">
  <h2 class="panel-title">Aktuální ročník soutěže</h2>
  <form class="two-column-form" method="post" action="/robo/system/competition/save">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int) $activeCompetition['id'] ?>">
    <label class="form-field">Rok<input name="year" type="number" min="2020" max="2100" value="<?= (int) $activeCompetition['competition_year'] ?>" required></label>
    <label class="form-field">Datum soutěže<input name="date" type="date" value="<?= htmlspecialchars($activeCompetition['competition_date'], ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label class="form-field">Jazyk<select name="locale"><option value="cs"<?= $activeCompetition['locale'] === 'cs' ? ' selected' : '' ?>>Česky</option><option value="en"<?= $activeCompetition['locale'] === 'en' ? ' selected' : '' ?>>English</option></select></label>
    <label class="form-field">Počet týmů<input name="team_count" type="number" min="1" max="200" value="<?= (int) $activeCompetition['team_count'] ?>" required></label>
    <div class="form-actions"><button class="button button-save" type="submit">Uložit ročník</button></div>
  </form>
</section>
<section class="panel">
  <h2 class="panel-title">Vytvořit nový ročník</h2>
  <form class="two-column-form" method="post" action="/robo/system/competition/create">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <label class="form-field">Rok<input name="year" type="number" min="2020" max="2100" value="<?= (int) date('Y') + 1 ?>" required></label>
    <label class="form-field">Datum soutěže<input name="date" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required></label>
    <label class="form-field">Jazyk<select name="locale"><option value="cs">Česky</option><option value="en">English</option></select></label>
    <label class="form-field">Počet týmů<input name="team_count" type="number" min="1" max="200" value="16" required></label>
    <div class="form-actions"><button class="button button-save" type="submit">Vytvořit ročník</button></div>
  </form>
</section>
<section class="panel"><h2 class="panel-title">Historie soutěží</h2><div class="data-table-wrap"><table class="data-table"><thead><tr><th>Rok</th><th>Datum</th><th>Jazyk</th><th>Týmů</th><th>Aktivní</th><th>Akce</th></tr></thead><tbody><?php foreach ($competitions as $row): ?><tr><td><?= (int) $row['competition_year'] ?></td><td><?= htmlspecialchars($row['competition_date'], ENT_QUOTES, 'UTF-8') ?></td><td><?= strtoupper(htmlspecialchars($row['locale'], ENT_QUOTES, 'UTF-8')) ?></td><td><?= (int) $row['team_count'] ?></td><td><?= (int) $row['is_active'] === 1 ? 'ANO' : '' ?></td><td class="row-actions"><?php if ((int) $row['is_active'] !== 1): ?><form method="post" action="/robo/system/competition/activate"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="button button-small" type="submit">Aktivovat</button></form><form method="post" action="/robo/system/competition/delete"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="button button-danger button-small" type="submit" data-confirm="Smazat ročník <?= (int) $row['competition_year'] ?> včetně všech jeho týmů, jízd a hodnocení?">Smazat</button></form><?php else: ?><span class="muted">Aktivní ročník nelze smazat.</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php $content=ob_get_clean(); $help='<p>Změna počtu týmů doplní nové prázdné týmy. Snížení počtu není možné, pokud by se odstranily týmy se zapsanou jízdou. Aktivace jiného ročníku přepne všechny pracovní stránky na jeho data. Historický ročník lze smazat včetně jeho soutěžních dat, aktivní ročník je chráněný.</p>'; require __DIR__.'/app-shell-v047.php';
