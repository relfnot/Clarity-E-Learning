/* ==========================================================================
   Clarity E-Learning — Hasil Quiz Mahasiswa & Penilaian Esai Dosen (Item 20)
   ========================================================================== */

(function (global) {
  'use strict';

  var containerEl = document.getElementById('qr-container');
  if (!containerEl) return;

  var H = global.ClarityHelpers;
  var esc = H ? H.esc : function (v) { return String(v || ''); };

  function currentUser() {
    return global.ClarityAuth && global.ClarityAuth.getUser ? global.ClarityAuth.getUser() : null;
  }

  function renderResult(a) {
    var user = currentUser();
    var isDosen = user && user.role === 'dosen';
    var isPendingEssay = a.status === 'menunggu_penilaian';

    var statusTag = isPendingEssay
      ? '<span class="clarity-tag clarity-tag-warning">Menunggu Penilaian Esai</span>'
      : '<span class="clarity-tag clarity-tag-success">Selesai & Dinilai</span>';

    var backUrl = isDosen
      ? global.ClarityAPI.pageUrl('quiz-builder.php') + '?course_id=' + a.course_id + '&quiz_id=' + a.quiz_id + '&tab=attempts'
      : global.ClarityAPI.pageUrl('quiz-intro.php') + '?course_id=' + a.course_id + '&quiz_id=' + a.quiz_id;

    var gradesUrl = global.ClarityAPI.pageUrl(isDosen ? 'grading.php' : 'grades.php') + '?course_id=' + a.course_id;

    var headerHtml = '<section class="clarity-card hallmark-reveal p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">' +
      '<div class="space-y-2">' +
      '  <div class="flex items-center gap-2 flex-wrap">' +
      '    ' + statusTag +
      '    <span class="course-code-pill font-mono">' + esc(a.course_code) + '</span>' +
      '    <span class="clarity-tag clarity-tag-neutral">Percobaan #' + esc(a.attempt_number) + '</span>' +
      '  </div>' +
      '  <h1 class="text-xl sm:text-2xl font-extrabold text-ink m-0">' + esc(a.quiz_title) + '</h1>' +
      '  <p class="text-sm text-secondary m-0">' +
      (isDosen ? 'Mahasiswa: <strong class="text-ink">' + esc(a.student_name) + '</strong> (' + esc(a.nim_nip) + ') | ' : '') +
      'Dikumpulkan: ' + esc(H.formatDateTime(a.submitted_at)) +
      '  </p>' +
      '</div>' +
      '<div class="clarity-well p-4 text-left md:text-right min-w-[200px] shrink-0">' +
      '  <div class="text-xs font-semibold text-muted">' + (isPendingEssay ? 'Skor Sementara (PG Otomatis)' : 'Skor Akhir Quiz') + '</div>' +
      '  <div class="text-3xl font-extrabold font-mono text-accent">' + (a.score !== null && a.score !== undefined ? esc(a.score) : '0') + ' <span class="text-base text-muted font-semibold">/ 100</span></div>' +
      '</div>' +
      '</section>';

    var revealNotice = '';
    if (!isDosen && !a.can_view_answers) {
      revealNotice = '<div class="clarity-alert clarity-alert-info">' +
        (a.show_answers
          ? 'Kunci jawaban Pilihan Ganda akan ditampilkan setelah jendela quiz ditutup pada ' + esc(H.formatDateTime(a.answers_reveal_at)) + '.'
          : 'Pengaturan quiz tidak menampilkan kunci jawaban.') +
        '</div>';
    }

    var questionsHtml = (a.questions || []).map(function (q, idx) {
      var badge = '';
      var bodyHtml = '';

      if (q.type === 'pg') {
        var selectedOpt = (q.options || []).find(function (o) { return Number(o.id) === Number(q.selected_option_id); });
        var correctOpt = (q.options || []).find(function (o) { return Boolean(o.is_correct); });
        var earned = q.earned_score !== null && q.earned_score !== undefined ? Number(q.earned_score) : 0;

        if (a.can_view_answers) {
          badge = earned > 0
            ? '<span class="clarity-tag clarity-tag-success">Benar (+' + esc(earned) + ' Poin)</span>'
            : '<span class="clarity-tag clarity-tag-danger">Kurang Tepat (0 Poin)</span>';
        } else {
          badge = '<span class="clarity-tag clarity-tag-neutral">PG | ' + esc(q.points) + ' Poin (Terkoreksi)</span>';
        }

        bodyHtml += '<p class="text-sm text-secondary m-0">Jawaban dipilih: <strong class="text-ink">' +
          (selectedOpt ? esc(selectedOpt.label + '. ' + selectedOpt.text) : 'Tidak dijawab') + '</strong></p>';

        if (a.can_view_answers && correctOpt) {
          bodyHtml += '<p class="text-sm text-success m-0">Kunci jawaban: <strong>' + esc(correctOpt.label + '. ' + correctOpt.text) + '</strong></p>';
        }
      } else {
        var essayGraded = Boolean(q.graded_at);
        badge = essayGraded
          ? '<span class="clarity-tag clarity-tag-success">Esai Dinilai (' + esc(q.earned_score) + ' / ' + esc(q.points) + ' Poin)</span>'
          : '<span class="clarity-tag clarity-tag-warning">Menunggu Penilaian Dosen (Maks ' + esc(q.points) + ' Poin)</span>';

        bodyHtml += '<div class="clarity-well p-3 text-sm text-ink whitespace-pre-wrap">' +
          (q.answer_text && String(q.answer_text).trim() !== '' ? esc(q.answer_text) : '<span class="text-muted">Tidak ada jawaban esai.</span>') +
          '</div>';

        if (isDosen && q.answer_id) {
          bodyHtml += '<form class="flex flex-wrap items-end gap-3 pt-2" data-grade-essay-form="' + q.answer_id + '">' +
            '<div><label for="essay-score-' + q.answer_id + '" class="block text-xs font-semibold text-ink mb-1">Nilai Esai (0 - ' + esc(q.points) + ')</label>' +
            '<input id="essay-score-' + q.answer_id + '" type="number" step="0.5" min="0" max="' + esc(q.points) + '" class="clarity-input w-36 text-sm" value="' + (q.earned_score !== null && q.earned_score !== undefined ? esc(q.earned_score) : '') + '" required></div>' +
            '<button type="submit" class="clarity-btn clarity-btn-primary clarity-btn-sm">Simpan Nilai Esai</button>' +
            '</form>';
        }
      }

      return '<article class="clarity-hover-row p-5 space-y-2">' +
        '<div class="flex flex-wrap items-center justify-between gap-2">' +
        '  <span class="text-sm font-bold text-ink">Soal ' + (idx + 1) + ' (' + (q.type === 'pg' ? 'Pilihan Ganda' : 'Esai') + ')</span>' +
        '  ' + badge +
        '</div>' +
        '<p class="text-sm font-medium text-ink m-0">' + esc(q.text) + '</p>' +
        bodyHtml +
        '</article>';
    }).join('');

    var listSection = '<section class="clarity-card overflow-hidden">' +
      '<div class="clarity-section-head">' +
      '  <h2 class="text-base font-bold text-ink m-0">Rincian Jawaban per Soal</h2>' +
      '  <div class="flex items-center gap-2">' +
      '    <a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="' + esc(backUrl) + '">' + (isDosen ? 'Kembali ke Daftar Percobaan' : 'Kembali ke Intro Quiz') + '</a>' +
      '    <a class="clarity-btn clarity-btn-primary clarity-btn-sm" href="' + esc(gradesUrl) + '">Rekap Nilai</a>' +
      '  </div>' +
      '</div>' +
      '<div class="clarity-list">' + questionsHtml + '</div>' +
      '</section>';

    containerEl.innerHTML = headerHtml + revealNotice + listSection;

    containerEl.querySelectorAll('[data-grade-essay-form]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var ansId = form.getAttribute('data-grade-essay-form');
        var input = document.getElementById('essay-score-' + ansId);
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        global.ClarityAPI.put('/answers/' + ansId + '/grade', { score: Number(input.value) })
          .then(function (res) {
            if (global.ClarityUI) global.ClarityUI.showToast('Nilai esai berhasil disimpan.', 'success');
            if (res && res.data) {
              renderResult(res.data);
            }
          })
          .catch(function (err) {
            if (global.ClarityUI) global.ClarityUI.showToast((err && err.message) || 'Gagal menyimpan nilai esai.', 'danger');
            btn.disabled = false;
          });
      });
    });
  }

  function loadResult() {
    var attemptId = parseInt(global.ClarityUI ? global.ClarityUI.getQueryParam('attempt_id') : '', 10) || 0;
    if (attemptId <= 0) {
      containerEl.innerHTML = H.renderError('Parameter attempt_id tidak valid.');
      return;
    }

    containerEl.innerHTML = H.renderLoading('Memuat hasil pengerjaan quiz…');
    global.ClarityAPI.get('/attempts/' + attemptId)
      .then(function (res) {
        var data = res && res.data;
        if (!data) throw new Error('Data hasil tidak ditemukan.');
        if (data.status === 'in_progress') {
          var user = currentUser();
          if (user && user.role === 'mahasiswa') {
            window.location.replace(global.ClarityAPI.pageUrl('quiz-attempt.php') + '?attempt_id=' + attemptId);
            return;
          }
        }
        renderResult(data);
      })
      .catch(function (err) {
        containerEl.innerHTML = H.renderError((err && err.message) || 'Gagal memuat hasil quiz.', 'btn-retry-qr');
        var r = document.getElementById('btn-retry-qr');
        if (r) r.addEventListener('click', loadResult);
      });
  }

  loadResult();
})(window);
