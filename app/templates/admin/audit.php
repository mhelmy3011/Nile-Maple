<?php use Nm\View; ?>
<table class="adm-table"><thead><tr><th>id</th><th>when</th><th>user</th><th>action</th><th>entity</th><th>diff</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?= (int) $r['id'] ?></td><td><?= View::e($r['created_at']) ?></td><td><?= View::e((string) ($r['email'] ?? 'system')) ?></td><td><?= View::e($r['action']) ?></td><td><?= View::e((string) $r['entity_type']) ?> #<?= (int) $r['entity_id'] ?></td><td class="hint"><?= View::e(mb_substr((string) $r['diff'], 0, 90)) ?></td></tr><?php endforeach; ?>
</tbody></table>
