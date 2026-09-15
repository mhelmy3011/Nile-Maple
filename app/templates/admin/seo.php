<?php use Nm\View; ?>
<div class="adm-actions">
  <a class="btn btn-ghost btn-sm" href="<?= cfg('admin.path') ?>/seo/globals">Global templates</a>
  <a class="btn btn-ghost btn-sm" href="<?= cfg('admin.path') ?>/seo/redirects">Redirects</a>
  <form method="post" action="<?= cfg('admin.path') ?>/seo/sitemap"><?= \Nm\Csrf::field() ?><button class="btn btn-ghost btn-sm">Regenerate sitemaps</button></form>
</div>
<h2>SEO completeness</h2>
<table class="adm-table"><thead><tr><th>entity</th><th>total</th><th>missing meta (any lang)</th></tr></thead><tbody>
<?php foreach ($board as $k => $c): ?><tr><td><?= $k ?></td><td><?= (int) $c['total'] ?></td><td><?= (int) $c['incomplete'] ?></td></tr><?php endforeach; ?>
</tbody></table>
<h2>Entities lacking title/description (<?= count($missing) ?>)</h2>
<table class="adm-table"><thead><tr><th>type</th><th>id</th><th>lang</th><th>fix</th></tr></thead><tbody>
<?php foreach (array_slice($missing, 0, 100) as [$t, $i, $l]): ?>
<tr><td><?= $t ?></td><td><?= $i ?></td><td><?= $l ?></td><td><a class="btn btn-ghost btn-sm" href="<?= cfg('admin.path') ?>/<?= $t === 'category' ? 'categories' : $t . 's' ?>/<?= $i ?>">edit</a></td></tr>
<?php endforeach; ?></tbody></table>
