/* ==========================================================================
   Clarity E-Learning — Interaksi Dashboard (Progress Ring, Counter & Filter)
   ========================================================================== */

(function (global) {
  'use strict';

  function animateRingText(el, targetPercent, duration) {
    if (!el || isNaN(targetPercent)) return;
    var start = null;
    function frame(ts) {
      if (start === null) start = ts;
      var progress = Math.min(1, (ts - start) / duration);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * targetPercent) + '%';
      if (progress < 1) {
        global.requestAnimationFrame(frame);
      } else {
        el.textContent = targetPercent + '%';
      }
    }
    global.requestAnimationFrame(frame);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var reducedMotion = global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (global.ClarityUI) {
      document.querySelectorAll('[data-ring]').forEach(function (el) {
        var val = parseInt(el.getAttribute('data-ring'), 10) || 0;
        var size = parseInt(el.getAttribute('data-ring-size'), 10) || 56;
        el.innerHTML = global.ClarityUI.renderProgressRing(val, size);
        if (!reducedMotion && typeof global.requestAnimationFrame === 'function') {
          var textEl = el.querySelector('[data-target-percent]');
          if (textEl) {
            textEl.textContent = '0%';
            animateRingText(textEl, val, 640);
          }
        }
      });
    }

    var filterBtns = document.querySelectorAll('[data-task-filter]');
    var taskItems = document.querySelectorAll('[data-task-item]');
    if (filterBtns.length && taskItems.length) {
      filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var filter = btn.getAttribute('data-task-filter');
          filterBtns.forEach(function (b) {
            b.classList.remove('is-active');
            b.setAttribute('aria-pressed', 'false');
          });
          btn.classList.add('is-active');
          btn.setAttribute('aria-pressed', 'true');

          taskItems.forEach(function (item) {
            var category = item.getAttribute('data-task-item') || '';
            var match = filter === 'all' || category.split(' ').indexOf(filter) !== -1;
            item.hidden = !match;
            if (match && !reducedMotion) {
              item.classList.remove('is-entering');
              void item.offsetWidth;
              item.classList.add('is-entering');
            }
          });
        });
      });
    }
  });
})(window);
