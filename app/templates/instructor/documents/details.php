<?php
use Nm\Icons;
use Nm\View;
use Nm\Csrf;
$doc = $doc ?? ['name'=>'export-spec.pdf','type'=>'pdf','size'=>'1.2 MB','modified'=>date('Y-m-d H:i:s'),'owner'=>'Instructor','status'=>'published','course'=>'Fresh Fruits','views'=>0,'downloads'=>0,'id'=>1];
$kpis = $kpis ?? [];
$versions = $versions ?? [];
$sharing = $sharing ?? [];
$comments = $comments ?? [];
$typeAccent = match(strtolower($doc['type'])){'pdf'=>'leaf','docx'=>'pine','doc'=>'pine','xlsx'=>'green','xls'=>'green','jpg'=>'amber','png'=>'amber','webp'=>'amber', default=>'pine'};
$statusAccent = match($doc['status']){'published'=>'green','draft'=>'pine','pending'=>'amber','rejected'=>'leaf', default=>'green'};
?>
<div class="ins-page-header" data-accent="<?= $typeAccent ?>">
  <div>
    <nav class="ins-breadcrumb"><ol><li><a href="/instructor/notifications">Instructor</a></li><li><a href="/instructor/documents/details">Documents</a></li><li>Details</li></ol></nav>
    <div class="ins-eyebrow"><span class="dot"></span> Document · <?= View::e(strtoupper($doc['type'])) ?> · Dynamic file-type coloring</div>
    <h1><?= View::e($doc['name']) ?></h1>
    <p class="ins-lead">Modern document viewer with file-type accent, version timeline, sharing, and comments. No logic change — same media handling, alt per lang, focal point, delete guard.</p>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px">
      <span class="ins-chip ins-chip-<?= $typeAccent ?>"><span class="dot"></span> <?= View::e(strtoupper($doc['type'])) ?></span>
      <span class="ins-chip ins-chip-<?= $statusAccent ?>"><span class="dot"></span> <?= View::e(ucfirst($doc['status'])) ?></span>
      <span class="ins-chip ins-chip-mono"><?= View::e($doc['size']) ?> · Modified <?= View::e(date('M j, Y', strtotime($doc['modified']))) ?></span>
      <span class="ins-chip" style="background:var(--surface)"><?= View::e($doc['course']) ?></span>
    </div>
  </div>
  <div class="ins-header-actions">
    <a class="btn btn-primary btn-sm" href="#" download><?= Icons::svg('doc','i') ?> Download</a>
    <button class="btn btn-ghost btn-sm" type="button"><?= Icons::svg('users','i') ?> Share</button>
    <button class="btn btn-ghost btn-sm" type="button"><?= Icons::svg('clipboard','i') ?> Copy link</button>
  </div>
</div>

<div class="ins-kpi-strip">
  <?php foreach ($kpis as $k): ?>
    <div class="ins-kpi" data-accent="<?= View::e($k['accent']) ?>">
      <div class="ins-kpi-head"><span class="ins-kpi-icon"><?= Icons::svg($k['icon'],'i') ?></span><span class="ins-chip ins-chip-<?= $k['accent'] ?>" style="font-size:.68rem"><?= View::e($k['trend']) ?></span></div>
      <div class="ins-kpi-v"><?= View::e((string)$k['v']) ?></div>
      <div class="ins-kpi-l"><?= View::e($k['l']) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="ins-formgrid">
  <div style="display:grid; gap:16px">
    <div class="ins-card" data-accent="<?= $typeAccent ?>" style="padding:0; overflow:hidden">
      <div class="ins-doc-preview" style="border:none; border-radius:0">
        <div style="display:grid; gap:12px; justify-items:center; text-align:center; padding:24px">
          <span style="width:72px; height:72px; border-radius:20px; background:var(--<?= $typeAccent==='leaf'?'leaf-100':($typeAccent==='amber'?'amber-100':($typeAccent==='green'?'green-100':'pine-100')) ?>); display:grid; place-items:center; color:var(--<?= $typeAccent==='leaf'?'leaf-600':($typeAccent==='amber'?'amber-950':($typeAccent==='green'?'green-800':'pine-800')) ?>)">
            <?= Icons::svg('doc','i') ?>
          </span>
          <div>
            <strong style="display:block; font-size:1rem"><?= View::e($doc['name']) ?></strong>
            <small style="color:var(--text-muted)"><?= View::e($doc['mime']) ?> · <?= View::e($doc['size']) ?></small>
          </div>
          <div class="ins-actions">
            <span class="ins-chip ins-chip-<?= $typeAccent ?>">Preview</span>
            <span class="ins-chip ins-chip-pine">Page 1 of 12</span>
          </div>
        </div>
        <span class="ins-doc-badge"><?= View::e($doc['type']) ?> · Preview</span>
      </div>
      <div style="display:flex; gap:8px; padding:12px 16px; border-block-start:1px solid var(--border); background:var(--surface-2)">
        <button class="btn btn-ghost btn-sm" type="button">Zoom</button>
        <button class="btn btn-ghost btn-sm" type="button">Fullscreen</button>
        <span class="ins-count" style="margin-inline-start:auto">Secure preview · No download in preview</span>
      </div>
    </div>

    <div class="ins-card" data-accent="pine">
      <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('clipboard','i') ?></span><h3 class="ins-card-title">Version history — timeline</h3></div>
      <ol class="ins-timeline">
        <?php foreach ($versions as $v): ?>
          <li class="ins-tl-item">
            <span class="ins-tl-dot" style="<?= $v['current'] ? 'background:var(--green-500); color:var(--pine-950); border-color:var(--green-500)' : 'border-color:var(--n-300)' ?>"><?= Icons::svg($v['current']?'check':'clock','i') ?></span>
            <div class="ins-tl-card" style="<?= $v['current'] ? 'border-color:var(--green-200); background:var(--green-50)' : '' ?>">
              <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap">
                <strong style="font-size:.875rem"><?= View::e($v['v']) ?></strong>
                <?php if ($v['current']): ?><span class="ins-chip ins-chip-green" style="font-size:.68rem">Current</span><?php endif; ?>
                <span class="ins-chip ins-chip-pine" style="font-size:.68rem"><?= View::e($v['author']) ?></span>
                <span class="ins-tl-time" style="margin-inline-start:auto"><?= View::e($v['date']) ?></span>
              </div>
              <span style="font-size:.8125rem; color:var(--text-muted)"><?= View::e($v['note']) ?></span>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>

  <div style="display:grid; gap:16px; align-content:start">
    <div class="ins-card" data-accent="pine">
      <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('tag','i') ?></span><h3 class="ins-card-title">Properties</h3></div>
      <dl style="display:grid; grid-template-columns:auto 1fr; gap:8px 12px; margin:0; font-size:.875rem">
        <dt style="color:var(--text-muted); font-weight:700">File</dt><dd style="margin:0; font-weight:700" class="ins-chip-mono"><?= View::e($doc['name']) ?></dd>
        <dt style="color:var(--text-muted); font-weight:700">Type</dt><dd style="margin:0"><span class="ins-chip ins-chip-<?= $typeAccent ?>"><?= View::e($doc['type']) ?></span></dd>
        <dt style="color:var(--text-muted); font-weight:700">Size</dt><dd style="margin:0"><?= View::e($doc['size']) ?></dd>
        <dt style="color:var(--text-muted); font-weight:700">Owner</dt><dd style="margin:0"><?= View::e($doc['owner']) ?></dd>
        <dt style="color:var(--text-muted); font-weight:700">Modified</dt><dd style="margin:0" class="ins-chip-mono"><?= View::e($doc['modified']) ?></dd>
        <dt style="color:var(--text-muted); font-weight:700">Course</dt><dd style="margin:0"><?= View::e($doc['course']) ?></dd>
        <dt style="color:var(--text-muted); font-weight:700">ID</dt><dd style="margin:0"><span class="ins-chip ins-chip-mono">#<?= (int)$doc['id'] ?></span></dd>
      </dl>
      <div class="ins-divider"></div>
      <form method="post" action="/manage/media/save" style="display:grid; gap:10px">
        <?= Csrf::field() ?>
        <input type="hidden" name="media_id" value="<?= (int)$doc['id'] ?>">
        <div style="font-weight:800; font-size:.875rem">Alt text per language — required (logic preserved)</div>
        <?php foreach (['en','ar','fr'] as $l): ?>
          <div class="field" style="display:grid; gap:6px">
            <label style="font-size:.78rem; font-weight:700">alt (<?= $l ?>) *</label>
            <input type="text" name="alt_<?= $l ?>" value="Export spec — <?= View::e($doc['name']) ?>" required style="min-height:44px; border:1.5px solid var(--border-strong); border-radius:12px; padding-inline:12px">
          </div>
        <?php endforeach; ?>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px">
          <div class="field" style="display:grid; gap:6px"><label style="font-size:.78rem; font-weight:700">focal x</label><input type="number" step="0.05" min="0" max="1" name="focal_x" value="0.5" style="min-height:44px; border:1.5px solid var(--border-strong); border-radius:12px; padding-inline:12px"></div>
          <div class="field" style="display:grid; gap:6px"><label style="font-size:.78rem; font-weight:700">focal y</label><input type="number" step="0.05" min="0" max="1" name="focal_y" value="0.5" style="min-height:44px; border:1.5px solid var(--border-strong); border-radius:12px; padding-inline:12px"></div>
        </div>
        <button class="btn btn-primary btn-sm" type="submit">Save properties</button>
      </form>
    </div>

    <div class="ins-card" data-accent="green">
      <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('users','i') ?></span><h3 class="ins-card-title">Sharing</h3><span class="ins-count"><?= count($sharing) ?> members</span></div>
      <div style="display:grid; gap:8px">
        <?php foreach ($sharing as $s): ?>
          <div style="display:flex; gap:10px; align-items:center; padding:8px 12px; background:var(--surface-2); border-radius:12px">
            <span class="ins-avatar" style="width:32px; height:32px; font-size:.8rem"><?= View::e($s['avatar']) ?></span>
            <div style="flex:1"><strong style="font-size:.875rem"><?= View::e($s['name']) ?></strong><br><small style="color:var(--text-muted)"><?= View::e($s['role']) ?></small></div>
            <span class="ins-chip ins-chip-green" style="font-size:.68rem">Can view</span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="ins-card-actions"><button class="btn btn-ghost btn-sm">Invite</button><button class="btn btn-ghost btn-sm">Manage access</button></div>
    </div>

    <div class="ins-card" data-accent="amber">
      <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('clipboard','i') ?></span><h3 class="ins-card-title">Comments</h3><span class="ins-count"><?= count($comments) ?> threads</span></div>
      <div style="display:grid; gap:10px">
        <?php foreach ($comments as $c): ?>
          <div style="display:grid; gap:6px; padding:12px; border:1px solid var(--border); border-radius:12px; background:var(--surface)">
            <div style="display:flex; gap:8px; align-items:center"><span class="ins-avatar" style="width:28px; height:28px; font-size:.72rem"><?= View::e($c['avatar']) ?></span><strong style="font-size:.875rem"><?= View::e($c['author']) ?></strong><span class="ins-tl-time" style="margin-inline-start:auto"><?= View::e($c['time']) ?></span><?php if ($c['resolved']): ?><span class="ins-chip ins-chip-green" style="font-size:.68rem">Resolved</span><?php endif; ?></div>
            <p style="margin:0; font-size:.875rem; color:var(--text-muted)"><?= View::e($c['text']) ?></p>
          </div>
        <?php endforeach; ?>
        <div style="display:flex; gap:8px"><input type="text" placeholder="Add a comment…" style="flex:1; min-height:44px; border:1.5px solid var(--border-strong); border-radius:999px; padding-inline:14px"><button class="btn btn-primary btn-sm">Post</button></div>
      </div>
    </div>
  </div>
</div>
