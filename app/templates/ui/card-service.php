<?php use Nm\View; ?>
<a class="card-service" href="/<?= $lang ?>/services/<?= View::e($s['slug']) ?>/">
  <span class="cs-icon" aria-hidden="true"><?= \Nm\Icons::svg($s['icon_key']) ?></span>
  <span class="cs-body">
    <h3><?= View::e($s['name']) ?></h3>
    <p><?= View::e($s['teaser']) ?></p>
    <span class="cs-more"><?= \Nm\I18n::t('cta.more') ?>
      <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
  </span>
</a>
