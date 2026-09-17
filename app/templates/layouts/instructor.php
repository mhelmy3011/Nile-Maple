<?php
use Nm\Icons;
use Nm\I18n;
use Nm\Manifest;
use Nm\View;
I18n::boot('en');
$section = $section ?? 'notifications';
$user = $user ?? ['full_name' => 'Instructor', 'role' => 'instructor'];
$nav = [
    'notifications' => ['label' => 'Notifications', 'icon' => 'mail', 'accent' => 'green'],
    'students/details' => ['label' => 'Students', 'icon' => 'users', 'accent' => 'amber'],
    'documents/details' => ['label' => 'Documents', 'icon' => 'doc', 'accent' => 'pine'],
    'announcements/create' => ['label' => 'Announcements', 'icon' => 'spark', 'accent' => 'green'],
    'faq' => ['label' => 'FAQ', 'icon' => 'clipboard', 'accent' => 'amber'],
];
$current = strtolower($section);
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= View::e(ucfirst(str_replace('/', ' — ', $section))) ?> — Instructor Console</title>
<style><?= Manifest::cssInline() ?></style>
<link rel="stylesheet" href="<?= Manifest::cssAdmin() ?>">
<link rel="stylesheet" href="<?= Manifest::cssInstructor() ?>">
</head>
<body class="admin">
<div class="ins-shell">
  <aside class="ins-side" id="ins-side" role="dialog" aria-modal="true" aria-label="Instructor navigation">
    <div class="ins-brand">
      <span class="ins-brand-mark">NM</span>
      <div>
        <strong>Nile-Maple</strong>
        <small>Instructor Console · Modern</small>
      </div>
      <button class="icon-btn ins-side-close" type="button" data-side-close aria-label="Close menu">
        <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
      </button>
    </div>

    <nav class="ins-nav" aria-label="Primary">
      <div class="ins-nav-section">Teaching</div>
      <?php foreach ($nav as $k => $meta): 
        $isOn = $current === $k || str_starts_with($current, explode('/', $k)[0]);
        if ($k === 'students/details' && !str_contains($current, 'students')) $isOn = false;
        if ($k === 'documents/details' && !str_contains($current, 'documents')) $isOn = false;
        if ($k === 'announcements/create' && !str_contains($current, 'announcements')) $isOn = false;
        // special handling for exact match highlighting
        $exactOn = $current === $k;
        if ($k === 'notifications' && $current === 'notifications') $exactOn = true;
        if ($k === 'faq' && $current === 'faq') $exactOn = true;
        if ($k === 'students/details' && str_contains($current, 'students')) $exactOn = true;
        if ($k === 'documents/details' && str_contains($current, 'documents')) $exactOn = true;
        if ($k === 'announcements/create' && str_contains($current, 'announcements')) $exactOn = true;
      ?>
        <a href="/instructor/<?= $k ?>" class="<?= $exactOn ? 'on' : '' ?>" data-accent="<?= $meta['accent'] ?>">
          <?= Icons::svg($meta['icon'], 'i') ?>
          <span><?= $meta['label'] ?></span>
          <?php if ($k === 'notifications'): ?><span class="ins-count" style="margin-inline-start:auto; background:rgba(255,255,255,.14); color:#fff; border:none">3</span><?php endif; ?>
        </a>
      <?php endforeach; ?>

      <div class="ins-nav-section">Platform</div>
      <a href="/manage/" class="">
        <?= Icons::svg('globe', 'i') ?><span>Control Room</span>
      </a>
      <a href="/" target="_blank" rel="noopener">
        <?= Icons::svg('globe','i') ?><span>View Site</span>
      </a>
    </nav>

    <div class="ins-side-foot">
      <div class="ins-user-mini">
        <span class="ins-avatar"><?= strtoupper(substr($user['full_name'] ?? 'I',0,1)) ?></span>
        <div style="display:grid">
          <strong style="font-size:.875rem"><?= View::e($user['full_name'] ?? 'Instructor') ?></strong>
          <small style="font-size:.72rem; color:var(--pine-300)"><?= View::e($user['role'] ?? 'instructor') ?> · <?= View::e($user['email'] ?? '') ?></small>
        </div>
      </div>
      <div class="ins-actions">
        <span class="ins-chip ins-chip-pine" style="font-size:.68rem">Dynamic · Modern · Rich</span>
        <span class="ins-chip ins-chip-green" style="font-size:.68rem">100% Ready</span>
      </div>
    </div>
  </aside>
  <div class="ins-side-backdrop" data-side-close></div>

  <div class="ins-main">
    <header class="ins-top">
      <button class="icon-btn ins-burger" type="button" aria-expanded="false" aria-controls="ins-side" aria-label="Menu">
        <svg class="i" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
      </button>
      <h2><?= View::e(ucfirst(str_replace(['/', '-'], [' · ', ' '], $section))) ?></h2>
      <span class="spacer"></span>
      <span class="ins-chip ins-chip-green" style="border:none"><span class="dot" style="background:var(--green-500)"></span> Live</span>
      <a class="btn btn-ghost btn-sm" href="/" target="_blank" rel="noopener">View site</a>
    </header>

    <main class="ins-body">
      <?php if (!empty($_GET['saved'])): ?>
        <div class="ins-toast ok" role="status">
          <?= Icons::svg('check','i') ?><span>Saved & updated successfully.</span>
        </div>
      <?php endif; ?>
      <?php if (!empty($_GET['e'])): ?>
        <div class="ins-toast err" role="alert">
          <?= Icons::svg('shield','i') ?><span>Notice: <?= View::e((string) $_GET['e']) ?></span>
        </div>
      <?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>

<script>
// minimal side drawer logic (same as admin)
document.addEventListener('DOMContentLoaded', ()=>{
  const side = document.getElementById('ins-side');
  const burger = document.querySelector('.ins-burger');
  const closes = document.querySelectorAll('[data-side-close]');
  const open = ()=>{ side?.classList.add('open'); burger?.setAttribute('aria-expanded','true'); };
  const close = ()=>{ side?.classList.remove('open'); burger?.setAttribute('aria-expanded','false'); };
  burger?.addEventListener('click', ()=> side.classList.contains('open') ? close() : open());
  closes.forEach(b=> b.addEventListener('click', close));
});
</script>
<script src="<?= Manifest::js('admin') ?>" defer></script>
</body>
</html>
