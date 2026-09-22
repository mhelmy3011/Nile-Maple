<?php use Nm\I18n; use Nm\Media; use Nm\View; ?>
<div class="page-head">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="eyebrow"><?= I18n::t('categories.eyebrow') ?></p>
    <h1><?= I18n::t('categories.title') ?></h1>
    <p class="lead"><?= I18n::t('categories.lead') ?></p>
  </div>
</div>
<section class="section">
  <div class="container">
    <div class="cat-grid">
      <?php foreach ($categories as $c): ?>
        <a class="cat-card" href="/<?= $lang ?>/categories/<?= View::e($c['slug']) ?>/">
          <?php $cover = (int) ($c['cover_media_id'] ?: ($c['cover_fallback_id'] ?? 0)); ?>
          <span class="cc-media"><?= $cover ? Media::img($cover, $c['name']) : '' ?></span>
          <span class="cc-body">
            <h2><?= View::e($c['name']) ?></h2>
            <p><?= View::e($c['summary']) ?></p>
            <span class="chip chip-green"><?= (int) $c['cnt'] ?> <?= I18n::t('home.products') ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section section-tint">
  <div class="container container-narrow">
    <?= View::render('ui/section-head', ['title' => I18n::t('categories.az')]) ?>
    <ul class="az-list">
      <?php foreach ($products as $p): ?><li><a href="/<?= $lang ?>/products/<?= View::e($p['slug']) ?>/"><?= View::e($p['name']) ?></a></li><?php endforeach; ?>
    </ul>
  </div>
</section>
<?= View::render('ui/cta-band', ['lang' => $lang]) ?>