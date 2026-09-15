<?php use Nm\Csrf; use Nm\View; ?>
<form class="adm-card" method="post" action="<?= cfg('admin.path') ?>/media/upload" enctype="multipart/form-data" data-upload>
  <?= Csrf::field() ?>
  <div class="field"><label>Upload images (jpg/png/webp, ≤ 8 MB)</label>
    <input type="file" name="files[]" accept="image/jpeg,image/png,image/webp" multiple required></div>
  <button class="btn btn-primary btn-sm">Upload & generate variants</button>
  <span class="adm-save-state" data-upload-state></span>
</form>
<form class="adm-actions" method="get"><input type="search" name="q" value="<?= View::e($q) ?>" placeholder="filename / source"><button class="btn btn-ghost btn-sm">Search</button></form>
<table class="adm-table"><thead><tr><th>id</th><th>preview</th><th>file</th><th>dims</th><th>alt (en)</th><th>usage</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $m): $u = \Nm\Media::usage((int) $m['id']); ?>
<tr><td><?= (int) $m['id'] ?></td>
<td><img src="/assets/media/<?= View::e(\Nm\Db::val("SELECT path FROM media_variant WHERE media_id=? AND fmt='webp' ORDER BY w LIMIT 1", [(int) $m['id']]) ?? '') ?>" alt="" width="48" height="48" loading="lazy"></td>
<td><?= View::e($m['filename']) ?><br><span class="hint"><?= View::e((string) $m['source_ref']) ?></span></td>
<td><?= (int) $m['width'] ?>×<?= (int) $m['height'] ?></td>
<td><?= View::e((string) ($m['alt'] ?? '')) ?></td>
<td><?= View::e(implode(', ', $u) ?: '—') ?></td>
<td><a class="btn btn-ghost btn-sm" href="?edit=<?= (int) $m['id'] ?>">Edit</a></td></tr>
<?php endforeach; ?></tbody></table>
<?php if ($edit): $m = \Nm\Media::row($edit); if ($m): ?>
<form class="adm-card" method="post" action="<?= cfg('admin.path') ?>/media/save">
  <?= Csrf::field() ?><input type="hidden" name="media_id" value="<?= (int) $m['id'] ?>">
  <h2>Media #<?= (int) $m['id'] ?></h2>
  <?php foreach (cfg('langs') as $l): ?>
    <div class="field"><label>alt (<?= $l ?>) *</label><input type="text" name="alt_<?= $l ?>" value="<?= View::e(\Nm\Media::alt((int) $m['id'], $l)) ?>" required></div>
  <?php endforeach; ?>
  <div class="field-row">
    <div class="field"><label>focal x</label><input type="number" step="0.05" min="0" max="1" name="focal_x" value="<?= (float) $m['focal_x'] ?>"></div>
    <div class="field"><label>focal y</label><input type="number" step="0.05" min="0" max="1" name="focal_y" value="<?= (float) $m['focal_y'] ?>"></div>
  </div>
  <button class="btn btn-primary btn-sm">Save</button>
</form>
<?php endif; endif; ?>
