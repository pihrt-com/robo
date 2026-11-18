<?php
$createdAt = null;
if (!empty($apiStatus['created_at'])) {
  try {
    $createdAt = (new DateTimeImmutable((string) $apiStatus['created_at']))->setTimezone(new DateTimeZone('Europe/Prague'))->format('j. n. Y H:i');
  } catch (Throwable) {
    $createdAt = (string) $apiStatus['created_at'];
  }
}
ob_start();
?>
<?php if (!empty($message)): ?><p class="notice notice-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<section class="panel api-settings">
  <h2 class="panel-title">JSON API pro program Playoff</h2>
  <p>Endpoint <code><?= htmlspecialchars($apiEndpoint ?? ($base . '/api/v1'), ENT_QUOTES, 'UTF-8') ?></code> vrací aktivní ročník a seznam týmů s pořadím, nejlepším časem, školou a soutěžícími.</p>
  <?php if (!empty($newToken)): ?>
  <div class="api-key-card" role="status">
    <h3>API klíč</h3>
    <p class="field-help">Klíč je šifrovaně uložený a může jej znovu zobrazit pouze přihlášený správce.</p>
    <label class="form-field">API klíč<input class="api-key-output" type="text" readonly value="<?= htmlspecialchars($newToken, ENT_QUOTES, 'UTF-8') ?>" data-api-key></label>
    <label class="form-field">Přímá adresa pro stažení JSON<input class="api-key-output" type="text" readonly value="<?= htmlspecialchars(($apiEndpoint ?? ($base . '/api/v1')) . '?key=' . rawurlencode($newToken), ENT_QUOTES, 'UTF-8') ?>" data-api-url></label>
  </div>
  <?php endif; ?>
  <dl class="api-status">
    <div><dt>Stav</dt><dd><?= !empty($apiStatus['enabled']) ? 'API klíč je aktivní' : 'API klíč zatím není vytvořen' ?></dd></div>
    <?php if ($createdAt !== null): ?><div><dt>Vytvořen</dt><dd><?= htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
  </dl>
  <form class="action-card" method="post" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/system/api/time-mode">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <h3>Nejlepší čas v API</h3>
    <label class="choice"><input type="radio" name="time_mode" value="including_penalties"<?= ($apiStatus['time_mode'] ?? 'driving') === 'including_penalties' ? ' checked' : '' ?>> Nejlepší čas včetně penalizací</label>
    <label class="choice"><input type="radio" name="time_mode" value="driving"<?= ($apiStatus['time_mode'] ?? 'driving') === 'driving' ? ' checked' : '' ?>> Jen čas jízdy bez penalizací</label>
    <p class="field-help">Volba určuje hodnoty <code>best_time_ms</code> a <code>best_time</code> ve staženém JSON. Pořadí týmů vždy vychází z výsledného času včetně penalizací.</p>
    <button class="button button-save" type="submit">Uložit nastavení API</button>
  </form>
  <p class="field-help">Program může klíč poslat také bezpečněji v hlavičce <code>Authorization: Bearer VÁŠ_KLÍČ</code>. Vytvoření nového klíče okamžitě zneplatní předchozí klíč.</p>
  <div class="form-actions">
    <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/system/api/generate">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <button class="button button-save" type="submit"<?= !empty($apiStatus['enabled']) ? ' data-confirm="Vytvořit nový klíč a okamžitě zneplatnit současný?"' : '' ?>><?= !empty($apiStatus['enabled']) ? 'Vytvořit nový klíč' : 'Vytvořit API klíč' ?></button>
    </form>
    <?php if (!empty($apiStatus['enabled'])): ?>
    <?php if (!empty($apiStatus['can_reveal'])): ?>
    <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/system/api/reveal">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <button class="button button-neutral" type="submit">Zobrazit klíč</button>
    </form>
    <?php else: ?>
    <p class="field-help">Tento klíč vznikl ve starší verzi a nelze jej znovu zobrazit. Vytvořte nový klíč.</p>
    <?php endif; ?>
    <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/system/api/revoke">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <button class="button button-danger" type="submit" data-confirm="Opravdu zrušit API klíč? Program Playoff už data nestáhne.">Zrušit API klíč</button>
    </form>
    <?php endif; ?>
  </div>
</section>
<?php $content=ob_get_clean(); $help='<p>API je určeno pro automatické stažení dat aktivního ročníku do programu Playoff. Správce může aktivní klíč kdykoli zobrazit a zkopírovat. Pokud se prozradí, vytvořte nový; původní se ihned zneplatní.</p>'; require __DIR__.'/app-shell-v047.php';
