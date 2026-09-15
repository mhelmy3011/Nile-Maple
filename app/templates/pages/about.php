<?php
use Nm\Content; use Nm\I18n; use Nm\View;
$t = static fn(string $k) => I18n::t($k);
$B = static fn(string $z) => $blocks[$z] ?? [];
$payload = static function (array $bs, int $i = 0): array { return isset($bs[$i]) ? Content::blockPayload($bs[$i]) : []; };
/* block payload convention (tools/seed_content.php): compact keys — i=icon, t=title, x=text,
   n/r/m = name/role/mandate, work rows = [division, scope, focus]. The template must match the
   data, not the other way round (an earlier draft read long keys and silently rendered empty
   headings — the e2e empty-heading gate exists to catch exactly that). */
$intro = $payload($B('about:intro'));
$introParas = $intro['paragraphs'] ?? [];
?>
<div class="page-head">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="eyebrow"><?= View::e($intro['eyebrow'] ?? $t('about.eyebrow')) ?></p>
    <h1><?= View::e($B('about:intro')[0]['title'] ?? $t('about.title')) ?></h1>
    <p class="lead"><?= View::e($introParas[0] ?? '') ?></p>
  </div>
</div>

<?php if (count($introParas) > 1): ?>
<section class="section">
  <div class="container container-narrow prose">
    <?php foreach (array_slice($introParas, 1) as $p): ?><p><?= View::e($p) ?></p><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="section section-tint">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:purpose')[0]['title'] ?? $t('about.purpose')]) ?>
    <div class="pillar-grid">
      <?php foreach ($payload($B('about:purpose'))['items'] ?? [] as $it): ?>
        <div class="pillar"><span class="q-icon" aria-hidden="true"><?= \Nm\Icons::svg($it['i'] ?? 'leaf') ?></span><strong><?= View::e($it['t'] ?? '') ?></strong><p><?= View::e($it['x'] ?? '') ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:history')[0]['title'] ?? $t('about.history')]) ?>
    <ol class="timeline">
      <?php foreach ($payload($B('about:history'))['items'] ?? [] as $i => $it): ?>
        <li class="tl-item"><span class="tl-num tabular"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span><div><h3><?= View::e($it['t'] ?? '') ?></h3><p><?= View::e($it['x'] ?? '') ?></p></div></li>
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
          <summary><span><?= View::e($r[0] ?? '') ?></span><svg class="i plus" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg></summary>
          <div class="faq-a"><p><strong><?= View::e(($payload($B('about:work'))['cols'] ?? [])[1] ?? 'Scope') ?>:</strong> <?= View::e($r[1] ?? '') ?></p><p><strong><?= View::e(($payload($B('about:work'))['cols'] ?? [])[2] ?? 'Focus') ?>:</strong> <?= View::e($r[2] ?? '') ?></p></div>
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
        <div class="q-item"><span class="q-icon" aria-hidden="true"><?= \Nm\Icons::svg($it['i'] ?? 'shield') ?></span><strong><?= View::e($it['t'] ?? '') ?></strong><p><?= View::e($it['x'] ?? '') ?></p></div>
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
    <div class="pillar-grid export-grid">
      <?php foreach ($payload($B('about:export'))['items'] ?? [] as $it): ?>
        <div class="pillar export-card"><span class="q-icon" aria-hidden="true"><?= \Nm\Icons::svg($it['i'] ?? 'box') ?></span><h3><?= View::e($it['t'] ?? '') ?></h3><p><?= View::e($it['x'] ?? '') ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:progress')[0]['title'] ?? $t('about.progress')]) ?>
    <div class="container-narrow prose"><?php foreach ($payload($B('about:progress'))['paragraphs'] ?? [] as $p): ?><p><?= View::e($p) ?></p><?php endforeach; ?></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => $B('about:leadership')[0]['title'] ?? $t('about.leadership')]) ?>
    <div class="people-grid">
      <?php foreach ($payload($B('about:leadership'))['people'] ?? [] as $p): ?>
        <div class="person">
          <span class="person-avatar" aria-hidden="true"><?= View::e(mb_substr($p['n'] ?? '?', 0, 1)) ?></span>
          <strong><?= View::e($p['n'] ?? '') ?></strong>
          <span class="person-role"><?= View::e($p['r'] ?? '') ?></span>
          <p><?= View::e($p['m'] ?? '') ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <blockquote class="vision">
      <h2><?= View::e($B('about:vision')[0]['title'] ?? $t('about.vision')) ?></h2>
      <?php foreach ($payload($B('about:vision'))['paragraphs'] ?? [] as $p): ?><p><?= View::e($p) ?></p><?php endforeach; ?>
    </blockquote>
  </div>
</section>

<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
