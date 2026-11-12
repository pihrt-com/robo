<?php ob_start(); ?>
<section class="panel">
  <h2 class="panel-title">Náhled importu týmů</h2>
  <p class="status status-warning">Po potvrzení bude současný seznam týmů aktivního ročníku nahrazen <?= count($rows) ?> řádky z importu.</p>
  <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Tým</th><th>Škola</th><th>Soutěžící</th></tr></thead><tbody><?php foreach($rows as $row): ?><tr><td><?= (int)$row['team_number'] ?></td><td><?= htmlspecialchars($row['school_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['contestant_names'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div>
  <form class="form-actions" method="post" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/system/teams/import-confirm"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><button class="button button-danger" type="submit">Potvrdit náhradu seznamu</button><a class="button button-neutral" href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/system/teams">Zrušit</a></form>
</section>
<?php $content=ob_get_clean();$help='<p>Pečlivě zkontrolujte týmy před potvrzením. Potvrzení nahradí seznam týmů pro aktivní ročník; záznam jízd a technické kontroly svázané s odstraněnými týmy budou odstraněny databázovými vazbami.</p>';require __DIR__.'/app-shell-v047.php';
