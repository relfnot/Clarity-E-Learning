/* ==========================================================================
   Clarity E-Learning — Tabel Pengumpulan Tugas Dosen (Item 19)
   ========================================================================== */

(function () {
  'use strict';

  var courseSelect = document.getElementById('sub-course-select');
  var asgSelect = document.getElementById('sub-assignment-select');
  var summaryEl = document.getElementById('sub-summary-container');
  var tableEl = document.getElementById('sub-table-container');
  var exportBtn = document.getElementById('btn-export-csv');
  if (!courseSelect || !asgSelect || !summaryEl || !tableEl) return;

  var H = window.ClarityHelpers;
  var statusFilter = '';

  function renderSummary(s) {
    if (!s) {
      summaryEl.innerHTML = '';
      return;
    }
    summaryEl.innerHTML = '<div class="stat-ledger">' +
      '<div class="stat-cell"><div class="stat-label">Total Peserta</div><div class="stat-value text-ink">' + H.esc(s.total) + '</div></div>' +
      '<div class="stat-cell"><div class="stat-label">Tepat Waktu</div><div class="stat-value text-success">' + H.esc(s.tepat_waktu) + '</div></div>' +
      '<div class="stat-cell"><div class="stat-label">Terlambat</div><div class="stat-value text-warning">' + H.esc(s.terlambat) + '</div></div>' +
      '<div class="stat-cell"><div class="stat-label">Belum Kumpul</div><div class="stat-value text-accent">' + H.esc(s.belum) + '</div></div>' +
      '</div>';
  }

  function loadSubmissions() {
    var aid = asgSelect.value;
    if (!aid) {
      exportBtn.disabled = true;
      summaryEl.innerHTML = '';
      tableEl.innerHTML = '<div class="p-6 text-sm text-muted">Pilih tugas terlebih dahulu untuk melihat tabel pengumpulan.</div>';
      return;
    }

    exportBtn.disabled = false;
    tableEl.innerHTML = '<div class="p-6">' + H.renderLoading('Memuat data pengumpulan mahasiswa...') + '</div>';
    var qs = '?per_page=100' + (statusFilter ? '&status=' + encodeURIComponent(statusFilter) : '');

    window.ClarityAPI.get('/assignments/' + aid + '/submissions' + qs)
      .then(function (res) {
        var items = Array.isArray(res.data) ? res.data : [];
        var meta = res.meta || {};
        renderSummary(meta.summary);

        if (items.length === 0) {
          tableEl.innerHTML = '<div class="p-6">' + H.renderEmpty(
            'Tidak Ada Data Pengumpulan',
            'Tidak ada mahasiswa dengan status yang sesuai filter.'
          ) + '</div>';
          return;
        }

        var html = '<div class="overflow-x-auto"><table class="clarity-table w-full">' +
          '<thead><tr><th>NIM</th><th>Nama Mahasiswa</th><th>Status</th><th>Waktu Kumpul</th><th>Berkas / Tautan</th></tr></thead><tbody>';

        items.forEach(function (row) {
          var artifactCell = '<span class="text-xs text-muted">-</span>';
          if (row.download_url) {
            artifactCell = '<button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" data-dl-sub="' + row.id + '" data-fn="' + H.esc(row.file_name || 'tugas') + '">Unduh (' + H.esc(row.file_name) + ')</button>';
          } else if (row.link_url) {
            artifactCell = '<a class="clarity-inline-link text-sm" href="' + H.esc(row.link_url) + '" target="_blank" rel="noopener noreferrer">Buka Tautan</a>';
          }

          html += '<tr>' +
            '<td class="font-mono text-sm">' + H.esc(row.nim_nip) + '</td>' +
            '<td class="font-semibold text-ink">' + H.esc(row.student_name) + '</td>' +
            '<td>' + H.submissionStatusBadge(row.status) + '</td>' +
            '<td class="text-sm text-muted">' + H.esc(row.submitted_at ? H.formatDateTime(row.submitted_at) : '-') + '</td>' +
            '<td>' + artifactCell + '</td>' +
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
      })
      .catch(function (err) {
        tableEl.innerHTML = '<div class="p-6">' + H.renderError(err && err.message, 'btn-retry-sub') + '</div>';
        var r = document.getElementById('btn-retry-sub');
        if (r) r.addEventListener('click', loadSubmissions);
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
          loadSubmissions();
          return;
        }
        var paramAid = window.ClarityUI ? window.ClarityUI.getQueryParam('assignment_id') : null;
        asgSelect.innerHTML = list.map(function (a) {
          return '<option value="' + a.id + '">' + H.esc(a.title) + '</option>';
        }).join('');
        if (paramAid && list.some(function (a) { return String(a.id) === String(paramAid); })) {
          asgSelect.value = paramAid;
        }
        loadSubmissions();
      })
      .catch(function (err) {
        tableEl.innerHTML = '<div class="p-6">' + H.renderError(err && err.message, 'btn-retry-sub-asg') + '</div>';
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
      loadSubmissions();
    });
  });

  exportBtn.addEventListener('click', function () {
    var aid = asgSelect.value;
    if (!aid) return;
    var qs = '?format=csv' + (statusFilter ? '&status=' + encodeURIComponent(statusFilter) : '');
    window.ClarityAPI.download('/assignments/' + aid + '/submissions' + qs, 'pengumpulan-tugas-' + aid + '.csv')
      .catch(function (err) {
        if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal mengekspor CSV.', 'danger');
      });
  });

  courseSelect.addEventListener('change', function () { loadAssignments(courseSelect.value); });
  asgSelect.addEventListener('change', loadSubmissions);

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
      });
  }

  init();
})();
