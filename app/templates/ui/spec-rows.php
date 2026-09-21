<?php
use Nm\Icons; use Nm\Util; use Nm\View;
/** @var array $p product_i18n+products row */
/* Client update (2026-09-21): storage temperatures are not displayed on product pages.
   The dedicated temperature badge is gone, and any cold-chain row whose value is
   essentially a temperature range (Util::hasTempText) is hidden as a whole, while rows
   with real handling guidance (e.g. canned storage notes) stay. The underlying data
   (temp_min/temp_max/temp_note/chain) is kept in the DB + dashboard. */
$rows = [
    ['leaf', $p['label_varieties'] ?? '', $p['varieties'] ?? '', false],
    ['route', $p['label_handling'] ?? '', $p['handling'] ?? '', false],
    ['box', $p['label_packing'] ?? '', $p['packing'] ?? '', false],
    ['thermometer', $p['label_chain'] ?? '', $p['chain'] ?? '', true],
];
?>
<dl class="spec-rows">
<?php foreach ($rows as [$icon, $label, $value, $temp]): ?>
  <?php $value = trim((string) $value); if ($value === '') continue; ?>
  <?php if ($temp && Util::hasTempText($value)) continue; ?>
  <div class="spec-row">
    <dt><span class="sr-icon" aria-hidden="true"><?= Icons::svg($icon) ?></span><?= View::e($label) ?></dt>
    <dd><?= View::e($value) ?></dd>
  </div>
<?php endforeach; ?>
</dl>