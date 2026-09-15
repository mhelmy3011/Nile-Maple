<?php use Nm\View; ?>
<div class="adm-actions">
  <nav><?php foreach (['' => 'all', 'new' => 'new', 'read' => 'read', 'replied' => 'replied', 'closed' => 'closed'] as $k => $lbl): ?>
    <a class="btn btn-ghost btn-sm<?= $status === $k ? ' on' : '' ?>" href="?status=<?= $k ?>"><?= $lbl ?></a><?php endforeach; ?></nav>
  <a class="btn btn-ghost btn-sm" href="<?= cfg('admin.path') ?>/enquiries/export">Export CSV</a>
</div>
<table class="adm-table"><thead><tr><th>id</th><th>when</th><th>lang</th><th>name</th><th>subject</th><th>status</th><th>mail</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= (int) $r['id'] ?></td><td><?= View::e($r['created_at']) ?></td><td><?= View::e($r['lang']) ?></td>
<td><?= View::e($r['full_name']) ?><br><span class="hint"><?= View::e($r['email']) ?></span></td>
<td><?= View::e($r['subject']) ?></td><td><?= View::e($r['status']) ?></td><td><?= $r['mailed'] ? '✓' : '✗' ?></td>
<td><a class="btn btn-ghost btn-sm" href="<?= cfg('admin.path') ?>/enquiries/<?= (int) $r['id'] ?>">Open</a></td></tr>
<?php endforeach; ?></tbody></table>
