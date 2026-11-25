/* ==========================================================================
   Clarity E-Learning — Presensi Mahasiswa (Check-in & Rekap)
   ========================================================================== */

(function () {
  'use strict';

  var courseSelect = document.getElementById('attendance-course-select');
  var summaryEl = document.getElementById('attendance-percent-summary');
  var contentEl = document.getElementById('attendance-content');
  if (!courseSelect || !contentEl) return;

  var H = window.ClarityHelpers;

  function loadCourseAttendance(cid) {
    if (!cid) return;
    window.ClarityAPI.setSelectedCourseId(String(cid));
    contentEl.innerHTML = H.renderLoading('Memuat sesi presensi dan rekap...');

    window.ClarityAPI.get('/courses/' + cid + '/attendance/summary')
      .then(function (res) {
        var d = res.data || {};
        var counts = d.counts || { hadir: 0, izin: 0, sakit: 0, alpa: 0 };
        var openSessions = Array.isArray(d.open_sessions) ? d.open_sessions : [];
        var records = Array.isArray(d.records) ? d.records : [];

        if (summaryEl) {
          summaryEl.textContent = 'Kehadiran: ' + (d.attendance_percent || 0) + '% dari ' + (d.total_meetings || 0) + ' pertemuan';
        }

        var html = '';

        // Sesi Terbuka
        html += '<section class="clarity-card p-6 space-y-4">' +
          '<h2 class="text-lg font-bold text-ink m-0">Sesi Presensi Terbuka</h2>';
        if (openSessions.length === 0) {
          html += '<p class="text-sm text-muted m-0">Saat ini tidak ada sesi presensi yang dibuka oleh dosen.</p>';
        } else {
          html += '<div class="space-y-3">';
          openSessions.forEach(function (s) {
            var alreadyChecked = Boolean(s.my_status);
            html += '<article class="clarity-well p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">' +
              '<div class="space-y-1">' +
              '  <div class="font-bold text-ink">Pertemuan ' + H.esc(s.meeting_number) + ': ' + H.esc(s.meeting_title) + '</div>' +
              '  <div class="text-xs text-muted">Dibuka: ' + H.esc(H.formatDateTime(s.opened_at)) +
              (s.closes_at ? ' · Ditutup: ' + H.esc(H.formatDateTime(s.closes_at)) : '') + '</div>' +
              '</div>';

            if (alreadyChecked) {
              html += '<div class="flex items-center gap-2 shrink-0">' + H.attendanceStatusBadge(s.my_status) + '<span class="text-xs text-muted">Sudah tercatat</span></div>';
            } else {
              html += '<form class="flex flex-wrap items-center gap-2 shrink-0" data-checkin-form="' + s.id + '">' +
                (s.requires_passcode
                  ? '<label class="sr-only" for="passcode-' + s.id + '">Kode Presensi</label>' +
                    '<input id="passcode-' + s.id + '" type="text" class="clarity-input w-40" placeholder="Kode presensi..." required>'
                  : '') +
                '<button type="submit" class="clarity-btn clarity-btn-primary clarity-btn-sm">Check-in Hadir</button>' +
                '</form>';
            }
            html += '</article>';
          });
          html += '</div>';
        }
        html += '</section>';

        // Rekap Angka
        html += '<div class="stat-ledger">' +
          '<div class="stat-cell"><div class="stat-label">Hadir</div><div class="stat-value text-success">' + H.esc(counts.hadir) + '</div></div>' +
          '<div class="stat-cell"><div class="stat-label">Izin</div><div class="stat-value text-ink">' + H.esc(counts.izin) + '</div></div>' +
          '<div class="stat-cell"><div class="stat-label">Sakit</div><div class="stat-value text-warning">' + H.esc(counts.sakit) + '</div></div>' +
          '<div class="stat-cell"><div class="stat-label">Alpa</div><div class="stat-value text-accent">' + H.esc(counts.alpa) + '</div></div>' +
          '</div>';

        // Riwayat Presensi
        html += '<section class="clarity-card overflow-hidden">' +
          '<div class="clarity-section-head"><h2 class="text-base font-bold text-ink m-0">Riwayat Kehadiran</h2></div>';
        if (records.length === 0) {
          html += '<div class="p-6 text-sm text-muted">Belum ada catatan kehadiran pada mata kuliah ini.</div>';
        } else {
          html += '<div class="overflow-x-auto"><table class="clarity-table w-full">' +
            '<thead><tr><th>Pertemuan</th><th>Topik</th><th>Status</th><th>Waktu Check-in</th></tr></thead><tbody>';
          records.forEach(function (r) {
            html += '<tr>' +
              '<td class="font-semibold">Pertemuan ' + H.esc(r.meeting_number || '-') + '</td>' +
              '<td>' + H.esc(r.meeting_title || '-') + '</td>' +
              '<td>' + H.attendanceStatusBadge(r.status) + '</td>' +
              '<td class="text-sm text-muted">' + H.esc(H.formatDateTime(r.checked_at)) + '</td>' +
              '</tr>';
          });
          html += '</tbody></table></div>';
        }
        html += '</section>';

        contentEl.innerHTML = html;

        contentEl.querySelectorAll('[data-checkin-form]').forEach(function (form) {
          form.addEventListener('submit', function (e) {
            e.preventDefault();
            var sid = form.getAttribute('data-checkin-form');
            var passcodeEl = document.getElementById('passcode-' + sid);
            var btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;

            window.ClarityAPI.post('/attendance-sessions/' + sid + '/check-in', {
              passcode: passcodeEl ? passcodeEl.value.trim() : null
            }).then(function () {
              if (window.ClarityUI) window.ClarityUI.showToast('Presensi berhasil dicatat sebagai Hadir.', 'success');
              loadCourseAttendance(cid);
            }).catch(function (err) {
              if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal melakukan check-in.', 'danger');
              btn.disabled = false;
            });
          });
        });
      })
      .catch(function (err) {
        contentEl.innerHTML = H.renderError(err && err.message, 'btn-retry-att-page');
        var r = document.getElementById('btn-retry-att-page');
        if (r) r.addEventListener('click', function () { loadCourseAttendance(cid); });
      });
  }

  courseSelect.addEventListener('change', function () {
    loadCourseAttendance(courseSelect.value);
  });

  function init() {
    contentEl.innerHTML = H.renderLoading('Memuat daftar mata kuliah...');
    window.ClarityAPI.get('/courses?per_page=50')
      .then(function (res) {
        var courses = Array.isArray(res.data) ? res.data : [];
        if (courses.length === 0) {
          contentEl.innerHTML = H.renderEmpty('Belum Ada Mata Kuliah', 'Anda belum terdaftar pada mata kuliah apa pun.');
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
        loadCourseAttendance(savedCid);
      })
      .catch(function (err) {
        contentEl.innerHTML = H.renderError(err && err.message, 'btn-retry-att-init');
        var r = document.getElementById('btn-retry-att-init');
        if (r) r.addEventListener('click', init);
      });
  }

  init();
})();
