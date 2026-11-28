/* ==========================================================================
   Clarity E-Learning — Detail Mata Kuliah, Peserta, Materi & Tugas (Item 16)
   ========================================================================== */

(function (global) {
  'use strict';

  var H = global.ClarityHelpers;
  var esc = H ? H.esc : function (v) { return String(v || ''); };

  var courseId = 1;
  var partState = { page: 1, perPage: 10, search: '' };
  var loadedTabs = { materi: false, tugas: false };
  var debounceTimer = null;

  function getCourseId() {
    try {
      var params = new URLSearchParams(window.location.search);
      var id = parseInt(params.get('id'), 10);
      if (!isNaN(id) && id > 0) return id;
    } catch (e) {}
    var saved = global.ClarityAPI ? parseInt(global.ClarityAPI.getSelectedCourseId(), 10) : NaN;
    return !isNaN(saved) && saved > 0 ? saved : 1;
  }

  function currentUser() {
    return global.ClarityAuth && global.ClarityAuth.getUser ? global.ClarityAuth.getUser() : null;
  }

  function initBreadcrumbsAndActions() {
    var user = currentUser();
    var isDosen = user && user.role === 'dosen';
    var dashLink = document.getElementById('breadcrumb-dashboard-link');
    var coursesLink = document.getElementById('breadcrumb-courses-link');
    var attLink = document.getElementById('course-attendance-link');
    var discLink = document.getElementById('course-discussion-link');

    if (dashLink && global.ClarityAPI) {
      dashLink.href = global.ClarityAPI.pageUrl(isDosen ? 'dashboard-dosen.php' : 'dashboard.php');
    }
    if (coursesLink && global.ClarityAPI) {
      coursesLink.href = global.ClarityAPI.pageUrl(isDosen ? 'course-manage.php' : 'courses.php');
      coursesLink.textContent = isDosen ? 'Kelola Mata Kuliah' : 'Mata Kuliah';
    }
    if (attLink && global.ClarityAPI) {
      attLink.href = global.ClarityAPI.pageUrl(isDosen ? 'attendance-manage.php' : 'attendance.php') + '?course_id=' + courseId;
      attLink.textContent = isDosen ? 'Kelola Presensi' : 'Isi Presensi';
    }
    if (discLink && global.ClarityAPI) {
      discLink.href = global.ClarityAPI.pageUrl('chat.php') + '?course_id=' + courseId;
    }
  }

  function initTabs() {
    var tabTriggers = document.querySelectorAll('[data-tab-target]');
    var tabPanels = document.querySelectorAll('.tab-content-panel');

    tabTriggers.forEach(function (trigger) {
      trigger.addEventListener('click', function () {
        var targetId = trigger.getAttribute('data-tab-target');

        tabTriggers.forEach(function (btn) {
          btn.classList.remove('is-active');
          btn.setAttribute('aria-selected', 'false');
        });
        tabPanels.forEach(function (panel) {
          panel.classList.add('hidden');
        });

        trigger.classList.add('is-active');
        trigger.setAttribute('aria-selected', 'true');

        var targetPanel = document.getElementById(targetId);
        if (targetPanel) {
          targetPanel.classList.remove('hidden');
        }

        if (targetId === 'tab-materi' && !loadedTabs.materi) {
          loadMaterials();
        } else if (targetId === 'tab-tugas' && !loadedTabs.tugas) {
          loadAssignments();
        }
      });
    });
  }

  function loadCourseDetail() {
    if (!global.ClarityAPI) return;
    global.ClarityAPI.setSelectedCourseId(String(courseId));

    global.ClarityAPI.get('/courses/' + courseId)
      .then(function (res) {
        var c = res && res.data;
        if (!c) throw new Error('Data mata kuliah tidak ditemukan.');
        renderCourseHeader(c);
        loadParticipants(1);
      })
      .catch(function (err) {
        var errBox = document.getElementById('course-detail-error');
        var headerEl = document.getElementById('course-detail-header');
        var tabsEl = document.getElementById('course-detail-tabs');
        var backUrl = global.ClarityAPI.pageUrl('courses.php');
        if (headerEl) headerEl.hidden = true;
        if (tabsEl) tabsEl.hidden = true;
        if (errBox) {
          errBox.hidden = false;
          errBox.innerHTML = '<div class="clarity-card p-6 space-y-4">' +
            '<div class="clarity-alert clarity-alert-danger" role="alert">' +
            '<span><strong>Akses Ditolak / Tidak Ditemukan:</strong> ' + esc((err && err.message) || 'Mata kuliah tidak tersedia untuk akun Anda.') + '</span>' +
            '</div>' +
            '<a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="' + esc(backUrl) + '">Kembali ke Daftar Mata Kuliah</a>' +
            '</div>';
        }
      });
  }

  function renderCourseHeader(c) {
    var titleEl = document.getElementById('course-name');
    var codeEl = document.getElementById('course-code');
    var creditsEl = document.getElementById('course-credits');
    var lecturerEl = document.getElementById('course-lecturer');
    var scheduleEl = document.getElementById('course-schedule');
    var descEl = document.getElementById('course-description');
    var breadcrumbEl = document.getElementById('breadcrumb-course-name');
    var progressEl = document.getElementById('course-progress-text');
    var progressBarEl = document.getElementById('course-progress-bar');

    var scheduleText = (c.day || '-') + (c.start_time ? ', ' + c.start_time + ' - ' + (c.end_time || '') + ' WIB' : '') + ' (' + (c.room || '-') + ')';

    if (titleEl) titleEl.textContent = c.name;
    if (codeEl) codeEl.textContent = c.code;
    if (creditsEl) creditsEl.textContent = c.credits + ' SKS';
    if (lecturerEl) lecturerEl.textContent = c.lecturer_name + (c.lecturer_email ? ' (' + c.lecturer_email + ')' : '');
    if (scheduleEl) scheduleEl.textContent = scheduleText;
    if (descEl) descEl.textContent = c.description || 'Mata kuliah ini membekali mahasiswa dengan kompetensi praktis dan teoritis sesuai kurikulum akademik program studi.';
    if (breadcrumbEl) breadcrumbEl.textContent = c.code + ' ' + c.name;

    var progress = c.progress_percent || 75;
    if (progressEl) progressEl.textContent = progress + '%';
    if (progressBarEl) progressBarEl.style.width = progress + '%';
  }

  function roleBadge(role) {
    if (role === 'dosen') {
      return '<span class="clarity-tag clarity-tag-info">Dosen Pengampu</span>';
    }
    return '<span class="clarity-tag clarity-tag-neutral">Mahasiswa</span>';
  }

  function loadParticipants(page) {
    partState.page = page || 1;
    var listEl = document.getElementById('participants-list');
    var pagEl = document.getElementById('participants-pagination');
    var lecEl = document.getElementById('participants-lecturer-strip');
    if (!listEl) return;

    listEl.innerHTML = '<div class="p-6">' + (H ? H.renderLoading('Memuat daftar peserta kelas...') : 'Memuat...') + '</div>';
    if (pagEl) pagEl.innerHTML = '';

    var qs = '?page=' + partState.page + '&per_page=' + partState.perPage +
      (partState.search ? '&search=' + encodeURIComponent(partState.search) : '');

    global.ClarityAPI.get('/courses/' + courseId + '/participants' + qs)
      .then(function (res) {
        var participants = Array.isArray(res.data) ? res.data : [];
        var meta = res.meta || {};
        var total = typeof meta.total === 'number' ? meta.total : participants.length;
        var currentPage = meta.page || 1;
        var totalPages = meta.total_pages || 1;
        var perPage = meta.per_page || partState.perPage;

        var countBadge = document.getElementById('participants-tab-count');
        if (countBadge) {
          countBadge.textContent = '(' + total + ')';
        }

        if (lecEl && meta.lecturer) {
          var l = meta.lecturer;
          lecEl.innerHTML = '<div class="px-5 py-3 bg-[var(--color-surface-alt)] border-b border-[var(--color-border-subtle)] flex flex-wrap items-center justify-between gap-2 text-sm">' +
            '<div class="flex items-center gap-2 flex-wrap">' +
            '  ' + roleBadge('dosen') +
            '  <span class="font-bold text-ink">' + esc(l.name) + '</span>' +
            '  <span class="font-mono text-xs text-muted">(' + esc(l.nim_nip) + ')</span>' +
            '</div>' +
            '<span class="text-xs text-secondary">' + esc(l.email) + '</span>' +
            '</div>';
        }

        if (participants.length === 0) {
          listEl.innerHTML = '<div class="p-6">' + (H
            ? H.renderEmpty(
              partState.search ? 'Peserta Tidak Ditemukan' : 'Belum Ada Peserta Terdaftar',
              partState.search ? 'Tidak ada peserta kelas yang cocok dengan kata kunci pencarian.' : 'Belum ada mahasiswa terdaftar pada mata kuliah ini.'
            )
            : '<p class="text-sm text-secondary">Belum ada peserta.</p>') + '</div>';
          return;
        }

        var startIndex = (currentPage - 1) * perPage;
        var rows = participants.map(function (p, index) {
          var nim = p.nim_nip || p.nim || '-';
          var statusTag = p.status === 'active'
            ? '<span class="clarity-tag clarity-tag-success">Aktif</span>'
            : '<span class="clarity-tag clarity-tag-neutral">' + esc(p.status || 'Terdaftar') + '</span>';

          return '<tr>' +
            '<td class="text-xs text-muted font-mono">' + (startIndex + index + 1) + '</td>' +
            '<td class="font-mono text-sm font-semibold text-ink">' + esc(nim) + '</td>' +
            '<td class="font-semibold text-ink">' + esc(p.name) + '<div class="text-xs text-muted font-normal">' + esc(p.email || '') + '</div></td>' +
            '<td>' + roleBadge(p.role) + '</td>' +
            '<td class="text-sm text-secondary">' + esc(p.program_studi || '-') + '</td>' +
            '<td class="text-right">' + statusTag + '</td>' +
            '</tr>';
        }).join('');

        listEl.innerHTML = '<div class="overflow-x-auto">' +
          '<table class="clarity-table w-full">' +
          '<thead><tr>' +
          '<th class="w-12">#</th>' +
          '<th>NIM / NIP</th>' +
          '<th>Nama Lengkap</th>' +
          '<th>Peran</th>' +
          '<th>Program Studi</th>' +
          '<th class="text-right">Status</th>' +
          '</tr></thead>' +
          '<tbody>' + rows + '</tbody>' +
          '</table></div>';

        if (pagEl) {
          var startShown = startIndex + 1;
          var endShown = startIndex + participants.length;
          pagEl.innerHTML = '<div class="px-5 py-4 border-t border-[var(--color-border-subtle)] flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">' +
            '<div class="text-muted">Menampilkan <strong class="text-ink">' + startShown + '–' + endShown + '</strong> dari <strong class="text-ink">' + total + '</strong> mahasiswa</div>' +
            '<div class="flex items-center gap-2">' +
            '  <button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" id="btn-part-prev"' + (currentPage <= 1 ? ' disabled' : '') + '>Sebelumnya</button>' +
            '  <span class="text-xs font-semibold text-ink px-2">Halaman ' + currentPage + ' / ' + totalPages + '</span>' +
            '  <button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" id="btn-part-next"' + (currentPage >= totalPages ? ' disabled' : '') + '>Berikutnya</button>' +
            '</div></div>';

          var prevBtn = document.getElementById('btn-part-prev');
          var nextBtn = document.getElementById('btn-part-next');
          if (prevBtn) prevBtn.addEventListener('click', function () { loadParticipants(currentPage - 1); });
          if (nextBtn) nextBtn.addEventListener('click', function () { loadParticipants(currentPage + 1); });
        }
      })
      .catch(function (err) {
        listEl.innerHTML = '<div class="p-6">' + (H
          ? H.renderError((err && err.message) || 'Gagal memuat daftar peserta kelas.', 'btn-retry-participants')
          : '<p class="text-sm text-danger">Gagal memuat daftar peserta kelas.</p>') + '</div>';
        var retryBtn = document.getElementById('btn-retry-participants');
        if (retryBtn) {
          retryBtn.addEventListener('click', function () { loadParticipants(partState.page); });
        }
      });
  }

  function loadMaterials() {
    var el = document.getElementById('course-materials-list');
    if (!el || !global.ClarityAPI) return;
    loadedTabs.materi = true;
    el.innerHTML = H ? H.renderLoading('Memuat materi perkuliahan...') : 'Memuat materi...';

    global.ClarityAPI.get('/courses/' + courseId + '/materials')
      .then(function (res) {
        var items = Array.isArray(res.data) ? res.data : [];
        if (items.length === 0) {
          el.innerHTML = H.renderEmpty('Belum Ada Materi', 'Dosen belum mengunggah materi untuk mata kuliah ini.');
          return;
        }
        var html = '<div class="clarity-list border border-[var(--color-border)] rounded-lg overflow-hidden">';
        items.forEach(function (m) {
          html += '<div class="clarity-hover-row px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">' +
            '<div class="min-w-0 space-y-1">' +
            '  <div class="flex items-center gap-2 flex-wrap">' +
            '    <span class="clarity-tag clarity-tag-neutral">Pertemuan ' + esc(m.meeting_number || '-') + '</span>' +
            '    <span class="font-bold text-ink">' + esc(m.title) + '</span>' +
            '  </div>' +
            '  <div class="text-xs text-muted">' + (m.type === 'file' ? esc(m.file_name) + ' (' + esc(m.file_size || '-') + ')' : esc(m.url)) + '</div>' +
            '</div>' +
            '<div class="shrink-0">' +
            (m.type === 'file'
              ? '<button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" data-cd-dl-mat="' + m.id + '" data-fn="' + esc(m.file_name || 'materi') + '">Unduh Berkas</button>'
              : '<a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="' + esc(m.url) + '" target="_blank" rel="noopener noreferrer">Buka Tautan</a>') +
            '</div></div>';
        });
        html += '</div>';
        el.innerHTML = html;

        el.querySelectorAll('[data-cd-dl-mat]').forEach(function (b) {
          b.addEventListener('click', function () {
            global.ClarityAPI.download('/materials/' + b.getAttribute('data-cd-dl-mat') + '/download', b.getAttribute('data-fn'))
              .catch(function (err) {
                if (global.ClarityUI) global.ClarityUI.showToast((err && err.message) || 'Gagal mengunduh materi.', 'danger');
              });
          });
        });
      })
      .catch(function (err) {
        loadedTabs.materi = false;
        el.innerHTML = H.renderError(err && err.message, 'btn-retry-cd-mat');
        var r = document.getElementById('btn-retry-cd-mat');
        if (r) r.addEventListener('click', loadMaterials);
      });
  }

  function loadAssignments() {
    var el = document.getElementById('course-assignments-list');
    if (!el || !global.ClarityAPI) return;
    loadedTabs.tugas = true;
    el.innerHTML = H ? H.renderLoading('Memuat daftar tugas dan quiz...') : 'Memuat...';

    var user = currentUser();
    var isDosen = user && user.role === 'dosen';

    Promise.all([
      global.ClarityAPI.get('/courses/' + courseId + '/assignments'),
      global.ClarityAPI.get('/courses/' + courseId + '/quizzes').catch(function () { return { data: [] }; })
    ]).then(function (results) {
      var assignments = Array.isArray(results[0].data) ? results[0].data : [];
      var quizzes = Array.isArray(results[1].data) ? results[1].data : [];

      if (assignments.length === 0 && quizzes.length === 0) {
        el.innerHTML = H.renderEmpty('Belum Ada Tugas atau Quiz', 'Belum ada tugas maupun quiz yang dipublikasikan pada mata kuliah ini.');
        return;
      }

      var html = '<div class="space-y-6">';
      if (assignments.length > 0) {
        html += '<div class="space-y-3"><h3 class="text-base font-bold text-ink m-0">Daftar Tugas</h3>' +
          '<div class="clarity-list border border-[var(--color-border)] rounded-lg overflow-hidden">';
        assignments.forEach(function (a) {
          var targetUrl = isDosen
            ? global.ClarityAPI.pageUrl('submissions.php') + '?course_id=' + courseId + '&assignment_id=' + a.id
            : global.ClarityAPI.pageUrl('assignment.php') + '?course_id=' + courseId + '&assignment_id=' + a.id;
          html += '<div class="clarity-hover-row px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">' +
            '<div class="min-w-0 space-y-1">' +
            '  <div class="flex items-center gap-2 flex-wrap">' +
            '    <span class="font-bold text-ink">' + esc(a.title) + '</span>' +
            '    ' + (a.urgency ? H.urgencyBadge(a.urgency) : '') +
            '  </div>' +
            '  <div class="text-xs text-muted">Batas: ' + esc(H.formatDateTime(a.deadline)) + ' | Bobot ' + esc(a.weight) + '%</div>' +
            '</div>' +
            '<a class="clarity-btn clarity-btn-outline clarity-btn-sm shrink-0" href="' + esc(targetUrl) + '">' + (isDosen ? 'Lihat Pengumpulan' : 'Buka Tugas') + '</a>' +
            '</div>';
        });
        html += '</div></div>';
      }

      if (quizzes.length > 0) {
        html += '<div class="space-y-3"><h3 class="text-base font-bold text-ink m-0">Daftar Quiz</h3>' +
          '<div class="clarity-list border border-[var(--color-border)] rounded-lg overflow-hidden">';
        quizzes.forEach(function (q) {
          var qUrl = isDosen
            ? global.ClarityAPI.pageUrl('quiz-builder.php') + '?course_id=' + courseId + '&quiz_id=' + q.id
            : global.ClarityAPI.pageUrl('quiz-intro.php') + '?course_id=' + courseId + '&quiz_id=' + q.id;
          html += '<div class="clarity-hover-row px-5 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">' +
            '<div class="min-w-0 space-y-1">' +
            '  <div class="font-bold text-ink">' + esc(q.title) + '</div>' +
            '  <div class="text-xs text-muted">Dibuka: ' + esc(H.formatDateTime(q.open_at)) + ' | Ditutup: ' + esc(H.formatDateTime(q.close_at)) + ' (' + esc(q.duration_min) + ' menit)</div>' +
            '</div>' +
            '<a class="clarity-btn clarity-btn-outline clarity-btn-sm shrink-0" href="' + esc(qUrl) + '">' + (isDosen ? 'Kelola Quiz' : 'Buka Quiz') + '</a>' +
            '</div>';
        });
        html += '</div></div>';
      }
      html += '</div>';
      el.innerHTML = html;
    }).catch(function (err) {
      loadedTabs.tugas = false;
      el.innerHTML = H.renderError(err && err.message, 'btn-retry-cd-asg');
      var r = document.getElementById('btn-retry-cd-asg');
      if (r) r.addEventListener('click', loadAssignments);
    });
  }

  function bindParticipantControls() {
    var searchInput = document.getElementById('participants-search');
    var perPageSelect = document.getElementById('participants-per-page');

    if (searchInput) {
      searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
          partState.search = searchInput.value.trim();
          loadParticipants(1);
        }, 300);
      });
    }

    if (perPageSelect) {
      perPageSelect.addEventListener('change', function () {
        partState.perPage = parseInt(perPageSelect.value, 10) || 10;
        loadParticipants(1);
      });
    }
  }

  function init() {
    courseId = getCourseId();
    initBreadcrumbsAndActions();
    initTabs();
    bindParticipantControls();
    loadCourseDetail();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window);
