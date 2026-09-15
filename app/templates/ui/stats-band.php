<?php
use Nm\Content; use Nm\View;
/** stats block payload: items:[{value,label}] */
$items = [];
foreach ($stats as $b) { foreach (Content::blockPayload($b)['items'] ?? [] as $it) $items[] = $it; }
?>
<section class="stats-band" aria-label="<?= \Nm\I18n::t('a11y.stats') ?>">
  <div class="container stats-grid">
    <?php foreach ($items as $it): ?>
      <div class="stat">
        <span class="stat-v tabular" data-count="<?= View::e($it['value'] ?? '') ?>"><?= View::e($it['value'] ?? '') ?></span>
        <span class="stat-l"><?= View::e($it['label'] ?? '') ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</section>
