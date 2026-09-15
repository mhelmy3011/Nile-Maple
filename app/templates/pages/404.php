<?php use Nm\I18n; use Nm\View; ?>
<section class="section nf">
  <div class="container container-narrow center">
    <p class="nf-code tabular">404</p>
    <h1><?= I18n::t('seo.404.title') ?></h1>
    <p class="lead"><?= I18n::t('seo.404.lead') ?></p>
    <div class="nf-actions">
      <a class="btn btn-primary" href="/<?= $lang ?>/"><?= I18n::t('nav.home') ?></a>
      <a class="btn btn-ghost" href="/<?= $lang ?>/categories/"><?= I18n::t('nav.categories') ?></a>
      <a class="btn btn-ghost" href="/<?= $lang ?>/contact/"><?= I18n::t('nav.contact') ?></a>
    </div>
    <ul class="az-list nf-cats">
      <?php foreach ($categories as $c): ?><li><a href="/<?= $lang ?>/categories/<?= View::e($c['slug']) ?>/"><?= View::e($c['name']) ?></a></li><?php endforeach; ?>
    </ul>
  </div>
</section>
