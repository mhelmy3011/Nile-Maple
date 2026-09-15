<?php
use Nm\Media; use Nm\Util; use Nm\View;
/** @var array $p product row with name/slug @var string $lang @var array|null $cat */
$badge = Util::tempBadge(isset($p['temp_min']) ? (float) $p['temp_min'] : null, isset($p['temp_max']) ? (float) $p['temp_max'] : null, $p['temp_unit'] ?? 'C');
?>
<a class="card-product" href="/<?= $lang ?>/products/<?= View::e($p['slug']) ?>/">
  <span class="cp-media"><?= $p['card_media_id'] ? Media::img((int) $p['card_media_id'], $p['name']) : '<span class="cp-noimg" aria-hidden="true"></span>' ?></span>
  <span class="cp-body">
    <?php if (!empty($cat)): ?><span class="chip chip-amber"><?= View::e($cat['name']) ?></span><?php endif; ?>
    <span class="cp-name"><?= View::e($p['name']) ?></span>
    <?php if ($badge): ?><span class="cp-temp"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4a2 2 0 0 1 4 0v9.3a4 4 0 1 1-4 0V4Z" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M12 9v7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg><span class="tabular"><?= View::e($badge) ?></span></span><?php endif; ?>
    <span class="cp-go" aria-hidden="true"><svg class="i" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
  </span>
</a>
