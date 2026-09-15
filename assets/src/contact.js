/* enquiry form: inline validation + fetch submit (doc 03 §9) */
/* keep the CSRF token live: the page itself may have been built (and cached) hours ago, so the
   form asks /api/csrf for a fresh visit-bound token on load and right before submit. */
(function () {
  'use strict';
  var form = document.getElementById('enquiry-form');
  if (!form) return;
  var input = form.querySelector('input[name="_csrf"]');
  if (!input) return;
  var api = (function () { try { return (JSON.parse(document.getElementById('nm-cfg').textContent) || {}).api || '/api'; } catch (e) { return '/api'; } })();
  function pull() {
    return fetch(api + '/csrf', { credentials: 'same-origin' }).then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) { if (j && j.token) input.value = j.token; return j && j.token; }).catch(function () { return null; });
  }
  pull();
  form.addEventListener('submit', function () { if (!input.value || input.value.length !== 64) pull(); });
})();
(function () {
  'use strict';
  var form = document.getElementById('enquiry-form');
  if (!form) return;
  var errBox = form.querySelector('.form-error');
  var okBox = form.querySelector('.form-ok');
  var submit = form.querySelector('[data-submit]');

  function setInvalid(field, on, msg) {
    var wrap = field.closest('.field');
    if (!wrap) return;
    wrap.classList.toggle('is-invalid', on);
    var e = wrap.querySelector('.err');
    if (on) {
      if (!e) { e = document.createElement('span'); e.className = 'err'; e.setAttribute('role', 'alert'); wrap.appendChild(e); }
      e.textContent = msg || '✕';
      field.setAttribute('aria-invalid', 'true');
    } else if (e) { e.remove(); field.removeAttribute('aria-invalid'); }
  }
  form.querySelectorAll('input,select,textarea').forEach(function (f) {
    f.addEventListener('blur', function () { validateOne(f); });
    f.addEventListener('input', function () { if (f.closest('.field') && f.closest('.field').classList.contains('is-invalid')) validateOne(f); });
  });
  function validateOne(f) {
    if (f.name === 'website') return true;
    var v = (f.value || '').trim();
    var bad = false, msg = '';
    if (f.required && f.type !== 'checkbox' && !v) { bad = true; }
    if (f.type === 'checkbox' && f.required && !f.checked) { bad = true; }
    if (f.type === 'email' && v && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) { bad = true; msg = '✕'; }
    if (f.name === 'message' && v && v.length < 10) { bad = true; }
    setInvalid(f, bad, msg);
    return !bad;
  }
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    okBox.hidden = true; errBox.hidden = true;
    var ok = true, first = null;
    form.querySelectorAll('input,select,textarea').forEach(function (f) {
      if (f.name === 'website') return;
      if (!validateOne(f)) { ok = false; first = first || f; }
    });
    if (!ok) { first.focus(); errBox.textContent = form.getAttribute('data-invalid-msg') || '…'; errBox.hidden = false; errBox.scrollIntoView({ block: 'center' }); return; }
    submit.disabled = true; submit.setAttribute('aria-busy', 'true');
    var fd = new FormData(form);
    fetch(form.action, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json().then(function (j) { return { s: r.status, j: j }; }); })
      .then(function (res) {
        submit.disabled = false; submit.removeAttribute('aria-busy');
        if (res.s === 200 && res.j.ok) {
          form.querySelectorAll('.field,button[type=submit]').forEach(function (el) { el.style.display = 'none'; });
          okBox.hidden = false; okBox.scrollIntoView({ block: 'center' }); okBox.focus && okBox.focus();
          try { navigator.sendBeacon('/api/event', new URLSearchParams({ name: 'enquiry_success', lang: window.NM.CFG.lang, path: location.pathname })); } catch (e2) {}
        } else {
          errBox.textContent = res.j.error || '…';
          if (res.j.fields) Object.keys(res.j.fields).forEach(function (k) { var f = form.elements[k]; if (f) setInvalid(f, true); });
          errBox.hidden = false; errBox.scrollIntoView({ block: 'center' });
        }
      })
      .catch(function () { submit.disabled = false; errBox.textContent = 'network'; errBox.hidden = false; });
  });
})();
