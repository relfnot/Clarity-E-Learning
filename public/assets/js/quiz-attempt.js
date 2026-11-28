/* ==========================================================================
   Clarity E-Learning — Pengerjaan Quiz Mahasiswa: Timer Server & Autosave
   ========================================================================== */

(function (global) {
  'use strict';

  var titleEl = document.getElementById('qa-quiz-title');
  var codeEl = document.getElementById('qa-course-code');
  var badgeEl = document.getElementById('qa-attempt-badge');
  var autosaveEl = document.getElementById('qa-autosave-status');
  var timerBadge = document.getElementById('qa-timer-badge');
  var timerText = document.getElementById('qa-timer-text');
  var openSubmitBtn = document.getElementById('qa-open-submit-btn');
  var sideSubmitBtn = document.getElementById('qa-side-submit-btn');
  var alertRegion = document.getElementById('qa-alert-region');
  var workspace = document.getElementById('qa-workspace');

  var qNumEl = document.getElementById('qa-q-num');
  var qTotalEl = document.getElementById('qa-q-total');
  var qTypeBadge = document.getElementById('qa-q-type-badge');
  var qContainer = document.getElementById('qa-question-container');
  var navGrid = document.getElementById('qa-nav-grid');
  var prevBtn = document.getElementById('qa-prev-btn');
  var flagBtn = document.getElementById('qa-flag-btn');
  var nextBtn = document.getElementById('qa-next-btn');
  var countAnsEl = document.getElementById('qa-count-answered');
  var countFlagEl = document.getElementById('qa-count-flagged');

  var modal = document.getElementById('qa-submit-modal');
  var modalSummary = document.getElementById('qa-modal-summary');
  var modalClose = document.getElementById('qa-modal-close');
  var modalCancel = document.getElementById('qa-modal-cancel');
  var modalConfirm = document.getElementById('qa-modal-confirm');

  if (!qContainer || !navGrid) return;

  var H = global.ClarityHelpers;
  var esc = H ? H.esc : function (v) { return String(v || ''); };

  var attemptId = 0;
  var attemptData = null;
  var questions = [];
  var answersMap = {}; // question_id -> { option_id: int|null, answer_text: string|null }
  var flaggedMap = {}; // question_id -> bool
  var dirtyMap = {};   // question_id -> bool
  var currentIdx = 0;

  var remainingAnchorSec = 0;
  var anchorClientMs = Date.now();
  var timerInterval = null;
  var autosaveInterval = null;
  var essayDebounceTimer = null;
  var isSubmitting = false;

  function resultUrl() {
    return global.ClarityAPI.pageUrl('quiz-result.php') + '?attempt_id=' + attemptId;
  }

  function showErrorState(msg) {
    if (workspace) workspace.hidden = true;
    if (alertRegion) {
      alertRegion.hidden = false;
      alertRegion.innerHTML = H.renderError(msg, 'btn-retry-qa');
      var r = document.getElementById('btn-retry-qa');
      if (r) r.addEventListener('click', loadAttempt);
    }
  }

  function isQuestionAnswered(q) {
    var a = answersMap[q.id];
    if (!a) return false;
    if (q.type === 'pg') {
      return a.option_id !== null && a.option_id !== undefined;
    }
    return Boolean(a.answer_text && String(a.answer_text).trim() !== '');
  }

  function buildAnswersPayload(onlyDirty) {
    var list = [];
    questions.forEach(function (q) {
      if (onlyDirty && !dirtyMap[q.id]) return;
      var a = answersMap[q.id];
      if (!a) return;
      list.push({
        question_id: q.id,
        option_id: q.type === 'pg' ? (a.option_id || null) : null,
        answer_text: q.type === 'esai' ? (a.answer_text || '') : null
      });
    });
    return list;
  }

  function flushAutosave(silent) {
    var payloadList = buildAnswersPayload(true);
    if (payloadList.length === 0 || isSubmitting) {
      return Promise.resolve();
    }

    var sentIds = payloadList.map(function (item) { return item.question_id; });
    if (autosaveEl && !silent) autosaveEl.textContent = 'Menyimpan jawaban…';

    return global.ClarityAPI.put('/attempts/' + attemptId + '/answers', { answers: payloadList })
      .then(function (res) {
        sentIds.forEach(function (id) { delete dirtyMap[id]; });
        var savedAt = res && res.data && res.data.saved_at ? H.formatDateTime(res.data.saved_at) : 'baru saja';
        if (autosaveEl) autosaveEl.textContent = 'Tersimpan otomatis pada ' + savedAt;
      })
      .catch(function (err) {
        if (err && err.details && err.details.expired) {
          window.location.replace(resultUrl());
          return;
        }
        if (autosaveEl) autosaveEl.textContent = 'Gagal menyimpan otomatis: ' + ((err && err.message) || 'coba periksa koneksi');
      });
  }

  function formatMmSs(totalSeconds) {
    var s = Math.max(0, Math.floor(totalSeconds));
    var hrs = Math.floor(s / 3600);
    var mins = Math.floor((s % 3600) / 60);
    var secs = s % 60;
    var pad = function (n) { return n < 10 ? '0' + n : String(n); };
    return hrs > 0 ? (hrs + ':' + pad(mins) + ':' + pad(secs)) : (pad(mins) + ':' + pad(secs));
  }

  function updateTimerDisplay() {
    var elapsed = Math.floor((Date.now() - anchorClientMs) / 1000);
    var rem = Math.max(0, remainingAnchorSec - elapsed);

    if (timerText) timerText.textContent = formatMmSs(rem);
    if (timerBadge) {
      timerBadge.classList.toggle('is-warning', rem <= 300 && rem > 60);
      timerBadge.classList.toggle('is-danger', rem <= 60);
    }

    if (rem <= 0 && !isSubmitting) {
      submitNow(true);
    }
  }

  function startTimers() {
    if (timerInterval) clearInterval(timerInterval);
    if (autosaveInterval) clearInterval(autosaveInterval);

    updateTimerDisplay();
    timerInterval = setInterval(updateTimerDisplay, 1000);
    autosaveInterval = setInterval(function () { flushAutosave(true); }, 30000);
  }

  function renderNavigator() {
    var answeredCount = 0;
    var flaggedCount = 0;

    questions.forEach(function (q) {
      if (isQuestionAnswered(q)) answeredCount++;
      if (flaggedMap[q.id]) flaggedCount++;
    });

    if (countAnsEl) countAnsEl.textContent = answeredCount + ' / ' + questions.length;
    if (countFlagEl) countFlagEl.textContent = String(flaggedCount);

    navGrid.innerHTML = questions.map(function (q, idx) {
      var cls = 'quiz-num-btn';
      if (isQuestionAnswered(q)) cls += ' is-answered';
      if (flaggedMap[q.id]) cls += ' is-flagged';
      if (idx === currentIdx) cls += ' is-current';
      return '<button type="button" class="' + cls + '" data-q-idx="' + idx + '" aria-label="Soal nomor ' + (idx + 1) + '">' + (idx + 1) + '</button>';
    }).join('');

    navGrid.querySelectorAll('[data-q-idx]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        currentIdx = parseInt(btn.getAttribute('data-q-idx'), 10) || 0;
        renderCurrentQuestion();
      });
    });

    if (flagBtn && questions[currentIdx]) {
      var isFlagged = Boolean(flaggedMap[questions[currentIdx].id]);
      flagBtn.textContent = isFlagged ? 'Hapus Tanda Ragu-ragu' : 'Tandai Ragu-ragu';
      flagBtn.setAttribute('aria-pressed', isFlagged ? 'true' : 'false');
    }
  }

  function renderCurrentQuestion() {
    if (questions.length === 0) return;
    var q = questions[currentIdx];
    var saved = answersMap[q.id] || { option_id: null, answer_text: '' };

    if (qNumEl) qNumEl.textContent = String(currentIdx + 1);
    if (qTotalEl) qTotalEl.textContent = String(questions.length);
    if (qTypeBadge) qTypeBadge.textContent = (q.type === 'pg' ? 'Pilihan Ganda' : 'Esai') + ' | ' + q.points + ' Poin';

    var html = '<p class="text-base font-semibold text-ink leading-relaxed m-0">' + esc(q.text) + '</p>';

    if (q.type === 'pg') {
      html += '<fieldset class="space-y-3 border-0 p-0 m-0"><legend class="sr-only">Pilih satu jawaban</legend>';
      (q.options || []).forEach(function (opt, idx) {
        var letter = String.fromCharCode(65 + idx);
        var checked = Number(saved.option_id) === Number(opt.id);
        html += '<label class="quiz-option-card' + (checked ? ' is-selected' : '') + '">' +
          '<input type="radio" name="qa_pg_' + q.id + '" value="' + opt.id + '"' + (checked ? ' checked' : '') + '>' +
          '<span class="text-sm text-ink leading-snug"><strong class="font-mono mr-1">' + esc(letter) + '.</strong> ' + esc(opt.text) + '</span>' +
          '</label>';
      });
      html += '</fieldset>';
    } else {
      html += '<div>' +
        '<label for="qa-essay-input" class="block text-sm font-semibold text-ink mb-1.5">Jawaban Esai Anda</label>' +
        '<textarea id="qa-essay-input" rows="6" class="clarity-input w-full" placeholder="Tuliskan jawaban lengkap Anda di sini…">' + esc(saved.answer_text || '') + '</textarea>' +
        '</div>';
    }

    qContainer.innerHTML = html;

    qContainer.querySelectorAll('input[type="radio"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        answersMap[q.id] = { option_id: parseInt(radio.value, 10), answer_text: null };
        dirtyMap[q.id] = true;
        renderCurrentQuestion();
        flushAutosave(false);
      });
    });

    var essayEl = document.getElementById('qa-essay-input');
    if (essayEl) {
      essayEl.addEventListener('input', function () {
        answersMap[q.id] = { option_id: null, answer_text: essayEl.value };
        dirtyMap[q.id] = true;
        renderNavigator();
        clearTimeout(essayDebounceTimer);
        essayDebounceTimer = setTimeout(function () {
          flushAutosave(false);
        }, 1200);
      });
    }

    if (prevBtn) prevBtn.disabled = currentIdx === 0;
    if (nextBtn) nextBtn.disabled = currentIdx >= questions.length - 1;
    if (flagBtn) flagBtn.disabled = false;
    renderNavigator();
  }

  function openConfirmModal() {
    if (!modal) return;
    var answeredCount = questions.filter(isQuestionAnswered).length;
    var unanswered = questions.length - answeredCount;
    if (modalSummary) {
      modalSummary.textContent = 'Terjawab: ' + answeredCount + ' dari ' + questions.length + ' soal.' +
        (unanswered > 0 ? ' Masih ada ' + unanswered + ' soal yang belum diisi.' : ' Seluruh soal telah terjawab.');
    }
    modal.hidden = false;
    if (modalConfirm) modalConfirm.focus();
  }

  function closeConfirmModal() {
    if (modal) modal.hidden = true;
  }

  function submitNow(isAutoTimeout) {
    if (isSubmitting) return;
    isSubmitting = true;
    closeConfirmModal();

    if (timerInterval) clearInterval(timerInterval);
    if (autosaveInterval) clearInterval(autosaveInterval);
    clearTimeout(essayDebounceTimer);

    if (openSubmitBtn) openSubmitBtn.disabled = true;
    if (sideSubmitBtn) sideSubmitBtn.disabled = true;
    if (modalConfirm) modalConfirm.disabled = true;
    if (autosaveEl) autosaveEl.textContent = isAutoTimeout ? 'Waktu habis, mengumpulkan jawaban…' : 'Mengumpulkan jawaban quiz…';

    var finalPayload = isAutoTimeout ? null : buildAnswersPayload(false);
    global.ClarityAPI.post('/attempts/' + attemptId + '/submit', finalPayload ? { answers: finalPayload } : {})
      .then(function () {
        window.location.replace(resultUrl());
      })
      .catch(function (err) {
        if (err && (err.status === 409 || (err.details && err.details.expired))) {
          window.location.replace(resultUrl());
          return;
        }
        isSubmitting = false;
        if (openSubmitBtn) openSubmitBtn.disabled = false;
        if (sideSubmitBtn) sideSubmitBtn.disabled = false;
        if (modalConfirm) modalConfirm.disabled = false;
        if (global.ClarityUI) global.ClarityUI.showToast((err && err.message) || 'Gagal mengumpulkan quiz.', 'danger');
      });
  }

  function loadAttempt() {
    attemptId = parseInt(global.ClarityUI ? global.ClarityUI.getQueryParam('attempt_id') : '', 10) || 0;
    if (attemptId <= 0) {
      showErrorState('Parameter attempt_id tidak ditemukan. Silakan mulai quiz melalui halaman Informasi Quiz.');
      return;
    }

    if (workspace) workspace.hidden = false;
    if (alertRegion) alertRegion.hidden = true;

    global.ClarityAPI.get('/attempts/' + attemptId)
      .then(function (res) {
        attemptData = res && res.data;
        if (!attemptData) throw new Error('Data percobaan tidak ditemukan.');
        if (attemptData.status !== 'in_progress') {
          window.location.replace(resultUrl());
          return;
        }

        questions = Array.isArray(attemptData.questions) ? attemptData.questions : [];
        questions.forEach(function (q) {
          if (q.selected_option_id !== null && q.selected_option_id !== undefined) {
            answersMap[q.id] = { option_id: q.selected_option_id, answer_text: null };
          } else if (q.answer_text !== null && q.answer_text !== undefined && q.answer_text !== '') {
            answersMap[q.id] = { option_id: null, answer_text: q.answer_text };
          }
        });

        if (codeEl) codeEl.textContent = attemptData.course_code || '';
        if (badgeEl) badgeEl.textContent = 'Percobaan #' + (attemptData.attempt_number || 1);
        if (titleEl) titleEl.textContent = attemptData.quiz_title || 'Quiz';
        if (autosaveEl) autosaveEl.textContent = 'Status: Tersimpan otomatis aktif (setiap perubahan & tiap 30 detik)';
        if (openSubmitBtn) openSubmitBtn.disabled = false;
        if (sideSubmitBtn) sideSubmitBtn.disabled = false;

        remainingAnchorSec = Number(attemptData.remaining_seconds || 0);
        anchorClientMs = Date.now();
        startTimers();
        renderCurrentQuestion();
      })
      .catch(function (err) {
        showErrorState((err && err.message) || 'Gagal memuat percobaan quiz.');
      });
  }

  if (prevBtn) prevBtn.addEventListener('click', function () {
    if (currentIdx > 0) { currentIdx--; renderCurrentQuestion(); }
  });
  if (nextBtn) nextBtn.addEventListener('click', function () {
    if (currentIdx < questions.length - 1) { currentIdx++; renderCurrentQuestion(); }
  });
  if (flagBtn) flagBtn.addEventListener('click', function () {
    if (!questions[currentIdx]) return;
    var qId = questions[currentIdx].id;
    flaggedMap[qId] = !flaggedMap[qId];
    renderNavigator();
  });

  if (openSubmitBtn) openSubmitBtn.addEventListener('click', openConfirmModal);
  if (sideSubmitBtn) sideSubmitBtn.addEventListener('click', openConfirmModal);
  if (modalClose) modalClose.addEventListener('click', closeConfirmModal);
  if (modalCancel) modalCancel.addEventListener('click', closeConfirmModal);
  if (modalConfirm) modalConfirm.addEventListener('click', function () { submitNow(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal && !modal.hidden) closeConfirmModal();
  });

  loadAttempt();
})(window);
