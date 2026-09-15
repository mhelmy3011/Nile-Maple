<?php use Nm\I18n; use Nm\View; ?>
<div class="page-head">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="eyebrow"><?= I18n::t('faq.eyebrow') ?></p>
    <h1><?= I18n::t('faq.title') ?></h1>
    <p class="lead"><?= I18n::t('faq.lead') ?></p>
  </div>
</div>
<section class="section">
  <div class="container container-narrow">
    <label class="sr-only" for="faq-q"><?= I18n::t('faq.search') ?></label>
    <input id="faq-q" type="search" class="faq-search" placeholder="<?= I18n::t('faq.search') ?>" data-faq-q>
    <?php
    $groups = [];
    foreach ($faqs as $f) $groups[$f['group_code']][] = $f;
    foreach ($groups as $g => $items): ?>
      <h2 class="faq-group"><?= I18n::t('faq.group.' . $g) ?></h2>
      <?= View::render('ui/faq-accordion', ['faqs' => $items, 'lang' => $lang, 'openFirst' => false]) ?>
    <?php endforeach; ?>
    <p class="center-link"><a class="btn btn-primary" href="/<?= $lang ?>/contact/"><?= I18n::t('faq.more') ?></a></p>
  </div>
</section>
