<?php
use Nm\Content; use Nm\I18n; use Nm\View;
$t = static fn(string $k) => I18n::t($k);
$B = static fn(string $z) => $blocks[$z] ?? [];
$payload = static function (array $bs, int $i = 0): array { return isset($bs[$i]) ? Content::blockPayload($bs[$i]) : []; };
?>
<div class="page-head">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="eyebrow"><?= View::e($payload($B('about:intro'))['eyebrow'] ?? $t('about.eyebrow')) ?></p>
    <h1><?= View::e($B('about:intro')[0]['title'] ?? $t('about.title')) ?></h1>
    <p class="lead"><?= View::e($payload($B('about:intro'))['text'] ?? '') ?></p>
  </div>
</div>

<section class="section">
  <div class="container container-narrow prose">
    <?php foreach ($payload($B('about:intro'))['paragraphs'] ?? [] as $p): ?><p><?= View::e($p) ?></p><?php endforeach; ?>
  </div>
</section>

<section class="section section-tint">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:purpose')[0]['title'] ?? $t('about.purpose')]) ?>
    <div class="pillar-grid">
      <?php foreach ($payload($B('about:purpose'))['items'] ?? [] as $it): ?>
        <div class="pillar"><span class="q-icon" aria-hidden="true"><?= \Nm\Icons::svg($it['icon'] ?? 'leaf') ?></span><strong><?= View::e($it['title'] ?? '') ?></strong><p><?= View::e($it['text'] ?? '') ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:history')[0]['title'] ?? $t('about.history')]) ?>
    <ol class="timeline">
      <?php foreach ($payload($B('about:history'))['items'] ?? [] as $i => $it): ?>
        <li class="tl-item"><span class="tl-num tabular"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span><div><h3><?= View::e($it['title'] ?? '') ?></h3><p><?= View::e($it['text'] ?? '') ?></p></div></li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="section section-tint">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:work')[0]['title'] ?? $t('about.work')]) ?>
    <div class="work-acc" data-accordion>
      <?php foreach ($payload($B('about:work'))['rows'] ?? [] as $i => $r): ?>
        <details class="faq-item"<?= $i === 0 ? ' open' : '' ?>>
          <summary><span><?= View::e($r['division'] ?? '') ?></span><svg class="i plus" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg></summary>
          <div class="faq-a"><p><strong><?= $t('about.range') ?>:</strong> <?= View::e($r['range'] ?? '') ?></p><p><strong><?= $t('about.focus') ?>:</strong> <?= View::e($r['focus'] ?? '') ?></p></div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:quality')[0]['title'] ?? $t('about.quality')]) ?>
    <div class="quality-grid">
      <?php foreach ($payload($B('about:quality'))['items'] ?? [] as $it): ?>
        <div class="q-item"><span class="q-icon" aria-hidden="true"><?= \Nm\Icons::svg($it['icon'] ?? 'shield') ?></span><strong><?= View::e($it['title'] ?? '') ?></strong><p><?= View::e($it['text'] ?? '') ?></p></div>
      <?php endforeach; ?>
    </div>
    <ul class="check-list light">
      <?php foreach ($payload($B('about:quality'))['commitments'] ?? [] as $c): ?>
        <li><span class="ck" aria-hidden="true"><?= \Nm\Icons::svg('check') ?></span><p><?= View::e($c) ?></p></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:export')[0]['title'] ?? $t('about.export')]) ?>
    <div class="export-grid">
      <?php foreach ($payload($B('about:export'))['groups'] ?? [] as $g): ?>
        <div class="export-card"><h3><span aria-hidden="true"><?= \Nm\Icons::svg($g['icon'] ?? 'box') ?></span><?= View::e($g['title'] ?? '') ?></h3>
          <ul><?php foreach ($g['items'] ?? [] as $i): ?><li><?= View::e($i) ?></li><?php endforeach; ?></ul></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:progress')[0]['title'] ?? $t('about.progress')]) ?>
    <div class="container-narrow prose"><?php foreach ($payload($B('about:progress'))['paragraphs'] ?? [] as $p): ?><p><?= View::e($p) ?></p><?php endforeach; ?></div>
    <ul class="check-list">
      <?php foreach ($payload($B('about:progress'))['items'] ?? [] as $it): ?>
        <li><span class="ck" aria-hidden="true"><?= \Nm\Icons::svg('check') ?></span><div><strong><?= View::e($it['title'] ?? '') ?></strong><p><?= View::e($it['text'] ?? '') ?></p></div></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:leadership')[0]['title'] ?? $t('about.leadership')]) ?>
    <div class="people-grid">
      <?php foreach ($payload($B('about:leadership'))['people'] ?? [] as $p): ?>
        <div class="person">
          <span class="person-avatar" aria-hidden="true"><?= View::e(mb_substr($p['name'] ?? '?', 0, 1)) ?></span>
          <strong><?= View::e($p['name'] ?? '') ?></strong>
          <span class="person-role"><?= View::e($p['role'] ?? '') ?></span>
          <p><?= View::e($p['mandate'] ?? '') ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <blockquote class="vision">
      <h2><?= View::e($B('about:vision')[0]['title'] ?? $t('about.vision')) ?></h2>
      <p><?= View::e($payload($B('about:vision'))['text'] ?? '') ?></p>
      <ul><?php foreach ($payload($B('about:vision'))['items'] ?? [] as $i): ?><li><?= View::e($i) ?></li><?php endforeach; ?></ul>
    </blockquote>
  </div>
</section>

<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
