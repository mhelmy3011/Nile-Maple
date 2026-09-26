/* category listing: client filter + load-more fragment (doc 03 §6) */
(function () {
  'use strict';
  var wrap = document.getElementById('grid-wrap');
  if (!wrap) return;
  var cat = wrap.getAttribute('data-cat');
  var pages = parseInt(wrap.getAttribute('data-pages'), 10) || 1;
  var page = parseInt(wrap.getAttribute('data-page'), 10) || 1;
  var btn = document.querySelector('[data-loadmore]');
  var q = document.querySelector('[data-filter-q]');
  var count = document.querySelector('.filter-count');

  function label(n) {
    if (!count) return;
    var tpl = count.textContent;
    count.textContent = tpl.replace(/\d+/, String(n));
  }
  function fetchPage(p, query) {
    var url = '/api/more?lang=' + window.NM.CFG.lang + '&cat=' + encodeURIComponent(cat) + '&page=' + p + '&q=' + encodeURIComponent(query || '');
    return fetch(url, { headers: { 'Accept': 'text/html' } }).then(function (r) {
      if (!r.ok) throw new Error('http ' + r.status);
      pages = parseInt(r.headers.get('X-NM-Pages') || '1', 10);
      return r.text();
    });
  }
  if (btn) btn.addEventListener('click', function () {
    btn.disabled = true;
    fetchPage(page + 1, q ? q.value : '').then(function (html) {
      var tmp = document.createElement('div'); tmp.innerHTML = html;
      while (tmp.firstElementChild) wrap.firstElementChild.appendChild(tmp.firstElementChild);
      page += 1;
      wrap.setAttribute('data-page', String(page));
      history.replaceState(null, '', '?page=' + page + (q && q.value ? '&q=' + encodeURIComponent(q.value) : ''));
      var total = wrap.firstElementChild.children.length;
      label(total);
      btn.disabled = false;
      if (page >= pages) btn.parentElement.style.display = 'none';
    }).catch(function () { btn.disabled = false; });
  });
  var sort = document.querySelector('[data-filter-sort]');
  var grid = wrap.firstElementChild;

  function applyFilter() {
    var v = q ? q.value.trim().toLowerCase() : '';
    var shown = 0;
    wrap.querySelectorAll('.card-product').forEach(function (c) {
      var hit = !v || c.textContent.toLowerCase().indexOf(v) > -1;
      c.style.display = hit ? '' : 'none';
      if (hit) shown++;
    });
    label(shown);
  }

  function applySort() {
    if (!sort || !grid) return;
    var cards = Array.from(grid.querySelectorAll('.card-product'));
    var mode = sort.value;
    if (mode === 'az') {
      cards.sort(function (a, b) {
        var na = (a.querySelector('.cp-name') || a).textContent.trim().toLowerCase();
        var nb = (b.querySelector('.cp-name') || b).textContent.trim().toLowerCase();
        return na.localeCompare(nb);
      });
    } else {
      cards.sort(function (a, b) {
        return (parseInt(a.getAttribute('data-order'), 10) || 0) - (parseInt(b.getAttribute('data-order'), 10) || 0);
      });
    }
    cards.forEach(function (c) { grid.appendChild(c); });
    applyFilter();
  }

  /* client-side instant filter over the loaded set (0 requests, doc 03 §6) */
  if (q) {
    var t;
    q.addEventListener('input', function () {
      clearTimeout(t);
      t = setTimeout(applyFilter, 120);
    });
  }
  if (sort) {
    sort.addEventListener('change', applySort);
  }

  /* store original order as data attributes */
  if (grid) {
    Array.from(grid.querySelectorAll('.card-product')).forEach(function (c, i) {
      c.setAttribute('data-order', String(i));
    });
  }
})();
