/* blog post: reading progress + native share (doc 03 §8) */
(function () {
  'use strict';
  var bar = document.querySelector('.progress-bar');
  if (bar) {
    var on = function () {
      var h = document.documentElement;
      var max = h.scrollHeight - h.clientHeight;
      bar.style.width = (max > 0 ? (h.scrollTop / max) * 100 : 0) + '%';
    };
    window.addEventListener('scroll', on, { passive: true }); on();
  }
  document.querySelectorAll('[data-share]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (navigator.share) navigator.share({ title: document.title, url: location.href }).catch(function () {});
      else location.href = 'mailto:?subject=' + encodeURIComponent(document.title) + '&body=' + encodeURIComponent(location.href);
    });
  });
})();
