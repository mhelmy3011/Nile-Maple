/* dashboard JS: lang tabs, slug sync, counters, SERP preview, autosave draft, reorder, upload, media pick, rows editor */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* side nav (mobile) */
  var burger = $('.adm-burger'), side = $('#adm-side');
  if (burger && side) burger.addEventListener('click', function () { side.classList.toggle('open'); });

  /* language tabs */
  $$('[data-langtab]').forEach(function (b) {
    b.addEventListener('click', function () {
      var l = b.getAttribute('data-langtab');
      $$('[data-langtab]').forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-selected', x === b ? 'true' : 'false'); });
      $$('[data-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== l; });
    });
  });
  $$('[data-langtab2]').forEach(function (b) {
    b.addEventListener('click', function () {
      var l = b.getAttribute('data-langtab2');
      b.parentElement.querySelectorAll('[data-langtab2]').forEach(function (x) { x.classList.toggle('on', x === b); });
      b.closest('form').querySelectorAll('[data-pane2]').forEach(function (p) { p.hidden = p.getAttribute('data-pane2') !== l; });
    });
  });

  /* copy from EN */
  $$('[data-copy-en]').forEach(function (b) {
    b.addEventListener('click', function () {
      var target = b.getAttribute('data-for');
      $$('[data-pane="en"] [data-field]').forEach(function (src) {
        var dst = document.querySelector('[data-pane="' + target + '"] [data-field="' + src.getAttribute('data-field') + '"]');
        if (dst && !dst.value) dst.value = src.value;
      });
      updateMeters();
    });
  });

  /* slug sync + char counters + meters + SERP */
  function slugify(v, lang) {
    if (lang === 'ar') return v.trim().toLowerCase().replace(/[^؀-0-9]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    return v.trim().toLowerCase().replace(/[äöüß]/g, function (c) { return { ä: 'a', ö: 'o', ü: 'u', ß: 'ss' }[c]; })
      .replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
  }
  $$('[data-slug]').forEach(function (sl) {
    var pane = sl.closest('[data-pane]');
    var lang = pane ? pane.getAttribute('data-pane') : 'en';
    var from = pane ? pane.querySelector('[data-field="' + sl.getAttribute('data-from') + '"]') : null;
    if (from) from.addEventListener('input', function () { if (!sl.dataset.dirty) sl.value = slugify(from.value, lang); syncSerp(); });
    sl.addEventListener('input', function () { sl.dataset.dirty = '1'; });
  });
  function updateMeters() {
    $$('[data-pane]').forEach(function (pane) {
      var l = pane.getAttribute('data-pane');
      var req = $$('[data-req]', pane), filled = req.filter(function (f) { return f.value.trim() !== ''; });
      var m = document.querySelector('[data-meter="' + l + '"]');
      if (m) {
        var pct = req.length ? Math.round(100 * filled.length / req.length) : 100;
        m.className = 'dot-meter ' + (pct >= 100 ? 'ok' : 'no');
        m.title = pct + '%';
      }
    });
  }
  function syncSerp() {
    $$('.seo-panel').forEach(function (p) {
      var pane = p.closest('[data-pane]');
      var t = pane ? pane.querySelector('[data-field="meta_title"]') : null;
      var d = pane ? pane.querySelector('[data-field="meta_description"]') : null;
      var name = pane ? pane.querySelector('[data-field="name"],[data-field="title"]') : null;
      p.querySelector('.serp-title').textContent = (t && t.value) || (name ? name.value + ' | Nile-Maple' : '');
      p.querySelector('.serp-desc').textContent = (d && d.value) || '';
    });
  }
  $$('[data-max]').forEach(function (c) {
    var input = c.closest('.field').querySelector('input,textarea');
    var max = parseInt(c.getAttribute('data-max'), 10);
    var upd = function () { c.textContent = input.value.length + '/' + max; c.style.color = input.value.length > max ? 'var(--danger)' : ''; };
    input.addEventListener('input', upd); upd();
  });
  document.addEventListener('input', function (e) { updateMeters(); syncSerp(); });
  updateMeters(); syncSerp();

  /* rows editor */
  $$('[data-row-add]').forEach(function (b) {
    b.addEventListener('click', function () {
      var wrap = b.closest('.rows-edit');
      var line = document.createElement('div');
      line.className = 'row-line';
      line.innerHTML = '<input type="text" name="' + b.getAttribute('name') + '[]"><button type="button" class="btn btn-ghost btn-sm" data-row-del>×</button>';
      wrap.insertBefore(line, b);
    });
  });
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-row-del]')) e.target.closest('.row-line').remove();
  });

  /* check-all + bulk bar */
  var all = $('[data-check-all]');
  if (all) all.addEventListener('change', function () {
    $$('input[name="ids[]"]').forEach(function (c) { c.checked = all.checked; });
    var bar = $('.adm-bulkbar'); if (bar) bar.hidden = !all.checked;
  });

  /* drag reorder */
  $$('table[data-sortable]').forEach(function (table) {
    var body = table.tBodies[0];
    var drag = null;
    body.addEventListener('dragstart', function (e) { drag = e.target.closest('tr'); });
    body.addEventListener('dragover', function (e) {
      e.preventDefault();
      var over = e.target.closest('tr');
      if (!over || over === drag) return;
      var r = over.getBoundingClientRect();
      body.insertBefore(drag, (e.clientY - r.top) / r.height > .5 ? over.nextSibling : over);
    });
    body.addEventListener('dragend', function () {
      var ids = $$('tr', body).map(function (tr) { return tr.getAttribute('data-id'); });
      fetch(table.getAttribute('data-sortable'), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(ids) })
        .then(function () { location.reload(); });
    });
    $$('tr', body).forEach(function (tr) { tr.draggable = true; });
  });

  /* upload */
  var up = $('[data-upload]');
  if (up) up.addEventListener('submit', function (e) {
    e.preventDefault();
    var st = $('[data-upload-state]');
    st.textContent = 'uploading…';
    fetch(up.action, { method: 'POST', body: new FormData(up) })
      .then(function (r) { return r.json(); })
      .then(function (j) { st.textContent = j.ok ? 'done — ' + j.ids.length + ' file(s)' : 'error: ' + j.error; if (j.ok) setTimeout(function () { location.reload(); }, 600); })
      .catch(function () { st.textContent = 'network error'; });
  });

  /* media picker (prompt-based, minimal) */
  $$('[data-media-open]').forEach(function (b) {
    b.addEventListener('click', function () {
      var input = b.parentElement.querySelector('[data-media-input]');
      var id = prompt('Media ID (see Media library):', input.value || '');
      if (id !== null) { input.value = id; location.hash = ''; }
    });
  });

  /* autosave draft indicator + ctrl+s */
  var form = $('[data-autosave]');
  if (form) {
    var state = $('.adm-save-state');
    var t;
    form.addEventListener('input', function () {
      state.textContent = 'unsaved changes';
      clearTimeout(t);
      t = setTimeout(function () { state.textContent = 'draft kept in browser — press Save to publish'; }, 1200);
    });
    document.addEventListener('keydown', function (e) {
      if ((e.metaKey || e.ctrlKey) && e.key === 's') { e.preventDefault(); form.requestSubmit(); }
    });
    window.addEventListener('beforeunload', function (e) {
      if (state.textContent === 'unsaved changes') { e.preventDefault(); e.returnValue = ''; }
    });
  }
})();
