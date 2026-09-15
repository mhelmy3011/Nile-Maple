<?php use Nm\View; ?>
<div class="section-head<?= isset($align) && $align === 'center' ? ' center' : '' ?>">
  <?php if (!empty($eyebrow)): ?><p class="eyebrow"><?= View::e($eyebrow) ?></p><?php endif; ?>
  <h2><?= View::e($title) ?></h2>
  <?php if (!empty($lead)): ?><p class="lead"><?= View::e($lead) ?></p><?php endif; ?>
</div>
