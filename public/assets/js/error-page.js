/* ==========================================================================
   Clarity E-Learning — Controller Halaman 403 & 404 (Role-Aware)
   ========================================================================== */

(function (global) {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var backBtn = document.getElementById('error-back-dashboard');
    var switchBtn = document.getElementById('error-switch-account');
    var userContextEl = document.getElementById('error-user-context');
    var pageType = document.body.getAttribute('data-error-page') || '404';

    var user = global.ClarityAuth ? global.ClarityAuth.getUser() : null;
    var hasValidSession = global.ClarityAuth && global.ClarityAuth.getToken() && !global.ClarityAuth.isTokenExpired() && user;

    if (backBtn) {
      if (hasValidSession) {
        var target = global.ClarityAuth.dashboardFilenameForRole(user.role);
        backBtn.href = global.ClarityAPI.pageUrl(target);
        backBtn.textContent = user.role === 'dosen' ? 'Kembali ke Dashboard Dosen' : 'Kembali ke Dashboard Mahasiswa';
      } else {
        backBtn.href = global.ClarityAPI.pageUrl('login.php');
        backBtn.textContent = 'Masuk ke Akun Kampus';
      }
    }

    if (userContextEl && hasValidSession) {
      var roleLabel = user.role === 'dosen' ? 'Dosen' : 'Mahasiswa';
      userContextEl.textContent = 'Akun aktif saat ini: ' + user.name + ' (' + user.nim_nip + ' · Peran: ' + roleLabel + ')';
      userContextEl.hidden = false;
    }

    if (switchBtn) {
      if (hasValidSession) {
        switchBtn.hidden = false;
        switchBtn.addEventListener('click', function () {
          global.ClarityAuth.logout('logout');
        });
      } else {
        switchBtn.hidden = true;
      }
    }

    if (pageType === '404') {
      var type = (global.ClarityUI && global.ClarityUI.getQueryParam('type')) || '404';
      var codeEl = document.getElementById('err-code');
      var titleEl = document.getElementById('err-title');
      var descEl = document.getElementById('err-desc');
      var configs = {
        '404': {
          code: '404',
          title: 'Halaman Tidak Ditemukan',
          desc: 'Tautan yang Anda buka tidak tersedia atau halaman modul belum dirilis.'
        },
        '403': {
          code: '403',
          title: 'Akses Ditolak (Peran Tidak Sesuai)',
          desc: 'Anda tidak memiliki izin peran untuk membuka halaman ini.'
        },
        'offline': {
          code: 'Offline',
          title: 'Anda Sedang Offline',
          desc: 'Koneksi internet terputus. Periksa jaringan Anda lalu muat ulang halaman.'
        }
      };
      var cfg = configs[type] || configs['404'];
      if (codeEl) codeEl.textContent = cfg.code;
      if (titleEl) titleEl.textContent = cfg.title;
      if (descEl) descEl.textContent = cfg.desc;
    }
  });
})(window);
