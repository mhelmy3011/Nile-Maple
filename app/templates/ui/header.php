<?php
use Nm\I18n; use Nm\View;
$t = static fn(string $k) => I18n::t($k);
$nav = [
    '' => $t('nav.home'), 'about' => $t('nav.about'), 'services' => $t('nav.services'),
    'categories' => $t('nav.categories'), 'blog' => $t('nav.blog'), 'contact' => $t('nav.contact'), 'faq' => $t('nav.faq'),
];
$current = trim($path ?? '', '/');
?>
<header class="header" id="header" data-sticky>
  <div class="container header-row">
    <a class="brand" href="/<?= $lang ?>/" aria-label="Nile-Maple — <?= $t('a11y.home') ?>">
      <img src="/assets/brand/logo-mark.webp" width="72" height="62" alt="" class="brand-mark" decoding="async">
      <img src="/assets/brand/logo-word-<?= $lang === 'ar' ? 'ar' : 'en' ?>.webp" width="190" height="40" alt="Nile-Maple" class="brand-word" decoding="async">
    </a>
    <nav class="nav-desktop" aria-label="<?= $t('a11y.mainnav') ?>">
      <?php foreach ($nav as $slug => $label): $act = ($slug === '' ? $current === '' : str_starts_with($current, $slug)); ?>
        <a href="/<?= $lang ?>/<?= $slug ? $slug . '/' : '' ?>" <?= $act ? 'aria-current="page"' : '' ?>><?= View::e($label) ?></a>
      <?php endforeach; ?>
      <a class="btn btn-primary btn-sm" href="/<?= $lang ?>/contact/"><?= $t('cta.quote') ?></a>
    </nav>
    <?= View::render('ui/langswitch', ['lang' => $lang, 'path' => $path ?? '']) ?>
    <button class="icon-btn menu-btn" type="button" data-sheet-open aria-expanded="false" aria-controls="sheet" aria-label="<?= $t('a11y.menu') ?>">
      <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
    </button>
  </div>
</header>
<div class="sheet" id="sheet" role="dialog" aria-modal="true" aria-label="<?= $t('a11y.menu') ?>" hidden>
  <div class="sheet-backdrop" data-sheet-close></div>
  <div class="sheet-panel">
    <div class="sheet-handle" aria-hidden="true"></div>
    <div class="sheet-head">
      <img src="/assets/brand/logo-mark.webp" width="56" height="48" alt="" decoding="async">
      <button class="icon-btn" type="button" data-sheet-close aria-label="<?= $t('a11y.close') ?>">
        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
      </button>
    </div>
    <nav class="sheet-nav" aria-label="<?= $t('a11y.mainnav') ?>">
      <?php foreach ($nav as $slug => $label): $act = ($slug === '' ? $current === '' : str_starts_with($current, $slug)); ?>
        <a href="/<?= $lang ?>/<?= $slug ? $slug . '/' : '' ?>" <?= $act ? 'aria-current="page"' : '' ?>><?= View::e($label) ?>
          <svg class="i chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
      <?php endforeach; ?>
    </nav>
    <div class="sheet-contact">
      <a class="btn btn-primary" href="tel:<?= View::e(preg_replace('/\s/', '', \Nm\Settings::get('contact.phone'))) ?>" data-event="click_call"><?= $t('contact.call') ?></a>
      <a class="btn btn-whatsapp" href="<?= \Nm\Util::waLink($t('wa.prefill')) ?>" target="_blank" rel="noopener" data-event="click_whatsapp"><?= $t('contact.whatsapp') ?></a>
      <a class="btn btn-ghost" href="mailto:<?= View::e(\Nm\Settings::get('contact.email')) ?>" data-event="click_email"><?= View::e(\Nm\Settings::get('contact.email')) ?></a>
    </div>
  </div>
</div>
