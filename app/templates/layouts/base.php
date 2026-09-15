<?php
use Nm\I18n; use Nm\Manifest; use Nm\Seo; use Nm\Settings; use Nm\View;
/** @var string $lang @var array $meta @var string $path @var array $ld @var string|null $preload */
$t = static fn(string $k, array $p = []) => I18n::t($k, $p);
$jsBundles = $jsBundles ?? ['base'];
$canon = $meta['canonical_override'] ?: Seo::urlFor($lang, $path);
$ogImage = !empty($meta['og_image']) ? \Nm\Media::url((int) $meta['og_image'], 800, 'jpg') : '/assets/brand/og-default.jpg';
?><!doctype html>
<html lang="<?= $lang ?>" dir="<?= I18n::dir() ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= View::e($meta['title']) ?></title>
<meta name="description" content="<?= View::e($meta['description']) ?>">
<meta name="robots" content="<?= View::e($meta['robots']) ?>">
<link rel="canonical" href="<?= View::e($canon) ?>">
<?= Seo::hreflang($path) ?>
<meta property="og:site_name" content="Nile-Maple">
<meta property="og:type" content="website">
<meta property="og:locale" content="<?= ['en' => 'en_US', 'ar' => 'ar_EG', 'fr' => 'fr_FR'][$lang] ?>">
<?php foreach (cfg('langs') as $l) if ($l !== $lang): ?><meta property="og:locale:alternate" content="<?= ['en' => 'en_US', 'ar' => 'ar_EG', 'fr' => 'fr_FR'][$l] ?>">
<?php endif; ?>
<meta property="og:title" content="<?= View::e($meta['title']) ?>">
<meta property="og:description" content="<?= View::e($meta['description']) ?>">
<meta property="og:url" content="<?= View::e($canon) ?>">
<meta property="og:image" content="<?= View::e(rtrim(cfg('base_url'), '/') . $ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0b542e">
<link rel="icon" href="/assets/brand/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/assets/brand/apple-touch-icon.png">
<?php if ($preload): ?><link rel="preload" as="image" href="<?= View::e($preload) ?>">
<?php endif; ?>
<style><?= Manifest::cssInline() ?></style>
<?php foreach ($ld as $g): ?><?= Seo::ld($g) . "\n"; endforeach; ?>
</head>
<body class="tpl-<?= View::e(str_replace('/', '-', $path ?: 'home')) ?>">
<a class="skip-link" href="#main"><?= $t('a11y.skip') ?></a>
<?= View::render('ui/topbar', ['lang' => $lang]) ?>
<?= View::render('ui/header', ['lang' => $lang, 'path' => $path]) ?>
<main id="main">
<?= $content ?>
</main>
<?= View::render('ui/footer', ['lang' => $lang]) ?>
<?= View::render('ui/commandbar', ['lang' => $lang, 'path' => $path]) ?>
<?= View::render('ui/cookie', ['lang' => $lang]) ?>
<a class="wa-fab" href="<?= \Nm\Util::waLink($t('wa.prefill')) ?>" target="_blank" rel="noopener" aria-label="<?= $t('contact.whatsapp') ?>" data-event="whatsapp_fab">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.2 14.2c-.2.6-1.2 1.2-1.7 1.2-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.5-2.6-1.1-4.3-3.8-4.4-4-.1-.2-1.1-1.4-1.1-2.7s.7-1.9.9-2.2c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4l.9 2.1c.1.2.1.4 0 .6l-.4.6-.5.5c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.5.1.7-.1l1-1.2c.2-.3.4-.2.7-.1l2 1c.3.1.5.2.6.4 0 .1 0 .7-.2 1.2Z"/></svg>
</a>
<noscript><style>.needs-js{display:none!important}</style></noscript>
<?php foreach ($jsBundles as $b): ?><script src="<?= Manifest::js($b) ?>" defer></script>
<?php endforeach; ?>
<script type="application/json" id="nm-cfg"><?= json_encode(['lang' => $lang, 'dir' => I18n::dir(), 'api' => '/api']) ?></script>
</body>
</html>
