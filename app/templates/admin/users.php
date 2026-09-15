<?php use Nm\Csrf; use Nm\View; ?>
<form class="adm-card" method="post" action="<?= cfg('admin.path') ?>/users">
  <?= Csrf::field() ?>
  <h2>Add / update user</h2>
  <div class="field-row">
    <div class="field"><label>email</label><input type="email" name="email" required></div>
    <div class="field"><label>full name</label><input name="full_name" required></div>
    <div class="field"><label>role</label><select name="role"><option value="editor">editor</option><option value="owner">owner</option></select></div>
    <div class="field"><label>password</label><input type="password" name="password" required autocomplete="new-password"></div>
  </div>
  <button class="btn btn-primary btn-sm">Save user</button>
</form>
<table class="adm-table"><thead><tr><th>id</th><th>email</th><th>name</th><th>role</th><th>last login</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= (int) $r['id'] ?></td><td><?= View::e($r['email']) ?></td><td><?= View::e($r['full_name']) ?></td><td><?= View::e($r['role']) ?></td><td><?= View::e((string) $r['last_login_at']) ?></td>
<td><?php if ($r['role'] !== 'owner'): ?><form method="post"><?= Csrf::field() ?><input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>"><button class="btn btn-ghost btn-sm">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table>
