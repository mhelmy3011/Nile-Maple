<?php
use Nm\Csrf; use Nm\Icons; use Nm\View;
$base = cfg('admin.path') . "/$key";
$iconKeys = array_keys(\Nm\Icons::all());
/* Everything you typed is still in the fields below (Admin::flashFail) — this banner only
   explains why the save didn't go through. */
$errMsg = null;
if (!empty($err)) {
    if (str_starts_with((string) $err, 'incomplete:')) {
        $missing = explode(',', substr((string) $err, strlen('incomplete:')));
        $errMsg = 'Cannot publish — required in ' . strtoupper((string) cfg('default_lang')) . ': ' . implode(', ', $missing) . '. Save as draft, or fill these in first.';
    } else {
        $bad = array_filter(explode(',', (string) $err));
        if ($bad) $errMsg = 'Please check: ' . implode(', ', $bad) . '.';
    }
}
?>
<form class="adm-form" method="post" action="<?= $base ?>/<?= $id ? $id : 'new' ?>" data-autosave>
  <?= Csrf::field() ?>
  <?php if ($errMsg): ?>
    <p class="adm-toast err" role="alert"><?= View::e($errMsg) ?></p>
  <?php endif; ?>
  <div class="adm-formbar">
    <a class="btn btn-ghost btn-sm" href="<?= $base ?>">← Back</a>
    <span class="adm-save-state" role="status"></span>
    <button class="btn btn-primary btn-sm" type="submit">Save</button>
  </div>
  <div class="adm-formgrid">
    <section class="adm-card">
      <h2>Record</h2>
      <?php foreach ($e['fields'] as $f): $v = $row[$f['name']] ?? ''; ?>
        <div class="field">
          <label><?= View::e($f['name']) ?><?= !empty($f['req']) ? ' *' : '' ?></label>
          <?php if ($f['type'] === 'select'): ?>
            <?php $opts = is_callable($f['options']) ? ($f['options'])() : $f['options']; ?>
            <select name="<?= $f['name'] ?>"<?= !empty($f['lock']) && $id ? ' disabled' : '' ?>>
              <?php foreach ($opts as $o): ?>
                <option value="<?= View::e((string) $o[0]) ?>"<?= (string) $v === (string) $o[0] ? ' selected' : '' ?>><?= View::e((string) $o[1]) ?></option>
              <?php endforeach; ?>
            </select>
          <?php elseif ($f['type'] === 'bool'): ?>
            <label class="switch"><input type="checkbox" name="<?= $f['name'] ?>" value="1"<?= $v ? ' checked' : '' ?>> <span></span></label>
          <?php elseif ($f['type'] === 'media'): ?>
            <div class="media-pick">
              <input type="number" name="<?= $f['name'] ?>" value="<?= (int) $v ?>" min="0" data-media-input>
              <button class="btn btn-ghost btn-sm" type="button" data-media-open>Pick</button>
              <span class="media-prev"><?= $v ? '<img src="/assets/media/' . \Nm\Db::val('SELECT path FROM media_variant WHERE media_id=? AND fmt=\'webp\' ORDER BY w DESC LIMIT 1', [(int) $v]) . '" alt="">' : '' ?></span>
            </div>
          <?php elseif ($f['type'] === 'icon'): ?>
            <select name="<?= $f['name'] ?>">
              <?php foreach ($iconKeys as $ik): ?><option value="<?= $ik ?>"<?= $v === $ik ? ' selected' : '' ?>><?= $ik ?></option><?php endforeach; ?>
            </select>
          <?php elseif ($f['type'] === 'datetime'): ?>
            <input type="datetime-local" name="<?= $f['name'] ?>" value="<?= View::e($v ? str_replace(' ', 'T', substr((string) $v, 0, 16)) : '') ?>">
          <?php elseif ($f['type'] === 'num'): ?>
            <input type="number" step="0.1" name="<?= $f['name'] ?>" value="<?= View::e((string) $v) ?>">
          <?php elseif ($f['type'] === 'int'): ?>
            <input type="number" name="<?= $f['name'] ?>" value="<?= View::e((string) ($v === null ? '' : (int) $v)) ?>">
          <?php else: ?>
            <input type="text" name="<?= $f['name'] ?>" value="<?= View::e((string) $v) ?>"<?= !empty($f['lock']) && $id ? ' readonly' : '' ?>>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
    <section class="adm-card adm-i18n">
      <div class="adm-tabs" role="tablist">
        <?php foreach (cfg('langs') as $i => $l): ?>
          <button class="adm-tab<?= $i === 0 ? ' on' : '' ?>" type="button" role="tab" data-langtab="<?= $l ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
            <?= strtoupper($l) ?> <span class="dot-meter" data-meter="<?= $l ?>"></span></button>
        <?php endforeach; ?>
      </div>
      <?php foreach (cfg('langs') as $i => $l): ?>
        <div class="adm-langpane" data-pane="<?= $l ?>"<?= $i === 0 ? '' : ' hidden' ?><?= $l === 'ar' ? ' dir="rtl"' : '' ?>>
          <button class="btn btn-ghost btn-sm" type="button" data-copy-en data-for="<?= $l ?>">Copy from EN</button>
          <?php foreach ($e['i18n_fields'] as $f): $v = $i18n[$l][$f['name']] ?? ''; $req = !empty($f['req']) ? ' data-req' : ''; ?>
            <div class="field">
              <label><?= View::e($f['name']) ?><?= !empty($f['req']) ? ' *' : '' ?><?php if (!empty($f['max'])): ?> <span class="cnt-chars" data-max="<?= (int) $f['max'] ?>"></span><?php endif; ?></label>
              <?php if ($f['type'] === 'textarea'): ?>
                <textarea name="i18n[<?= $l ?>][<?= $f['name'] ?>]" rows="3"<?= $req ?> data-field="<?= $f['name'] ?>"><?= View::e((string) $v) ?></textarea>
              <?php elseif ($f['type'] === 'markdown'): ?>
                <textarea name="i18n[<?= $l ?>][<?= $f['name'] ?>]" rows="14" class="md"<?= $req ?> data-field="<?= $f['name'] ?>"><?= View::e((string) $v) ?></textarea>
                <p class="hint">Markdown-lite: # h2 · ## h3 · - list · 1. list · **bold** · *em* · [link](/path) · &gt; quote · | table |</p>
              <?php elseif ($f['type'] === 'rows'): $rowsv = json_decode((string) $v, true) ?: []; ?>
                <div class="rows-edit" data-rows="<?= $f['name'] ?>">
                  <?php foreach ($rowsv as $ri => $rv): ?><div class="row-line"><input type="text" name="i18n[<?= $l ?>][<?= $f['name'] ?>][]" value="<?= View::e((string) $rv) ?>"><button type="button" class="btn btn-ghost btn-sm" data-row-del>×</button></div><?php endforeach; ?>
                  <button class="btn btn-ghost btn-sm" type="button" data-row-add name="i18n[<?= $l ?>][<?= $f['name'] ?>]">+ row</button>
                </div>
              <?php else: ?>
                <input type="text" name="i18n[<?= $l ?>][<?= $f['name'] ?>]" value="<?= View::e((string) $v) ?>"<?= $req ?> data-field="<?= $f['name'] ?>"
                  <?= $f['type'] === 'slug' ? ' data-slug data-from="' . View::e($f['from'] ?? '') . '"' : '' ?>>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <details class="seo-panel"><summary>SEO panel</summary>
            <div class="serp" data-serp><span class="serp-url">nilemaple.com/<?= $l ?>/…</span><strong class="serp-title"></strong><span class="serp-desc"></span></div>
          </details>
        </div>
      <?php endforeach; ?>
    </section>
  </div>
</form>
