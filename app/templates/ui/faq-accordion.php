<?php
use Nm\View;
/** @var array $faqs */
?>
<div class="faq-list" data-accordion>
<?php foreach ($faqs as $i => $f): ?>
  <details class="faq-item" id="faq-<?= (int) $f['id'] ?>"<?= $i === 0 && ($openFirst ?? true) ? ' open' : '' ?>>
    <summary>
      <span><?= View::e($f['question']) ?></span>
      <svg class="i plus" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
    </summary>
    <div class="faq-a"><p><?= nl2br(View::e($f['answer'])) ?></p></div>
  </details>
<?php endforeach; ?>
</div>
