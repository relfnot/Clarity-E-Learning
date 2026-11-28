/* ==========================================================================
   Clarity E-Learning — Kelola Mata Kuliah Dosen
   ========================================================================== */

(function () {
  'use strict';

  var courseSelect = document.getElementById('manage-course-select');
  var infoEl = document.getElementById('manage-course-info');
  var tabsSection = document.getElementById('manage-tabs-section');
  var panelEl = document.getElementById('manage-tab-panel');
  var modal = document.getElementById('course-modal');
  var modalForm = document.getElementById('course-modal-form');
  var modalTitle = document.getElementById('course-modal-title');
  var modalFeedback = document.getElementById('course-modal-feedback');
  if (!courseSelect || !infoEl || !panelEl) return;

  var H = window.ClarityHelpers;
  var courses = [];
  var activeCourse = null;
  var activeTab = 'materials';
  var editingCourseId = null;

  function openCourseModal(course) {
    editingCourseId = course ? course.id : null;
    modalTitle.textContent = course ? 'Ubah Informasi Mata Kuliah' : 'Buat Mata Kuliah Baru';
    modalFeedback.innerHTML = '';
    document.getElementById('cm-code').value = course ? course.code : '';
    document.getElementById('cm-credits').value = course ? course.credits : 3;
    document.getElementById('cm-name').value = course ? course.name : '';
    document.getElementById('cm-semester').value = course ? course.semester : 'Ganjil 2026/2027';
    document.getElementById('cm-day').value = course ? course.day : '';
    document.getElementById('cm-start').value = course ? course.start_time : '';
    document.getElementById('cm-end').value = course ? course.end_time : '';
    document.getElementById('cm-room').value = course ? course.room : '';
    document.getElementById('cm-desc').value = course ? (course.description || '') : '';
    modal.hidden = false;
    document.getElementById('cm-code').focus();
  }

  function closeCourseModal() {
    modal.hidden = true;
  }

  document.getElementById('btn-new-course').addEventListener('click', function () { openCourseModal(null); });
  document.getElementById('btn-edit-course').addEventListener('click', function () {
    if (activeCourse) openCourseModal(activeCourse);
  });
  document.getElementById('btn-close-course-modal').addEventListener('click', closeCourseModal);
  document.getElementById('btn-cancel-course-modal').addEventListener('click', closeCourseModal);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) closeCourseModal();
  });

  modalForm.addEventListener('submit', function (e) {
    e.preventDefault();
    modalFeedback.innerHTML = '';
    var payload = {
      code: document.getElementById('cm-code').value.trim(),
      credits: Number(document.getElementById('cm-credits').value),
      name: document.getElementById('cm-name').value.trim(),
      semester: document.getElementById('cm-semester').value.trim(),
      day: document.getElementById('cm-day').value.trim(),
      start_time: document.getElementById('cm-start').value.trim(),
      end_time: document.getElementById('cm-end').value.trim(),
      room: document.getElementById('cm-room').value.trim(),
      description: document.getElementById('cm-desc').value.trim()
    };

    var saveBtn = document.getElementById('btn-save-course-modal');
    saveBtn.disabled = true;
    var req = editingCourseId
      ? window.ClarityAPI.put('/courses/' + editingCourseId, payload)
      : window.ClarityAPI.post('/courses', payload);

    req.then(function (res) {
      closeCourseModal();
      if (window.ClarityUI) window.ClarityUI.showToast('Mata kuliah berhasil disimpan.', 'success');
      var savedId = res && res.data ? String(res.data.id) : null;
      if (savedId) window.ClarityAPI.setSelectedCourseId(savedId);
      initCourses();
    }).catch(function (err) {
      modalFeedback.innerHTML = '<div class="clarity-alert clarity-alert-danger">' + H.esc((err && err.message) || 'Gagal menyimpan mata kuliah.') + '</div>';
    }).finally(function () {
      saveBtn.disabled = false;
    });
  });

  function renderCourseInfo(c) {
    infoEl.innerHTML = '<article class="clarity-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">' +
      '<div class="space-y-1.5">' +
      '  <div class="flex items-center gap-2 flex-wrap">' +
      '    <span class="course-code-pill">' + H.esc(c.code) + '</span>' +
      '    <span class="clarity-tag clarity-tag-neutral">' + H.esc(c.credits) + ' SKS</span>' +
      '    <span class="clarity-tag clarity-tag-info">' + H.esc(c.enrolled_count) + ' peserta</span>' +
      '  </div>' +
      '  <h2 class="text-xl font-extrabold text-ink m-0">' + H.esc(c.name) + '</h2>' +
      '  <p class="text-sm text-muted m-0">' + H.esc(c.day) + ', ' + H.esc(c.start_time) + ' - ' + H.esc(c.end_time) + ' WIB | ' + H.esc(c.room) + '</p>' +
      '</div>' +
      '<div class="flex flex-wrap gap-2 shrink-0">' +
      '  <a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="' + window.ClarityAPI.pageUrl('material-upload.php') + '?course_id=' + c.id + '">Upload Materi</a>' +
      '  <a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="' + window.ClarityAPI.pageUrl('submissions.php') + '?course_id=' + c.id + '">Lihat Pengumpulan</a>' +
      '  <a class="clarity-btn clarity-btn-primary clarity-btn-sm" href="' + window.ClarityAPI.pageUrl('attendance-manage.php') + '?course_id=' + c.id + '">Kelola Presensi</a>' +
      '</div>' +
      '</article>';
    tabsSection.hidden = false;
  }

  function loadMaterialsTab() {
    var cid = activeCourse.id;
    panelEl.innerHTML = H.renderLoading('Memuat materi mata kuliah...');
    window.ClarityAPI.get('/courses/' + cid + '/materials')
      .then(function (res) {
        var items = Array.isArray(res.data) ? res.data : [];
        if (items.length === 0) {
          panelEl.innerHTML = H.renderEmpty(
            'Belum Ada Materi',
            'Unggah materi berkas atau tautan untuk pertemuan pada mata kuliah ini.',
            '<a class="clarity-btn clarity-btn-primary clarity-btn-sm" href="' + window.ClarityAPI.pageUrl('material-upload.php') + '?course_id=' + cid + '">Upload Materi Sekarang</a>'
          );
          return;
        }
        var html = '<div class="clarity-card overflow-hidden"><div class="clarity-list">';
        items.forEach(function (m) {
          html += '<div class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">' +
            '<div class="min-w-0 space-y-1">' +
            '  <div class="flex items-center gap-2 flex-wrap">' +
            '    <span class="clarity-tag clarity-tag-neutral">Pertemuan ' + H.esc(m.meeting_number || '-') + '</span>' +
            '    <span class="font-bold text-ink">' + H.esc(m.title) + '</span>' +
            '  </div>' +
            '  <div class="text-xs text-muted">' + (m.type === 'file' ? H.esc(m.file_name) + ' (' + H.esc(m.file_size || '-') + ')' : H.esc(m.url)) + '</div>' +
            '</div>' +
            '<div class="flex items-center gap-2 shrink-0">' +
            (m.type === 'file'
              ? '<button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" data-dl-mat="' + m.id + '" data-fn="' + H.esc(m.file_name || 'materi') + '">Unduh</button>'
              : '<a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="' + H.esc(m.url) + '" target="_blank" rel="noopener noreferrer">Buka</a>') +
            '  <button type="button" class="clarity-btn clarity-btn-crimson clarity-btn-sm" data-del-mat="' + m.id + '">Hapus</button>' +
            '</div></div>';
        });
        html += '</div></div>';
        panelEl.innerHTML = html;

        panelEl.querySelectorAll('[data-dl-mat]').forEach(function (b) {
          b.addEventListener('click', function () {
            window.ClarityAPI.download('/materials/' + b.getAttribute('data-dl-mat') + '/download', b.getAttribute('data-fn'));
          });
        });
        panelEl.querySelectorAll('[data-del-mat]').forEach(function (b) {
          b.addEventListener('click', function () {
            if (!window.confirm('Hapus materi ini?')) return;
            window.ClarityAPI.del('/materials/' + b.getAttribute('data-del-mat'))
              .then(function () {
                if (window.ClarityUI) window.ClarityUI.showToast('Materi dihapus.', 'info');
                loadMaterialsTab();
              })
              .catch(function (err) {
                if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal menghapus.', 'danger');
              });
          });
        });
      })
      .catch(function (err) {
        panelEl.innerHTML = H.renderError(err && err.message, 'btn-retry-mg-mat');
        var r = document.getElementById('btn-retry-mg-mat');
        if (r) r.addEventListener('click', loadMaterialsTab);
      });
  }

  function loadMeetingsTab() {
    var cid = activeCourse.id;
    panelEl.innerHTML = H.renderLoading('Memuat jadwal pertemuan...');
    window.ClarityAPI.get('/courses/' + cid + '/meetings')
      .then(function (res) {
        var items = Array.isArray(res.data) ? res.data : [];
        var nextNumber = items.length > 0 ? Math.max.apply(null, items.map(function (m) { return m.number; })) + 1 : 1;

        var html = '<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">' +
          '<form id="add-meeting-form" class="clarity-card p-5 lg:col-span-4 space-y-3">' +
          '  <h3 class="text-base font-bold text-ink m-0">Tambah Pertemuan</h3>' +
          '  <div><label for="mt-number" class="block text-sm font-semibold text-ink mb-1">Pertemuan Ke</label>' +
          '  <input id="mt-number" type="number" min="1" class="clarity-input w-full" value="' + nextNumber + '" required></div>' +
          '  <div><label for="mt-date" class="block text-sm font-semibold text-ink mb-1">Tanggal</label>' +
          '  <input id="mt-date" type="date" class="clarity-input w-full" required></div>' +
          '  <div><label for="mt-title" class="block text-sm font-semibold text-ink mb-1">Topik Pertemuan</label>' +
          '  <input id="mt-title" type="text" class="clarity-input w-full" required placeholder="Contoh: Pengujian Integrasi API"></div>' +
          '  <div><label for="mt-desc" class="block text-sm font-semibold text-ink mb-1">Ringkasan (Opsional)</label>' +
          '  <textarea id="mt-desc" rows="2" class="clarity-input w-full"></textarea></div>' +
          '  <button type="submit" class="clarity-btn clarity-btn-primary clarity-btn-sm w-full">Simpan Pertemuan</button>' +
          '</form>' +
          '<div class="clarity-card overflow-hidden lg:col-span-8">';

        if (items.length === 0) {
          html += '<div class="p-6 text-sm text-muted">Belum ada pertemuan terdaftar.</div>';
        } else {
          html += '<div class="clarity-list">';
          items.forEach(function (mt) {
            html += '<div class="px-6 py-4 flex items-center justify-between gap-3">' +
              '<div><div class="font-bold text-ink">Pertemuan ' + H.esc(mt.number) + ': ' + H.esc(mt.title) + '</div>' +
              '<div class="text-xs text-muted">' + H.esc(H.formatDateOnly(mt.meeting_date)) + (mt.description ? ' | ' + H.esc(mt.description) : '') + '</div></div>' +
              '<a class="clarity-btn clarity-btn-outline clarity-btn-sm shrink-0" href="' + window.ClarityAPI.pageUrl('attendance-manage.php') + '?course_id=' + cid + '&meeting_id=' + mt.id + '">Presensi</a>' +
              '</div>';
          });
          html += '</div>';
        }
        html += '</div></div>';
        panelEl.innerHTML = html;

        document.getElementById('add-meeting-form').addEventListener('submit', function (e) {
          e.preventDefault();
          window.ClarityAPI.post('/courses/' + cid + '/meetings', {
            number: Number(document.getElementById('mt-number').value),
            meeting_date: document.getElementById('mt-date').value,
            title: document.getElementById('mt-title').value.trim(),
            description: document.getElementById('mt-desc').value.trim() || null
          }).then(function () {
            if (window.ClarityUI) window.ClarityUI.showToast('Pertemuan berhasil ditambahkan.', 'success');
            loadMeetingsTab();
          }).catch(function (err) {
            if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal menambah pertemuan.', 'danger');
          });
        });
      })
      .catch(function (err) {
        panelEl.innerHTML = H.renderError(err && err.message, 'btn-retry-mg-mt');
        var r = document.getElementById('btn-retry-mg-mt');
        if (r) r.addEventListener('click', loadMeetingsTab);
      });
  }

  function loadAssignmentsTab() {
    var cid = activeCourse.id;
    panelEl.innerHTML = H.renderLoading('Memuat daftar tugas...');
    window.ClarityAPI.get('/courses/' + cid + '/assignments')
      .then(function (res) {
        var items = Array.isArray(res.data) ? res.data : [];
        var html = '<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">' +
          '<form id="add-asg-form" class="clarity-card p-5 lg:col-span-5 space-y-3">' +
          '  <h3 class="text-base font-bold text-ink m-0">Buat Tugas Baru</h3>' +
          '  <div><label for="asg-title" class="block text-sm font-semibold text-ink mb-1">Judul Tugas</label>' +
          '  <input id="asg-title" type="text" class="clarity-input w-full" required placeholder="Contoh: Tugas 5 Pengujian Endpoint"></div>' +
          '  <div><label for="asg-desc" class="block text-sm font-semibold text-ink mb-1">Instruksi</label>' +
          '  <textarea id="asg-desc" rows="2" class="clarity-input w-full" placeholder="Jelaskan kriteria pengumpulan..."></textarea></div>' +
          '  <div><label for="asg-deadline" class="block text-sm font-semibold text-ink mb-1">Tenggat Waktu (WIB)</label>' +
          '  <input id="asg-deadline" type="datetime-local" class="clarity-input w-full" required></div>' +
          '  <div class="grid grid-cols-2 gap-3">' +
          '    <div><label for="asg-weight" class="block text-sm font-semibold text-ink mb-1">Bobot (%)</label>' +
          '    <input id="asg-weight" type="number" step="0.5" min="0" max="100" class="clarity-input w-full" value="20" required></div>' +
          '    <div><label for="asg-max" class="block text-sm font-semibold text-ink mb-1">Nilai Maks</label>' +
          '    <input id="asg-max" type="number" min="1" class="clarity-input w-full" value="100" required></div>' +
          '  </div>' +
          '  <div class="flex flex-wrap gap-4 pt-1">' +
          '    <label class="inline-flex items-center gap-2 text-sm cursor-pointer"><input id="asg-file" type="checkbox" checked> Terima Berkas</label>' +
          '    <label class="inline-flex items-center gap-2 text-sm cursor-pointer"><input id="asg-link" type="checkbox" checked> Terima Tautan</label>' +
          '    <label class="inline-flex items-center gap-2 text-sm cursor-pointer"><input id="asg-late" type="checkbox" checked> Izinkan Terlambat</label>' +
          '  </div>' +
          '  <button type="submit" class="clarity-btn clarity-btn-primary clarity-btn-sm w-full">Publikasikan Tugas</button>' +
          '</form>' +
          '<div class="clarity-card overflow-hidden lg:col-span-7">';

        if (items.length === 0) {
          html += '<div class="p-6 text-sm text-muted">Belum ada tugas pada mata kuliah ini.</div>';
        } else {
          html += '<div class="clarity-list">';
          items.forEach(function (a) {
            html += '<div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">' +
              '<div class="min-w-0 space-y-1">' +
              '  <div class="font-bold text-ink">' + H.esc(a.title) + '</div>' +
              '  <div class="text-xs text-muted">Batas: ' + H.esc(H.formatDateTime(a.deadline)) + ' | Bobot ' + H.esc(a.weight) + '%</div>' +
              '</div>' +
              '<div class="flex items-center gap-2 shrink-0">' +
              '  <a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="' + window.ClarityAPI.pageUrl('submissions.php') + '?course_id=' + cid + '&assignment_id=' + a.id + '">Pengumpulan</a>' +
              '  <button type="button" class="clarity-btn clarity-btn-crimson clarity-btn-sm" data-del-asg="' + a.id + '">Hapus</button>' +
              '</div></div>';
          });
          html += '</div>';
        }
        html += '</div></div>';
        panelEl.innerHTML = html;

        document.getElementById('add-asg-form').addEventListener('submit', function (e) {
          e.preventDefault();
          var rawDeadline = document.getElementById('asg-deadline').value;
          var isoDeadline = rawDeadline ? rawDeadline + ':00+07:00' : '';
          window.ClarityAPI.post('/courses/' + cid + '/assignments', {
            title: document.getElementById('asg-title').value.trim(),
            description: document.getElementById('asg-desc').value.trim(),
            deadline: isoDeadline,
            accept_file: document.getElementById('asg-file').checked,
            accept_link: document.getElementById('asg-link').checked,
            allow_late: document.getElementById('asg-late').checked,
            weight: Number(document.getElementById('asg-weight').value),
            max_score: Number(document.getElementById('asg-max').value)
          }).then(function () {
            if (window.ClarityUI) window.ClarityUI.showToast('Tugas berhasil dibuat.', 'success');
            loadAssignmentsTab();
          }).catch(function (err) {
            if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal membuat tugas.', 'danger');
          });
        });

        panelEl.querySelectorAll('[data-del-asg]').forEach(function (b) {
          b.addEventListener('click', function () {
            if (!window.confirm('Menghapus tugas juga menghapus seluruh pengumpulan mahasiswa di dalamnya. Lanjutkan?')) return;
            window.ClarityAPI.del('/assignments/' + b.getAttribute('data-del-asg'))
              .then(function () {
                if (window.ClarityUI) window.ClarityUI.showToast('Tugas dihapus.', 'info');
                loadAssignmentsTab();
              })
              .catch(function (err) {
                if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal menghapus tugas.', 'danger');
              });
          });
        });
      })
      .catch(function (err) {
        panelEl.innerHTML = H.renderError(err && err.message, 'btn-retry-mg-asg');
        var r = document.getElementById('btn-retry-mg-asg');
        if (r) r.addEventListener('click', loadAssignmentsTab);
      });
  }

  var partPage = 1;
  var partSearch = '';
  var partSearchTimer = null;

  function loadParticipantsTab(page, search) {
    if (typeof page === 'number') partPage = page;
    if (typeof search === 'string') partSearch = search;
    var cid = activeCourse.id;
    panelEl.innerHTML = H.renderLoading('Memuat daftar peserta...');
    var qs = '?page=' + partPage + '&per_page=10' + (partSearch ? '&search=' + encodeURIComponent(partSearch) : '');

    window.ClarityAPI.get('/courses/' + cid + '/participants' + qs)
      .then(function (res) {
        var items = Array.isArray(res.data) ? res.data : [];
        var meta = res.meta || {};
        var total = typeof meta.total === 'number' ? meta.total : items.length;
        var currentPage = meta.page || 1;
        var totalPages = meta.total_pages || 1;

        var html = '<div class="clarity-card p-6 space-y-4">' +
          '<div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 pb-2 border-b border-[var(--color-border-subtle)]">' +
          '  <form id="enroll-form" class="flex flex-col sm:flex-row sm:items-end gap-3 flex-1">' +
          '    <div class="flex-1 sm:max-w-xs"><label for="enroll-nim" class="block text-sm font-semibold text-ink mb-1">Daftarkan Mahasiswa (NIM)</label>' +
          '    <input id="enroll-nim" type="text" class="clarity-input w-full" placeholder="Contoh: 24.N1.0004" required></div>' +
          '    <button type="submit" class="clarity-btn clarity-btn-primary clarity-btn-sm">Tambahkan Peserta</button>' +
          '  </form>' +
          '  <div class="w-full lg:w-72">' +
          '    <label for="mg-part-search" class="block text-sm font-semibold text-ink mb-1">Cari Peserta</label>' +
          '    <input id="mg-part-search" type="search" class="clarity-input w-full" value="' + H.esc(partSearch) + '" placeholder="Cari nama atau NIM...">' +
          '  </div>' +
          '</div>';

        if (items.length === 0) {
          html += H.renderEmpty(
            partSearch ? 'Peserta Tidak Ditemukan' : 'Belum Ada Peserta',
            partSearch ? 'Tidak ada mahasiswa yang cocok dengan kata kunci pencarian.' : 'Belum ada mahasiswa terdaftar di kelas ini.'
          );
        } else {
          html += '<div class="overflow-x-auto"><table class="clarity-table w-full">' +
            '<thead><tr><th>NIM</th><th>Nama</th><th>Peran</th><th>Program Studi</th><th>Aksi</th></tr></thead><tbody>';
          items.forEach(function (u) {
            html += '<tr>' +
              '<td class="font-mono text-sm">' + H.esc(u.nim_nip || u.nim || '') + '</td>' +
              '<td class="font-semibold text-ink">' + H.esc(u.name) + '<div class="text-xs text-muted font-normal">' + H.esc(u.email || '') + '</div></td>' +
              '<td><span class="clarity-tag clarity-tag-neutral">Mahasiswa</span></td>' +
              '<td class="text-sm text-muted">' + H.esc(u.program_studi || '-') + '</td>' +
              '<td><button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" data-unenroll="' + u.id + '">Keluarkan</button></td>' +
              '</tr>';
          });
          html += '</tbody></table></div>' +
            '<div class="pt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">' +
            '  <div class="text-muted">Total <strong class="text-ink">' + total + '</strong> peserta terdaftar</div>' +
            '  <div class="flex items-center gap-2">' +
            '    <button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" id="mg-part-prev"' + (currentPage <= 1 ? ' disabled' : '') + '>Sebelumnya</button>' +
            '    <span class="text-xs font-semibold text-ink px-2">Halaman ' + currentPage + ' / ' + totalPages + '</span>' +
            '    <button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" id="mg-part-next"' + (currentPage >= totalPages ? ' disabled' : '') + '>Berikutnya</button>' +
            '  </div>' +
            '</div>';
        }
        html += '</div>';
        panelEl.innerHTML = html;

        var searchEl = document.getElementById('mg-part-search');
        if (searchEl) {
          searchEl.addEventListener('input', function () {
            clearTimeout(partSearchTimer);
            partSearchTimer = setTimeout(function () {
              loadParticipantsTab(1, searchEl.value.trim());
            }, 300);
          });
        }
        var prevBtn = document.getElementById('mg-part-prev');
        var nextBtn = document.getElementById('mg-part-next');
        if (prevBtn) prevBtn.addEventListener('click', function () { loadParticipantsTab(currentPage - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { loadParticipantsTab(currentPage + 1); });

        document.getElementById('enroll-form').addEventListener('submit', function (e) {
          e.preventDefault();
          var nim = document.getElementById('enroll-nim').value.trim();
          window.ClarityAPI.post('/courses/' + cid + '/enrollments', { nim_nip: nim })
            .then(function () {
              if (window.ClarityUI) window.ClarityUI.showToast('Mahasiswa berhasil didaftarkan.', 'success');
              if (activeCourse) {
                activeCourse.enrolled_count = (Number(activeCourse.enrolled_count) || 0) + 1;
                renderCourseInfo(activeCourse);
              }
              loadParticipantsTab(1);
            })
            .catch(function (err) {
              if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal mendaftarkan mahasiswa.', 'danger');
            });
        });

        panelEl.querySelectorAll('[data-unenroll]').forEach(function (b) {
          b.addEventListener('click', function () {
            if (!window.confirm('Keluarkan mahasiswa ini dari kelas?')) return;
            window.ClarityAPI.del('/courses/' + cid + '/enrollments/' + b.getAttribute('data-unenroll'))
              .then(function () {
                if (window.ClarityUI) window.ClarityUI.showToast('Peserta dikeluarkan.', 'info');
                if (activeCourse && Number(activeCourse.enrolled_count) > 0) {
                  activeCourse.enrolled_count = Number(activeCourse.enrolled_count) - 1;
                  renderCourseInfo(activeCourse);
                }
                loadParticipantsTab(currentPage);
              })
              .catch(function (err) {
                if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal mengeluarkan peserta.', 'danger');
              });
          });
        });
      })
      .catch(function (err) {
        panelEl.innerHTML = H.renderError(err && err.message, 'btn-retry-mg-part');
        var r = document.getElementById('btn-retry-mg-part');
        if (r) r.addEventListener('click', function () { loadParticipantsTab(partPage); });
      });
  }

  function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('[data-manage-tab]').forEach(function (btn) {
      var on = btn.getAttribute('data-manage-tab') === tab;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    if (!activeCourse) return;
    if (tab === 'materials') loadMaterialsTab();
    else if (tab === 'meetings') loadMeetingsTab();
    else if (tab === 'assignments') loadAssignmentsTab();
    else { partPage = 1; partSearch = ''; loadParticipantsTab(1, ''); }
  }

  document.querySelectorAll('[data-manage-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      switchTab(btn.getAttribute('data-manage-tab'));
    });
  });

  courseSelect.addEventListener('change', function () {
    var cid = courseSelect.value;
    window.ClarityAPI.setSelectedCourseId(cid);
    activeCourse = courses.find(function (c) { return String(c.id) === String(cid); }) || null;
    if (activeCourse) {
      renderCourseInfo(activeCourse);
      switchTab(activeTab);
    }
  });

  function initCourses() {
    infoEl.innerHTML = H.renderLoading('Memuat mata kuliah diampu...');
    window.ClarityAPI.get('/courses?per_page=50')
      .then(function (res) {
        courses = Array.isArray(res.data) ? res.data : [];
        if (courses.length === 0) {
          infoEl.innerHTML = H.renderEmpty(
            'Belum Ada Mata Kuliah Diampu',
            'Buat mata kuliah pertama Anda menggunakan tombol Buat Mata Kuliah di atas.'
          );
          tabsSection.hidden = true;
          return;
        }
        var savedCid = window.ClarityAPI.getSelectedCourseId() || String(courses[0].id);
        if (!courses.some(function (c) { return String(c.id) === String(savedCid); })) {
          savedCid = String(courses[0].id);
        }
        courseSelect.innerHTML = courses.map(function (c) {
          return '<option value="' + c.id + '">' + H.esc(c.code + ' - ' + c.name) + '</option>';
        }).join('');
        courseSelect.value = savedCid;
        activeCourse = courses.find(function (c) { return String(c.id) === String(savedCid); }) || courses[0];
        renderCourseInfo(activeCourse);
        switchTab(activeTab);
      })
      .catch(function (err) {
        infoEl.innerHTML = H.renderError(err && err.message, 'btn-retry-mg-init');
        var r = document.getElementById('btn-retry-mg-init');
        if (r) r.addEventListener('click', initCourses);
      });
  }

  initCourses();
})();
