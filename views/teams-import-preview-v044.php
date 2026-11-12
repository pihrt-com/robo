<?php ob_start(); ?>
<section class="panel">
  <h2>Náhled importu</h2>
  <p>Bylo rozpoznáno <?= count($rows ?? []) ?> týmů. Zkontrolujte čísla, školy a soutěžící. Prázdný sloupec v původním souboru zůstane prázdný.</p>
  <div class="data-table-wrap"><table class="data-table teams-table"><thead><tr><th class="teams-col-number">Tým</th><th class="teams-col-school">Škola</th><th class="teams-col-contestants">Soutěžící</th></tr></thead><tbody><?php foreach(($rows ?? []) as $row): ?><tr><td class="team-number-static"><?= (int)$row['team_number'] ?></td><td><?= htmlspecialchars($row['school_name'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars($row['contestant_names'],ENT_QUOTES,'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div>
  <form class="form-actions import-confirm-actions" method="post" action="/robo/teams-import-v044.php"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf,ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="action" value="confirm"><button class="button button-danger" type="submit" data-confirm="Import nahradí současný seznam týmů. Opravdu pokračovat?">Nahradit seznam týmů</button><a class="button button-neutral" href="/robo/system/teams">Zrušit</a></form>
</section>
<?php $title='Náhled importu týmů';$content=ob_get_clean();$help='<p>Před potvrzením zkontrolujte rozpoznané údaje. Import nahradí celý seznam týmů aktivního ročníku.</p>';require __DIR__.'/app-shell-v047.php';
