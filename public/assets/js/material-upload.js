/* ==========================================================================
   Clarity E-Learning — Upload & Kelola Materi Dosen
   ========================================================================== */

(function () {
  'use strict';

  var courseSelect = document.getElementById('mu-course');
  var meetingSelect = document.getElementById('mu-meeting');
  var form = document.getElementById('material-upload-form');
  var listEl = document.getElementById('mu-list-container');
  var feedbackEl = document.getElementById('mu-feedback');
  if (!courseSelect || !meetingSelect || !form || !listEl) return;

  var H = window.ClarityHelpers;
  var activeType = 'file';

  document.querySelectorAll('[data-mat-type]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      activeType = btn.getAttribute('data-mat-type');
      document.querySelectorAll('[data-mat-type]').forEach(function (b) {
        var on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      document.getElementById('mu-file-group').hidden = activeType !== 'file';
      document.getElementById('mu-link-group').hidden = activeType !== 'link';
    });
  });

  function loadCourseData(cid) {
    if (!cid) return;
    window.ClarityAPI.setSelectedCourseId(String(cid));
    listEl.innerHTML = H.renderLoading('Memuat daftar materi...');

    Promise.all([
      window.ClarityAPI.get('/courses/' + cid + '/meetings'),
      window.ClarityAPI.get('/courses/' + cid + '/materials')
    ]).then(function (results) {
      var meetings = Array.isArray(results[0].data) ? results[0].data : [];
      var materials = Array.isArray(results[1].data) ? results[1].data : [];

      if (meetings.length === 0) {
        meetingSelect.innerHTML = '<option value="">Belum ada pertemuan</option>';
      } else {
        meetingSelect.innerHTML = meetings.map(function (m) {
          return '<option value="' + m.id + '">Pertemuan ' + H.esc(m.number) + ': ' + H.esc(m.title) + '</option>';
        }).join('');
      }

      if (materials.length === 0) {
        listEl.innerHTML = H.renderEmpty('Belum Ada Materi', 'Gunakan formulir di sebelah kiri untuk mengunggah materi pertama.');
        return;
      }

      var html = '<div class="clarity-card overflow-hidden">' +
        '<div class="clarity-section-head"><h2 class="text-base font-bold text-ink m-0">Materi Terdaftar (' + materials.length + ')</h2></div>' +
        '<div class="clarity-list">';
      materials.forEach(function (mat) {
        var isFile = mat.type === 'file';
        html += '<div class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">' +
          '<div class="min-w-0 space-y-1">' +
          '  <div class="flex items-center gap-2 flex-wrap">' +
          '    <span class="clarity-tag clarity-tag-neutral">Pertemuan ' + H.esc(mat.meeting_number || '-') + '</span>' +
          '    <span class="clarity-tag ' + (isFile ? 'clarity-tag-info' : 'clarity-tag-warning') + '">' + (isFile ? 'Berkas' : 'Tautan') + '</span>' +
          '    <span class="font-bold text-ink">' + H.esc(mat.title) + '</span>' +
          '  </div>' +
          '  <div class="text-xs text-muted truncate">' + (isFile ? H.esc(mat.file_name) + ' (' + H.esc(mat.file_size || '-') + ')' : H.esc(mat.url)) + '</div>' +
          '</div>' +
          '<div class="flex items-center gap-2 shrink-0">' +
          (isFile
            ? '<button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" data-dl-mat="' + mat.id + '" data-fn="' + H.esc(mat.file_name || 'materi') + '">Unduh</button>'
            : '<a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="' + H.esc(mat.url) + '" target="_blank" rel="noopener noreferrer">Buka</a>') +
          '  <button type="button" class="clarity-btn clarity-btn-crimson clarity-btn-sm" data-del-mat="' + mat.id + '">Hapus</button>' +
          '</div></div>';
      });
      html += '</div></div>';
      listEl.innerHTML = html;

      listEl.querySelectorAll('[data-dl-mat]').forEach(function (b) {
        b.addEventListener('click', function () {
          window.ClarityAPI.download('/materials/' + b.getAttribute('data-dl-mat') + '/download', b.getAttribute('data-fn'));
        });
      });

      listEl.querySelectorAll('[data-del-mat]').forEach(function (b) {
        b.addEventListener('click', function () {
          if (!window.confirm('Hapus materi ini?')) return;
          window.ClarityAPI.del('/materials/' + b.getAttribute('data-del-mat'))
            .then(function () {
              if (window.ClarityUI) window.ClarityUI.showToast('Materi dihapus.', 'info');
              loadCourseData(cid);
            });
        });
      });
    }).catch(function (err) {
      listEl.innerHTML = H.renderError(err && err.message, 'btn-retry-mu');
      var r = document.getElementById('btn-retry-mu');
      if (r) r.addEventListener('click', function () { loadCourseData(cid); });
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    feedbackEl.innerHTML = '';
    var cid = courseSelect.value;
    var mid = meetingSelect.value;
    var title = document.getElementById('mu-title').value.trim();
    if (!cid || !mid) {
      feedbackEl.innerHTML = '<div class="clarity-alert clarity-alert-danger">Pilih mata kuliah dan pertemuan terlebih dahulu.</div>';
      return;
    }

    var fd = new FormData();
    fd.append('meeting_id', mid);
    fd.append('title', title);
    fd.append('type', activeType);

    if (activeType === 'file') {
      var fileInput = document.getElementById('mu-file');
      if (!fileInput.files || !fileInput.files[0]) {
        feedbackEl.innerHTML = '<div class="clarity-alert clarity-alert-danger">Pilih berkas materi untuk diunggah.</div>';
        return;
      }
      fd.append('file', fileInput.files[0]);
    } else {
      var urlVal = document.getElementById('mu-url').value.trim();
      if (!urlVal) {
        feedbackEl.innerHTML = '<div class="clarity-alert clarity-alert-danger">Masukkan URL tautan yang valid.</div>';
        return;
      }
      fd.append('url', urlVal);
    }

    var btn = document.getElementById('btn-upload-material');
    btn.disabled = true;
    btn.textContent = 'Mengunggah...';
    window.ClarityAPI.post('/courses/' + cid + '/materials', fd)
      .then(function () {
        document.getElementById('mu-title').value = '';
        document.getElementById('mu-file').value = '';
        document.getElementById('mu-url').value = '';
        if (window.ClarityUI) window.ClarityUI.showToast('Materi berhasil disimpan.', 'success');
        loadCourseData(cid);
      })
      .catch(function (err) {
        feedbackEl.innerHTML = '<div class="clarity-alert clarity-alert-danger">' + H.esc((err && err.message) || 'Gagal mengunggah materi.') + '</div>';
      })
      .finally(function () {
        btn.disabled = false;
        btn.textContent = 'Simpan Materi';
      });
  });

  courseSelect.addEventListener('change', function () {
    loadCourseData(courseSelect.value);
  });

  function init() {
    listEl.innerHTML = H.renderLoading('Memuat mata kuliah...');
    window.ClarityAPI.get('/courses?per_page=50')
      .then(function (res) {
        var courses = Array.isArray(res.data) ? res.data : [];
        if (courses.length === 0) {
          listEl.innerHTML = H.renderEmpty('Belum Ada Mata Kuliah', 'Buat mata kuliah di halaman Kelola Mata Kuliah terlebih dahulu.');
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
        loadCourseData(savedCid);
      })
      .catch(function (err) {
        listEl.innerHTML = H.renderError(err && err.message, 'btn-retry-mu-init');
        var r = document.getElementById('btn-retry-mu-init');
        if (r) r.addEventListener('click', init);
      });
  }

  init();
})();
