<?php use Nm\Csrf; use Nm\View; ?>
<form class="adm-card" method="post" action="<?= cfg('admin.path') ?>/settings/system">
  <?= Csrf::field() ?>
  <h2>System</h2>
  <div class="field"><label>maintenance mode</label>
    <select name="maintenance"><option value="0"<?= \Nm\Settings::get('sys.maintenance') !== '1' ? ' selected' : '' ?>>off</option><option value="1"<?= \Nm\Settings::get('sys.maintenance') === '1' ? ' selected' : '' ?>>on (503 for public)</option></select></div>
  <div class="adm-actions">
    <button class="btn btn-ghost btn-sm" name="purge" value="1">Purge caches</button>
    <button class="btn btn-ghost btn-sm" name="rebuild" value="1">Rebuild all static pages</button>
    <button class="btn btn-ghost btn-sm" name="backup" value="1">Backup DB now</button>
    <button class="btn btn-primary btn-sm">Save</button>
  </div>
</form>
<h2>Build jobs</h2>
<table class="adm-table"><thead><tr><th>id</th><th>scope</th><th>status</th><th>pages</th><th>ms</th><th>finished</th></tr></thead><tbody>
<?php foreach ($jobs as $j): ?><tr><td><?= (int) $j['id'] ?></td><td><?= View::e($j['scope']) ?></td><td><?= View::e($j['status']) ?></td><td><?= (int) $j['pages'] ?></td><td><?= (int) $j['ms'] ?></td><td><?= View::e((string) $j['finished_at']) ?></td></tr><?php endforeach; ?>
</tbody></table>
