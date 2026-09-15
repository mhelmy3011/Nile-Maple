<?php use Nm\Csrf; ?>
<div class="adm-card" style="max-width:34rem">
  <h2>Rotate your password</h2>
  <p style="color:var(--muted,#5b6b62)">This account signed in with the initial seed password, so nothing else in the
    Control Room is reachable until a real password is set. Choose at least 10 characters that you have not used
    elsewhere.</p>
  <?php
  $msgs = [
      'current' => 'The current password was not correct.',
      'seed'    => 'That is the seed password — it can never be used again.',
      'short'   => 'The new password must be at least 10 characters.',
      'same'    => 'The new password must differ from the current one.',
      'csrf'    => 'The form expired — please try again.',
  ];
  if (!empty($_GET['e']) && isset($msgs[(string) $_GET['e']])): ?>
    <p class="adm-toast err" role="alert"><?= $msgs[(string) $_GET['e']] ?></p>
  <?php endif; ?>
  <form method="post" action="<?= cfg('admin.path') ?>/password">
    <?= Csrf::field() ?>
    <label>Current password<input type="password" name="current" autocomplete="current-password" required></label>
    <label>New password<input type="password" name="password" minlength="10" autocomplete="new-password" required></label>
    <button class="btn btn-primary" type="submit">Set new password</button>
  </form>
</div>
