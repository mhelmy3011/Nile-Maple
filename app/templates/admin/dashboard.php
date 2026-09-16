<?php use Nm\View; ?>
<div class="kpi-grid">
  <div class="kpi"><span class="kpi-v"><?= (int) $enq_new ?></span><span>new enquiries</span></div>
  <div class="kpi"><span class="kpi-v"><?= (int) $enq_30 ?></span><span>enquiries / 30 d</span></div>
  <div class="kpi <?= $enq_spam > 20 ? 'warn' : '' ?>"><span class="kpi-v"><?= (int) $enq_spam ?></span><span>spam-held (review)</span></div>
  <div class="kpi"><span class="kpi-v"><?= (int) $published ?>/<?= (int) $products ?></span><span>products published</span></div>
  <?php foreach ($comp as $k => $c): ?>
    <div class="kpi <?= $c['incomplete'] ? 'warn' : '' ?>"><span class="kpi-v"><?= (int) $c['total'] - (int) $c['incomplete'] ?>/<?= (int) $c['total'] ?></span><span><?= View::e($k) ?> complete (3 langs)</span></div>
  <?php endforeach; ?>
</div>
<h2>Recent build jobs</h2>
<table class="adm-table"><thead><tr><th>scope</th><th>status</th><th>pages</th><th>ms</th><th>when</th></tr></thead><tbody>
<?php foreach ($jobs as $j): ?><tr><td data-label="Scope"><?= View::e($j['scope']) ?></td><td data-label="Status"><?= View::e($j['status']) ?></td><td data-label="Pages"><?= (int) $j['pages'] ?></td><td data-label="Ms"><?= (int) $j['ms'] ?></td><td data-label="When"><?= View::e($j['finished_at']) ?></td></tr><?php endforeach; ?>
<?php if (!$jobs): ?><tr><td colspan="5" class="adm-empty">No builds yet.</td></tr><?php endif; ?>
</tbody></table>
<h2>Top events</h2>
<table class="adm-table"><tbody>
<?php foreach ($events as $e): ?><tr><td data-label="Event"><?= View::e($e['name']) ?></td><td data-label="Count"><?= (int) $e['c'] ?></td></tr><?php endforeach; ?>
<?php if (!$events): ?><tr><td class="adm-empty">No events recorded yet.</td></tr><?php endif; ?>
</tbody></table>
