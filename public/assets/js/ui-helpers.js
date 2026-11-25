/* ==========================================================================
   Clarity E-Learning — Shared UI Helpers (Format Waktu, State, Badge)
   ========================================================================== */

(function (global) {
  'use strict';

  function esc(val) {
    return global.ClarityUI ? global.ClarityUI.escapeHtml(val) : String(val || '');
  }

  function formatDateTime(iso) {
    if (!iso) return '-';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return String(iso);
    return d.toLocaleString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      timeZone: 'Asia/Jakarta'
    }) + ' WIB';
  }

  function formatDateOnly(dateStr) {
    if (!dateStr) return '-';
    var d = new Date(dateStr + (dateStr.length === 10 ? 'T00:00:00+07:00' : ''));
    if (isNaN(d.getTime())) return String(dateStr);
    return d.toLocaleDateString('id-ID', {
      weekday: 'short',
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      timeZone: 'Asia/Jakarta'
    });
  }

  function renderLoading(label) {
    var msg = label || 'Memuat data akademik...';
    return '<div class="clarity-card p-6 space-y-4" aria-busy="true" aria-label="' + esc(msg) + '">' +
      '<div class="text-sm font-medium text-muted">' + esc(msg) + '</div>' +
      '<div class="skeleton h-5 w-48"></div>' +
      '<div class="skeleton h-20 w-full"></div>' +
      '<div class="skeleton h-20 w-full"></div>' +
      '</div>';
  }

  function renderEmpty(title, reason, actionHtml) {
    return '<div class="clarity-card p-8 text-center flex flex-col items-center gap-3" role="status">' +
      '<h3 class="text-base font-bold text-ink m-0">' + esc(title) + '</h3>' +
      '<p class="text-sm text-muted max-w-md m-0">' + esc(reason) + '</p>' +
      (actionHtml ? '<div class="mt-2">' + actionHtml + '</div>' : '') +
      '</div>';
  }

  function renderError(message, retryId) {
    var btn = retryId
      ? '<button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm mt-3" id="' + esc(retryId) + '">Coba Lagi</button>'
      : '';
    return '<div class="clarity-card p-6">' +
      '<div class="clarity-alert clarity-alert-danger" role="alert">' +
      '<span><strong>Terjadi Kesalahan:</strong> ' + esc(message || 'Gagal memuat data dari server.') + '</span>' +
      '</div>' + btn +
      '</div>';
  }

  function submissionStatusBadge(status) {
    switch (status) {
      case 'tepat_waktu':
        return '<span class="clarity-tag clarity-tag-success">Tepat Waktu</span>';
      case 'terlambat':
        return '<span class="clarity-tag clarity-tag-warning">Terlambat</span>';
      case 'dinilai':
        return '<span class="clarity-tag clarity-tag-info">Dinilai</span>';
      default:
        return '<span class="clarity-tag clarity-tag-danger">Belum</span>';
    }
  }

  function attendanceStatusBadge(status) {
    switch (status) {
      case 'hadir':
        return '<span class="clarity-tag clarity-tag-success">Hadir</span>';
      case 'izin':
        return '<span class="clarity-tag clarity-tag-info">Izin</span>';
      case 'sakit':
        return '<span class="clarity-tag clarity-tag-warning">Sakit</span>';
      case 'alpa':
        return '<span class="clarity-tag clarity-tag-danger">Alpa</span>';
      default:
        return '<span class="clarity-tag clarity-tag-neutral">Belum</span>';
    }
  }

  function urgencyBadge(urgency) {
    switch (urgency) {
      case 'j3':
        return '<span class="clarity-tag clarity-tag-danger">Kurang 3 Jam</span>';
      case 'h1':
        return '<span class="clarity-tag clarity-tag-danger">H-1</span>';
      case 'h3':
        return '<span class="clarity-tag clarity-tag-warning">H-3</span>';
      default:
        return '<span class="clarity-tag clarity-tag-neutral">Terjadwal</span>';
    }
  }

  global.ClarityHelpers = {
    esc: esc,
    formatDateTime: formatDateTime,
    formatDateOnly: formatDateOnly,
    renderLoading: renderLoading,
    renderEmpty: renderEmpty,
    renderError: renderError,
    submissionStatusBadge: submissionStatusBadge,
    attendanceStatusBadge: attendanceStatusBadge,
    urgencyBadge: urgencyBadge
  };
})(window);
