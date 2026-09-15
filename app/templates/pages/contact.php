<?php
use Nm\Csrf; use Nm\I18n; use Nm\Settings; use Nm\View;
$t = static fn(string $k) => I18n::t($k);
$jsBundles = ['base', 'contact'];
$pre = (string) ($query['product'] ?? '');
?>
<div class="page-head">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="eyebrow"><?= $t('contact.eyebrow') ?></p>
    <h1><?= $t('contact.title') ?></h1>
    <p class="lead"><?= $t('contact.lead') ?></p>
  </div>
</div>
<section class="section">
  <div class="container contact-grid">
    <div class="contact-cards">
      <a class="contact-card" href="tel:<?= View::e(preg_replace('/\s/', '', Settings::get('contact.phone'))) ?>" data-event="click_call">
        <span class="cc-icon" aria-hidden="true"><?= \Nm\Icons::svg('phone') ?></span>
        <strong><?= $t('contact.call') ?></strong><span dir="ltr"><?= View::e(Settings::get('contact.phone')) ?></span></a>
      <a class="contact-card" href="<?= \Nm\Util::waLink($t('wa.prefill')) ?>" target="_blank" rel="noopener" data-event="click_whatsapp">
        <span class="cc-icon" aria-hidden="true"><?= \Nm\Icons::svg('spark') ?></span>
        <strong><?= $t('contact.whatsapp') ?></strong><span dir="ltr"><?= View::e(Settings::get('contact.whatsapp')) ?></span></a>
      <a class="contact-card" href="mailto:<?= View::e(Settings::get('contact.email')) ?>" data-event="click_email">
        <span class="cc-icon" aria-hidden="true"><?= \Nm\Icons::svg('mail') ?></span>
        <strong><?= $t('contact.email') ?></strong><span><?= View::e(Settings::get('contact.email')) ?></span></a>
      <div class="contact-card static">
        <span class="cc-icon" aria-hidden="true"><?= \Nm\Icons::svg('globe') ?></span>
        <strong><?= $t('contact.social') ?></strong>
        <span class="cc-social">
          <a href="<?= View::e(Settings::get('social.instagram')) ?>" target="_blank" rel="noopener">Instagram</a>
          <a href="<?= View::e(Settings::get('social.facebook')) ?>" target="_blank" rel="noopener">Facebook</a>
        </span></div>
      <p class="contact-promise"><?= $t('contact.promise') ?></p>
    </div>
    <form class="form" id="enquiry-form" action="/api/enquiry" method="post" novalidate data-form>
      <?= Csrf::field() ?>
      <input type="hidden" name="lang" value="<?= $lang ?>">
      <input type="hidden" name="_t" value="<?= time() ?>">
      <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
      <div class="form-error" role="alert" hidden></div>
      <div class="form-ok" role="status" hidden><strong><?= $t('form.success') ?></strong><p><?= $t('form.success.lead') ?></p></div>
      <div class="field"><label for="f-name"><?= $t('form.name') ?> <span class="req">*</span></label>
        <input id="f-name" name="full_name" required autocomplete="name" maxlength="120"></div>
      <div class="field"><label for="f-email"><?= $t('form.email') ?> <span class="req">*</span></label>
        <input id="f-email" name="email" type="email" required autocomplete="email" maxlength="190"></div>
      <div class="field"><label for="f-phone"><?= $t('form.phone') ?></label>
        <input id="f-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" maxlength="40"></div>
      <div class="field-row">
        <div class="field"><label for="f-company"><?= $t('form.company') ?></label>
          <input id="f-company" name="company" autocomplete="organization" maxlength="160"></div>
        <div class="field"><label for="f-country"><?= $t('form.country') ?></label>
          <input id="f-country" name="country" autocomplete="country-name" maxlength="90"></div>
      </div>
      <div class="field"><label for="f-subject"><?= $t('form.subject') ?> <span class="req">*</span></label>
        <input id="f-subject" name="subject" required maxlength="190"></div>
      <div class="field"><label for="f-product"><?= $t('form.product') ?></label>
        <select id="f-product" name="product_interest">
          <option value=""><?= $t('form.product.any') ?></option>
          <?php foreach ($productsByCat as $catName => $items): ?>
            <optgroup label="<?= View::e($catName) ?>">
              <?php foreach ($items as $it): ?><option value="<?= View::e($it['name']) ?>"<?= $it['name'] === $pre ? ' selected' : '' ?>><?= View::e($it['name']) ?></option><?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select></div>
      <div class="field"><label for="f-message"><?= $t('form.message') ?> <span class="req">*</span></label>
        <textarea id="f-message" name="message" rows="5" required maxlength="4000"></textarea></div>
      <div class="field field-check">
        <input id="f-consent" name="consent" type="checkbox" value="1" required>
        <label for="f-consent"><?= $t('form.consent', ['url' => "/$lang/privacy/"]) ?></label></div>
      <button class="btn btn-primary btn-lg" type="submit" data-submit><?= $t('form.send') ?></button>
    </form>
  </div>
</section>
<section class="section section-tint">
  <div class="container container-narrow">
    <?= View::render('ui/section-head', ['title' => $t('home.faq.title'), 'align' => 'center']) ?>
    <?= View::render('ui/faq-accordion', ['faqs' => $faqs, 'lang' => $lang]) ?>
  </div>
</section>
