/* ==========================================================================
   Clarity E-Learning — Kalender Akademik & Deadline (Item 18)
   ========================================================================== */

(function (global) {
  'use strict';

  var courseSelect = document.getElementById('calendar-course-select');
  var headingEl = document.getElementById('cal-month-heading');
  var summaryEl = document.getElementById('calendar-summary-text');
  var contentEl = document.getElementById('calendar-content');
  var prevBtn = document.getElementById('cal-prev-month');
  var nextBtn = document.getElementById('cal-next-month');
  var todayBtn = document.getElementById('cal-today-btn');
  if (!contentEl || !headingEl) return;

  var H = global.ClarityHelpers;
  var esc = H ? H.esc : function (v) { return String(v || ''); };

  var MONTH_NAMES = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];
  var WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

  var TYPE_ICONS = {
    lecture: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    assignment_deadline: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
    quiz_window: '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'
  };

  var TYPE_META = {
    lecture: { label: 'Jadwal Kuliah', cls: 'cal-event-lecture', tagCls: 'clarity-tag-info' },
    assignment_deadline: { label: 'Deadline Tugas', cls: 'cal-event-assignment', tagCls: 'clarity-tag-danger' },
    quiz_window: { label: 'Jadwal Quiz', cls: 'cal-event-quiz', tagCls: 'clarity-tag-warning' }
  };

  function jakartaTodayIso() {
    return new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Jakarta' });
  }

  function jakartaYearMonth() {
    var iso = jakartaTodayIso();
    var parts = iso.split('-');
    return { year: parseInt(parts[0], 10), month: parseInt(parts[1], 10) - 1, iso: iso };
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

  function wibTimeRange(ev) {
    if (!ev.start_at) return '';
    var opts = { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' };
    var start = new Date(ev.start_at).toLocaleTimeString('id-ID', opts);
    if (!ev.end_at || ev.end_at === ev.start_at) {
      return start + ' WIB';
    }
    var end = new Date(ev.end_at).toLocaleTimeString('id-ID', opts);
    return start + ' - ' + end + ' WIB';
  }

  function currentUser() {
    return global.ClarityAuth && global.ClarityAuth.getUser ? global.ClarityAuth.getUser() : null;
  }

  function eventTargetUrl(ev) {
    if (!global.ClarityAPI) return '#';
    var user = currentUser();
    var isDosen = user && user.role === 'dosen';
    if (ev.type === 'lecture') {
      return global.ClarityAPI.pageUrl(isDosen ? 'course-manage.php' : 'course-detail.php') + '?id=' + ev.course_id;
    }
    if (ev.type === 'assignment_deadline') {
      if (isDosen) {
        return global.ClarityAPI.pageUrl('submissions.php') + '?course_id=' + ev.course_id + '&assignment_id=' + (ev.entity_id || '');
      }
      return global.ClarityAPI.pageUrl('assignment.php') + '?course_id=' + ev.course_id + '&assignment_id=' + (ev.entity_id || '');
    }
    if (ev.type === 'quiz_window') {
      if (isDosen) {
        return global.ClarityAPI.pageUrl('quiz-builder.php') + '?course_id=' + ev.course_id + '&quiz_id=' + (ev.entity_id || '');
      }
      return global.ClarityAPI.pageUrl('quiz-intro.php') + '?course_id=' + ev.course_id + '&quiz_id=' + (ev.entity_id || '');
    }
    return global.ClarityAPI.pageUrl('course-detail.php') + '?id=' + ev.course_id;
  }

  var initial = jakartaYearMonth();
  var paramDate = global.ClarityUI ? global.ClarityUI.getQueryParam('date') : '';
  var initYear = initial.year;
  var initMonth = initial.month;
  var initSelectedDate = initial.iso;
  if (paramDate && /^\d{4}-\d{2}-\d{2}$/.test(paramDate)) {
    var dParts = paramDate.split('-');
    var parsedYear = parseInt(dParts[0], 10);
    var parsedMonth = parseInt(dParts[1], 10) - 1;
    var parsedDay = parseInt(dParts[2], 10);
    var checkDate = new Date(Date.UTC(parsedYear, parsedMonth, parsedDay));
    if (checkDate.getUTCFullYear() === parsedYear && checkDate.getUTCMonth() === parsedMonth && checkDate.getUTCDate() === parsedDay) {
      initYear = parsedYear;
      initMonth = parsedMonth;
      initSelectedDate = paramDate;
    }
  }

  var state = {
    year: initYear,
    month: initMonth,
    todayIso: initial.iso,
    selectedDate: initSelectedDate,
    courseId: '',
    view: global.innerWidth < 768 ? 'agenda' : 'month',
    enabledTypes: { lecture: true, assignment_deadline: true, quiz_window: true },
    events: []
  };

  function syncViewButtons() {
    document.querySelectorAll('[data-cal-view]').forEach(function (btn) {
      var active = btn.getAttribute('data-cal-view') === state.view;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
  }

  function syncTypeButtons() {
    document.querySelectorAll('[data-cal-type]').forEach(function (btn) {
      var t = btn.getAttribute('data-cal-type');
      var on = Boolean(state.enabledTypes[t]);
      btn.classList.toggle('is-muted', !on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  function filteredEvents() {
    return state.events.filter(function (ev) {
      return Boolean(state.enabledTypes[ev.type]);
    });
  }

  function groupEventsByDate(list) {
    var map = {};
    list.forEach(function (ev) {
      var ymd = wibDateOf(ev.start_at);
      if (!ymd) return;
      if (!map[ymd]) map[ymd] = [];
      map[ymd].push(ev);
    });
    return map;
  }

  function renderEventCard(ev) {
    var meta = TYPE_META[ev.type] || { label: ev.type, cls: 'cal-event-lecture', tagCls: 'clarity-tag-neutral' };
    var icon = TYPE_ICONS[ev.type] || '';
    var timeText = wibTimeRange(ev);
    var url = eventTargetUrl(ev);

    return '<article class="clarity-card clarity-hover-row p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">' +
      '<div class="min-w-0 space-y-1.5">' +
      '  <div class="flex items-center gap-2 flex-wrap">' +
      '    <span class="clarity-tag ' + meta.tagCls + ' inline-flex items-center gap-1">' + icon + '<span>' + esc(meta.label) + '</span></span>' +
      '    <span class="course-code-pill font-mono">' + esc(ev.course_code) + '</span>' +
      '    <span class="text-xs font-medium text-muted truncate">' + esc(ev.course_name) + '</span>' +
      '  </div>' +
      '  <h3 class="text-base font-bold text-ink m-0">' + esc(ev.title) + '</h3>' +
      '  <div class="text-xs text-secondary flex flex-wrap items-center gap-2">' +
      '    <span>Waktu: <strong class="text-ink">' + esc(timeText) + '</strong></span>' +
      (ev.location ? '<span>| Lokasi: <strong class="text-ink">' + esc(ev.location) + '</strong></span>' : '') +
      '  </div>' +
      '</div>' +
      '<a class="clarity-btn clarity-btn-outline clarity-btn-sm shrink-0" href="' + esc(url) + '">Buka Detail</a>' +
      '</article>';
  }

  function renderSelectedDayPanel(byDate) {
    var dayEvents = byDate[state.selectedDate] || [];
    var dateLabel = H ? H.formatDateOnly(state.selectedDate) : state.selectedDate;
    var html = '<section class="clarity-card p-5 space-y-4">' +
      '<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-[var(--color-border-subtle)]">' +
      '  <h2 class="text-base font-bold text-ink m-0">Agenda Tanggal: ' + esc(dateLabel) + '</h2>' +
      '  <span class="text-xs font-semibold text-muted">' + dayEvents.length + ' agenda terjadwal</span>' +
      '</div>';

    if (dayEvents.length === 0) {
      html += '<p class="text-sm text-muted m-0">Tidak ada jadwal kuliah, deadline tugas, atau quiz pada tanggal ini.</p>';
    } else {
      html += '<div class="space-y-3">' + dayEvents.map(renderEventCard).join('') + '</div>';
    }
    html += '</section>';
    return html;
  }

  function renderMonthView(events) {
    var byDate = groupEventsByDate(events);
    var firstDay = new Date(Date.UTC(state.year, state.month, 1));
    var daysInMonth = new Date(Date.UTC(state.year, state.month + 1, 0)).getUTCDate();
    var startOffset = (firstDay.getUTCDay() + 6) % 7; // Senin = 0
    var totalCells = Math.ceil((startOffset + daysInMonth) / 7) * 7;
    var prevMonthLastDay = new Date(Date.UTC(state.year, state.month, 0)).getUTCDate();

    var cellsHtml = '';
    for (var i = 0; i < totalCells; i++) {
      var cellYear = state.year;
      var cellMonth = state.month;
      var dayNum;
      var isOutside = false;

      if (i < startOffset) {
        isOutside = true;
        dayNum = prevMonthLastDay - startOffset + i + 1;
        cellMonth = state.month - 1;
        if (cellMonth < 0) { cellMonth = 11; cellYear--; }
      } else if (i >= startOffset + daysInMonth) {
        isOutside = true;
        dayNum = i - (startOffset + daysInMonth) + 1;
        cellMonth = state.month + 1;
        if (cellMonth > 11) { cellMonth = 0; cellYear++; }
      } else {
        dayNum = i - startOffset + 1;
      }

      var ymd = toYmd(cellYear, cellMonth, dayNum);
      var dayEvents = byDate[ymd] || [];
      var cls = 'cal-day' +
        (isOutside ? ' is-outside' : '') +
        (ymd === state.todayIso ? ' is-today' : '') +
        (ymd === state.selectedDate ? ' is-selected' : '');

      var pillsHtml = dayEvents.slice(0, 3).map(function (ev) {
        var meta = TYPE_META[ev.type] || { cls: 'cal-event-lecture' };
        var icon = TYPE_ICONS[ev.type] || '';
        return '<span class="cal-event-pill ' + meta.cls + '" title="' + esc(ev.course_code + ' - ' + ev.title) + '">' +
          icon + '<span class="truncate">' + esc(ev.title) + '</span></span>';
      }).join('');

      if (dayEvents.length > 3) {
        pillsHtml += '<span class="text-xs font-semibold text-muted px-1">+' + (dayEvents.length - 3) + ' lainnya</span>';
      }

      cellsHtml += '<button type="button" class="' + cls + '" data-cal-date="' + ymd + '" aria-label="Pilih tanggal ' + esc(ymd) + ' (' + dayEvents.length + ' agenda)">' +
        '<span class="cal-day-num">' + dayNum + '</span>' +
        pillsHtml +
        '</button>';
    }

    var gridHtml = '<div class="cal-grid-wrap overflow-x-auto">' +
      '<div class="min-w-[680px]">' +
      '  <div class="cal-grid-weekdays">' +
      WEEKDAYS.map(function (w) { return '<div class="cal-weekday">' + w + '</div>'; }).join('') +
      '  </div>' +
      '  <div class="cal-grid">' + cellsHtml + '</div>' +
      '</div></div>';

    contentEl.innerHTML = gridHtml + renderSelectedDayPanel(byDate);

    contentEl.querySelectorAll('[data-cal-date]').forEach(function (cell) {
      cell.addEventListener('click', function () {
        state.selectedDate = cell.getAttribute('data-cal-date') || state.selectedDate;
        renderCurrentView();
      });
    });
  }

  function renderAgendaView(events) {
    if (events.length === 0) {
      contentEl.innerHTML = H
        ? H.renderEmpty('Tidak Ada Agenda Bulan Ini', 'Belum ada jadwal pertemuan, deadline tugas, atau quiz yang cocok dengan filter aktif.')
        : '<div class="clarity-card p-8 text-center text-sm text-muted">Tidak ada agenda.</div>';
      return;
    }

    var byDate = groupEventsByDate(events);
    var dates = Object.keys(byDate).sort();
    var html = '<div class="space-y-4">';

    dates.forEach(function (ymd) {
      var dateLabel = H ? H.formatDateOnly(ymd) : ymd;
      var isToday = ymd === state.todayIso;
      html += '<section class="clarity-card p-5 space-y-3">' +
        '<div class="flex items-center justify-between gap-2 pb-2 border-b border-[var(--color-border-subtle)]">' +
        '  <div class="flex items-center gap-2">' +
        '    <h2 class="text-base font-bold text-ink m-0">' + esc(dateLabel) + '</h2>' +
        (isToday ? '<span class="clarity-tag clarity-tag-danger">Hari Ini</span>' : '') +
        '  </div>' +
        '  <span class="text-xs font-semibold text-muted">' + byDate[ymd].length + ' agenda</span>' +
        '</div>' +
        '<div class="space-y-3">' + byDate[ymd].map(renderEventCard).join('') + '</div>' +
        '</section>';
    });

    html += '</div>';
    contentEl.innerHTML = html;
  }

  function renderCurrentView() {
    var visible = filteredEvents();
    headingEl.textContent = MONTH_NAMES[state.month] + ' ' + state.year;
    if (summaryEl) {
      summaryEl.textContent = visible.length + ' agenda tampil (' + state.events.length + ' total bulan ini)';
    }
    if (state.view === 'agenda') {
      renderAgendaView(visible);
    } else {
      renderMonthView(visible);
    }
  }

  function fetchCalendarEvents() {
    var fromStr = toYmd(state.year, state.month, 1);
    var lastDay = new Date(Date.UTC(state.year, state.month + 1, 0)).getUTCDate();
    var toStr = toYmd(state.year, state.month, lastDay);

    headingEl.textContent = MONTH_NAMES[state.month] + ' ' + state.year;
    contentEl.innerHTML = H ? H.renderLoading('Memuat jadwal dan deadline kalender...') : 'Memuat...';

    var qs = '?from=' + encodeURIComponent(fromStr) + '&to=' + encodeURIComponent(toStr) +
      (state.courseId ? '&course_id=' + encodeURIComponent(state.courseId) : '');

    global.ClarityAPI.get('/calendar' + qs)
      .then(function (res) {
        state.events = Array.isArray(res.data) ? res.data : [];
        if (state.selectedDate.slice(0, 7) !== fromStr.slice(0, 7)) {
          state.selectedDate = (state.todayIso.slice(0, 7) === fromStr.slice(0, 7)) ? state.todayIso : fromStr;
        }
        renderCurrentView();
      })
      .catch(function (err) {
        contentEl.innerHTML = H
          ? H.renderError((err && err.message) || 'Gagal memuat kalender akademik.', 'btn-retry-cal')
          : '<div class="clarity-card p-6 text-danger">Gagal memuat kalender.</div>';
        var retryBtn = document.getElementById('btn-retry-cal');
        if (retryBtn) retryBtn.addEventListener('click', fetchCalendarEvents);
      });
  }

  function loadCourseOptions() {
    if (!courseSelect || !global.ClarityAPI) return;
    global.ClarityAPI.get('/courses?per_page=50')
      .then(function (res) {
        var courses = Array.isArray(res.data) ? res.data : [];
        var html = '<option value="">Semua Mata Kuliah</option>' + courses.map(function (c) {
          return '<option value="' + c.id + '">' + esc(c.code + ' - ' + c.name) + '</option>';
        }).join('');
        courseSelect.innerHTML = html;

        var paramCid = global.ClarityUI ? global.ClarityUI.getQueryParam('course_id') : '';
        if (paramCid && courses.some(function (c) { return String(c.id) === String(paramCid); })) {
          state.courseId = String(paramCid);
          courseSelect.value = state.courseId;
        }
        fetchCalendarEvents();
      })
      .catch(function () {
        fetchCalendarEvents();
      });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      state.month--;
      if (state.month < 0) { state.month = 11; state.year--; }
      fetchCalendarEvents();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      state.month++;
      if (state.month > 11) { state.month = 0; state.year++; }
      fetchCalendarEvents();
    });
  }

  if (todayBtn) {
    todayBtn.addEventListener('click', function () {
      var nowYm = jakartaYearMonth();
      state.year = nowYm.year;
      state.month = nowYm.month;
      state.todayIso = nowYm.iso;
      state.selectedDate = nowYm.iso;
      fetchCalendarEvents();
    });
  }

  if (courseSelect) {
    courseSelect.addEventListener('change', function () {
      state.courseId = courseSelect.value;
      if (state.courseId && global.ClarityAPI) {
        global.ClarityAPI.setSelectedCourseId(state.courseId);
      }
      fetchCalendarEvents();
    });
  }

  document.querySelectorAll('[data-cal-view]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      state.view = btn.getAttribute('data-cal-view') || 'month';
      syncViewButtons();
      renderCurrentView();
    });
  });

  document.querySelectorAll('[data-cal-type]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var t = btn.getAttribute('data-cal-type');
      if (!t) return;
      state.enabledTypes[t] = !state.enabledTypes[t];
      syncTypeButtons();
      renderCurrentView();
    });
  });

  syncViewButtons();
  syncTypeButtons();
  loadCourseOptions();
})(window);
