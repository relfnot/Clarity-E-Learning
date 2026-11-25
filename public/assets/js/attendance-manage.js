/* ==========================================================================
   Clarity E-Learning — Kelola Presensi Dosen
   ========================================================================== */

(function () {
  'use strict';

  var courseSelect = document.getElementById('am-course-select');
  var meetingSelect = document.getElementById('am-meeting-select');
  var sessionEl = document.getElementById('am-session-container');
  var rosterEl = document.getElementById('am-roster-container');
  if (!courseSelect || !meetingSelect || !sessionEl || !rosterEl) return;

  var H = window.ClarityHelpers;

  function loadMeetingAttendance() {
    var mid = meetingSelect.value;
    if (!mid) {
      sessionEl.innerHTML = H.renderEmpty('Pilih Pertemuan', 'Tambahkan atau pilih pertemuan untuk mengelola sesi presensi.');
      rosterEl.innerHTML = '';
      return;
    }

    sessionEl.innerHTML = H.renderLoading('Memuat status sesi presensi...');
    rosterEl.innerHTML = '';

    window.ClarityAPI.get('/meetings/' + mid + '/attendance')
      .then(function (res) {
        var session = res.meta ? res.meta.session : null;
        var items = Array.isArray(res.data) ? res.data : [];

        if (!session) {
          sessionEl.innerHTML = '<article class="clarity-card p-6 space-y-4">' +
            '<h2 class="text-lg font-bold text-ink m-0">Buka Sesi Presensi Pertemuan</h2>' +
            '<p class="text-sm text-muted m-0">Belum ada sesi presensi untuk pertemuan ini. Mahasiswa hanya bisa check-in ketika sesi dibuka.</p>' +
            '<form id="open-session-form" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">' +
            '  <div><label for="am-closes" class="block text-sm font-semibold text-ink mb-1">Batas Tutup Otomatis (Opsional)</label>' +
            '  <input id="am-closes" type="datetime-local" class="clarity-input w-full"></div>' +
            '  <div><label for="am-passcode" class="block text-sm font-semibold text-ink mb-1">Kode Presensi (Opsional)</label>' +
            '  <input id="am-passcode" type="text" class="clarity-input w-full" placeholder="Contoh: MANPRO10"></div>' +
            '  <button type="submit" class="clarity-btn clarity-btn-primary">Buka Sesi Presensi</button>' +
            '</form></article>';

          document.getElementById('open-session-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var rawCloses = document.getElementById('am-closes').value;
            var closesAt = rawCloses ? rawCloses + ':00+07:00' : null;
            var passcode = document.getElementById('am-passcode').value.trim() || null;

            window.ClarityAPI.post('/meetings/' + mid + '/attendance-session', {
              closes_at: closesAt,
              passcode: passcode
            }).then(function () {
              if (window.ClarityUI) window.ClarityUI.showToast('Sesi presensi dibuka.', 'success');
              loadMeetingAttendance();
            }).catch(function (err) {
              if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal membuka sesi.', 'danger');
            });
          });
          return;
        }

        var isOpen = session.status === 'open';
        sessionEl.innerHTML = '<article class="clarity-card p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">' +
          '<div class="space-y-1">' +
          '  <div class="flex items-center gap-2 flex-wrap">' +
          '    <span class="clarity-tag ' + (isOpen ? 'clarity-tag-success' : 'clarity-tag-neutral') + '">' + (isOpen ? 'Sesi Terbuka' : 'Sesi Ditutup') + '</span>' +
          (session.passcode ? '<span class="course-code-pill">Kode: ' + H.esc(session.passcode) + '</span>' : '') +
          '  </div>' +
          '  <div class="text-sm text-muted">Dibuka: ' + H.esc(H.formatDateTime(session.opened_at)) +
          (session.closes_at ? ' · Batas: ' + H.esc(H.formatDateTime(session.closes_at)) : '') + '</div>' +
          '</div>' +
          (isOpen
            ? '<button type="button" id="btn-close-att-session" class="clarity-btn clarity-btn-crimson clarity-btn-sm shrink-0">Tutup Sesi (Tandai Alpa yang Belum)</button>'
            : '<span class="text-xs text-muted">Anda dapat mengubah status kehadiran mahasiswa pada tabel di bawah.</span>') +
          '</article>';

        var closeBtn = document.getElementById('btn-close-att-session');
        if (closeBtn) {
          closeBtn.addEventListener('click', function () {
            if (!window.confirm('Tutup sesi presensi? Mahasiswa yang belum check-in akan otomatis dicatat Alpa.')) return;
            closeBtn.disabled = true;
            window.ClarityAPI.patch('/attendance-sessions/' + session.id + '/close', {})
              .then(function () {
                if (window.ClarityUI) window.ClarityUI.showToast('Sesi presensi ditutup.', 'info');
                loadMeetingAttendance();
              })
              .catch(function (err) {
                if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal menutup sesi.', 'danger');
                closeBtn.disabled = false;
              });
          });
        }

        if (items.length === 0) {
          rosterEl.innerHTML = H.renderEmpty('Belum Ada Peserta', 'Belum ada mahasiswa terdaftar pada mata kuliah ini.');
          return;
        }

        var html = '<article class="clarity-card overflow-hidden">' +
          '<div class="clarity-section-head"><h2 class="text-base font-bold text-ink m-0">Daftar Kehadiran Peserta (' + items.length + ')</h2></div>' +
          '<div class="overflow-x-auto"><table class="clarity-table w-full">' +
          '<thead><tr><th>NIM</th><th>Nama Mahasiswa</th><th>Status</th><th>Waktu Check-in</th><th>Ubah Status</th></tr></thead><tbody>';

        items.forEach(function (row) {
          var canEdit = row.id !== null;
          html += '<tr>' +
            '<td class="font-mono text-sm">' + H.esc(row.nim_nip) + '</td>' +
            '<td class="font-semibold text-ink">' + H.esc(row.student_name) + '</td>' +
            '<td>' + H.attendanceStatusBadge(row.status) + '</td>' +
            '<td class="text-sm text-muted">' + H.esc(row.checked_at ? H.formatDateTime(row.checked_at) : '-') + '</td>' +
            '<td>' +
            (canEdit
              ? '<label class="sr-only" for="rec-' + row.id + '">Status untuk ' + H.esc(row.student_name) + '</label>' +
                '<select id="rec-' + row.id + '" class="clarity-input text-sm py-1.5" data-record-status="' + row.id + '">' +
                ['hadir', 'izin', 'sakit', 'alpa'].map(function (st) {
                  return '<option value="' + st + '"' + (row.status === st ? ' selected' : '') + '>' + st.toUpperCase() + '</option>';
                }).join('') +
                '</select>'
              : '<span class="text-xs text-muted">Menunggu sesi ditutup / check-in</span>') +
            '</td></tr>';
        });
        html += '</tbody></table></div></article>';
        rosterEl.innerHTML = html;

        rosterEl.querySelectorAll('[data-record-status]').forEach(function (sel) {
          sel.addEventListener('change', function () {
            var rid = sel.getAttribute('data-record-status');
            var newStatus = sel.value;
            sel.disabled = true;
            window.ClarityAPI.put('/attendance-records/' + rid, { status: newStatus })
              .then(function () {
                if (window.ClarityUI) window.ClarityUI.showToast('Status kehadiran diperbarui.', 'success');
                loadMeetingAttendance();
              })
              .catch(function (err) {
                if (window.ClarityUI) window.ClarityUI.showToast((err && err.message) || 'Gagal memperbarui status.', 'danger');
                sel.disabled = false;
              });
          });
        });
      })
      .catch(function (err) {
        sessionEl.innerHTML = H.renderError(err && err.message, 'btn-retry-am');
        var r = document.getElementById('btn-retry-am');
        if (r) r.addEventListener('click', loadMeetingAttendance);
      });
  }

  function loadMeetings(cid) {
    if (!cid) return;
    window.ClarityAPI.setSelectedCourseId(String(cid));
    meetingSelect.innerHTML = '<option value="">Memuat pertemuan...</option>';
    window.ClarityAPI.get('/courses/' + cid + '/meetings')
      .then(function (res) {
        var list = Array.isArray(res.data) ? res.data : [];
        if (list.length === 0) {
          meetingSelect.innerHTML = '<option value="">Belum ada pertemuan</option>';
          loadMeetingAttendance();
          return;
        }
        var paramMid = window.ClarityUI ? window.ClarityUI.getQueryParam('meeting_id') : null;
        meetingSelect.innerHTML = list.map(function (m) {
          return '<option value="' + m.id + '">Pertemuan ' + H.esc(m.number) + ': ' + H.esc(m.title) + '</option>';
        }).join('');
        if (paramMid && list.some(function (m) { return String(m.id) === String(paramMid); })) {
          meetingSelect.value = paramMid;
        } else {
          meetingSelect.value = String(list[list.length - 1].id);
        }
        loadMeetingAttendance();
      })
      .catch(function (err) {
        sessionEl.innerHTML = H.renderError(err && err.message, 'btn-retry-am-mt');
      });
  }

  courseSelect.addEventListener('change', function () { loadMeetings(courseSelect.value); });
  meetingSelect.addEventListener('change', loadMeetingAttendance);

  function init() {
    window.ClarityAPI.get('/courses?per_page=50')
      .then(function (res) {
        var courses = Array.isArray(res.data) ? res.data : [];
        if (courses.length === 0) {
          sessionEl.innerHTML = H.renderEmpty('Belum Ada Mata Kuliah', 'Buat mata kuliah di Kelola Mata Kuliah terlebih dahulu.');
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
        loadMeetings(savedCid);
      })
      .catch(function (err) {
        sessionEl.innerHTML = H.renderError(err && err.message, 'btn-retry-am-init');
      });
  }

  init();
})();
