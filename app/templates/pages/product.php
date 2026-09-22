<?php
use Nm\I18n; use Nm\Media; use Nm\Util; use Nm\View;
$p = $product;
/* Client update (2026-09-21): the temperature line under the gallery is no longer
   rendered. temp_min/temp_max/temp_note remain in the DB + dashboard. */
?>
<div class="container pd-top"><?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?></div>
<section class="section section-first pd-layout">
  <div class="container pd-grid">
    <div class="pd-gallery">
      <div class="pd-main" data-carousel>
        <div class="hero-track pd-track">
          <div class="pd-slide"><?= $p['card_media_id'] ? Media::img((int) $p['card_media_id'], $p['pname'], 'hero', '100vw') : '' ?></div>
        </div>
      </div>
    </div>
    <div class="pd-info">
      <?php if ($cat): ?><a class="chip chip-amber" href="/<?= $lang ?>/categories/<?= View::e($cat['slug']) ?>/"><?= View::e($cat['name']) ?></a><?php endif; ?>
      <h1><?= View::e($p['pname']) ?></h1>
      <p class="pd-sku muted"><?= View::e($p['sku'] ?? '') ?><?= $p['source_index'] ? ' · #' . str_pad((string) $p['source_index'], 2, '0', STR_PAD_LEFT) : '' ?></p>
      <p class="pd-desc"><?= View::e($p['description']) ?></p>
      <?= View::render('ui/spec-rows', ['p' => $p]) ?>
      <div class="pd-actions">
        <a class="btn btn-primary btn-lg" href="/<?= $lang ?>/contact/?product=<?= rawurlencode($p['pname']) ?>" data-event="click_quote"><?= I18n::t('cta.quote.for', ['p' => $p['pname']]) ?></a>
        <a class="btn btn-whatsapp btn-lg" href="<?= Util::waLink(I18n::t('wa.product', ['p' => $p['pname']])) ?>" target="_blank" rel="noopener" data-event="click_whatsapp"><?= I18n::t('contact.whatsapp') ?></a>
      </div>
    </div>
  </div>
</section>
<?php if ($related): ?>
<section class="section section-tint">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => I18n::t('product.related')]) ?>
    <div class="rail" data-carousel>
      <div class="hero-track rail-track">
        <?php foreach ($related as $r): ?>
          <div class="rail-item"><?= View::render('ui/card-product', ['p' => $r, 'lang' => $lang, 'cat' => null]) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
<?= View::render('ui/cta-band', ['lang' => $lang]) ?>