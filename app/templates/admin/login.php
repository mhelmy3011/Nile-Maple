<?php use Nm\Csrf; use Nm\Manifest; ?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow"><title>Sign in — Nile-Maple Control Room</title>
<style><?= Manifest::cssInline() ?></style><link rel="stylesheet" href="<?= Manifest::cssAdmin() ?>"></head>
<body class="admin adm-login-body">
<form class="adm-login" method="post" action="<?= cfg('admin.path') ?>/<?= !empty($totp) ? '2fa' : 'login' ?>">
  <?= Csrf::field() ?>
  <img src="/assets/brand/logo-mark.webp" width="72" height="62" alt="Nile-Maple">
  <h1>Control Room</h1>
  <?php if (!empty($error)): ?><p class="adm-toast err" role="alert">Sign-in failed<?= $error === 'rate' ? ' (too many attempts)' : '' ?>.</p><?php endif; ?>
  <?php if (!empty($totp)): ?>
    <label>Two-factor code<input name="code" inputmode="numeric" autocomplete="one-time-code" required></label>
  <?php else: ?>
    <label>Email<input type="email" name="email" autocomplete="username" required></label>
    <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
  <?php endif; ?>
  <button class="btn btn-primary" type="submit">Sign in</button>
</form>
</body></html>
