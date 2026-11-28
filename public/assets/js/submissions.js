/* ==========================================================================
   Clarity E-Learning — Tabel Pengumpulan Tugas Dosen (Item 19)
   ========================================================================== */

(function () {
  'use strict';

  var courseSelect = document.getElementById('sub-course-select');
  var asgSelect = document.getElementById('sub-assignment-select');
  var summaryEl = document.getElementById('sub-summary-container');
  var tableEl = document.getElementById('sub-table-container');
  var pagEl = document.getElementById('sub-pagination-container');
  var searchInput = document.getElementById('sub-search-input');
  var exportBtn = document.getElementById('btn-export-csv');
  if (!courseSelect || !asgSelect || !summaryEl || !tableEl) return;

  var H = window.ClarityHelpers;
  var statusFilter = '';
  var searchTerm = '';
  var currentPage = 1;
  var searchTimer = null;

  function renderSummary(s) {
    if (!s) {
      summaryEl.innerHTML = '';
      return;
    }
    summaryEl.innerHTML = '<div class="clarity-card p-5"><div class="stat-ledger stat-ledger-5">' +
      '<div class="stat-cell"><div class="stat-label">Total Peserta</div><div class="stat-value text-ink">' + H.esc(s.total) + '</div></div>' +
      '<div class="stat-cell"><div class="stat-label">Tepat Waktu</div><div class="stat-value text-success">' + H.esc(s.tepat_waktu) + '</div></div>' +
      '<div class="stat-cell"><div class="stat-label">Terlambat</div><div class="stat-value text-warning">' + H.esc(s.terlambat) + '</div></div>' +
      '<div class="stat-cell"><div class="stat-label">Dinilai</div><div class="stat-value text-ink">' + H.esc(s.dinilai || 0) + '</div></div>' +
      '<div class="stat-cell"><div class="stat-label">Belum Kumpul</div><div class="stat-value text-accent">' + H.esc(s.belum) + '</div></div>' +
      '</div></div>';
  }

  function loadSubmissions(page) {
    if (typeof page === 'number') currentPage = page;
    var aid = asgSelect.value;
    if (pagEl) pagEl.innerHTML = '';

    if (!aid) {
      exportBtn.disabled = true;
      summaryEl.innerHTML = '';
      tableEl.innerHTML = '<div class="p-6 text-sm text-muted">Pilih tugas terlebih dahulu untuk melihat tabel pengumpulan.</div>';
      return;
    }

    exportBtn.disabled = false;
    tableEl.innerHTML = '<div class="p-6">' + H.renderLoading('Memuat data pengumpulan mahasiswa...') + '</div>';
    var qs = '?page=' + currentPage + '&per_page=20' +
      (statusFilter ? '&status=' + encodeURIComponent(statusFilter) : '') +
      (searchTerm ? '&search=' + encodeURIComponent(searchTerm) : '');

    window.ClarityAPI.get('/assignments/' + aid + '/submissions' + qs)
      .then(function (res) {
        var items = Array.isArray(res.data) ? res.data : [];
        var meta = res.meta || {};
        renderSummary(meta.summary);

        if (items.length === 0) {
          tableEl.innerHTML = '<div class="p-6">' + H.renderEmpty(
            'Tidak Ada Data Pengumpulan',
            'Tidak ada mahasiswa dengan status atau pencarian yang sesuai filter.'
          ) + '</div>';
          return;
        }

        var html = '<div class="overflow-x-auto"><table class="clarity-table w-full">' +
          '<thead><tr><th>NIM</th><th>Nama Mahasiswa</th><th>Status</th><th>Waktu Kumpul</th><th>Berkas / Tautan</th><th>Nilai</th></tr></thead><tbody>';

        items.forEach(function (row) {
          var parts = [];
          if (row.download_url) {
            parts.push('<button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" data-dl-sub="' + row.id + '" data-fn="' + H.esc(row.file_name || 'tugas') + '">Unduh (' + H.esc(row.file_name) + ')</button>');
          }
          if (row.link_url) {
            parts.push('<a class="clarity-inline-link text-sm" href="' + H.esc(row.link_url) + '" target="_blank" rel="noopener noreferrer">Buka Tautan</a>');
          }
          var artifactCell = parts.length > 0 ? '<div class="flex flex-wrap items-center gap-2">' + parts.join('') + '</div>' : '<span class="text-xs text-muted">-</span>';

          var statusCell = H.submissionStatusBadge(row.status);
          if (row.status === 'dinilai' && row.is_late) {
            statusCell += ' <span class="clarity-tag clarity-tag-warning ml-1">Terlambat</span>';
          }

          var scoreCell = row.score !== null && row.score !== undefined
            ? '<span class="font-mono font-bold text-ink">' + H.esc(row.score) + '</span>'
            : '<span class="text-xs text-muted">-</span>';

          html += '<tr>' +
            '<td class="font-mono text-sm">' + H.esc(row.nim_nip) + '</td>' +
            '<td class="font-semibold text-ink">' + H.esc(row.student_name) + '</td>' +
            '<td>' + statusCell + '</td>' +
            '<td class="text-sm text-muted">' + H.esc(row.submitted_at ? H.formatDateTime(row.submitted_at) : '-') + '</td>' +
            '<td>' + artifactCell + '</td>' +
            '<td>' + scoreCell + '</td>' +
            '</tr>';
        });
        html += '</tbody></table></div>';
        tableEl.innerHTML = html;

        tableEl.querySelectorAll('[data-dl-sub]').forEach(function (b) {
          b.addEventListener('click', function () {
            window.ClarityAPI.download('/submissions/' + b.getAttribute('data-dl-sub') + '/download', b.getAttribute('data-fn'))
              .catch(function (err) {
                if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal mengunduh berkas.', 'danger');
              });
          });
        });

        var total = typeof meta.total === 'number' ? meta.total : items.length;
        var pageNum = meta.page || 1;
        var totalPages = meta.total_pages || 1;
        if (pagEl && total > 0) {
          pagEl.innerHTML = '<div class="px-5 py-4 border-t border-[var(--color-border-subtle)] flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">' +
            '<div class="text-muted">Menampilkan <strong class="text-ink">' + items.length + '</strong> dari <strong class="text-ink">' + total + '</strong> peserta sesuai filter</div>' +
            '<div class="flex items-center gap-2">' +
            '  <button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" id="btn-sub-prev"' + (pageNum <= 1 ? ' disabled' : '') + '>Sebelumnya</button>' +
            '  <span class="text-xs font-semibold text-ink px-2">Halaman ' + pageNum + ' / ' + totalPages + '</span>' +
            '  <button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" id="btn-sub-next"' + (pageNum >= totalPages ? ' disabled' : '') + '>Berikutnya</button>' +
            '</div></div>';

          var prevBtn = document.getElementById('btn-sub-prev');
          var nextBtn = document.getElementById('btn-sub-next');
          if (prevBtn) prevBtn.addEventListener('click', function () { loadSubmissions(pageNum - 1); });
          if (nextBtn) nextBtn.addEventListener('click', function () { loadSubmissions(pageNum + 1); });
        }
      })
      .catch(function (err) {
        tableEl.innerHTML = '<div class="p-6">' + H.renderError(err && err.message, 'btn-retry-sub') + '</div>';
        var r = document.getElementById('btn-retry-sub');
        if (r) r.addEventListener('click', function () { loadSubmissions(currentPage); });
      });
  }

  function loadAssignments(cid) {
    if (!cid) return;
    window.ClarityAPI.setSelectedCourseId(String(cid));
    asgSelect.innerHTML = '<option value="">Memuat tugas...</option>';
    window.ClarityAPI.get('/courses/' + cid + '/assignments')
      .then(function (res) {
        var list = Array.isArray(res.data) ? res.data : [];
        if (list.length === 0) {
          asgSelect.innerHTML = '<option value="">Belum ada tugas</option>';
          loadSubmissions(1);
          return;
        }
        var paramAid = window.ClarityUI ? window.ClarityUI.getQueryParam('assignment_id') : null;
        asgSelect.innerHTML = list.map(function (a) {
          return '<option value="' + a.id + '">' + H.esc(a.title) + '</option>';
        }).join('');
        if (paramAid && list.some(function (a) { return String(a.id) === String(paramAid); })) {
          asgSelect.value = paramAid;
        }
        loadSubmissions(1);
      })
      .catch(function (err) {
        tableEl.innerHTML = '<div class="p-6">' + H.renderError(err && err.message, 'btn-retry-sub-asg') + '</div>';
        var r = document.getElementById('btn-retry-sub-asg');
        if (r) r.addEventListener('click', function () { loadAssignments(cid); });
      });
  }

  document.querySelectorAll('[data-sub-status]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      statusFilter = btn.getAttribute('data-sub-status') || '';
      document.querySelectorAll('[data-sub-status]').forEach(function (b) {
        var on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      loadSubmissions(1);
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        searchTerm = searchInput.value.trim();
        loadSubmissions(1);
      }, 300);
    });
  }

  exportBtn.addEventListener('click', function () {
    var aid = asgSelect.value;
    if (!aid) return;
    var qs = '?format=csv' +
      (statusFilter ? '&status=' + encodeURIComponent(statusFilter) : '') +
      (searchTerm ? '&search=' + encodeURIComponent(searchTerm) : '');
    window.ClarityAPI.download('/assignments/' + aid + '/submissions' + qs, 'pengumpulan-tugas-' + aid + '.csv')
      .catch(function (err) {
        if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal mengekspor CSV.', 'danger');
      });
  });

  courseSelect.addEventListener('change', function () { loadAssignments(courseSelect.value); });
  asgSelect.addEventListener('change', function () { loadSubmissions(1); });

  function init() {
    window.ClarityAPI.get('/courses?per_page=50')
      .then(function (res) {
        var courses = Array.isArray(res.data) ? res.data : [];
        if (courses.length === 0) {
          tableEl.innerHTML = '<div class="p-6">' + H.renderEmpty('Belum Ada Mata Kuliah', 'Buat mata kuliah di Kelola Mata Kuliah.') + '</div>';
          return;
        }
        var paramCid = window.ClarityUI ? window.ClarityUI.getQueryParam('course_id') : null;
        var savedCid = paramCid || window.ClarityAPI.getSelectedCourseId() || String(courses[0].id);
        if (!courses.some(function (c) { return String(c.id) === String(savedCid); })) {
          savedCid = String(courses[0].id);
        }
        courseSelect.innerHTML = courses.map(function (c) {
          return '<option value="' + c.id + '">' + H.esc(c.code + ' - ' + c.name) + '</option>';
        }).join('');
        courseSelect.value = savedCid;
        loadAssignments(savedCid);
      })
      .catch(function (err) {
        tableEl.innerHTML = '<div class="p-6">' + H.renderError(err && err.message, 'btn-retry-sub-init') + '</div>';
        var r = document.getElementById('btn-retry-sub-init');
        if (r) r.addEventListener('click', init);
      });
  }

  init();
})();
