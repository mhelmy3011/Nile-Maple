import fs from 'node:fs';
let home = fs.readFileSync('app/templates/pages/home.php', 'utf-8');
// Fix the hero block array handling and add carousel markup
home = home.replace(
  /<\?php[\s\S]*?<section class="hero">[\s\S]*?<\/section>/,
  `<?php
use Nm\\Content; use Nm\\I18n; use Nm\\Media; use Nm\\View;
$t = static fn(string $k, array $p = []) => I18n::t($k, $p);
?>
<section class="hero" data-carousel aria-roledescription="carousel" aria-label="<?= $t('home.hero.aria') ?>">
  <div class="hero-track" tabindex="0">
    <?php foreach ($hero as $i => $s): $hp = Content::blockPayload($s); ?>
      <?php $mid = !empty($hp['media_id']) ? (int) $hp['media_id'] : (!empty($hp['media']) ? (int) \\Nm\\Db::val('SELECT id FROM media WHERE source_ref=?', [$hp['media']]) : 0); ?>
      <div class="hero-slide" role="group" aria-label="<?= ($i+1) . '/' . max(1, count($hero)) ?>">
        <?= $mid ? Media::img($mid, $hp['alt'] ?? $s['title'] ?? '', 'hero', '100vw') : '' ?>
        <div class="hero-scrim" aria-hidden="true"></div>
        <div class="container hero-copy">
          <p class="eyebrow eyebrow-light"><?= View::e($s['eyebrow'] ?? '') ?></p>
          <h1><?= View::e($s['title'] ?? '') ?></h1>
          <p class="hero-lead"><?= View::e($hp['lead'] ?? '') ?></p>
          <div class="hero-cta">
            <a class="btn btn-primary btn-lg" href="/<?= $lang ?>/categories/"><?= $t('cta.explore') ?></a>
            <a class="btn btn-outline-light btn-lg" href="/<?= $lang ?>/contact/"><?= $t('cta.quote') ?></a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (count($hero) > 1): ?>
  <div class="hero-dots" role="tablist" aria-label="<?= $t('home.hero.slides') ?>"></div>
  <?php endif; ?>
</section>`
);

// Fix why-media to resolve media_id fallback
home = home.replace(
  /<div class="why-media"><\?php \$wm = isset\(\$why\[0\]\) \? Content::blockPayload\(\$why\[0\]\) : \[\];\n      if \(!empty\(\$wm\['media_id'\]\)\) echo Media::img\(\(int\) \$wm\['media_id'\], \(string\) \(\$wm\['alt'\] \?\? ''\)\); \?><\/div>/,
  `<div class="why-media"><?php $wm = isset($why[0]) ? Content::blockPayload($why[0]) : [];
      $wMid = !empty($wm['media_id']) ? (int) $wm['media_id'] : (!empty($wm['media']) ? (int) \\Nm\\Db::val('SELECT id FROM media WHERE source_ref=?', [$wm['media']]) : 0);
      if ($wMid) echo Media::img($wMid, (string) ($wm['alt'] ?? '')); ?></div>`
);

fs.writeFileSync('app/templates/pages/home.php', home);

let pubCtrl = fs.readFileSync('app/PublicController.php', 'utf-8');
pubCtrl = pubCtrl.replace(
  /\$data\['preload'\] = !empty\(\$heroPl\['media_id'\]\) \? Media::url\(\(int\) \$heroPl\['media_id'\], 800, 'webp'\) : null;/,
  `$mid = !empty($heroPl['media_id']) ? (int) $heroPl['media_id'] : (!empty($heroPl['media']) ? (int) \\Nm\\Db::val('SELECT id FROM media WHERE source_ref=?', [$heroPl['media']]) : 0);
                $data['preload'] = $mid ? Media::url($mid, 800, 'webp') : null;`
);
fs.writeFileSync('app/PublicController.php', pubCtrl);

