/* ==========================================================================
   Clarity E-Learning — Interaksi Dashboard (Mini Kalender, Ring & Filter Tugas)
   ========================================================================== */

(function (global) {
  'use strict';

  var MONTH_NAMES = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];

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

  function pad2(n) {
    return n < 10 ? '0' + n : String(n);
  }

  function toYmd(year, monthZeroBased, day) {
    return year + '-' + pad2(monthZeroBased + 1) + '-' + pad2(day);
  }

  function wibDateOf(isoStr) {
    if (!isoStr) return '';
    var d = new Date(isoStr);
    if (isNaN(d.getTime())) return String(isoStr).slice(0, 10);
    return d.toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });
  }

  function escapeAttr(value) {
    if (value === null || value === undefined) return '';
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  }

  function groupEventsByDate(events) {
    var map = {};
    (events || []).forEach(function (ev) {
      var ymd = wibDateOf(ev && ev.start_at);
      if (!ymd) return;
      if (!map[ymd]) map[ymd] = [];
      map[ymd].push(ev);
    });
    return map;
  }

  function paintMiniCalendar(gridEl, year, month, todayIso, eventsByDate) {
    var baseCalHref = global.ClarityAPI && global.ClarityAPI.pageUrl ? global.ClarityAPI.pageUrl('calendar.php') : 'calendar.php';
    var headers = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    var firstDay = new Date(Date.UTC(year, month, 1));
    var daysInMonth = new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
    var startOffset = (firstDay.getUTCDay() + 6) % 7;
    var prevLast = new Date(Date.UTC(year, month, 0)).getUTCDate();
    var totalCells = Math.ceil((startOffset + daysInMonth) / 7) * 7;

    var html = headers.map(function (h) {
      return '<div class="mini-cal-head">' + h + '</div>';
    }).join('');

    for (var i = 0; i < totalCells; i++) {
      if (i < startOffset) {
        html += '<span class="mini-cal-cell is-outside">' + (prevLast - startOffset + i + 1) + '</span>';
      } else if (i >= startOffset + daysInMonth) {
        html += '<span class="mini-cal-cell is-outside">' + (i - (startOffset + daysInMonth) + 1) + '</span>';
      } else {
        var d = i - startOffset + 1;
        var ymd = toYmd(year, month, d);
        var dayEvents = eventsByDate[ymd] || [];
        var count = dayEvents.length;

        var cls = 'mini-cal-cell';
        if (count >= 3) {
          cls += ' lvl-3';
        } else if (count === 2) {
          cls += ' lvl-2';
        } else if (count === 1) {
          cls += ' lvl-1';
        }
        if (ymd === todayIso) {
          cls += ' is-today';
        }

        var dateLabel = d + ' ' + MONTH_NAMES[month] + ' ' + year;
        var tip = count > 0
          ? dateLabel + ' (' + count + ' agenda): ' + dayEvents.slice(0, 3).map(function (ev) { return ev.title || 'Agenda'; }).join(', ')
          : dateLabel + ': Tidak ada agenda';
        var href = baseCalHref + '?date=' + encodeURIComponent(ymd);

        html += '<a class="' + cls + '" href="' + escapeAttr(href) + '" title="' + escapeAttr(tip) + '" aria-label="' + escapeAttr(tip) + '">' + d + '</a>';
      }
    }

    gridEl.innerHTML = html;
  }

  function loadMiniCalendar() {
    var gridEl = document.getElementById('mini-cal-grid');
    var monthLabelEl = document.getElementById('mini-cal-month');
    if (!gridEl) return;

    var wibIso = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });
    var parts = wibIso.split('-');
    var year = parseInt(parts[0], 10);
    var month = parseInt(parts[1], 10) - 1;

    if (monthLabelEl) {
      monthLabelEl.textContent = MONTH_NAMES[month] + ' ' + year;
    }

    paintMiniCalendar(gridEl, year, month, wibIso, {});

    if (!global.ClarityAPI || typeof global.ClarityAPI.get !== 'function') return;
    if (global.ClarityAuth && typeof global.ClarityAuth.getToken === 'function' && !global.ClarityAuth.getToken()) return;

    var fromStr = toYmd(year, month, 1);
    var lastDay = new Date(Date.UTC(year, month + 1, 0)).getUTCDate();
    var toStr = toYmd(year, month, lastDay);

    global.ClarityAPI.get('/calendar?from=' + encodeURIComponent(fromStr) + '&to=' + encodeURIComponent(toStr))
      .then(function (res) {
        var events = Array.isArray(res && res.data) ? res.data : [];
        paintMiniCalendar(gridEl, year, month, wibIso, groupEventsByDate(events));
      })
      .catch(function () {
        // Pertahankan tampilan kalender polos jika API belum tersedia
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var reducedMotion = global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches;

    loadMiniCalendar();

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
