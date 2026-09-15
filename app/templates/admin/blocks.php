<?php use Nm\View; ?>
<form method="get" class="adm-actions"><select name="zone" onchange="this.form.submit()">
<?php foreach ($zones as $z): ?><option value="<?= View::e($z['zone']) ?>"<?= $z['zone'] === $zone ? ' selected' : '' ?>><?= View::e($z['zone']) ?></option><?php endforeach; ?>
</select></form>
<?php foreach ($blocks as $b): ?>
<form class="adm-card" method="post" action="<?= cfg('admin.path') ?>/blocks">
  <?= \Nm\Csrf::field() ?>
  <input type="hidden" name="zone" value="<?= View::e($zone) ?>">
  <input type="hidden" name="block_id" value="<?= (int) $b['base']['id'] ?>">
  <h2>#<?= (int) $b['base']['id'] ?> · <?= View::e($b['base']['kind']) ?> · <?= View::e($b['base']['zone']) ?></h2>
  <p class="hint">Base payload (JSON, structure): <code><?= View::e(mb_substr((string) $b['base']['payload'], 0, 120)) ?>…</code></p>
  <div class="adm-tabs"><?php foreach (cfg('langs') as $i => $l): ?>
    <button class="adm-tab<?= $i === 0 ? ' on' : '' ?>" type="button" data-langtab2="<?= $l ?>"><?= strtoupper($l) ?></button><?php endforeach; ?></div>
  <?php foreach (cfg('langs') as $i => $l): $lr = $b['langs'][$l] ?? []; ?>
    <div data-pane2="<?= $l ?>"<?= $i === 0 ? '' : ' hidden' ?>>
      <div class="field"><label>title</label><input type="text" name="title_<?= $l ?>" value="<?= View::e((string) ($lr['title'] ?? '')) ?>"></div>
      <div class="field"><label>eyebrow</label><input type="text" name="eyebrow_<?= $l ?>" value="<?= View::e((string) ($lr['eyebrow'] ?? '')) ?>"></div>
      <div class="field"><label>payload (<?= $l ?>)</label><textarea name="payload_<?= $l ?>" rows="8" class="mono"><?= View::e((string) ($lr['payload'] ?? $b['base']['payload'])) ?></textarea></div>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-primary btn-sm">Save block</button>
</form>
<?php endforeach; ?>
