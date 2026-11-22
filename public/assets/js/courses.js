/* ==========================================================================
   Clarity E-Learning — Courses Page Logic (Timeline No. 9 · PIC: Matthew & Rizky)
   Server-side filtering, role-aware card rendering, debounce search
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
    try {
      var raw = localStorage.getItem('clarity_user');
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function init() {
    var searchInput = document.getElementById('filter-search');
    var semesterSelect = document.getElementById('filter-semester');
    var daySelect = document.getElementById('filter-day');

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
        // Filter hari dapat difilter di client-side atas hasil pencarian server
        renderCourseCards(currentCourses);
      });
    }

    // Muat opsi semester dinamis dari API
    loadSemesters();
    // Muat daftar mata kuliah awal
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
      .catch(function () {
        // Gunakan opsi default jika gagal fetch
      });
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

    // Tampilkan Skeleton / State Loading
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
      container.innerHTML = '<div class="col-span-full clarity-card p-12 text-center space-y-3">' +
        '<div class="text-3xl">🔍</div>' +
        '<h3 class="text-lg font-bold text-ink">Tidak ada mata kuliah yang cocok</h3>' +
        '<p class="text-sm text-secondary">Coba ubah kata kunci pencarian atau ganti filter semester/hari kuliah.</p>' +
        '</div>';
      return;
    }

    var user = getUser();
    var isDosen = user && user.role === 'dosen';

    var html = filtered.map(function (c) {
      var detailUrl = 'course-detail.php?id=' + encodeURIComponent(c.id);
      var schedule = (c.day || '-') + (c.start_time ? ', ' + c.start_time + '–' + (c.end_time || '') : '');
      var progress = c.progress_percent || 75;

      if (isDosen) {
        return '<article class="clarity-card clarity-hover-card p-5 flex flex-col justify-between gap-5 hallmark-reveal">' +
          '<div class="space-y-3">' +
            '<div class="flex items-center justify-between gap-2 flex-wrap">' +
              '<span class="course-code-pill font-mono">' + escapeHtml(c.code) + '</span>' +
              '<span class="clarity-tag clarity-tag-info">' + escapeHtml(c.credits) + ' SKS</span>' +
            '</div>' +
            '<h3 class="text-lg font-bold text-ink leading-snug m-0">' +
              '<a class="text-inherit no-underline hover:text-accent" href="' + detailUrl + '">' + escapeHtml(c.name) + '</a>' +
            '</h3>' +
            '<p class="text-xs text-secondary line-clamp-2 m-0">' + escapeHtml(c.description || 'Tidak ada deskripsi.') + '</p>' +
            '<div class="space-y-1.5 pt-2 border-t border-[var(--color-border-subtle)] text-xs text-secondary">' +
              '<div class="flex items-center gap-2">' +
                '<span class="text-muted">Jadwal:</span>' +
                '<span class="font-medium text-ink">' + escapeHtml(schedule) + ' (' + escapeHtml(c.room || '-') + ')</span>' +
              '</div>' +
              '<div class="flex items-center gap-2">' +
                '<span class="text-muted">Mahasiswa:</span>' +
                '<span class="font-medium text-ink">' + escapeHtml(c.enrolled_count || 0) + ' peserta aktif</span>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="pt-2 flex items-center justify-between border-t border-[var(--color-border-subtle)]">' +
            '<span class="text-xs text-muted font-medium">' + escapeHtml(c.semester) + '</span>' +
            '<a class="clarity-btn clarity-btn-primary clarity-btn-sm" href="' + detailUrl + '">Kelola Kelas</a>' +
          '</div>' +
        '</article>';
      }

      // Tampilan Mahasiswa
      return '<article class="clarity-card clarity-hover-card p-5 flex flex-col justify-between gap-5 hallmark-reveal">' +
        '<div class="space-y-3">' +
          '<div class="flex items-center justify-between gap-2 flex-wrap">' +
            '<span class="course-code-pill font-mono">' + escapeHtml(c.code) + '</span>' +
            '<span class="clarity-tag clarity-tag-info">' + escapeHtml(c.credits) + ' SKS</span>' +
          '</div>' +
          '<h3 class="text-lg font-bold text-ink leading-snug m-0">' +
            '<a class="text-inherit no-underline hover:text-accent" href="' + detailUrl + '">' + escapeHtml(c.name) + '</a>' +
          '</h3>' +
          '<div class="text-xs text-secondary">' +
            '<span class="text-muted">Dosen: </span><span class="font-medium text-ink">' + escapeHtml(c.lecturer_name || '-') + '</span>' +
          '</div>' +
          '<div class="space-y-1.5 pt-2 border-t border-[var(--color-border-subtle)] text-xs text-secondary">' +
            '<div class="flex items-center gap-2">' +
              '<span class="text-muted">Jadwal:</span>' +
              '<span class="font-medium text-ink">' + escapeHtml(schedule) + ' (' + escapeHtml(c.room || '-') + ')</span>' +
            '</div>' +
            '<div class="space-y-1 pt-1">' +
              '<div class="flex items-center justify-between text-xs font-semibold">' +
                '<span class="text-muted">Progres Selesai</span>' +
                '<span class="text-accent font-bold">' + progress + '%</span>' +
              '</div>' +
              '<div class="w-full bg-[var(--color-surface-2)] rounded-full h-2 overflow-hidden">' +
                '<div class="bg-[var(--color-accent)] h-2 rounded-full transition-all duration-500" style="width:' + progress + '%"></div>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>' +
        '<div class="pt-2 flex items-center justify-between border-t border-[var(--color-border-subtle)]">' +
          '<span class="text-xs text-muted font-medium">' + escapeHtml(c.semester) + '</span>' +
          '<a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="' + detailUrl + '">Buka Kelas</a>' +
        '</div>' +
      '</article>';
    }).join('');

    container.innerHTML = html;
  }

  function renderSkeleton() {
    var cards = [];
    for (var i = 0; i < 4; i++) {
      cards.push(
        '<div class="clarity-card p-5 space-y-4 animate-pulse">' +
          '<div class="flex justify-between items-center">' +
            '<div class="h-5 w-24 bg-[var(--color-surface-2)] rounded"></div>' +
            '<div class="h-5 w-14 bg-[var(--color-surface-2)] rounded"></div>' +
          '</div>' +
          '<div class="h-6 w-3/4 bg-[var(--color-surface-2)] rounded"></div>' +
          '<div class="h-4 w-1/2 bg-[var(--color-surface-2)] rounded"></div>' +
          '<div class="space-y-2 pt-3 border-t border-[var(--color-border-subtle)]">' +
            '<div class="h-4 w-full bg-[var(--color-surface-2)] rounded"></div>' +
            '<div class="h-2 w-full bg-[var(--color-surface-2)] rounded"></div>' +
          '</div>' +
        '</div>'
      );
    }
    return cards.join('');
  }

  function renderError(msg) {
    var container = document.getElementById('courses-grid');
    if (!container) return;
    container.innerHTML = '<div class="col-span-full clarity-card p-8 text-center space-y-2 border-red-300">' +
      '<p class="text-sm font-semibold text-red-600">' + escapeHtml(msg) + '</p>' +
      '<button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" onclick="location.reload()">Coba Lagi</button>' +
      '</div>';
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window);
