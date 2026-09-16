<?php
use Nm\View;
$labels = ['en' => 'English', 'ar' => 'العربية', 'fr' => 'Français'];
$short = ['en' => 'EN', 'ar' => 'ع', 'fr' => 'FR'];
$path = trim($path ?? '', '/');
?>
<?php /* Plain disclosure menu of links, not an ARIA listbox: base.js only opens/closes it and
        treats the <a> elements as the interactive units — there is no roving-tabindex or
        arrow-key handling anywhere, so role="listbox"/"option" described a widget this markup
        never actually implemented. axe-core caught the resulting structural mismatches
        (aria-required-children/parent, nested-interactive, listitem) two different ways in a
        row; the fix is to stop asserting a widget contract this component doesn't fulfil, per
        ARIA's own first rule — a native <ul><li><a> already says exactly what this is. */ ?>
<div class="langswitch" data-langswitch>
  <button class="lang-btn" type="button" aria-expanded="false" aria-label="<?= \Nm\I18n::t('a11y.lang') ?>">
    <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.75"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
    <span class="lang-cur"><?= $short[$lang] ?></span>
    <svg class="i caret" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
  </button>
  <ul class="lang-menu" aria-label="<?= \Nm\I18n::t('a11y.lang') ?>" hidden>
    <?php foreach (cfg('langs') as $l): ?>
      <li>
        <a href="/<?= $l ?>/<?= $path ? View::e($path) . '/' : '' ?>" hreflang="<?= $l ?>" lang="<?= $l ?>"<?= $l === $lang ? ' aria-current="true"' : '' ?>>
          <span><?= View::e($labels[$l]) ?></span><?= $l === $lang ? '<svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 13 4 4L19 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' : '' ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
