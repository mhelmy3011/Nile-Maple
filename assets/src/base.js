/* Nile-Maple base.js — zero deps, ES2020. doc 04 §5 */
(function () {
  'use strict';
  document.documentElement.classList.add('js');
  var CFG = (function () { try { return JSON.parse(document.getElementById('nm-cfg').textContent); } catch (e) { return { lang: 'en', dir: 'ltr', api: '/api' }; } })();

  /* header hide-on-scroll-down */
  var header = document.getElementById('header');
  if (header) {
    var lastY = 0;
    var onScroll = function () {
      var y = window.scrollY;
      header.classList.toggle('is-scrolled', y > 8);
      if (y > 96 && y > lastY + 12) header.classList.add('is-hidden');
      else if (y < lastY - 4) header.classList.remove('is-hidden');
      lastY = y;
    };
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* bottom sheet menu */
  var sheet = document.getElementById('sheet');
  if (sheet) {
    var btn = document.querySelector('[data-sheet-open]');
    var open = function (on) {
      sheet.hidden = !on;
      document.body.style.overflow = on ? 'hidden' : '';
      if (btn) btn.setAttribute('aria-expanded', on ? 'true' : 'false');
      if (on) { var f = sheet.querySelector('a,button'); if (f) f.focus(); }
      else if (btn) btn.focus();
    };
    if (btn) btn.addEventListener('click', function () { open(sheet.hidden); });
    sheet.querySelectorAll('[data-sheet-close]').forEach(function (el) { el.addEventListener('click', function () { open(false); }); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !sheet.hidden) open(false); });
  }

  /* language dropdown */
  document.querySelectorAll('[data-langswitch]').forEach(function (sw) {
    var btn = sw.querySelector('.lang-btn'), menu = sw.querySelector('.lang-menu');
    var set = function (on) { menu.hidden = !on; btn.setAttribute('aria-expanded', on ? 'true' : 'false'); };
    btn.addEventListener('click', function (e) { e.stopPropagation(); set(menu.hidden); });
    document.addEventListener('click', function () { set(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
  });

  /* command bar after 40vh */
  var cb = document.getElementById('commandbar');
  if (cb) {
    var toggle = function () { cb.classList.toggle('is-visible', window.scrollY > window.innerHeight * 0.4); };
    window.addEventListener('scroll', toggle, { passive: true }); toggle();
  }

  /* cookie sheet */
  var cookie = document.getElementById('cookie');
  if (cookie && !/nm_cookie=/.test(document.cookie)) {
    setTimeout(function () { cookie.hidden = false; }, 1200);
    cookie.querySelectorAll('[data-cookie]').forEach(function (b) {
      b.addEventListener('click', function () {
        document.cookie = 'nm_cookie=1;max-age=' + 60 * 60 * 24 * 180 + ';path=/;samesite=lax';
        cookie.hidden = true;
      });
    });
  }
  /* remember language preference */
  document.querySelectorAll('.lang-menu a').forEach(function (a) {
    a.addEventListener('click', function () {
      var l = a.getAttribute('href').split('/')[1];
      document.cookie = 'nm_lang=' + l + ';max-age=31536000;path=/;samesite=lax';
    });
  });

  /* scroll-snap carousels: dots */
  document.querySelectorAll('[data-carousel]').forEach(function (car) {
    var track = car.querySelector('.hero-track, .rail-track');
    var dots = car.parentElement ? document.querySelector('.hero-dots') : null;
    if (!track) return;
    if (car.classList.contains('hero') || car.closest('.hero')) {
      dots = document.querySelector('.hero-dots');
      if (dots && track.children.length > 1) {
        dots.innerHTML = '';
        Array.prototype.forEach.call(track.children, function (slide, i) {
          var b = document.createElement('button');
          b.type = 'button'; b.setAttribute('aria-label', 'slide ' + (i + 1));
          if (i === 0) b.setAttribute('aria-current', 'true');
          b.addEventListener('click', function () { track.scrollTo({ left: slide.offsetLeft, behavior: 'smooth' }); });
          dots.appendChild(b);
        });
        var sync = function () {
          var i = Math.round(track.scrollLeft / track.clientWidth);
          dots.querySelectorAll('button').forEach(function (b, j) { if (j === i) b.setAttribute('aria-current', 'true'); else b.removeAttribute('aria-current'); });
        };
        track.addEventListener('scroll', sync, { passive: true });
      }
    }
  });

  /* tabs */
  document.querySelectorAll('[data-tabs]').forEach(function (tabs) {
    var list = tabs.querySelectorAll('.tab');
    var activate = function (tab) {
      list.forEach(function (t) {
        var on = t === tab;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        var panel = document.getElementById(t.getAttribute('aria-controls'));
        if (panel) panel.hidden = !on;
      });
    };
    list.forEach(function (t, i) {
      t.addEventListener('click', function () { activate(t); });
      t.addEventListener('keydown', function (e) {
        var d = e.key === (CFG.dir === 'rtl' ? 'ArrowLeft' : 'ArrowRight') ? 1 : e.key === (CFG.dir === 'rtl' ? 'ArrowRight' : 'ArrowLeft') ? -1 : 0;
        if (!d) return;
        e.preventDefault();
        var n = list[(i + d + list.length) % list.length]; n.focus(); activate(n);
      });
    });
  });

  /* reveal on intersect (desktop-weighted, doc 02 §9) */
  if ('IntersectionObserver' in window && window.matchMedia('(min-width: 640px)').matches) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px' });
    document.querySelectorAll('.section-head, .card-product, .card-service, .card-post, .division-card, .tl-item, .q-item, .pillar').forEach(function (el, i) {
      el.classList.add('reveal'); el.style.setProperty('--i', String(i % 6)); io.observe(el);
    });
    /* counters once */
    var cio = new IntersectionObserver(function (es) {
      es.forEach(function (en) {
        if (!en.isIntersecting) return;
        cio.unobserve(en.target);
        var el = en.target, raw = el.getAttribute('data-count') || el.textContent;
        var m = raw.match(/(\d+)/);
        if (!m || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        var target = parseInt(m[1], 10), t0 = performance.now();
        var step = function (t) {
          var k = Math.min(1, (t - t0) / 900);
          el.textContent = raw.replace(/\d+/, String(Math.round(target * (1 - Math.pow(1 - k, 3)))));
          if (k < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      });
    }, { threshold: 0.6 });
    document.querySelectorAll('[data-count]').forEach(function (el) { cio.observe(el); });
  }

  /* FAQ client filter */
  var fq = document.querySelector('[data-faq-q]');
  if (fq) fq.addEventListener('input', function () {
    var q = fq.value.trim().toLowerCase();
    document.querySelectorAll('.faq-item').forEach(function (it) {
      it.style.display = !q || it.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
    });
  });

  /* event beacon (doc 05 §8) */
  document.addEventListener('click', function (e) {
    var el = e.target.closest ? e.target.closest('[data-event]') : null;
    if (!el) return;
    try {
      var fd = new URLSearchParams({ name: el.getAttribute('data-event'), lang: CFG.lang, path: location.pathname });
      navigator.sendBeacon(CFG.api + '/event', fd);
    } catch (err) { /* noop */ }
  });

  window.NM = { CFG: CFG };
})();
