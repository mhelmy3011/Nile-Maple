<?php
use Nm\I18n; use Nm\Settings; use Nm\View;
$t = static fn(string $k) => I18n::t($k);
?>
<div class="topbar" role="region" aria-label="<?= $t('a11y.contactbar') ?>">
  <div class="container">
    <?php /* D-06: ONE scroller holds links and social icons — on 360 px the row scrolls with
           momentum instead of squeezing (which clipped the phone number). Every target is ≥48 px. */ ?>
    <div class="topbar-scroll">
      <a class="topbar-link" href="mailto:<?= View::e(Settings::get('contact.email')) ?>" data-event="click_email">
        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.75"/></svg>
        <span><?= View::e(Settings::get('contact.email')) ?></span></a>
      <a class="topbar-link" href="tel:<?= View::e(preg_replace('/\s/', '', Settings::get('contact.phone'))) ?>" data-event="click_call">
        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h3l2 5-2.5 1.5a12 12 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>
        <span dir="ltr"><?= View::e(Settings::get('contact.phone')) ?></span></a>
      <a class="topbar-social" href="<?= View::e(\Nm\Util::waLink($t('wa.prefill'))) ?>" target="_blank" rel="noopener" aria-label="WhatsApp" data-event="click_whatsapp"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 14.9L2 22l5.3-1.4A10 10 0 1 0 12 2Zm5.4 14.2c-.2.6-1.3 1.2-1.8 1.2s-.9.1-2.9-.6a10 10 0 0 1-4.2-3.7 5 5 0 0 1-1-2.7A2.8 2.8 0 0 1 8.3 8c.3 0 .5 0 .7.1s.5.1.7.5.9 2.2 1 2.3a.6.6 0 0 1 0 .5 2 2 0 0 1-.3.5l-.5.5c-.2.2-.4.4-.2.8a11.3 11.3 0 0 0 2 2.5 9.7 9.7 0 0 0 2.9 1.8c.4.2.6.2.8-.1s1-1.1 1.2-1.5.5-.3.8-.2 2 1 2.3 1.1.5.3.6.4a2.6 2.6 0 0 1-.2 1.5Z" fill="currentColor"/></svg></a>
      <a class="topbar-social" href="<?= View::e(Settings::get('social.instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram" data-event="click_instagram"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor"/></svg></a>
      <a class="topbar-social" href="<?= View::e(Settings::get('social.facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook" data-event="click_facebook"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4.5h-3A4.5 4.5 0 0 0 9.5 9v2.5H7V15h2.5v6H13v-6h3l.5-3.5H13V9a1 1 0 0 1 1-1Z" fill="currentColor"/></svg></a>
    </div>
  </div>
</div>
