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
      <span class="topbar-item"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M12 7v5l3 3" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg><span dir="ltr"><?= View::e(Settings::get('hours')) ?></span></span>
      <a class="topbar-social" href="<?= View::e(Settings::get('social.instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram" data-event="click_instagram"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor"/></svg></a>
      <a class="topbar-social" href="<?= View::e(Settings::get('social.facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook" data-event="click_facebook"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4.5h-3A4.5 4.5 0 0 0 9.5 9v2.5H7V15h2.5v6H13v-6h3l.5-3.5H13V9a1 1 0 0 1 1-1Z" fill="currentColor"/></svg></a>
    </div>
  </div>
</div>
