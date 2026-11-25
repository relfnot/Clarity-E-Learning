/* ==========================================================================
   Clarity E-Learning — Tugas & Pengumpulan Mahasiswa
   ========================================================================== */

(function () {
  'use strict';

  var courseSelect = document.getElementById('assignment-course-filter');
  var listEl = document.getElementById('assignment-list-container');
  var detailEl = document.getElementById('assignment-detail-container');
  if (!courseSelect || !listEl || !detailEl) return;

  var H = window.ClarityHelpers;
  var courses = [];
  var assignments = [];
  var statusFilter = 'all';
  var selectedAssignmentId = window.ClarityUI ? window.ClarityUI.getQueryParam('assignment_id') : null;

  function renderDetail(a) {
    if (!a) {
      detailEl.innerHTML = H.renderEmpty(
        'Pilih Tugas dari Daftar',
        'Klik salah satu tugas di sebelah kiri untuk melihat instruksi dan mengumpulkan jawaban.'
      );
      return;
    }

    selectedAssignmentId = String(a.id);
    var sub = a.my_submission;
    var status = sub ? sub.status : 'belum';
    var defaultMode = a.accept_file ? 'file' : 'link';

    var subHtml = '';
    if (sub) {
      subHtml = '<div class="clarity-well p-4 space-y-2">' +
        '<div class="flex items-center justify-between gap-2 flex-wrap">' +
        '  <span class="text-sm font-bold text-ink">Status Pengumpulan Anda</span>' +
        H.submissionStatusBadge(status) +
        '</div>' +
        '<div class="text-xs text-muted">Dikumpulkan pada: ' + H.esc(H.formatDateTime(sub.submitted_at)) + '</div>' +
        (sub.file_name
          ? '<div class="flex items-center gap-3 flex-wrap"><span class="text-sm text-ink font-medium">' + H.esc(sub.file_name) + ' (' + H.esc(sub.file_size || '-') + ')</span>' +
            '<button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" id="btn-download-my-sub" data-sub-id="' + sub.id + '" data-filename="' + H.esc(sub.file_name) + '">Unduh Berkas Saya</button></div>'
          : '') +
        (sub.link_url
          ? '<div class="text-sm"><a class="clarity-inline-link" href="' + H.esc(sub.link_url) + '" target="_blank" rel="noopener noreferrer">' + H.esc(sub.link_url) + '</a></div>'
          : '') +
        (sub.score !== null && sub.score !== undefined
          ? '<div class="text-sm font-bold text-success">Nilai: ' + H.esc(sub.score) + ' / ' + H.esc(a.max_score) + '</div>'
          : '') +
        (sub.feedback ? '<div class="text-sm text-secondary">Catatan dosen: ' + H.esc(sub.feedback) + '</div>' : '') +
        '</div>';
    }

    var modeTabs = '';
    if (a.accept_file && a.accept_link) {
      modeTabs = '<div class="filter-tab-group" role="tablist" aria-label="Mode Pengumpulan">' +
        '<button type="button" role="tab" class="filter-tab-btn is-active" data-sub-mode="file" aria-selected="true">Unggah Berkas</button>' +
        '<button type="button" role="tab" class="filter-tab-btn" data-sub-mode="link" aria-selected="false">Kirim Tautan</button>' +
        '</div>';
    }

    detailEl.innerHTML = '<article class="clarity-card p-6 space-y-5">' +
      '<div class="space-y-2">' +
      '  <div class="flex items-center gap-2 flex-wrap">' +
      '    <span class="course-code-pill">' + H.esc(a.course_code) + '</span>' +
      H.urgencyBadge(a.urgency) +
      '  </div>' +
      '  <h2 class="text-xl font-extrabold text-ink m-0">' + H.esc(a.title) + '</h2>' +
      '  <div class="meta-row">' +
      '    <span class="meta-item">Tenggat: ' + H.esc(H.formatDateTime(a.deadline)) + '</span>' +
      '    <span class="meta-item">Bobot: ' + H.esc(a.weight) + '%</span>' +
      '    <span class="meta-item">Skor Maks: ' + H.esc(a.max_score) + '</span>' +
      '  </div>' +
      '  <p class="text-sm text-secondary m-0 pt-2">' + H.esc(a.description || 'Tidak ada instruksi tambahan.') + '</p>' +
      '</div>' +
      subHtml +
      '<form id="submission-form" class="space-y-4 pt-4 border-t border-[var(--color-border-subtle)]">' +
      '  <div class="flex items-center justify-between gap-3 flex-wrap">' +
      '    <h3 class="text-base font-bold text-ink m-0">' + (sub ? 'Kumpulkan Ulang Tugas' : 'Form Pengumpulan Tugas') + '</h3>' +
      modeTabs +
      '  </div>' +
      '  <div id="sub-mode-file-wrap" ' + (defaultMode === 'file' ? '' : 'hidden') + '>' +
      '    <label for="sub-file-input" class="block text-sm font-semibold text-ink mb-1.5">Pilih Berkas (Maks 10 MB: pdf, doc, docx, zip, jpg, png, txt)</label>' +
      '    <input id="sub-file-input" name="file" type="file" class="clarity-input w-full">' +
      '  </div>' +
      '  <div id="sub-mode-link-wrap" ' + (defaultMode === 'link' ? '' : 'hidden') + '>' +
      '    <label for="sub-link-input" class="block text-sm font-semibold text-ink mb-1.5">Tautan Jawaban (http:// atau https://)</label>' +
      '    <input id="sub-link-input" name="link_url" type="url" class="clarity-input w-full" placeholder="https://github.com/username/repo-tugas">' +
      '  </div>' +
      '  <div id="sub-form-feedback" aria-live="polite"></div>' +
      '  <button type="submit" id="btn-submit-assignment" class="clarity-btn clarity-btn-primary">Kirim Pengumpulan</button>' +
      '</form>' +
      '</article>';

    var activeMode = defaultMode;
    detailEl.querySelectorAll('[data-sub-mode]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        activeMode = btn.getAttribute('data-sub-mode');
        detailEl.querySelectorAll('[data-sub-mode]').forEach(function (b) {
          var on = b === btn;
          b.classList.toggle('is-active', on);
          b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.getElementById('sub-mode-file-wrap').hidden = activeMode !== 'file';
        document.getElementById('sub-mode-link-wrap').hidden = activeMode !== 'link';
      });
    });

    var dlBtn = document.getElementById('btn-download-my-sub');
    if (dlBtn) {
      dlBtn.addEventListener('click', function () {
        var sid = dlBtn.getAttribute('data-sub-id');
        var fname = dlBtn.getAttribute('data-filename') || 'tugas';
        window.ClarityAPI.download('/submissions/' + sid + '/download', fname).catch(function (err) {
          if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal mengunduh.', 'danger');
        });
      });
    }

    var form = document.getElementById('submission-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var feedbackEl = document.getElementById('sub-form-feedback');
        var submitBtn = document.getElementById('btn-submit-assignment');
        feedbackEl.innerHTML = '';

        var fd = new FormData();
        if (activeMode === 'file') {
          var fileInput = document.getElementById('sub-file-input');
          if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            feedbackEl.innerHTML = '<div class="clarity-alert clarity-alert-danger">Pilih berkas terlebih dahulu sebelum mengirim.</div>';
            return;
          }
          fd.append('file', fileInput.files[0]);
        } else {
          var linkInput = document.getElementById('sub-link-input');
          var linkVal = linkInput ? linkInput.value.trim() : '';
          if (!linkVal) {
            feedbackEl.innerHTML = '<div class="clarity-alert clarity-alert-danger">Masukkan tautan http/https terlebih dahulu.</div>';
            return;
          }
          fd.append('link_url', linkVal);
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Mengirim...';
        window.ClarityAPI.post('/assignments/' + a.id + '/submissions', fd)
          .then(function () {
            if (window.ClarityUI) window.ClarityUI.showToast('Tugas berhasil dikumpulkan.', 'success');
            loadAssignmentsForCourse(courseSelect.value);
          })
          .catch(function (err) {
            feedbackEl.innerHTML = '<div class="clarity-alert clarity-alert-danger">' + H.esc((err && err.message) || 'Gagal mengumpulkan tugas.') + '</div>';
          })
          .finally(function () {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Kirim Pengumpulan';
          });
      });
    }
  }

  function renderList() {
    var filtered = assignments.filter(function (a) {
      var hasSub = Boolean(a.my_submission);
      if (statusFilter === 'belum') return !hasSub;
      if (statusFilter === 'submitted') return hasSub;
      return true;
    });

    if (filtered.length === 0) {
      listEl.innerHTML = H.renderEmpty(
        'Tidak Ada Tugas',
        'Belum ada tugas pada mata kuliah atau filter status yang dipilih.'
      );
      renderDetail(null);
      return;
    }

    var chosen = filtered.find(function (a) { return String(a.id) === String(selectedAssignmentId); }) || filtered[0];
    var html = '';
    filtered.forEach(function (a) {
      var active = String(a.id) === String(chosen.id);
      var status = a.my_submission ? a.my_submission.status : 'belum';
      html += '<button type="button" class="clarity-card w-full text-left p-4 space-y-2 transition-colors ' +
        (active ? 'border-[var(--color-accent)] bg-[var(--color-row-hover)]' : '') + '" data-select-asg="' + a.id + '">' +
        '<div class="flex items-center justify-between gap-2">' +
        '  <span class="text-xs font-bold text-muted">' + H.esc(a.course_code) + '</span>' +
        H.submissionStatusBadge(status) +
        '</div>' +
        '<div class="font-bold text-ink text-sm leading-snug">' + H.esc(a.title) + '</div>' +
        '<div class="text-xs text-muted">Batas: ' + H.esc(H.formatDateTime(a.deadline)) + '</div>' +
        '</button>';
    });
    listEl.innerHTML = html;

    listEl.querySelectorAll('[data-select-asg]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = btn.getAttribute('data-select-asg');
        var target = assignments.find(function (x) { return String(x.id) === String(id); });
        if (target) {
          selectedAssignmentId = String(target.id);
          renderList();
        }
      });
    });

    renderDetail(chosen);
  }

  function loadAssignmentsForCourse(cid) {
    if (!cid) return;
    window.ClarityAPI.setSelectedCourseId(String(cid));
    listEl.innerHTML = H.renderLoading('Memuat daftar tugas...');
    window.ClarityAPI.get('/courses/' + cid + '/assignments')
      .then(function (res) {
        assignments = Array.isArray(res.data) ? res.data : [];
        renderList();
      })
      .catch(function (err) {
        listEl.innerHTML = H.renderError(err && err.message, 'btn-retry-asg-list');
        var r = document.getElementById('btn-retry-asg-list');
        if (r) r.addEventListener('click', function () { loadAssignmentsForCourse(cid); });
      });
  }

  document.querySelectorAll('[data-status-filter]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      statusFilter = btn.getAttribute('data-status-filter');
      document.querySelectorAll('[data-status-filter]').forEach(function (b) {
        var on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      renderList();
    });
  });

  courseSelect.addEventListener('change', function () {
    selectedAssignmentId = null;
    loadAssignmentsForCourse(courseSelect.value);
  });

  function init() {
    listEl.innerHTML = H.renderLoading('Memuat mata kuliah...');
    window.ClarityAPI.get('/courses?per_page=50')
      .then(function (res) {
        courses = Array.isArray(res.data) ? res.data : [];
        if (courses.length === 0) {
          listEl.innerHTML = H.renderEmpty('Belum Ada Mata Kuliah', 'Anda belum terdaftar pada mata kuliah apa pun.');
          detailEl.innerHTML = '';
          return;
        }

        var paramCourseId = window.ClarityUI ? window.ClarityUI.getQueryParam('course_id') : null;
        var savedCourseId = paramCourseId || window.ClarityAPI.getSelectedCourseId() || String(courses[0].id);
        if (!courses.some(function (c) { return String(c.id) === String(savedCourseId); })) {
          savedCourseId = String(courses[0].id);
        }

        courseSelect.innerHTML = courses.map(function (c) {
          return '<option value="' + c.id + '">' + H.esc(c.code + ' - ' + c.name) + '</option>';
        }).join('');
        courseSelect.value = savedCourseId;
        loadAssignmentsForCourse(savedCourseId);
      })
      .catch(function (err) {
        listEl.innerHTML = H.renderError(err && err.message, 'btn-retry-init-asg');
        var r = document.getElementById('btn-retry-init-asg');
        if (r) r.addEventListener('click', init);
      });
  }

  init();
})();
