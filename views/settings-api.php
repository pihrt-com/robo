<?php ob_start(); ?>
<?php if (!empty($message)): ?><p class="notice notice-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<section class="panel api-settings">
  <h2 class="panel-title">JSON API pro program Playoff</h2>
  <p>Endpoint <code><?= htmlspecialchars($apiEndpoint ?? ($base . '/api/v1'), ENT_QUOTES, 'UTF-8') ?></code> vrací aktivní ročník a seznam týmů s pořadím, nejlepším časem, školou a soutěžícími.</p>
  <?php if (!empty($newToken)): ?>
  <div class="api-key-card" role="status">
    <h3>Nový klíč — zkopírujte jej nyní</h3>
    <p class="field-help">Klíč se z bezpečnostních důvodů ukládá pouze jako otisk a po opuštění stránky už jej nelze znovu zobrazit.</p>
    <label class="form-field">API klíč<input class="api-key-output" type="text" readonly value="<?= htmlspecialchars($newToken, ENT_QUOTES, 'UTF-8') ?>" data-api-key></label>
    <label class="form-field">Přímá adresa pro stažení JSON<input class="api-key-output" type="text" readonly value="<?= htmlspecialchars(($apiEndpoint ?? ($base . '/api/v1')) . '?key=' . rawurlencode($newToken), ENT_QUOTES, 'UTF-8') ?>" data-api-url></label>
  </div>
  <?php endif; ?>
  <dl class="api-status">
    <div><dt>Stav</dt><dd><?= !empty($apiStatus['enabled']) ? 'API klíč je aktivní' : 'API klíč zatím není vytvořen' ?></dd></div>
    <?php if (!empty($apiStatus['created_at'])): ?><div><dt>Vytvořen</dt><dd><?= htmlspecialchars((string) $apiStatus['created_at'], ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
  </dl>
  <p class="field-help">Program může klíč poslat také bezpečněji v hlavičce <code>Authorization: Bearer VÁŠ_KLÍČ</code>. Vytvoření nového klíče okamžitě zneplatní předchozí klíč.</p>
  <div class="form-actions">
    <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/system/api/generate">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <button class="button button-save" type="submit"<?= !empty($apiStatus['enabled']) ? ' data-confirm="Vytvořit nový klíč a okamžitě zneplatnit současný?"' : '' ?>><?= !empty($apiStatus['enabled']) ? 'Vytvořit nový klíč' : 'Vytvořit API klíč' ?></button>
    </form>
    <?php if (!empty($apiStatus['enabled'])): ?>
    <form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/system/api/revoke">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <button class="button button-danger" type="submit" data-confirm="Opravdu zrušit API klíč? Program Playoff už data nestáhne.">Zrušit API klíč</button>
    </form>
    <?php endif; ?>
  </div>
</section>
<?php $content=ob_get_clean(); $help='<p>API je určeno pro automatické stažení dat aktivního ročníku do programu Playoff. Klíč je tajný stejně jako heslo. Pokud jej ztratíte nebo se prozradí, vytvořte nový; původní se ihned zneplatní.</p>'; require __DIR__.'/app-shell-v047.php';
