<?php
use Nm\Media; use Nm\View;
/** @var array $p product row with name/slug @var string $lang @var array|null $cat */
/* Client update (2026-09-21): temperature is no longer displayed on product cards.
   The underlying temp_min/temp_max data is kept in the DB + dashboard. */
?>
<a class="card-product" href="/<?= $lang ?>/products/<?= View::e($p['slug']) ?>/">
  <span class="cp-media"><?= $p['card_media_id'] ? Media::img((int) $p['card_media_id'], $p['name']) : '<span class="cp-noimg" aria-hidden="true"></span>' ?></span>
  <span class="cp-body">
    <?php if (!empty($cat)): ?><span class="chip chip-amber"><?= View::e($cat['name']) ?></span><?php endif; ?>
    <span class="cp-name"><?= View::e($p['name']) ?></span>
    <span class="cp-go" aria-hidden="true"><svg class="i" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
  </span>
</a>