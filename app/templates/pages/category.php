<?php
use Nm\I18n; use Nm\Media; use Nm\View;
$jsBundles = ['base', 'listing'];
?>
<div class="page-head page-head-compact">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <h1><?= View::e($cat['name']) ?></h1>
    <p class="lead"><?= View::e($cat['headline']) ?></p>
  </div>
</div>
<div class="filterbar" data-filterbar>
  <div class="container filter-row">
    <div class="filter-search">
      <svg class="filter-search-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="m16.5 16.5 4 4" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
      <label class="sr-only" for="q"><?= I18n::t('listing.search') ?></label>
      <input id="q" type="search" inputmode="search" autocomplete="off" placeholder="<?= I18n::t('listing.search') ?>" value="<?= View::e($q) ?>" data-filter-q>
    </div>
    <div class="filter-sort">
      <label class="sr-only" for="sort"><?= I18n::t('listing.sort') ?></label>
      <select id="sort" data-filter-sort>
        <option value="order"><?= I18n::t('listing.sort.order') ?></option>
        <option value="az"><?= I18n::t('listing.sort.az') ?></option>
      </select>
    </div>
    <span class="filter-count tabular" role="status" aria-live="polite"><?= I18n::t('listing.count', ['n' => $total]) ?></span>
  </div>
</div>
<section class="section section-first">
  <div class="container">
    <div id="grid-wrap" data-cat="<?= View::e($cat['slug']) ?>" data-page="<?= (int) $pageNo ?>" data-pages="<?= (int) $pages ?>">
      <?= View::render('ui/product-grid', ['products' => $products, 'lang' => $lang, 'cat' => null]) ?>
    </div>
    <?php if ($pageNo < $pages): ?>
    <div class="loadmore-wrap">
      <button class="btn btn-ghost" type="button" data-loadmore><?= I18n::t('listing.more') ?></button>
      <noscript><a class="btn btn-ghost" href="page-<?= (int) $pageNo + 1 ?>/"><?= I18n::t('listing.more') ?></a></noscript>
    </div>
    <?php endif; ?>
  </div>
</section>
<section class="section section-tint">
  <div class="container container-narrow prose">
    <h2><?= View::e($cat['name']) ?> — <?= I18n::t('listing.about') ?></h2>
    <p><?= View::e($cat['summary']) ?></p>
  </div>
</section>
<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
