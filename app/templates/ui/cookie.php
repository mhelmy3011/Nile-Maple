<?php use Nm\I18n; ?>
<div class="cookie" id="cookie" role="region" aria-label="<?= I18n::t('cookie.title') ?>" hidden>
  <div class="container cookie-box">
    <p><strong><?= I18n::t('cookie.title') ?></strong> <?= I18n::t('cookie.body') ?></p>
    <div class="cookie-actions">
      <button class="btn btn-primary btn-sm" type="button" data-cookie="accept"><?= I18n::t('cookie.accept') ?></button>
      <a class="btn btn-ghost btn-sm" href="/<?= $lang ?>/privacy/"><?= I18n::t('cookie.manage') ?></a>
    </div>
  </div>
</div>
