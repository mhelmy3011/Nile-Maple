<?php use Nm\Csrf; use Nm\View; ?>
<form class="adm-card" method="post" action="<?= cfg('admin.path') ?>/seo/redirects">
  <?= Csrf::field() ?>
  <h2>Add redirect</h2>
  <div class="field-row">
    <div class="field"><label>from</label><input name="from_path" placeholder="/en/old/" required></div>
    <div class="field"><label>to</label><input name="to_path" placeholder="/en/new/" required></div>
    <div class="field"><label>code</label><select name="code"><option>301</option><option>302</option></select></div>
  </div>
  <button class="btn btn-primary btn-sm">Add</button>
</form>
<table class="adm-table"><thead><tr><th>from</th><th>to</th><th>code</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= View::e($r['from_path']) ?></td><td><?= View::e($r['to_path']) ?></td><td><?= (int) $r['code'] ?></td>
<td><form method="post" action="<?= cfg('admin.path') ?>/seo/redirects"><?= Csrf::field() ?><input type="hidden" name="delete_id" value="<?= (int) $r['id'] ?>"><button class="btn btn-ghost btn-sm">Delete</button></form></td></tr>
<?php endforeach; ?></tbody></table>
