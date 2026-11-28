/* ==========================================================================
   Clarity E-Learning — Pembuat Quiz, Bank Soal & Daftar Percobaan (Item 21)
   ========================================================================== */

(function (global) {
  'use strict';

  var courseSelect = document.getElementById('qb-course-select');
  var quizSelect = document.getElementById('qb-quiz-select');
  var newQuizBtn = document.getElementById('qb-btn-new-quiz');
  var deleteQuizBtn = document.getElementById('qb-btn-delete-quiz');
  var headingEl = document.getElementById('qb-page-heading');
  var statusRegion = document.getElementById('qb-status-region');

  var builderPanel = document.getElementById('qb-panel-builder');
  var attemptsPanel = document.getElementById('qb-panel-attempts');

  var metaTitleEl = document.getElementById('qb-meta-title');
  var metaForm = document.getElementById('qb-meta-form');
  var titleIn = document.getElementById('qb-title');
  var meetingIn = document.getElementById('qb-meeting');
  var descIn = document.getElementById('qb-desc');
  var openIn = document.getElementById('qb-open');
  var closeIn = document.getElementById('qb-close');
  var durIn = document.getElementById('qb-duration');
  var attMaxIn = document.getElementById('qb-attempts');
  var weightIn = document.getElementById('qb-weight');
  var shuffleIn = document.getElementById('qb-shuffle');
  var showAnsIn = document.getElementById('qb-show-answers');
  var metaFeedback = document.getElementById('qb-meta-feedback');

  var attemptsWarn = document.getElementById('qb-attempts-warning');
  var qFormTitle = document.getElementById('qb-qform-title');
  var qLockedNotice = document.getElementById('qb-q-locked-notice');
  var qForm = document.getElementById('qb-question-form');
  var qCancelEditBtn = document.getElementById('qb-q-cancel-edit');
  var qTypeIn = document.getElementById('qb-q-type');
  var qPointsIn = document.getElementById('qb-q-points');
  var qPosIn = document.getElementById('qb-q-pos');
  var qTextIn = document.getElementById('qb-q-text');
  var optsSection = document.getElementById('qb-options-section');
  var optsRowsEl = document.getElementById('qb-options-rows');
  var addOptBtn = document.getElementById('qb-add-opt-btn');
  var qFeedback = document.getElementById('qb-q-feedback');
  var qCountEl = document.getElementById('qb-q-count');
  var qPointsSumEl = document.getElementById('qb-q-points-sum');
  var qListEl = document.getElementById('qb-questions-list');

  var attSummaryEl = document.getElementById('qb-att-summary');
  var attTableEl = document.getElementById('qb-att-table');

  if (!courseSelect || !quizSelect || !metaForm || !qForm) return;

  var H = global.ClarityHelpers;
  var esc = H ? H.esc : function (v) { return String(v || ''); };

  var quizzes = [];
  var activeQuiz = null;
  var editingQuestionId = null;
  var optionDrafts = [];
  var activeTab = 'builder';
  var attStatusFilter = '';

  function isoToLocalInput(isoStr) {
    if (!isoStr) return '';
    var d = new Date(isoStr);
    if (isNaN(d.getTime())) return String(isoStr).slice(0, 16);
    var wib = new Date(d.getTime() + 7 * 3600 * 1000);
    return wib.toISOString().slice(0, 16);
  }

  function defaultWindowPair() {
    var now = new Date();
    var open = new Date(now.getTime() + 3600 * 1000);
    var close = new Date(now.getTime() + 7 * 86400 * 1000);
    return {
      open: isoToLocalInput(open.toISOString()),
      close: isoToLocalInput(close.toISOString())
    };
  }

  function renderOptionRows() {
    if (!optsRowsEl) return;
    if (qTypeIn.value !== 'pg') {
      if (optsSection) optsSection.hidden = true;
      return;
    }
    if (optsSection) optsSection.hidden = false;

    if (optionDrafts.length < 2) {
      optionDrafts = [
        { text: '', is_correct: true },
        { text: '', is_correct: false },
        { text: '', is_correct: false },
        { text: '', is_correct: false }
      ];
    }

    optsRowsEl.innerHTML = optionDrafts.map(function (opt, idx) {
      var letter = String.fromCharCode(65 + idx);
      return '<div class="flex items-center gap-2" data-opt-row="' + idx + '">' +
        '<input type="radio" name="qb_correct_opt" value="' + idx + '"' + (opt.is_correct ? ' checked' : '') + ' aria-label="Jadikan opsi ' + letter + ' kunci jawaban">' +
        '<span class="font-mono text-xs font-bold w-5 text-ink">' + letter + '.</span>' +
        '<input type="text" class="clarity-input text-sm flex-1" data-opt-text="' + idx + '" value="' + esc(opt.text) + '" placeholder="Teks opsi ' + letter + '…">' +
        (optionDrafts.length > 2
          ? '<button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" data-opt-del="' + idx + '" aria-label="Hapus opsi ' + letter + '">Hapus</button>'
          : '') +
        '</div>';
    }).join('');

    optsRowsEl.querySelectorAll('input[name="qb_correct_opt"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        var selIdx = parseInt(radio.value, 10);
        optionDrafts.forEach(function (o, i) { o.is_correct = i === selIdx; });
      });
    });

    optsRowsEl.querySelectorAll('[data-opt-text]').forEach(function (inp) {
      inp.addEventListener('input', function () {
        var idx = parseInt(inp.getAttribute('data-opt-text'), 10);
        if (optionDrafts[idx]) optionDrafts[idx].text = inp.value;
      });
    });

    optsRowsEl.querySelectorAll('[data-opt-del]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var idx = parseInt(btn.getAttribute('data-opt-del'), 10);
        if (optionDrafts.length <= 2) return;
        var wasCorrect = optionDrafts[idx] && optionDrafts[idx].is_correct;
        optionDrafts.splice(idx, 1);
        if (wasCorrect && optionDrafts[0]) optionDrafts[0].is_correct = true;
        renderOptionRows();
      });
    });
  }

  function resetQuestionForm(nextPosition) {
    editingQuestionId = null;
    if (qFormTitle) qFormTitle.textContent = 'Tambah Soal ke Bank Soal';
    if (qCancelEditBtn) qCancelEditBtn.hidden = true;
    if (qFeedback) { qFeedback.hidden = true; qFeedback.innerHTML = ''; }
    qTypeIn.value = 'pg';
    qPointsIn.value = '20';
    qPosIn.value = String(nextPosition || 1);
    qTextIn.value = '';
    optionDrafts = [
      { text: '', is_correct: true },
      { text: '', is_correct: false },
      { text: '', is_correct: false },
      { text: '', is_correct: false }
    ];
    renderOptionRows();
  }

  function fillMetaFormForNew() {
    activeQuiz = null;
    if (metaTitleEl) metaTitleEl.textContent = 'Buat Quiz Baru';
    if (deleteQuizBtn) deleteQuizBtn.hidden = true;
    if (attemptsWarn) attemptsWarn.hidden = true;
    if (qLockedNotice) qLockedNotice.hidden = false;
    qForm.hidden = true;
    qListEl.innerHTML = '<div class="p-6 text-sm text-muted">Belum ada soal karena quiz belum dibuat.</div>';
    if (qCountEl) qCountEl.textContent = '0';
    if (qPointsSumEl) qPointsSumEl.textContent = 'Total 0 Poin';

    titleIn.value = '';
    meetingIn.value = '';
    descIn.value = '';
    var pair = defaultWindowPair();
    openIn.value = pair.open;
    closeIn.value = pair.close;
    durIn.value = '30';
    attMaxIn.value = '2';
    weightIn.value = '20';
    shuffleIn.checked = true;
    showAnsIn.checked = true;
    if (metaFeedback) { metaFeedback.hidden = true; metaFeedback.innerHTML = ''; }
  }

  function fillMetaFormForQuiz(q) {
    activeQuiz = q;
    if (metaTitleEl) metaTitleEl.textContent = 'Pengaturan Quiz';
    if (deleteQuizBtn) deleteQuizBtn.hidden = false;
    if (attemptsWarn) attemptsWarn.hidden = !(q.attempts_count && q.attempts_count > 0);
    if (qLockedNotice) qLockedNotice.hidden = true;
    qForm.hidden = false;
    if (metaFeedback) { metaFeedback.hidden = true; metaFeedback.innerHTML = ''; }

    titleIn.value = q.title || '';
    meetingIn.value = q.meeting_id ? String(q.meeting_id) : '';
    descIn.value = q.description || '';
    openIn.value = isoToLocalInput(q.open_at);
    closeIn.value = isoToLocalInput(q.close_at);
    durIn.value = String(q.duration_min || 30);
    attMaxIn.value = String(q.max_attempts || 1);
    weightIn.value = String(q.weight !== undefined ? q.weight : 20);
    shuffleIn.checked = Boolean(q.shuffle);
    showAnsIn.checked = Boolean(q.show_answers);
  }

  function renderQuestionsList(items) {
    var totalPts = 0;
    items.forEach(function (q) { totalPts += Number(q.points || 0); });
    if (qCountEl) qCountEl.textContent = String(items.length);
    if (qPointsSumEl) qPointsSumEl.textContent = 'Total ' + totalPts + ' Poin';

    if (items.length === 0) {
      qListEl.innerHTML = '<div class="p-6">' + H.renderEmpty('Bank Soal Kosong', 'Tambahkan minimal 1 soal Pilihan Ganda atau Esai menggunakan formulir di atas.') + '</div>';
      return;
    }

    qListEl.innerHTML = items.map(function (q) {
      var typeBadge = q.type === 'pg'
        ? '<span class="clarity-tag clarity-tag-info">Pilihan Ganda</span>'
        : '<span class="clarity-tag clarity-tag-warning">Esai</span>';

      var optsHtml = '';
      if (q.type === 'pg' && Array.isArray(q.options)) {
        optsHtml = '<ul class="list-none p-0 m-0 space-y-1 text-xs text-secondary">' + q.options.map(function (o) {
          return '<li class="' + (o.is_correct ? 'font-bold text-success' : '') + '">' +
            esc(o.label + '. ' + o.text) + (o.is_correct ? ' (Kunci Jawaban)' : '') + '</li>';
        }).join('') + '</ul>';
      }

      return '<article class="p-5 space-y-3">' +
        '<div class="flex flex-wrap items-center justify-between gap-2">' +
        '  <div class="flex items-center gap-2 flex-wrap">' +
        '    <span class="course-code-pill">#' + esc(q.position) + '</span>' +
        '    ' + typeBadge +
        '    <span class="clarity-tag clarity-tag-neutral">' + esc(q.points) + ' Poin</span>' +
        '  </div>' +
        '  <div class="flex items-center gap-2">' +
        '    <button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm" data-qb-edit-q="' + q.id + '">Ubah</button>' +
        '    <button type="button" class="clarity-btn clarity-btn-crimson clarity-btn-sm" data-qb-del-q="' + q.id + '">Hapus</button>' +
        '  </div>' +
        '</div>' +
        '<p class="text-sm font-semibold text-ink m-0">' + esc(q.text) + '</p>' +
        optsHtml +
        '</article>';
    }).join('');

    qListEl.querySelectorAll('[data-qb-edit-q]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var qId = parseInt(btn.getAttribute('data-qb-edit-q'), 10);
        var q = items.find(function (item) { return Number(item.id) === qId; });
        if (!q) return;
        editingQuestionId = qId;
        if (qFormTitle) qFormTitle.textContent = 'Ubah Soal #' + q.position;
        if (qCancelEditBtn) qCancelEditBtn.hidden = false;
        if (qFeedback) { qFeedback.hidden = true; qFeedback.innerHTML = ''; }
        qTypeIn.value = q.type || 'pg';
        qPointsIn.value = String(q.points || 20);
        qPosIn.value = String(q.position || 1);
        qTextIn.value = q.text || '';
        if (q.type === 'pg' && Array.isArray(q.options)) {
          optionDrafts = q.options.map(function (o) {
            return { text: o.text || '', is_correct: Boolean(o.is_correct) };
          });
        }
        renderOptionRows();
        qTextIn.focus();
      });
    });

    qListEl.querySelectorAll('[data-qb-del-q]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var qId = btn.getAttribute('data-qb-del-q');
        if (!window.confirm('Hapus soal ini dari bank soal?')) return;
        deleteQuestionWithForce(qId, false);
      });
    });
  }

  function deleteQuestionWithForce(questionId, force) {
    var qs = force ? '?force=true' : '';
    global.ClarityAPI.del('/questions/' + questionId + qs)
      .then(function () {
        if (global.ClarityUI) global.ClarityUI.showToast('Soal berhasil dihapus.', 'info');
        loadQuestions();
      })
      .catch(function (err) {
        if (err && err.status === 409 && !force) {
          if (window.confirm((err.message || 'Quiz sudah memiliki percobaan mahasiswa.') + '\n\nTetap paksa hapus soal?')) {
            deleteQuestionWithForce(questionId, true);
          }
          return;
        }
        if (global.ClarityUI) global.ClarityUI.showToast((err && err.message) || 'Gagal menghapus soal.', 'danger');
      });
  }

  function loadQuestions() {
    if (!activeQuiz || !activeQuiz.id) return;
    qListEl.innerHTML = '<div class="p-6">' + H.renderLoading('Memuat daftar soal…') + '</div>';
    global.ClarityAPI.get('/quizzes/' + activeQuiz.id + '/questions')
      .then(function (res) {
        var items = Array.isArray(res.data) ? res.data : [];
        renderQuestionsList(items);
        if (!editingQuestionId) {
          var nextPos = items.length > 0 ? Math.max.apply(null, items.map(function (q) { return Number(q.position) || 0; })) + 1 : 1;
          resetQuestionForm(nextPos);
        }
      })
      .catch(function (err) {
        qListEl.innerHTML = '<div class="p-6">' + H.renderError(err && err.message, 'btn-retry-qb-q') + '</div>';
        var r = document.getElementById('btn-retry-qb-q');
        if (r) r.addEventListener('click', loadQuestions);
      });
  }

  function attemptStatusBadge(status) {
    if (status === 'in_progress') return '<span class="clarity-tag clarity-tag-info">Sedang Dikerjakan</span>';
    if (status === 'menunggu_penilaian') return '<span class="clarity-tag clarity-tag-warning">Perlu Nilai Esai</span>';
    return '<span class="clarity-tag clarity-tag-success">Selesai</span>';
  }

  function loadAttempts() {
    if (!activeQuiz || !activeQuiz.id) {
      attSummaryEl.innerHTML = '';
      attTableEl.innerHTML = '<div class="p-6">' + H.renderEmpty('Pilih Quiz Terlebih Dahulu', 'Pilih atau buat quiz untuk melihat percobaan mahasiswa.') + '</div>';
      return;
    }

    attTableEl.innerHTML = '<div class="p-6">' + H.renderLoading('Memuat percobaan mahasiswa…') + '</div>';
    var qs = '?per_page=50' + (attStatusFilter ? '&status=' + encodeURIComponent(attStatusFilter) : '');

    global.ClarityAPI.get('/quizzes/' + activeQuiz.id + '/attempts' + qs)
      .then(function (res) {
        var items = Array.isArray(res.data) ? res.data : [];
        var s = (res.meta && res.meta.summary) || { total: 0, graded: 0, menunggu_penilaian: 0, in_progress: 0 };

        attSummaryEl.innerHTML = '<div class="clarity-card p-5"><div class="stat-ledger">' +
          '<div class="stat-cell"><div class="stat-label">Total Percobaan</div><div class="stat-value text-ink">' + esc(s.total) + '</div></div>' +
          '<div class="stat-cell"><div class="stat-label">Selesai Dinilai</div><div class="stat-value text-success">' + esc(s.graded) + '</div></div>' +
          '<div class="stat-cell"><div class="stat-label">Perlu Nilai Esai</div><div class="stat-value text-warning">' + esc(s.menunggu_penilaian) + '</div></div>' +
          '<div class="stat-cell"><div class="stat-label">Sedang Mengerjakan</div><div class="stat-value text-accent">' + esc(s.in_progress) + '</div></div>' +
          '</div></div>';

        if (items.length === 0) {
          attTableEl.innerHTML = '<div class="p-6">' + H.renderEmpty('Belum Ada Percobaan', 'Belum ada mahasiswa yang mengerjakan quiz ini atau cocok dengan filter.') + '</div>';
          return;
        }

        var html = '<div class="overflow-x-auto"><table class="clarity-table w-full">' +
          '<thead><tr><th>NIM</th><th>Nama Mahasiswa</th><th>Ke-</th><th>Status</th><th>Skor</th><th>Waktu Submit</th><th class="text-right">Aksi</th></tr></thead><tbody>';

        items.forEach(function (a) {
          var resUrl = global.ClarityAPI.pageUrl('quiz-result.php') + '?attempt_id=' + a.id;
          html += '<tr>' +
            '<td class="font-mono text-sm">' + esc(a.nim_nip) + '</td>' +
            '<td class="font-semibold text-ink">' + esc(a.student_name) + '</td>' +
            '<td class="font-mono">#' + esc(a.attempt_number) + '</td>' +
            '<td>' + attemptStatusBadge(a.status) + '</td>' +
            '<td class="font-mono font-bold">' + (a.score !== null && a.score !== undefined ? esc(a.score) : '-') + '</td>' +
            '<td class="text-sm text-muted">' + esc(a.submitted_at ? H.formatDateTime(a.submitted_at) : '-') + '</td>' +
            '<td class="text-right"><a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="' + esc(resUrl) + '">' + (a.status === 'menunggu_penilaian' ? 'Nilai Esai' : 'Lihat Detail') + '</a></td>' +
            '</tr>';
        });
        html += '</tbody></table></div>';
        attTableEl.innerHTML = html;
      })
      .catch(function (err) {
        attTableEl.innerHTML = '<div class="p-6">' + H.renderError(err && err.message, 'btn-retry-qb-att') + '</div>';
        var r = document.getElementById('btn-retry-qb-att');
        if (r) r.addEventListener('click', loadAttempts);
      });
  }

  function switchTab(tab) {
    activeTab = tab;
    document.querySelectorAll('[data-qb-tab]').forEach(function (btn) {
      var on = btn.getAttribute('data-qb-tab') === tab;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    if (builderPanel) builderPanel.hidden = tab !== 'builder';
    if (attemptsPanel) attemptsPanel.hidden = tab !== 'attempts';
    if (headingEl) headingEl.textContent = tab === 'attempts' ? 'Daftar Percobaan & Nilai Esai' : 'Pengaturan & Bank Soal Quiz';
    if (tab === 'attempts') {
      loadAttempts();
    }
  }

  function saveQuestionWithForce(payload, force) {
    if (!activeQuiz || !activeQuiz.id) return;
    if (qFeedback) { qFeedback.hidden = true; qFeedback.innerHTML = ''; }
    payload.force = force;

    var req = editingQuestionId
      ? global.ClarityAPI.put('/questions/' + editingQuestionId, payload)
      : global.ClarityAPI.post('/quizzes/' + activeQuiz.id + '/questions', payload);

    req.then(function () {
      if (global.ClarityUI) global.ClarityUI.showToast('Soal berhasil disimpan.', 'success');
      editingQuestionId = null;
      loadQuestions();
    }).catch(function (err) {
      if (err && err.status === 409 && !force && editingQuestionId) {
        if (window.confirm((err.message || 'Quiz sudah memiliki percobaan.') + '\n\nTetap paksa simpan perubahan soal?')) {
          saveQuestionWithForce(payload, true);
          return;
        }
      }
      var msg = (err && err.message) || 'Gagal menyimpan soal.';
      if (qFeedback) {
        qFeedback.hidden = false;
        qFeedback.innerHTML = '<div class="clarity-alert clarity-alert-danger" role="alert">' + esc(msg) + '</div>';
      }
      if (global.ClarityUI) global.ClarityUI.showToast(msg, 'danger');
    });
  }

  function selectQuiz(val) {
    if (!val || val === 'new') {
      fillMetaFormForNew();
      if (activeTab === 'attempts') loadAttempts();
      return;
    }
    var found = quizzes.find(function (q) { return String(q.id) === String(val); });
    if (!found) {
      fillMetaFormForNew();
      return;
    }
    fillMetaFormForQuiz(found);
    if (activeTab === 'attempts') {
      loadAttempts();
    } else {
      loadQuestions();
    }
  }

  function loadCourseData(cid, preferredQuizId) {
    if (!cid) return;
    global.ClarityAPI.setSelectedCourseId(String(cid));
    if (statusRegion) statusRegion.hidden = true;

    Promise.all([
      global.ClarityAPI.get('/courses/' + cid + '/quizzes'),
      global.ClarityAPI.get('/courses/' + cid + '/meetings').catch(function () { return { data: [] }; })
    ]).then(function (results) {
      quizzes = Array.isArray(results[0].data) ? results[0].data : [];
      var meetings = Array.isArray(results[1].data) ? results[1].data : [];

      meetingIn.innerHTML = '<option value="">Tidak terikat pertemuan</option>' + meetings.map(function (m) {
        return '<option value="' + m.id + '">Pertemuan ' + esc(m.number) + ': ' + esc(m.title) + '</option>';
      }).join('');

      var optsHtml = '<option value="new">+ Buat Quiz Baru</option>' + quizzes.map(function (q) {
        return '<option value="' + q.id + '">' + esc(q.title) + ' (' + esc(q.total_questions || 0) + ' soal)</option>';
      }).join('');
      quizSelect.innerHTML = optsHtml;

      var targetId = preferredQuizId || (global.ClarityUI ? global.ClarityUI.getQueryParam('quiz_id') : '');
      if (targetId && quizzes.some(function (q) { return String(q.id) === String(targetId); })) {
        quizSelect.value = String(targetId);
      } else if (quizzes.length > 0 && preferredQuizId !== 'new') {
        quizSelect.value = String(quizzes[0].id);
      } else {
        quizSelect.value = 'new';
      }
      selectQuiz(quizSelect.value);
    }).catch(function (err) {
      if (statusRegion) {
        statusRegion.hidden = false;
        statusRegion.innerHTML = H.renderError(err && err.message, 'btn-retry-qb-course');
        var r = document.getElementById('btn-retry-qb-course');
        if (r) r.addEventListener('click', function () { loadCourseData(cid, preferredQuizId); });
      }
    });
  }

  metaForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var cid = courseSelect.value;
    if (!cid) return;
    if (metaFeedback) { metaFeedback.hidden = true; metaFeedback.innerHTML = ''; }

    var payload = {
      title: titleIn.value.trim(),
      meeting_id: meetingIn.value ? Number(meetingIn.value) : null,
      description: descIn.value.trim(),
      open_at: openIn.value ? openIn.value + ':00+07:00' : '',
      close_at: closeIn.value ? closeIn.value + ':00+07:00' : '',
      duration_min: Number(durIn.value),
      max_attempts: Number(attMaxIn.value),
      weight: Number(weightIn.value),
      shuffle: shuffleIn.checked,
      show_answers: showAnsIn.checked
    };

    var isEdit = Boolean(activeQuiz && activeQuiz.id);
    var req = isEdit
      ? global.ClarityAPI.put('/quizzes/' + activeQuiz.id, payload)
      : global.ClarityAPI.post('/courses/' + cid + '/quizzes', payload);

    req.then(function (res) {
      var saved = res && res.data;
      if (global.ClarityUI) global.ClarityUI.showToast(isEdit ? 'Pengaturan quiz diperbarui.' : 'Quiz baru berhasil dibuat.', 'success');
      loadCourseData(cid, saved && saved.id ? String(saved.id) : null);
    }).catch(function (err) {
      var msg = (err && err.message) || 'Gagal menyimpan quiz.';
      if (metaFeedback) {
        metaFeedback.hidden = false;
        metaFeedback.innerHTML = '<div class="clarity-alert clarity-alert-danger" role="alert">' + esc(msg) + '</div>';
      }
      if (global.ClarityUI) global.ClarityUI.showToast(msg, 'danger');
    });
  });

  qForm.addEventListener('submit', function (e) {
    e.preventDefault();
    var type = qTypeIn.value;
    var payload = {
      type: type,
      text: qTextIn.value.trim(),
      points: Number(qPointsIn.value),
      position: Number(qPosIn.value)
    };
    if (type === 'pg') {
      payload.options = optionDrafts.map(function (o) {
        return { text: o.text.trim(), is_correct: Boolean(o.is_correct) };
      });
    }
    saveQuestionWithForce(payload, false);
  });

  if (qTypeIn) qTypeIn.addEventListener('change', renderOptionRows);
  if (addOptBtn) addOptBtn.addEventListener('click', function () {
    optionDrafts.push({ text: '', is_correct: false });
    renderOptionRows();
  });
  if (qCancelEditBtn) qCancelEditBtn.addEventListener('click', function () {
    resetQuestionForm(1);
    loadQuestions();
  });

  if (newQuizBtn) newQuizBtn.addEventListener('click', function () {
    quizSelect.value = 'new';
    switchTab('builder');
    selectQuiz('new');
    titleIn.focus();
  });

  if (deleteQuizBtn) deleteQuizBtn.addEventListener('click', function () {
    if (!activeQuiz || !activeQuiz.id) return;
    if (!window.confirm('Hapus quiz "' + activeQuiz.title + '" beserta seluruh soal dan riwayat percobaan?')) return;
    global.ClarityAPI.del('/quizzes/' + activeQuiz.id)
      .then(function () {
        if (global.ClarityUI) global.ClarityUI.showToast('Quiz dihapus.', 'info');
        loadCourseData(courseSelect.value, null);
      })
      .catch(function (err) {
        if (global.ClarityUI) global.ClarityUI.showToast((err && err.message) || 'Gagal menghapus quiz.', 'danger');
      });
  });

  courseSelect.addEventListener('change', function () {
    loadCourseData(courseSelect.value, null);
  });
  quizSelect.addEventListener('change', function () {
    selectQuiz(quizSelect.value);
  });

  document.querySelectorAll('[data-qb-tab]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      switchTab(btn.getAttribute('data-qb-tab') || 'builder');
    });
  });

  document.querySelectorAll('[data-qb-att-status]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      attStatusFilter = btn.getAttribute('data-qb-att-status') || '';
      document.querySelectorAll('[data-qb-att-status]').forEach(function (b) {
        var on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      loadAttempts();
    });
  });

  function init() {
    var paramTab = global.ClarityUI ? global.ClarityUI.getQueryParam('tab') : '';
    if (paramTab === 'attempts') {
      switchTab('attempts');
    }

    global.ClarityAPI.get('/courses?per_page=50')
      .then(function (res) {
        var courses = Array.isArray(res.data) ? res.data : [];
        if (courses.length === 0) {
          if (statusRegion) {
            statusRegion.hidden = false;
            statusRegion.innerHTML = H.renderEmpty('Belum Ada Mata Kuliah', 'Buat mata kuliah di Kelola Mata Kuliah terlebih dahulu.');
          }
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
        loadCourseData(savedCid, null);
      })
      .catch(function (err) {
        if (statusRegion) {
          statusRegion.hidden = false;
          statusRegion.innerHTML = H.renderError(err && err.message, 'btn-retry-qb-init');
          var r = document.getElementById('btn-retry-qb-init');
          if (r) r.addEventListener('click', init);
        }
      });
  }

  init();
})(window);
