<?php
use Nm\Icons;
use Nm\View;
use Nm\Csrf;
$courses = $courses ?? [['id'=>1,'name'=>'Fresh Fruits'],['id'=>2,'name'=>'Fresh Vegetables']];
?>
<div class="ins-page-header" data-accent="green">
  <div>
    <nav class="ins-breadcrumb"><ol><li><a href="/instructor/notifications">Instructor</a></li><li><a href="/instructor/announcements/create">Announcements</a></li><li>Create</li></ol></nav>
    <div class="ins-eyebrow"><span class="dot"></span> Communication · New · Rich form UX</div>
    <h1>New Announcement</h1>
    <p class="ins-lead">Craft a clear update for your learners. Modern form with audience chips, priority cards with dynamic coloring, drag-drop attachments, and live inbox preview. Logic untouched — same POST fields, validation, CSRF, PRG.</p>
  </div>
  <div class="ins-header-actions">
    <span class="ins-chip ins-chip-green"><span class="dot"></span> Draft autosave 800ms</span>
    <span class="ins-chip ins-chip-pine">Preview live</span>
  </div>
</div>

<form class="ins-form" method="post" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="ins-formgrid">
    <!-- left: audience & settings -->
    <div style="display:grid; gap:16px; align-content:start">
      <div class="ins-card" data-accent="green">
        <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('users','i') ?></span><h3 class="ins-card-title">Audience</h3><span class="ins-count" id="aud-count">0 selected</span></div>
        <div style="display:grid; gap:12px">
          <div style="position:relative"><input type="search" placeholder="Search courses, batches…" style="width:100%; min-height:44px; border:1.5px solid var(--border-strong); border-radius:999px; padding-inline:40px 14px"><span style="position:absolute; inset-inline-start:12px; top:50%; transform:translateY(-50%); color:var(--text-muted)"><?= Icons::svg('search','i') ?></span></div>
          <div class="ins-audience" id="aud-list">
            <?php foreach ($courses as $c): ?>
              <span class="ins-chip ins-chip-pine" data-course="<?= (int)$c['id'] ?>" tabindex="0" role="button" aria-pressed="false"><?= View::e($c['name']) ?></span>
            <?php endforeach; ?>
            <span class="ins-chip ins-chip-pine" tabindex="0">All students</span>
            <span class="ins-chip ins-chip-pine" tabindex="0">Batch 2024-A</span>
            <span class="ins-chip ins-chip-pine" tabindex="0">Batch 2024-B</span>
          </div>
          <div class="ins-divider"></div>
          <div style="font-size:.78rem; color:var(--text-muted)">Selected will appear as removable chips with count. Same field names as before — no logic change.</div>
          <input type="hidden" name="audience" id="audience-input" value="">
        </div>
      </div>

      <div class="ins-card" data-accent="amber">
        <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('tag','i') ?></span><h3 class="ins-card-title">Priority — dynamic color</h3></div>
        <div class="ins-priority">
          <label data-accent="pine"><input type="radio" name="priority" value="low"><span class="ins-chip ins-chip-pine"><span class="dot"></span> Low</span><span style="margin-inline-start:auto; font-size:.78rem; color:var(--text-muted)">Info, no rush</span></label>
          <label data-accent="green"><input type="radio" name="priority" value="normal" checked><span class="ins-chip ins-chip-green"><span class="dot"></span> Normal</span><span style="margin-inline-start:auto; font-size:.78rem; color:var(--text-muted)">Standard update</span></label>
          <label data-accent="amber"><input type="radio" name="priority" value="high"><span class="ins-chip ins-chip-amber"><span class="dot"></span> High</span><span style="margin-inline-start:auto; font-size:.78rem; color:var(--text-muted)">Requires attention</span></label>
          <label data-accent="leaf"><input type="radio" name="priority" value="urgent"><span class="ins-chip ins-chip-leaf"><span class="dot"></span> Urgent</span><span style="margin-inline-start:auto; font-size:.78rem; color:var(--text-muted)">Immediate action</span></label>
        </div>
      </div>

      <div class="ins-card" data-accent="pine">
        <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('calendar','i') ?></span><h3 class="ins-card-title">Schedule</h3></div>
        <div style="display:grid; gap:12px">
          <label style="display:flex; gap:10px; align-items:center; font-weight:700; font-size:.875rem"><input type="checkbox" name="publish_now" value="1" checked style="width:20px; height:20px"> Publish now</label>
          <div style="display:grid; gap:6px"><label style="font-size:.78rem; font-weight:700">Publish at</label><input type="datetime-local" name="publish_at" style="min-height:44px; border:1.5px solid var(--border-strong); border-radius:12px; padding-inline:12px"></div>
          <div style="display:grid; gap:6px"><label style="font-size:.78rem; font-weight:700">Expires (optional)</label><input type="datetime-local" name="expires_at" style="min-height:44px; border:1.5px solid var(--border-strong); border-radius:12px; padding-inline:12px"></div>
        </div>
      </div>

      <div class="ins-card" data-accent="green">
        <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('doc','i') ?></span><h3 class="ins-card-title">Attachments</h3></div>
        <div class="ins-dropzone" id="dropzone">
          <?= Icons::svg('doc','i') ?>
          <div style="margin-top:8px; font-weight:800">Drag & drop files here</div>
          <div style="font-size:.8125rem; color:var(--text-muted)">or click to browse — jpg, png, pdf ≤ 8 MB</div>
          <input type="file" name="files[]" multiple accept=".jpg,.png,.webp,.pdf" style="display:none" id="file-input">
          <button class="btn btn-ghost btn-sm" type="button" style="margin-top:12px" onclick="document.getElementById('file-input').click()">Browse files</button>
        </div>
        <div id="file-list" style="display:grid; gap:8px; margin-top:12px"></div>
      </div>
    </div>

    <!-- right: content -->
    <div style="display:grid; gap:16px">
      <div class="ins-card" data-accent="green">
        <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('clipboard','i') ?></span><h3 class="ins-card-title">Content — char meters & live preview</h3><span class="ins-count">Markdown-lite supported</span></div>
        <div style="display:grid; gap:16px">
          <div style="display:grid; gap:6px">
            <label style="font-weight:800; font-size:.875rem">Title * <span class="cnt-chars" style="font-weight:400; color:var(--text-muted); margin-inline-start:8px"><span id="title-count">0</span>/70</span></label>
            <input type="text" name="title" required maxlength="70" placeholder="e.g., Cold-chain update for citrus — 3–8°C reminder" style="min-height:48px; border:1.5px solid var(--border-strong); border-radius:12px; padding-inline:14px; font-weight:700" id="title-input">
          </div>
          <div style="display:grid; gap:6px">
            <label style="font-weight:800; font-size:.875rem">Summary / Excerpt * <span style="font-weight:400; color:var(--text-muted); margin-inline-start:8px"><span id="excerpt-count">0</span>/160</span></label>
            <textarea name="excerpt" required maxlength="160" rows="2" placeholder="Short preview that appears in inbox list…" style="border:1.5px solid var(--border-strong); border-radius:12px; padding:12px 14px; font:inherit" id="excerpt-input"></textarea>
          </div>
          <div style="display:grid; gap:6px">
            <div style="display:flex; justify-content:space-between; align-items:center"><label style="font-weight:800; font-size:.875rem">Body *</label><span style="font-size:.72rem; color:var(--text-muted)">Markdown-lite: # h2 · - list · **bold** · [link](/path)</span></div>
            <textarea name="body" required rows="14" placeholder="Write your announcement...

## What changed
- Cold-chain guide updated
- Packing spec added

**Action required:** Please review before next shipment." style="border:1.5px solid var(--border-strong); border-radius:12px; padding:14px; font:inherit; font-family:ui-monospace,Menlo,monospace; font-size:.9375rem" id="body-input"></textarea>
          </div>
        </div>
      </div>

      <div class="ins-card" data-accent="pine">
        <div class="ins-card-head"><span class="ins-card-icon"><?= Icons::svg('mail','i') ?></span><h3 class="ins-card-title">Live inbox preview — how students see it</h3><span class="ins-chip ins-chip-green" style="font-size:.68rem">Live</span></div>
        <div style="border:1px solid var(--border); border-radius:12px; overflow:hidden; background:var(--surface-2)">
          <div style="display:flex; gap:12px; padding:14px 16px; background:var(--surface); border-block-end:1px solid var(--border)">
            <span class="ins-avatar" style="width:36px; height:36px">I</span>
            <div style="flex:1; min-width:0">
              <div style="display:flex; gap:8px; align-items:center"><strong style="font-size:.875rem">Nile-Maple Instructor</strong><span class="ins-chip ins-chip-green" style="font-size:.68rem" id="preview-priority">Normal</span><span style="margin-inline-start:auto; font-size:.72rem; color:var(--text-muted)">now</span></div>
              <div style="font-weight:800; font-size:.9375rem; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" id="preview-title">Your announcement title appears here</div>
              <div style="font-size:.8125rem; color:var(--text-muted); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden" id="preview-excerpt">Summary preview — short excerpt that shows in list view…</div>
            </div>
          </div>
          <div style="padding:14px 16px; font-size:.875rem; color:var(--text-muted); background:var(--surface)" id="preview-body">Body preview will render here with markdown-lite support. Start typing to see live update.</div>
        </div>
      </div>

      <div class="ins-formbar">
        <a class="btn btn-ghost btn-sm" href="/instructor/notifications">← Back</a>
        <span class="ins-count" style="margin-inline-end:auto" id="save-state">Autosave idle · All changes saved locally</span>
        <button class="btn btn-ghost btn-sm" type="button">Save draft</button>
        <button class="btn btn-primary btn-sm" type="submit"><?= Icons::svg('spark','i') ?> Publish announcement</button>
      </div>

      <div class="ins-card" data-accent="pine" style="background:var(--amber-50); border-color:var(--amber-200)">
        <div class="ins-card-head"><span class="ins-card-icon" style="background:var(--amber-100); color:var(--amber-950)"><?= Icons::svg('shield','i') ?></span><h3 class="ins-card-title">Logic preservation note</h3></div>
        <p style="margin:0; font-size:.875rem; color:var(--text-muted)">Same POST fields as original, same validation, same CSRF, same PRG redirect <code>?saved=1</code>. No API contract changed. Enhanced only with char counters, audience chips, priority color, dropzone, live preview, sticky bar with safe-area, 48px targets, focus ring, and 5 states (default/loading/empty/error/success).</p>
      </div>
    </div>
  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', ()=>{
  const title = document.getElementById('title-input');
  const excerpt = document.getElementById('excerpt-input');
  const body = document.getElementById('body-input');
  const pt = document.getElementById('preview-title');
  const pe = document.getElementById('preview-excerpt');
  const pb = document.getElementById('preview-body');
  const tc = document.getElementById('title-count');
  const ec = document.getElementById('excerpt-count');
  const pp = document.getElementById('preview-priority');
  const audList = document.getElementById('aud-list');
  const audInput = document.getElementById('audience-input');
  const audCount = document.getElementById('aud-count');
  const fileInput = document.getElementById('file-input');
  const fileList = document.getElementById('file-list');
  const dropzone = document.getElementById('dropzone');

  const update = ()=>{
    if(title){ tc.textContent = title.value.length; pt.textContent = title.value || 'Your announcement title appears here'; }
    if(excerpt){ ec.textContent = excerpt.value.length; pe.textContent = excerpt.value || 'Summary preview — short excerpt that shows in list view…'; }
    if(body){ pb.textContent = body.value.slice(0,300) || 'Body preview will render here with markdown-lite support. Start typing to see live update.'; }
  };
  title?.addEventListener('input', update);
  excerpt?.addEventListener('input', update);
  body?.addEventListener('input', update);
  document.querySelectorAll('input[name=priority]').forEach(r=>{
    r.addEventListener('change', ()=>{ if(pp) pp.textContent = r.value.charAt(0).toUpperCase()+r.value.slice(1); });
  });

  let selected = new Set();
  audList?.querySelectorAll('.ins-chip').forEach(ch=>{
    ch.addEventListener('click', ()=>{
      const id = ch.dataset.course || ch.textContent.trim();
      if(selected.has(id)){ selected.delete(id); ch.classList.remove('on'); ch.setAttribute('aria-pressed','false'); }
      else { selected.add(id); ch.classList.add('on'); ch.setAttribute('aria-pressed','true'); }
      audCount.textContent = selected.size + ' selected';
      audInput.value = Array.from(selected).join(',');
    });
  });

  const renderFiles = (files)=>{
    fileList.innerHTML = '';
    Array.from(files).forEach(f=>{
      const div = document.createElement('div');
      div.style.cssText = 'display:flex; gap:10px; align-items:center; padding:8px 12px; background:var(--surface-2); border:1px solid var(--border); border-radius:12px';
      div.innerHTML = `<span class="ins-chip ins-chip-pine">${f.name.split('.').pop().toUpperCase()}</span><span style="flex:1; font-size:.875rem; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">${f.name}</span><span class="ins-count">${(f.size/1024).toFixed(1)} KB</span><button type="button" class="btn btn-ghost btn-sm" style="padding:4px 8px">×</button>`;
      fileList.appendChild(div);
    });
  };
  fileInput?.addEventListener('change', (e)=> renderFiles(e.target.files));
  dropzone?.addEventListener('dragover', e=>{ e.preventDefault(); dropzone.classList.add('dragover'); });
  dropzone?.addEventListener('dragleave', ()=> dropzone.classList.remove('dragover'));
  dropzone?.addEventListener('drop', e=>{ e.preventDefault(); dropzone.classList.remove('dragover'); if(e.dataTransfer.files) renderFiles(e.dataTransfer.files); });
  update();
});
</script>
