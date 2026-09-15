<?php
use Nm\Content; use Nm\I18n; use Nm\Settings; use Nm\View;
$t = static fn(string $k) => I18n::t($k);
$cats = Content::categories($lang);
?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <img src="/assets/brand/logo-mono-light.webp" width="210" height="60" alt="Nile-Maple" loading="lazy" decoding="async">
        <p><?= $t('footer.tagline') ?></p>
        <div class="footer-social">
          <a href="<?= View::e(Settings::get('social.instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="17.2" cy="6.8" r="1.2" fill="currentColor"/></svg></a>
          <a href="<?= View::e(Settings::get('social.facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4.5h-3A4.5 4.5 0 0 0 9.5 9v2.5H7V15h2.5v6H13v-6h3l.5-3.5H13V9a1 1 0 0 1 1-1Z" fill="currentColor"/></svg></a>
        </div>
      </div>
      <div class="footer-col">
        <h2><?= $t('footer.company') ?></h2>
        <ul>
          <li><a href="/<?= $lang ?>/about/"><?= $t('nav.about') ?></a></li>
          <li><a href="/<?= $lang ?>/services/"><?= $t('nav.services') ?></a></li>
          <li><a href="/<?= $lang ?>/quality-handling/"><?= $t('zone.quality-handling') ?></a></li>
          <li><a href="/<?= $lang ?>/packaging-logistics/"><?= $t('zone.packaging-logistics') ?></a></li>
          <li><a href="/<?= $lang ?>/seasonal-availability/"><?= $t('zone.seasonal-availability') ?></a></li>
          <li><a href="/<?= $lang ?>/export-documentation/"><?= $t('zone.export-documentation') ?></a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h2><?= $t('nav.categories') ?></h2>
        <ul>
          <?php foreach ($cats as $c): ?><li><a href="/<?= $lang ?>/categories/<?= $c['slug'] ?>/"><?= View::e($c['name']) ?> <span class="cnt"><?= (int) $c['cnt'] ?></span></a></li><?php endforeach; ?>
          <li><a href="/<?= $lang ?>/blog/"><?= $t('nav.blog') ?></a></li>
          <li><a href="/<?= $lang ?>/faq/"><?= $t('nav.faq') ?></a></li>
        </ul>
      </div>
      <div class="footer-col footer-contact" id="contact-info">
        <h2><?= $t('footer.contact') ?></h2>
        <a class="f-row" href="mailto:<?= View::e(Settings::get('contact.email')) ?>" data-event="click_email">
          <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.75"/></svg>
          <span><?= View::e(Settings::get('contact.email')) ?></span></a>
        <a class="f-row" href="tel:<?= View::e(preg_replace('/\s/', '', Settings::get('contact.phone'))) ?>" data-event="click_call">
          <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h3l2 5-2.5 1.5a12 12 0 0 0 5 5L15 12l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg>
          <span dir="ltr"><?= View::e(Settings::get('contact.phone')) ?></span></a>
        <a class="f-row" href="<?= \Nm\Util::waLink($t('wa.prefill')) ?>" target="_blank" rel="noopener" data-event="click_whatsapp">
          <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.2 14.2c-.2.6-1.2 1.2-1.7 1.2-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.5-2.6-1.1-4.3-3.8-4.4-4-.1-.2-1.1-1.4-1.1-2.7s.7-1.9.9-2.2c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.9 2.1c.1.2.1.4 0 .6l-.4.6-.5.5c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.5.1.7-.1l1-1.2c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.6.4 0 .1 0 .7-.2 1.2Z"/></svg>
          <span><?= $t('contact.whatsapp') ?> <span dir="ltr"><?= View::e(Settings::get('contact.whatsapp')) ?></span></span></a>
        <?php if (Settings::get('address')): ?>
        <p class="f-addr"><svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11Z" fill="none" stroke="currentColor" stroke-width="1.75"/><circle cx="12" cy="10" r="2.5" fill="none" stroke="currentColor" stroke-width="1.75"/></svg> <?= View::e(Settings::get('address')) ?></p>
        <?php endif; ?>
      </div>
    </div>
    <div class="footer-legal">
      <span>© <?= date('Y') ?> Nile-Maple — <?= $t('footer.rights') ?></span>
      <span class="legal-links">
        <a href="/<?= $lang ?>/privacy/"><?= $t('nav.privacy') ?></a>
        <a href="/<?= $lang ?>/terms/"><?= $t('nav.terms') ?></a>
        <?= View::render('ui/langswitch', ['lang' => $lang, 'path' => $path ?? '']) ?>
      </span>
    </div>
  </div>
</footer>
