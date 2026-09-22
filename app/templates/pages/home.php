<?php
use Nm\Content; use Nm\I18n; use Nm\Media; use Nm\View;
$t = static fn(string $k, array $p = []) => I18n::t($k, $p);
$hero = $hero[0] ?? null;
$hp = $hero ? Content::blockPayload($hero) : [];
/* D-03: a single art-directed hero, no carousel chrome. The previous markup declared a
   2-slide carousel with one slide and empty dots — a screen reader announced navigation
   that did not exist, and the LCP candidate was a text node. One honest hero, preloaded. */
$heroImg = !empty($hp['media_id']) ? Media::img((int) $hp['media_id'], (string) ($hp['alt'] ?? $hero['title'] ?? ''), 'hero', '100vw') : '';
?>
<section class="hero">
  <div class="hero-slide">
    <?= $heroImg ?>
    <div class="hero-scrim" aria-hidden="true"></div>
    <div class="container hero-copy">
      <p class="eyebrow eyebrow-light"><?= View::e($hero['eyebrow'] ?? '') ?></p>
      <h1><?= View::e($hero['title'] ?? '') ?></h1>
      <p class="hero-lead"><?= View::e($hp['lead'] ?? '') ?></p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="/<?= $lang ?>/categories/"><?= $t('cta.explore') ?></a>
        <a class="btn btn-outline-light btn-lg" href="/<?= $lang ?>/contact/"><?= $t('cta.quote') ?></a>
      </div>
    </div>
  </div>
</section>

<?= View::render('ui/stats-band', ['stats' => $stats, 'lang' => $lang]) ?>

<section class="section" id="divisions">
  <div class="container">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.divisions.eyebrow'), 'title' => $t('home.divisions.title'), 'lead' => $t('home.divisions.lead')]) ?>
    <div class="division-list">
      <?php foreach ($categories as $c): ?>
        <a class="division-card" href="/<?= $lang ?>/categories/<?= View::e($c['slug']) ?>/">
          <?php $cover = (int) ($c['cover_media_id'] ?: ($c['cover_fallback_id'] ?? 0)); ?>
          <?php if ($cover): /* D-03: real division photography, never an empty box (2026-09-21: falls back to the category's own product photo) */ ?>
            <span class="dc-media"><?= Media::img($cover, (string) $c['name'], 'lazy', '(min-width:768px) 46vw, 92vw') ?></span>
          <?php else: ?>
            <span class="dc-media dc-media-mono" aria-hidden="true"><?= \Nm\Icons::svg($c['icon_key'] ?? 'citrus') ?></span>
          <?php endif; ?>
          <span class="dc-body">
            <span class="chip chip-green"><?= (int) $c['cnt'] ?> <?= $t('home.products') ?></span>
            <h3><?= View::e($c['name']) ?></h3>
            <p><?= View::e($c['summary']) ?></p>
            <span class="cs-more"><?= $t('cta.view') ?>
              <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint" id="process">
  <div class="container">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.process.eyebrow'), 'title' => $t('home.process.title'), 'lead' => $t('home.process.lead')]) ?>
    <ol class="timeline">
      <?php foreach ($process as $b): $pl = Content::blockPayload($b); ?>
        <?php foreach (($pl['steps'] ?? [['t' => $b['title'] ?? '', 'x' => $pl['text'] ?? '']]) as $i => $st): ?>
        <li class="tl-item">
          <span class="tl-num tabular"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div><h3><?= View::e($st['t'] ?? '') ?></h3><p><?= View::e($st['x'] ?? '') ?></p></div>
        </li>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="section" id="why">
  <div class="container why-grid">
    <div class="why-media"><?php $wm = isset($why[0]) ? Content::blockPayload($why[0]) : [];
      if (!empty($wm['media_id'])) echo Media::img((int) $wm['media_id'], (string) ($wm['alt'] ?? '')); ?></div>
    <div>
      <?= View::render('ui/section-head', ['eyebrow' => $t('home.why.eyebrow'), 'title' => $t('home.why.title'), 'lead' => $t('home.why.lead')]) ?>
      <ul class="check-list">
        <?php foreach ($why as $b): $pl = Content::blockPayload($b); foreach ($pl['items'] ?? [] as $it): ?>
          <li><span class="ck" aria-hidden="true"><?= \Nm\Icons::svg('check') ?></span><div><strong><?= View::e($it['t'] ?? '') ?></strong><p><?= View::e($it['x'] ?? '') ?></p></div></li>
        <?php endforeach; endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<section class="section section-tint" id="featured">
  <div class="container">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.featured.eyebrow'), 'title' => $t('home.featured.title'), 'lead' => $t('home.featured.lead')]) ?>
    <div class="tabs" role="tablist" aria-label="<?= $t('home.featured.aria') ?>" data-tabs>
      <?php foreach ($featuredByCat as $i => $fc): ?>
        <button class="tab<?= $i === 0 ? ' is-active' : '' ?>" role="tab" id="tab-<?= (int) $fc['cat']['id'] ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="panel-<?= (int) $fc['cat']['id'] ?>" type="button"><?= View::e($fc['cat']['name']) ?></button>
      <?php endforeach; ?>
    </div>
    <?php foreach ($featuredByCat as $i => $fc): ?>
      <div class="tab-panel<?= $i === 0 ? ' is-active' : '' ?>" role="tabpanel" id="panel-<?= (int) $fc['cat']['id'] ?>" aria-labelledby="tab-<?= (int) $fc['cat']['id'] ?>"<?= $i === 0 ? '' : ' hidden' ?>>
        <?= View::render('ui/product-grid', ['products' => $fc['items'], 'lang' => $lang, 'cat' => null]) ?>
        <a class="tab-all" href="/<?= $lang ?>/categories/<?= View::e($fc['cat']['slug']) ?>/"><?= $t('home.viewall', ['n' => (int) $fc['cat']['cnt']]) ?>
          <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section-dark" id="quality">
  <div class="container">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.quality.eyebrow'), 'title' => $t('home.quality.title'), 'lead' => $t('home.quality.lead')]) ?>
    <div class="quality-grid">
      <?php foreach ($quality as $b): $pl = Content::blockPayload($b); foreach (array_slice($pl['items'] ?? [], 0, 6) as $it): ?>
        <div class="q-item"><span class="q-icon" aria-hidden="true"><?= \Nm\Icons::svg($it['i'] ?? 'shield') ?></span><strong><?= View::e($it['t'] ?? '') ?></strong><p><?= View::e($it['x'] ?? '') ?></p></div>
      <?php endforeach; endforeach; ?>
    </div>
    <a class="btn btn-outline-light" href="/<?= $lang ?>/quality-handling/"><?= $t('cta.quality') ?></a>
  </div>
</section>

<section class="section section-amber" id="season">
  <div class="container season-inner">
    <span class="season-icon" aria-hidden="true"><?= \Nm\Icons::svg('calendar') ?></span>
    <div><h2><?= $t('home.season.title') ?></h2><p><?= $t('home.season.lead') ?></p></div>
    <a class="btn btn-on-amber" href="/<?= $lang ?>/seasonal-availability/"><?= $t('cta.season') ?></a>
  </div>
</section>

<section class="section" id="insights">
  <div class="container">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.blog.eyebrow'), 'title' => $t('home.blog.title'), 'lead' => $t('home.blog.lead')]) ?>
    <div class="post-list">
      <?php foreach ($posts as $post): ?><?= View::render('ui/card-post', ['post' => $post, 'lang' => $lang]) ?><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint" id="faq-teaser">
  <div class="container container-narrow">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.faq.eyebrow'), 'title' => $t('home.faq.title'), 'align' => 'center']) ?>
    <?= View::render('ui/faq-accordion', ['faqs' => $faqs, 'lang' => $lang]) ?>
    <p class="center-link"><a class="btn btn-ghost" href="/<?= $lang ?>/faq/"><?= $t('cta.allfaq') ?></a></p>
  </div>
</section>

<?= View::render('ui/cta-band', ['lang' => $lang]) ?>