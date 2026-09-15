<?php
use Nm\Content; use Nm\I18n; use Nm\Markdown; use Nm\View;
$title = I18n::t('zone.' . $zone);
?>
<div class="page-head">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="eyebrow"><?= I18n::t('zone.eyebrow') ?></p>
    <h1><?= View::e($blocks[0]['title'] ?? $title) ?></h1>
    <?php if (!empty($blocks[0]['eyebrow'])): ?><p class="lead"><?= View::e($blocks[0]['eyebrow']) ?></p><?php endif; ?>
  </div>
</div>
<section class="section">
  <div class="container container-narrow">
    <?php foreach ($blocks as $b): $pl = Content::blockPayload($b); ?>
      <?php if ($b['kind'] === 'text'): ?>
        <div class="prose"><?= Markdown::render($pl['text'] ?? '') ?></div>
      <?php elseif ($b['kind'] === 'list'): ?>
        <h2><?= View::e($b['title'] ?? '') ?></h2>
        <ul class="check-list"><?php foreach ($pl['items'] ?? [] as $it): ?>
          <li><span class="ck" aria-hidden="true"><?= \Nm\Icons::svg('check') ?></span><div><strong><?= View::e($it['title'] ?? '') ?></strong><p><?= View::e($it['text'] ?? '') ?></p></div></li>
        <?php endforeach; ?></ul>
      <?php elseif ($b['kind'] === 'table'): ?>
        <h2><?= View::e($b['title'] ?? '') ?></h2>
        <div class="table-wrap"><table>
          <thead><tr><?php foreach ($pl['cols'] ?? [] as $c): ?><th scope="col"><?= View::e($c) ?></th><?php endforeach; ?></tr></thead>
          <tbody><?php foreach ($pl['rows'] ?? [] as $r): ?><tr><?php foreach ($r as $cell): ?><td><?= View::e($cell) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
        </table></div>
        <?php if (!empty($pl['legend'])): ?><p class="muted table-legend"><?= View::e($pl['legend']) ?></p><?php endif; ?>
      <?php elseif ($b['kind'] === 'stat'): ?>
        <?= View::render('ui/stats-band', ['stats' => [$b], 'lang' => $lang]) ?>
      <?php elseif ($b['kind'] === 'markdown'): ?>
        <div class="prose"><?= Markdown::render($pl['body'] ?? '') ?></div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
