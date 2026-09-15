<?php use Nm\View; ?>
<nav class="breadcrumbs" aria-label="<?= \Nm\I18n::t('a11y.crumbs') ?>">
  <ol>
    <?php foreach ($crumbs as $i => [$name, $url]): $last = $i === count($crumbs) - 1; ?>
      <li><?= $last ? '<span aria-current="page">' . View::e($name) . '</span>' : '<a href="' . View::e($url) . '">' . View::e($name) . '</a>' ?></li>
    <?php endforeach; ?>
  </ol>
</nav>
