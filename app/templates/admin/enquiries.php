<?php use Nm\View; ?>
<div class="adm-actions">
  <nav><?php foreach (['' => 'all', 'new' => 'new', 'read' => 'read', 'replied' => 'replied', 'closed' => 'closed', 'spam' => 'spam'] as $k => $lbl): ?>
    <a class="btn btn-ghost btn-sm<?= $status === $k ? ' on' : '' ?>" href="?status=<?= $k ?>"><?= $lbl ?><?= $k === 'spam' && !empty($spamCount) ? ' (' . (int) $spamCount . ')' : '' ?></a><?php endforeach; ?></nav>
  <a class="btn btn-ghost btn-sm" href="<?= cfg('admin.path') ?>/enquiries/export">Export CSV</a>
</div>
<?php if ($status === 'spam'): ?>
  <p class="hint" style="margin:.4rem 0 .8rem">Flagged by the spam gates (honeypot / time-trap) and never mailed.
    A fast autofill user can trip the time-trap — review before discarding. Open a row and set its status
    back to <strong>new</strong> to return it to the inbox.</p>
<?php endif; ?>
<table class="adm-table"><thead><tr><th>id</th><th>when</th><th>lang</th><th>name</th><th>subject</th><th>status</th><th>mail</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= (int) $r['id'] ?></td><td><?= View::e($r['created_at']) ?></td><td><?= View::e($r['lang']) ?></td>
<td><?= View::e($r['full_name']) ?><br><span class="hint"><?= View::e($r['email']) ?></span></td>
<td><?= View::e($r['subject']) ?><?= !empty($r['spam_reason']) ? ' <span class="hint">(' . View::e($r['spam_reason']) . ')</span>' : '' ?></td><td><?= View::e($r['status']) ?></td><td><?= $r['mailed'] ? '✓' : '✗' ?></td>
<td><a class="btn btn-ghost btn-sm" href="<?= cfg('admin.path') ?>/enquiries/<?= (int) $r['id'] ?>">Open</a></td></tr>
<?php endforeach; ?></tbody></table>
