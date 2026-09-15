<?php use Nm\I18n; use Nm\View; ?>
<div class="page-head">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="eyebrow"><?= I18n::t('services.eyebrow') ?></p>
    <h1><?= I18n::t('services.title') ?></h1>
    <p class="lead"><?= I18n::t('services.lead') ?></p>
  </div>
</div>
<section class="section">
  <div class="container">
    <div class="service-grid">
      <?php foreach ($services as $s): ?><?= View::render('ui/card-service', ['s' => $s, 'lang' => $lang]) ?><?php endforeach; ?>
    </div>
  </div>
</section>
<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
