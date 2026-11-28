/* ==========================================================================
   Clarity E-Learning — Intro & Riwayat Quiz Mahasiswa (Item 20)
   ========================================================================== */

(function (global) {
  'use strict';

  var courseSelect = document.getElementById('qi-course-select');
  var quizSelect = document.getElementById('qi-quiz-select');
  var detailEl = document.getElementById('qi-detail-container');
  if (!courseSelect || !quizSelect || !detailEl) return;

  var H = global.ClarityHelpers;
  var esc = H ? H.esc : function (v) { return String(v || ''); };

  var quizzes = [];

  function availabilityTag(status) {
    if (status === 'tersedia') return '<span class="clarity-tag clarity-tag-success">Quiz Tersedia</span>';
    if (status === 'belum_buka') return '<span class="clarity-tag clarity-tag-warning">Belum Dibuka</span>';
    return '<span class="clarity-tag clarity-tag-neutral">Sudah Ditutup</span>';
  }

  function attemptStatusTag(status) {
    if (status === 'in_progress') return '<span class="clarity-tag clarity-tag-info">Sedang Dikerjakan</span>';
    if (status === 'menunggu_penilaian') return '<span class="clarity-tag clarity-tag-warning">Menunggu Penilaian Esai</span>';
    return '<span class="clarity-tag clarity-tag-success">Selesai</span>';
  }

  function renderQuizDetail(q, attempts) {
    var attemptsUsed = typeof q.attempts_used === 'number' ? q.attempts_used : attempts.length;
    var remaining = Math.max(0, (q.max_attempts || 1) - attemptsUsed);
    var activeAttempt = attempts.find(function (a) { return a.status === 'in_progress'; });

    var actionBtnHtml = '';
    if (activeAttempt) {
      var resumeUrl = global.ClarityAPI.pageUrl('quiz-attempt.php') + '?attempt_id=' + activeAttempt.id;
      actionBtnHtml = '<a class="clarity-btn clarity-btn-primary clarity-btn-lg" href="' + esc(resumeUrl) + '">Lanjutkan Pengerjaan (Percobaan #' + esc(activeAttempt.attempt_number) + ')</a>';
    } else if (q.availability_status !== 'tersedia') {
      var reasonText = q.availability_status === 'belum_buka' ? 'Quiz Belum Dibuka' : 'Jendela Quiz Ditutup';
      actionBtnHtml = '<button type="button" class="clarity-btn clarity-btn-primary clarity-btn-lg" disabled>' + esc(reasonText) + '</button>';
    } else if (remaining <= 0) {
      actionBtnHtml = '<button type="button" class="clarity-btn clarity-btn-primary clarity-btn-lg" disabled>Batas Percobaan Habis</button>';
    } else if ((q.total_questions || 0) === 0) {
      actionBtnHtml = '<button type="button" class="clarity-btn clarity-btn-primary clarity-btn-lg" disabled>Soal Belum Tersedia</button>';
    } else {
      actionBtnHtml = '<button type="button" id="btn-start-quiz" class="clarity-btn clarity-btn-primary clarity-btn-lg" data-quiz-id="' + q.id + '">Mulai Kerjakan Quiz (Percobaan ke-' + (attemptsUsed + 1) + ')</button>';
    }

    var courseDetailUrl = global.ClarityAPI.pageUrl('course-detail.php') + '?id=' + q.course_id;

    var mainCard = '<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">' +
      '<section class="clarity-card p-6 lg:col-span-8 space-y-6">' +
      '  <div class="space-y-2">' +
      '    <div class="flex items-center gap-2 flex-wrap">' +
      '      ' + availabilityTag(q.availability_status) +
      '      <span class="course-code-pill font-mono">' + esc(q.course_code) + '</span>' +
      '      <span class="clarity-tag clarity-tag-info">Bobot ' + esc(q.weight || 0) + '%</span>' +
      '    </div>' +
      '    <h1 class="text-xl sm:text-2xl font-extrabold text-ink m-0">' + esc(q.title) + '</h1>' +
      (q.description ? '<p class="text-sm text-secondary m-0">' + esc(q.description) + '</p>' : '') +
      '  </div>' +
      '  <div class="clarity-well p-4"><div class="stat-ledger">' +
      '    <div class="stat-cell"><div class="stat-label">Durasi</div><div class="stat-value text-ink">' + esc(q.duration_min) + 'm</div></div>' +
      '    <div class="stat-cell"><div class="stat-label">Jumlah Soal</div><div class="stat-value text-ink">' + esc(q.total_questions || 0) + '</div></div>' +
      '    <div class="stat-cell"><div class="stat-label">Percobaan</div><div class="stat-value text-accent">' + esc(attemptsUsed) + '/' + esc(q.max_attempts) + '</div></div>' +
      '    <div class="stat-cell"><div class="stat-label">Skor Terbaik</div><div class="stat-value text-success">' + (q.best_score !== null && q.best_score !== undefined ? esc(q.best_score) : '-') + '</div></div>' +
      '  </div></div>' +
      '  <div class="clarity-alert clarity-alert-info">' +
      '    <div class="space-y-1 text-sm">' +
      '      <div class="font-bold">Ketentuan Pengerjaan Quiz:</div>' +
      '      <ul class="list-disc pl-5 m-0 space-y-1">' +
      '        <li>Timer berjalan di server sesuai durasi ' + esc(q.duration_min) + ' menit dan tidak akan melebihi waktu tutup quiz.</li>' +
      '        <li>' + (q.shuffle ? 'Urutan soal dan opsi jawaban diacak per percobaan dan tetap konsisten saat halaman dimuat ulang.' : 'Urutan soal mengikuti susunan standar dari dosen.') + '</li>' +
      '        <li>Jawaban tersimpan otomatis saat Anda memilih opsi atau mengetik, dan soal Pilihan Ganda langsung dinilai setelah dikumpulkan.</li>' +
      '      </ul>' +
      '    </div>' +
      '  </div>' +
      '  <div id="qi-start-feedback" hidden></div>' +
      '  <div class="flex flex-wrap items-center gap-3">' +
      '    ' + actionBtnHtml +
      '    <a class="clarity-btn clarity-btn-outline clarity-btn-lg" href="' + esc(courseDetailUrl) + '">Kembali ke Mata Kuliah</a>' +
      '  </div>' +
      '</section>' +
      '<aside class="clarity-card p-6 lg:col-span-4 space-y-5">' +
      '  <h2 class="text-base font-bold text-ink m-0 pb-3 border-b border-[var(--color-border-subtle)]">Jadwal & Riwayat Percobaan</h2>' +
      '  <div class="space-y-2 text-sm">' +
      '    <div class="flex justify-between gap-2"><span class="text-muted">Dibuka</span><strong class="text-ink text-right">' + esc(H.formatDateTime(q.open_at)) + '</strong></div>' +
      '    <div class="flex justify-between gap-2"><span class="text-muted">Ditutup</span><strong class="text-ink text-right">' + esc(H.formatDateTime(q.close_at)) + '</strong></div>' +
      '    <div class="flex justify-between gap-2"><span class="text-muted">Kunci Jawaban</span><strong class="text-ink text-right">' + (q.show_answers ? 'Tampil setelah tutup' : 'Tidak ditampilkan') + '</strong></div>' +
      '  </div>';

    if (attempts.length === 0) {
      mainCard += '<div class="clarity-well p-4 text-sm text-muted">Belum ada riwayat percobaan pada quiz ini.</div>';
    } else {
      mainCard += '<div class="overflow-x-auto"><table class="clarity-table w-full">' +
        '<thead><tr><th>#</th><th>Status</th><th>Skor</th><th class="text-right">Aksi</th></tr></thead><tbody>';
      attempts.forEach(function (a) {
        var linkUrl = a.status === 'in_progress'
          ? global.ClarityAPI.pageUrl('quiz-attempt.php') + '?attempt_id=' + a.id
          : global.ClarityAPI.pageUrl('quiz-result.php') + '?attempt_id=' + a.id;
        mainCard += '<tr>' +
          '<td class="font-mono font-bold">#' + esc(a.attempt_number) + '</td>' +
          '<td>' + attemptStatusTag(a.status) + '</td>' +
          '<td class="font-mono font-bold">' + (a.score !== null && a.score !== undefined ? esc(a.score) : '-') + '</td>' +
          '<td class="text-right"><a class="clarity-inline-link text-sm" href="' + esc(linkUrl) + '">' + (a.status === 'in_progress' ? 'Lanjut' : 'Hasil') + '</a></td>' +
          '</tr>';
      });
      mainCard += '</tbody></table></div>';
    }

    mainCard += '</aside></div>';
    detailEl.innerHTML = mainCard;

    var startBtn = document.getElementById('btn-start-quiz');
    if (startBtn) {
      startBtn.addEventListener('click', function () {
        var fb = document.getElementById('qi-start-feedback');
        if (fb) { fb.hidden = true; fb.innerHTML = ''; }
        startBtn.disabled = true;
        startBtn.textContent = 'Memulai Percobaan…';

        global.ClarityAPI.post('/quizzes/' + q.id + '/attempts', {})
          .then(function (res) {
            var attempt = res && res.data;
            if (!attempt || !attempt.id) throw new Error('Gagal membuat percobaan quiz.');
            window.location.href = global.ClarityAPI.pageUrl('quiz-attempt.php') + '?attempt_id=' + attempt.id;
          })
          .catch(function (err) {
            var msg = (err && err.message) || 'Tidak dapat memulai percobaan quiz.';
            if (err && err.details && err.details.active_attempt_id) {
              window.location.href = global.ClarityAPI.pageUrl('quiz-attempt.php') + '?attempt_id=' + err.details.active_attempt_id;
              return;
            }
            if (fb) {
              fb.hidden = false;
              fb.innerHTML = '<div class="clarity-alert clarity-alert-danger" role="alert">' + esc(msg) + '</div>';
            }
            if (global.ClarityUI) global.ClarityUI.showToast(msg, 'danger');
            startBtn.disabled = false;
            startBtn.textContent = 'Mulai Kerjakan Quiz (Percobaan ke-' + (attemptsUsed + 1) + ')';
          });
      });
    }
  }

  function loadSelectedQuiz() {
    var qid = quizSelect.value;
    if (!qid) {
      detailEl.innerHTML = H.renderEmpty('Belum Ada Quiz', 'Dosen belum membuat quiz pada mata kuliah ini.');
      return;
    }

    detailEl.innerHTML = H.renderLoading('Memuat detail dan riwayat percobaan quiz…');
    Promise.all([
      global.ClarityAPI.get('/quizzes/' + qid),
      global.ClarityAPI.get('/quizzes/' + qid + '/attempts/me')
    ]).then(function (results) {
      var q = results[0] && results[0].data;
      var attempts = Array.isArray(results[1] && results[1].data) ? results[1].data : [];
      if (!q) throw new Error('Detail quiz tidak ditemukan.');
      renderQuizDetail(q, attempts);
    }).catch(function (err) {
      detailEl.innerHTML = H.renderError(err && err.message, 'btn-retry-qi-detail');
      var r = document.getElementById('btn-retry-qi-detail');
      if (r) r.addEventListener('click', loadSelectedQuiz);
    });
  }

  function loadCourseQuizzes(cid) {
    if (!cid) return;
    global.ClarityAPI.setSelectedCourseId(String(cid));
    quizSelect.innerHTML = '<option value="">Memuat quiz…</option>';
    detailEl.innerHTML = H.renderLoading('Memuat daftar quiz…');

    global.ClarityAPI.get('/courses/' + cid + '/quizzes')
      .then(function (res) {
        quizzes = Array.isArray(res.data) ? res.data : [];
        if (quizzes.length === 0) {
          quizSelect.innerHTML = '<option value="">Belum ada quiz</option>';
          detailEl.innerHTML = H.renderEmpty('Belum Ada Quiz', 'Belum ada quiz yang tersedia pada mata kuliah ini.');
          return;
        }
        var paramQid = global.ClarityUI ? global.ClarityUI.getQueryParam('quiz_id') : '';
        quizSelect.innerHTML = quizzes.map(function (q) {
          return '<option value="' + q.id + '">' + esc(q.title) + '</option>';
        }).join('');
        if (paramQid && quizzes.some(function (q) { return String(q.id) === String(paramQid); })) {
          quizSelect.value = String(paramQid);
        }
        loadSelectedQuiz();
      })
      .catch(function (err) {
        detailEl.innerHTML = H.renderError(err && err.message, 'btn-retry-qi-list');
        var r = document.getElementById('btn-retry-qi-list');
        if (r) r.addEventListener('click', function () { loadCourseQuizzes(cid); });
      });
  }

  courseSelect.addEventListener('change', function () { loadCourseQuizzes(courseSelect.value); });
  quizSelect.addEventListener('change', loadSelectedQuiz);

  function init() {
    detailEl.innerHTML = H.renderLoading('Memuat daftar mata kuliah…');
    global.ClarityAPI.get('/courses?per_page=50')
      .then(function (res) {
        var courses = Array.isArray(res.data) ? res.data : [];
        if (courses.length === 0) {
          detailEl.innerHTML = H.renderEmpty('Belum Ada Mata Kuliah', 'Anda belum terdaftar pada mata kuliah apa pun.');
          return;
        }
        var paramCid = global.ClarityUI ? global.ClarityUI.getQueryParam('course_id') : '';
        var savedCid = paramCid || global.ClarityAPI.getSelectedCourseId() || String(courses[0].id);
        if (!courses.some(function (c) { return String(c.id) === String(savedCid); })) {
          savedCid = String(courses[0].id);
        }
        courseSelect.innerHTML = courses.map(function (c) {
          return '<option value="' + c.id + '">' + esc(c.code + ' - ' + c.name) + '</option>';
        }).join('');
        courseSelect.value = savedCid;
        loadCourseQuizzes(savedCid);
      })
      .catch(function (err) {
        detailEl.innerHTML = H.renderError(err && err.message, 'btn-retry-qi-init');
        var r = document.getElementById('btn-retry-qi-init');
        if (r) r.addEventListener('click', init);
      });
  }

  init();
})(window);
