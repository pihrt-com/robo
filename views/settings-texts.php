<?php ob_start(); ?>
<section class="panel">
  <h2 class="panel-title">Texty stránek v češtině a angličtině</h2>
  <p>Každý řetězec náleží aktivnímu ročníku. Kliknutím na název stránky otevřete náhled místa, kde se používá.</p>
  <?php if (!empty($message)): ?><p class="notice notice-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (!empty($error)): ?><p class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <form class="filter-row" method="get" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/system/texts">
    <label for="page-filter">Zobrazit stránku</label>
    <select id="page-filter" name="page" onchange="this.form.submit()">
      <option value="all"<?= $selectedPage === 'all' ? ' selected' : '' ?>>Všechny stránky</option>
      <?php foreach ($pages as $page): ?><option value="<?= htmlspecialchars($page['key'], ENT_QUOTES, 'UTF-8') ?>"<?= $selectedPage === $page['key'] ? ' selected' : '' ?>><?= htmlspecialchars($page['label'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
    </select>
    <button class="button button-neutral button-small" type="submit">Filtrovat</button>
  </form>
  <div class="data-table-wrap">
    <table class="data-table text-catalog-table">
      <thead><tr><th>Stránka a náhled</th><th>Klíč</th><th>Čeština</th><th>Angličtina</th><th>Uložit</th></tr></thead>
      <tbody>
      <?php foreach ($textRows as $row): ?>
        <tr><form method="post" action="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>/system/texts/save"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="page_key" value="<?= htmlspecialchars($row['page_key'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="text_key" value="<?= htmlspecialchars($row['text_key'], ENT_QUOTES, 'UTF-8') ?>"><td><a class="page-preview-link" href="<?= htmlspecialchars($base . $row['preview_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($row['page_label'], ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">↗</span><span class="sr-only"> (otevře náhled v nové kartě)</span></a></td><td><code><?= htmlspecialchars($row['text_key'], ENT_QUOTES, 'UTF-8') ?></code></td><td><input name="text_cs" value="<?= htmlspecialchars($row['text_cs'], ENT_QUOTES, 'UTF-8') ?>" maxlength="255" required></td><td><input name="text_en" value="<?= htmlspecialchars($row['text_en'], ENT_QUOTES, 'UTF-8') ?>" maxlength="255" required></td><td><button class="button button-save button-small" type="submit">Uložit</button></td></form></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php $content=ob_get_clean(); $help='<p>Tabulka obsahuje veškeré pojmenované řetězce rozhraní. Výběrem stránky zmenšíte seznam; odkaz u každého řádku otevře příslušnou obrazovku v nové kartě.</p>'; require __DIR__.'/app-shell-v042.php';
