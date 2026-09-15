<?php
use Nm\I18n; use Nm\Manifest; use Nm\View;
I18n::boot('en');
$section = $section ?? 'dashboard';
$nav = [
    'dashboard' => 'Dashboard', 'products' => 'Products', 'categories' => 'Categories', 'services' => 'Services',
    'posts' => 'Blog', 'faqs' => 'FAQs', 'blocks' => 'Page blocks', 'media' => 'Media', 'enquiries' => 'Enquiries',
    'seo' => 'SEO', 'settings' => 'Settings', 'users' => 'Users', 'audit' => 'Audit',
];
?><!doctype html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= View::e(ucfirst($section)) ?> — Nile-Maple Control Room</title>
<style><?= Manifest::cssInline() ?></style>
<link rel="stylesheet" href="<?= Manifest::cssAdmin() ?>">
</head>
<body class="admin">
<div class="adm-shell">
  <aside class="adm-side" id="adm-side">
    <div class="adm-brand"><img src="/assets/brand/logo-mark.webp" width="40" height="34" alt=""> <strong>Control Room</strong></div>
    <nav>
      <?php foreach ($nav as $k => $label): ?>
        <a href="<?= cfg('admin.path') ?>/<?= $k === 'dashboard' ? '' : $k . '/' ?>" class="<?= $section === $k ? 'on' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <a href="<?= cfg('admin.path') ?>/logout" class="out">Log out</a>
    </nav>
  </aside>
  <div class="adm-main">
    <header class="adm-top">
      <button class="icon-btn adm-burger" type="button" data-side aria-label="Menu"><svg class="i" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg></button>
      <strong><?= View::e(ucfirst($section)) ?></strong>
      <span class="adm-user"><?= View::e($user['full_name'] ?? '') ?> (<?= View::e($user['role'] ?? '') ?>)</span>
      <a class="btn btn-ghost btn-sm" href="/" target="_blank" rel="noopener">View site</a>
    </header>
    <main class="adm-body">
      <?php if (!empty($_GET['saved'])): ?><div class="adm-toast ok" role="status">Saved & static pages rebuilt.</div><?php endif; ?>
      <?php if (!empty($_GET['e'])): ?><div class="adm-toast err" role="alert">Error: <?= View::e((string) $_GET['e']) ?></div><?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="<?= Manifest::js('admin') ?>" defer></script>
</body>
</html>
