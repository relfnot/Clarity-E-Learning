/* ==========================================================================
   Clarity E-Learning — Course Detail Page Logic (Timeline No. 9 · PIC: Matthew & Rizky)
   Dynamic fetch /courses/{id} and /courses/{id}/participants with tab switching
   ========================================================================== */

(function (global) {
  'use strict';

  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getCourseId() {
    try {
      var params = new URLSearchParams(window.location.search);
      var id = parseInt(params.get('id'), 10);
      return !isNaN(id) && id > 0 ? id : 1;
    } catch (e) {
      return 1;
    }
  }

  function initTabs() {
    var tabTriggers = document.querySelectorAll('[data-tab-target]');
    var tabPanels = document.querySelectorAll('.tab-content-panel');

    tabTriggers.forEach(function (trigger) {
      trigger.addEventListener('click', function () {
        var targetId = trigger.getAttribute('data-tab-target');

        tabTriggers.forEach(function (btn) {
          btn.classList.remove('is-active');
          btn.setAttribute('aria-selected', 'false');
        });
        tabPanels.forEach(function (panel) {
          panel.classList.add('hidden');
        });

        trigger.classList.add('is-active');
        trigger.setAttribute('aria-selected', 'true');

        var targetPanel = document.getElementById(targetId);
        if (targetPanel) {
          targetPanel.classList.remove('hidden');
        }
      });
    });
  }

  function loadCourseDetail() {
    var courseId = getCourseId();
    if (!global.ClarityAPI) return;

    // 1. Fetch Detail Mata Kuliah
    global.ClarityAPI.get('/courses/' + courseId)
      .then(function (res) {
        var c = res && res.data;
        if (!c) throw new Error('Data mata kuliah kosong');
        renderCourseHeader(c);
      })
      .catch(function (err) {
        var titleEl = document.getElementById('course-name');
        if (titleEl) {
          titleEl.textContent = 'Mata Kuliah Tidak Ditemukan';
        }
      });

    // 2. Fetch Peserta Kelas
    global.ClarityAPI.get('/courses/' + courseId + '/participants')
      .then(function (res) {
        var participants = (res && res.data) || [];
        renderParticipants(participants);
      })
      .catch(function () {
        var container = document.getElementById('participants-list');
        if (container) {
          container.innerHTML = '<p class="text-sm text-secondary p-6">Gagal memuat daftar peserta kelas.</p>';
        }
      });
  }

  function renderCourseHeader(c) {
    var titleEl = document.getElementById('course-name');
    var codeEl = document.getElementById('course-code');
    var creditsEl = document.getElementById('course-credits');
    var lecturerEl = document.getElementById('course-lecturer');
    var scheduleEl = document.getElementById('course-schedule');
    var descEl = document.getElementById('course-description');
    var breadcrumbEl = document.getElementById('breadcrumb-course-name');
    var progressEl = document.getElementById('course-progress-text');
    var progressBarEl = document.getElementById('course-progress-bar');

    var scheduleText = (c.day || '-') + (c.start_time ? ', ' + c.start_time + '–' + (c.end_time || '') + ' WIB' : '') + ' (' + (c.room || '-') + ')';

    if (titleEl) titleEl.textContent = c.name;
    if (codeEl) codeEl.textContent = c.code;
    if (creditsEl) creditsEl.textContent = c.credits + ' SKS';
    if (lecturerEl) lecturerEl.textContent = c.lecturer_name + (c.lecturer_email ? ' (' + c.lecturer_email + ')' : '');
    if (scheduleEl) scheduleEl.textContent = scheduleText;
    if (descEl) descEl.textContent = c.description || 'Mata kuliah ini membekali mahasiswa dengan kompetensi praktis dan teoritis sesuai kurikulum akademik program studi.';
    if (breadcrumbEl) breadcrumbEl.textContent = c.code + ' ' + c.name;

    var progress = c.progress_percent || 75;
    if (progressEl) progressEl.textContent = progress + '%';
    if (progressBarEl) progressBarEl.style.width = progress + '%';
  }

  function renderParticipants(participants) {
    var countBadge = document.getElementById('participants-tab-count');
    var container = document.getElementById('participants-list');

    if (countBadge) {
      countBadge.textContent = '(' + participants.length + ')';
    }

    if (!container) return;

    if (participants.length === 0) {
      container.innerHTML = '<div class="p-8 text-center text-sm text-secondary">Belum ada mahasiswa terdaftar di kelas ini.</div>';
      return;
    }

    var rows = participants.map(function (p, index) {
      return '<tr class="border-b border-[var(--color-border-subtle)] hover:bg-[var(--color-surface-2)] transition-colors">' +
        '<td class="py-3 px-4 text-xs text-muted">' + (index + 1) + '</td>' +
        '<td class="py-3 px-4 text-sm font-bold font-mono text-ink">' + escapeHtml(p.nim) + '</td>' +
        '<td class="py-3 px-4 text-sm font-semibold text-ink">' + escapeHtml(p.name) + '</td>' +
        '<td class="py-3 px-4 text-xs text-secondary">' + escapeHtml(p.email) + '</td>' +
        '<td class="py-3 px-4 text-xs text-secondary">' + escapeHtml(p.program_studi) + '</td>' +
        '<td class="py-3 px-4 text-right"><span class="clarity-tag clarity-tag-success">Aktif</span></td>' +
      '</tr>';
    }).join('');

    container.innerHTML = '<div class="overflow-x-auto">' +
      '<table class="w-full text-left border-collapse">' +
        '<thead>' +
          '<tr class="border-b border-[var(--color-border)] text-xs font-bold text-muted uppercase tracking-wider bg-[var(--color-surface-2)]">' +
            '<th class="py-3 px-4 w-12">#</th>' +
            '<th class="py-3 px-4">NIM</th>' +
            '<th class="py-3 px-4">Nama Lengkap</th>' +
            '<th class="py-3 px-4">Email Kampus</th>' +
            '<th class="py-3 px-4">Program Studi</th>' +
            '<th class="py-3 px-4 text-right">Status</th>' +
          '</tr>' +
        '</thead>' +
        '<tbody>' + rows + '</tbody>' +
      '</table>' +
    '</div>';
  }

  function init() {
    initTabs();
    loadCourseDetail();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window);
