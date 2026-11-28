/* ==========================================================================
   Clarity E-Learning — Katalog Mata Kuliah (Crisp White + Red Minimalist)
   ========================================================================== */

(function (global) {
  'use strict';

  var debounceTimer = null;
  var currentCourses = [];

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getUser() {
    return global.ClarityAuth && global.ClarityAuth.getUser ? global.ClarityAuth.getUser() : null;
  }

  function init() {
    var searchInput = document.getElementById('filter-search');
    var semesterSelect = document.getElementById('filter-semester');
    var daySelect = document.getElementById('filter-day');

    var urlSearch = global.ClarityUI ? global.ClarityUI.getQueryParam('search') : '';
    if (searchInput && urlSearch) {
      searchInput.value = urlSearch;
    }

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchCourses, 300);
      });
    }

    if (semesterSelect) {
      semesterSelect.addEventListener('change', fetchCourses);
    }

    if (daySelect) {
      daySelect.addEventListener('change', function () {
        renderCourseCards(currentCourses);
      });
    }

    loadSemesters();
    fetchCourses();
  }

  function loadSemesters() {
    var semesterSelect = document.getElementById('filter-semester');
    if (!semesterSelect || !global.ClarityAPI) return;

    global.ClarityAPI.get('/courses/semesters')
      .then(function (res) {
        var semesters = (res && res.data) || [];
        if (semesters.length > 0) {
          var currentVal = semesterSelect.value;
          var html = '<option value="">Semua Semester</option>';
          semesters.forEach(function (sem) {
            var selected = (sem === currentVal || sem === 'Ganjil 2026/2027') ? ' selected' : '';
            html += '<option value="' + escapeHtml(sem) + '"' + selected + '>' + escapeHtml(sem) + '</option>';
          });
          semesterSelect.innerHTML = html;
        }
      })
      .catch(function () {});
  }

  function fetchCourses() {
    var container = document.getElementById('courses-grid');
    if (!container) return;

    var searchInput = document.getElementById('filter-search');
    var semesterSelect = document.getElementById('filter-semester');

    var search = searchInput ? searchInput.value.trim() : '';
    var semester = semesterSelect ? semesterSelect.value.trim() : '';

    var queryParams = [];
    if (search) queryParams.push('search=' + encodeURIComponent(search));
    if (semester && semester !== 'all') queryParams.push('semester=' + encodeURIComponent(semester));

    var queryUrl = '/courses' + (queryParams.length > 0 ? '?' + queryParams.join('&') : '');

    container.innerHTML = renderSkeleton();

    if (!global.ClarityAPI) {
      renderError('Layanan API Clarity belum siap.');
      return;
    }

    global.ClarityAPI.get(queryUrl)
      .then(function (res) {
        currentCourses = (res && res.data) || [];
        renderCourseCards(currentCourses);
      })
      .catch(function (err) {
        renderError((err && err.message) || 'Gagal memuat daftar mata kuliah dari server.');
      });
  }

  function renderCourseCards(courses) {
    var container = document.getElementById('courses-grid');
    var countBadge = document.getElementById('courses-count');
    var daySelect = document.getElementById('filter-day');
    var selectedDay = daySelect ? daySelect.value.trim() : '';

    if (!container) return;

    var filtered = courses;
    if (selectedDay) {
      filtered = courses.filter(function (c) {
        return (c.day || '').toLowerCase() === selectedDay.toLowerCase();
      });
    }

    if (countBadge) {
      countBadge.textContent = filtered.length + ' Kelas';
    }

    if (filtered.length === 0) {
      container.innerHTML = '<div class="col-span-full">' +
        (global.ClarityHelpers
          ? global.ClarityHelpers.renderEmpty('Tidak Ada Mata Kuliah Cocok', 'Coba ubah kata kunci pencarian atau ganti filter semester dan hari kuliah.')
          : '<div class="clarity-card p-10 text-center text-sm text-muted">Tidak ada mata kuliah yang cocok.</div>') +
        '</div>';
      return;
    }

    var user = getUser();
    var isDosen = user && user.role === 'dosen';

    container.innerHTML = filtered.map(function (c, idx) {
      var detailUrl = (global.ClarityAPI ? global.ClarityAPI.pageUrl('course-detail.php') : 'course-detail.php') + '?id=' + encodeURIComponent(c.id);
      var schedule = (c.day || '-') + (c.start_time ? ', ' + c.start_time + ' - ' + (c.end_time || '') : '');
      var progress = c.progress_percent || 75;
      var coverClass = 'course-tile-cover-' + ((idx % 4) + 1);

      return '<a class="course-tile-card hallmark-reveal" href="' + escapeHtml(detailUrl) + '">' +
        '<div class="course-tile-cover ' + coverClass + '">' +
        '  <div class="flex items-center justify-between gap-2 text-xs font-semibold text-accent">' +
        '    <span class="font-mono">' + escapeHtml(c.code) + '</span>' +
        '    <span>' + escapeHtml(c.credits) + ' SKS</span>' +
        '  </div>' +
        '  <div class="text-xs font-medium text-secondary truncate">' +
        (isDosen ? escapeHtml(c.enrolled_count || 0) + ' peserta aktif' : escapeHtml(c.lecturer_name || '-')) +
        '  </div>' +
        '</div>' +
        '<div class="px-2 pb-2 space-y-3">' +
        '  <div>' +
        '    <div class="text-base font-bold text-ink leading-snug">' + escapeHtml(c.name) + '</div>' +
        '    <div class="text-xs text-muted mt-1 truncate">' + escapeHtml(schedule) + ' (' + escapeHtml(c.room || '-') + ')</div>' +
        '  </div>' +
        '  <div class="space-y-1.5">' +
        '    <div class="course-progress-bar"><div class="course-progress-fill" style="width:' + progress + '%"></div></div>' +
        '    <div class="flex items-center justify-between text-xs font-semibold text-muted">' +
        '      <span>Selesai ' + progress + '%</span>' +
        '      <span>' + escapeHtml(c.semester) + '</span>' +
        '    </div>' +
        '  </div>' +
        '</div>' +
        '</a>';
    }).join('');
  }

  function renderSkeleton() {
    var cards = [];
    for (var i = 0; i < 4; i++) {
      cards.push(
        '<div class="clarity-card p-5 space-y-4">' +
        '  <div class="skeleton h-20 w-full"></div>' +
        '  <div class="skeleton h-5 w-3/4"></div>' +
        '  <div class="skeleton h-3 w-full"></div>' +
        '</div>'
      );
    }
    return cards.join('');
  }

  function renderError(msg) {
    var container = document.getElementById('courses-grid');
    if (!container) return;
    container.innerHTML = '<div class="col-span-full">' +
      (global.ClarityHelpers
        ? global.ClarityHelpers.renderError(msg, 'btn-retry-courses')
        : '<div class="clarity-card p-6 text-accent">' + escapeHtml(msg) + '</div>') +
      '</div>';
    var retryBtn = document.getElementById('btn-retry-courses');
    if (retryBtn) retryBtn.addEventListener('click', fetchCourses);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window);
