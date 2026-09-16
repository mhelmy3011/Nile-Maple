<?php
use Nm\Icons; use Nm\View;
/** @var array $p product_i18n+products row */
$rows = [
    ['leaf', $p['label_varieties'] ?? '', $p['varieties'] ?? '', false],
    ['route', $p['label_handling'] ?? '', $p['handling'] ?? '', false],
    ['box', $p['label_packing'] ?? '', $p['packing'] ?? '', false],
    ['thermometer', $p['label_chain'] ?? '', $p['chain'] ?? '', true],
];
?>
<dl class="spec-rows">
<?php foreach ($rows as [$icon, $label, $value, $temp]): ?>
  <?php if (trim((string) $value) === '') continue; ?>
  <div class="spec-row">
    <dt><span class="sr-icon" aria-hidden="true"><?= Icons::svg($icon) ?></span><?= View::e($label) ?></dt>
    <dd><?= $temp
        ? '<span class="temp-badge tabular" dir="ltr">' . View::e(\Nm\Util::tempBadge((float) $p['temp_min'], (float) $p['temp_max'], $p['temp_unit'])) . '</span> ' . View::e($value)
        : View::e($value) ?></dd>
  </div>
<?php endforeach; ?>
</dl>
