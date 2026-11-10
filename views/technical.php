<?php
/** @var array<int, array<string, mixed>> $teams */
$teams = $teams ?? [];
ob_start();
?>
<section class="panel" data-page="technical" data-endpoint="/robo/api/technical">
  <h2 class="sr-only">Technická kontrola vozítek</h2>
  <div class="data-table-wrap"><table class="data-table"><thead><tr><th>Tým</th><th><?= htmlspecialchars($labels['condition_1'] ?? 'Rozměry 20x20x20 (cm)', ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($labels['condition_2'] ?? 'Držák kelímku 1 (mm)', ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($labels['condition_3'] ?? 'Prostor kelímku 2,5 (cm)', ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($labels['note'] ?? 'Poznámka', ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($labels['penalty'] ?? 'Penalizace (sec)', ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars($labels['passed'] ?? 'Prošel', ENT_QUOTES, 'UTF-8') ?></th></tr></thead>
  <tbody><?php foreach ($teams as $team): $id = (int) $team['id']; ?><tr data-team-id="<?= $id ?>"><td><button class="team-button" type="button" data-help-modal="team-<?= $id ?>"><?= (int) $team['number'] ?></button><dialog id="team-<?= $id ?>"><div class="modal-header"><h2>Tým <?= (int) $team['number'] ?></h2><button class="modal-close" type="button" data-close-modal>×</button></div><div class="modal-body team-details"><p>Škola: <?= htmlspecialchars((string) ($team['school'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p><p>Soutěžící: <?= htmlspecialchars((string) ($team['members'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p></div></dialog></td>
    <?php foreach (['condition_1','condition_2','condition_3'] as $field): $state = $team[$field] ?? 'pending'; ?><td><button type="button" class="tri-state" data-state="<?= htmlspecialchars((string) $state, ENT_QUOTES, 'UTF-8') ?>" data-field="<?= $field ?>" aria-label="Zatím nevyplněno"></button></td><?php endforeach; ?>
    <td><button class="button button-small note-button<?= trim((string)($team['note'] ?? '')) !== '' ? ' has-note' : '' ?>" type="button" data-note-button data-help-modal="note-<?= $id ?>"><?= trim((string)($team['note'] ?? '')) !== '' ? 'Poznámka ✓' : 'Poznámka' ?></button><dialog id="note-<?= $id ?>"><div class="modal-header"><h2>Poznámka týmu <?= (int) $team['number'] ?></h2><button class="modal-close" type="button" data-close-modal>×</button></div><div class="modal-body"><label class="form-field">Poznámka<textarea data-field="note" maxlength="1000"><?= htmlspecialchars((string) ($team['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></label></div><div class="modal-footer"><button class="button button-save" type="button" data-save-note="<?= $id ?>">Uložit</button></div></dialog></td>
    <td data-computed="technical-penalty"><?= (int) ($team['technical_penalty'] ?? 0) ?: '—' ?></td><td data-computed="technical-result" class="<?= !empty($team['passed']) ? 'result-passed' : (!empty($team['complete']) ? 'result-failed' : '') ?>"><?= !empty($team['passed']) ? 'ANO' : (!empty($team['complete']) ? 'NE' : '?') ?></td></tr><?php endforeach; ?></tbody></table></div>
</section>
<?php
$content = ob_get_clean();
$help = '<p>Vyberte každou ze tří kontrolních podmínek. První stisk změní stav na zelený, druhý na červený a třetí jej vrátí do bílého nevyplněného stavu. Pokud je alespoň jedna podmínka červená, systém dopočítá technickou penalizaci.</p>';
require __DIR__.'/layout.php';
