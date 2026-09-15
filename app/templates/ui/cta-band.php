<?php use Nm\I18n; ?>
<section class="cta-band">
  <div class="container cta-inner">
    <div>
      <h2><?= I18n::t('cta.band.title') ?></h2>
      <p><?= I18n::t('cta.band.lead') ?></p>
    </div>
    <div class="cta-actions">
      <a class="btn btn-on-green" href="/<?= $lang ?>/contact/"><?= I18n::t('cta.quote') ?></a>
      <a class="btn btn-outline-light" href="<?= \Nm\Util::waLink(I18n::t('wa.prefill')) ?>" target="_blank" rel="noopener" data-event="click_whatsapp"><?= I18n::t('contact.whatsapp') ?></a>
    </div>
  </div>
</section>
