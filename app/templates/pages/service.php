<?php use Nm\I18n; use Nm\Markdown; use Nm\View; ?>
<div class="page-head">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="eyebrow"><?= I18n::t('services.eyebrow') ?></p>
    <h1><?= View::e($service['name']) ?></h1>
    <p class="lead"><?= View::e($service['teaser']) ?></p>
  </div>
</div>
<section class="section">
  <div class="container container-narrow">
    <div class="prose"><?= Markdown::render($service['body']) ?></div>
    <?php $bullets = json_decode($service['bullets'] ?: '[]', true) ?: []; if ($bullets): ?>
    <ul class="check-list">
      <?php foreach ($bullets as $b): ?><li><span class="ck" aria-hidden="true"><?= \Nm\Icons::svg('check') ?></span><p><?= View::e($b) ?></p></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</section>
<?php if ($related): ?>
<section class="section section-tint">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => I18n::t('services.related')]) ?>
    <div class="service-grid">
      <?php foreach (array_slice($related, 0, 3) as $s): ?><?= View::render('ui/card-service', ['s' => $s, 'lang' => $lang]) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
