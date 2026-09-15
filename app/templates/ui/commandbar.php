<?php
use Nm\I18n; use Nm\Settings; use Nm\View;
if (trim($path ?? '', '/') === 'contact') return;   // redundant on contact (doc 03 §1.4)
$t = static fn(string $k) => I18n::t($k);
?>
<nav class="commandbar" id="commandbar" aria-label="<?= $t('a11y.actions') ?>">
  <a class="cb-item" href="tel:<?= View::e(preg_replace('/\s/', '', Settings::get('contact.phone'))) ?>" data-event="click_call">
    <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h3l2 5-2.5 1.5a12 12 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>
    <span><?= $t('contact.call') ?></span></a>
  <a class="cb-item cb-wa" href="<?= \Nm\Util::waLink($t('wa.prefill')) ?>" target="_blank" rel="noopener" data-event="click_whatsapp">
    <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.2 14.2c-.2.6-1.2 1.2-1.7 1.2-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.5-2.6-1.1-4.3-3.8-4.4-4-.1-.2-1.1-1.4-1.1-2.7s.7-1.9.9-2.2c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.9 2.1c.1.2.1.4 0 .6l-.4.6-.5.5c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.5.1.7-.1l1-1.2c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.6.4 0 .1 0 .7-.2 1.2Z"/></svg>
    <span><?= $t('contact.whatsapp') ?></span></a>
  <a class="cb-item cb-quote" href="/<?= $lang ?>/contact/" data-event="click_quote">
    <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v11H8l-4 4V5Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>
    <span><?= $t('cta.quote') ?></span></a>
</nav>
