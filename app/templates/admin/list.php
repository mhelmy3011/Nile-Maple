<?php
use Nm\Csrf; use Nm\View;
$base = cfg('admin.path') . "/$key";
?>
<div class="adm-actions">
  <form class="adm-search" method="get"><input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Search…"><button class="btn btn-ghost btn-sm">Search</button></form>
  <a class="btn btn-primary btn-sm" href="<?= $base ?>/new">+ New</a>
</div>
<form method="post" action="<?= $base ?>/bulk" data-bulk>
  <?= Csrf::field() ?>
  <div class="adm-bulkbar" hidden>
    <select name="op"><option value="publish">Publish</option><option value="unpublish">Unpublish</option><option value="delete">Delete</option></select>
    <button class="btn btn-ghost btn-sm">Apply</button>
  </div>
  <table class="adm-table" data-sortable="<?= $base ?>/order">
    <thead><tr><th class="chk"><input type="checkbox" data-check-all aria-label="select all"></th>
      <?php foreach ($e['list'] as $c): ?><th><?= View::e($c) ?></th><?php endforeach; ?>
      <th>langs</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr data-id="<?= (int) $r['id'] ?>">
        <td class="chk"><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>"></td>
        <?php foreach ($e['list'] as $c): ?>
          <td><?php
            if ($c === 'comp') { foreach ($r['comp'] as $l => $p) echo '<span class="dot ' . ($p >= 100 ? 'ok' : 'no') . '" title="' . $l . ' ' . $p . '%"></span>'; }
            elseif ($c === 'name' || $c === 'title' || $c === 'question') echo View::e($r[$c] ?? '—');
            else echo View::e((string) ($r[$c] ?? '—'));
          ?></td>
        <?php endforeach; ?>
        <td><?php foreach ($r['comp'] as $l => $p): ?><span class="langtag <?= $p >= 100 ? 'ok' : 'no' ?>"><?= $l ?></span><?php endforeach; ?></td>
        <td class="row-actions">
          <a class="btn btn-ghost btn-sm" href="<?= $base ?>/<?= (int) $r['id'] ?>">Edit</a>
          <button class="btn btn-ghost btn-sm" type="submit" formaction="<?= $base ?>/delete/<?= (int) $r['id'] ?>" formmethod="post">Delete</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</form>
<?php $pages = (int) ceil($total / $per); if ($pages > 1): ?>
<nav class="adm-pager"><?php for ($i = 1; $i <= $pages; $i++): ?>
  <a href="?page=<?= $i ?>&q=<?= rawurlencode($q) ?>" class="<?= $i === $page ? 'on' : '' ?>"><?= $i ?></a>
<?php endfor; ?></nav>
<?php endif; ?>
