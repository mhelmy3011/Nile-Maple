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
<?php foreach ($jobs as $j): ?><tr><td><?= View::e($j['scope']) ?></td><td><?= View::e($j['status']) ?></td><td><?= (int) $j['pages'] ?></td><td><?= (int) $j['ms'] ?></td><td><?= View::e($j['finished_at']) ?></td></tr><?php endforeach; ?>
</tbody></table>
<h2>Top events</h2>
<table class="adm-table"><tbody>
<?php foreach ($events as $e): ?><tr><td><?= View::e($e['name']) ?></td><td><?= (int) $e['c'] ?></td></tr><?php endforeach; ?>
</tbody></table>
