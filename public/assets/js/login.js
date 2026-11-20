/* ==========================================================================
   Clarity E-Learning — Controller Halaman Login
   ========================================================================== */

(function (global) {
  'use strict';

  var EYE_OPEN = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
  var EYE_CLOSED = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
  var SPINNER_SVG = '<svg class="btn-spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><circle cx="12" cy="12" r="9" opacity="0.25"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>';

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('login-form');
    if (!form) return;

    var identifierInput = document.getElementById('login-identifier');
    var passwordInput = document.getElementById('login-password');
    var identifierErr = document.getElementById('identifier-error');
    var passwordErr = document.getElementById('password-error');
    var togglePwdBtn = document.getElementById('toggle-password-btn');
    var submitBtn = document.getElementById('login-submit-btn');
    var errAlert = document.getElementById('login-error-alert');
    var errText = document.getElementById('login-error-text');
    var infoAlert = document.getElementById('login-info-alert');
    var infoText = document.getElementById('login-info-text');
    var sessionBanner = document.getElementById('active-session-banner');
    var sessionUserEl = document.getElementById('active-session-user');
    var sessionDashLink = document.getElementById('active-session-dash-link');
    var demoPills = document.querySelectorAll('.demo-account-pill');

    var isSubmitting = false;

    function getParam(key) {
      return global.ClarityUI ? global.ClarityUI.getQueryParam(key) : null;
    }

    function showBannerForReason() {
      var reason = getParam('reason');
      if (!reason || !infoAlert || !infoText) return;

      infoAlert.className = 'clarity-alert text-sm';
      if (reason === 'expired') {
        infoAlert.classList.add('clarity-alert-warning');
        infoText.textContent = 'Sesi Anda telah kedaluwarsa. Silakan masuk kembali untuk melanjutkan.';
        infoAlert.hidden = false;
      } else if (reason === 'unauthenticated') {
        infoAlert.classList.add('clarity-alert-info');
        infoText.textContent = 'Silakan masuk dengan akun kampus Anda untuk mengakses halaman tersebut.';
        infoAlert.hidden = false;
      } else if (reason === 'logout') {
        infoAlert.classList.add('clarity-alert-success');
        infoText.textContent = 'Anda berhasil keluar dari sesi perkuliahan.';
        infoAlert.hidden = false;
      }
    }

    function showExistingSessionNotice() {
      if (!global.ClarityAuth || !sessionBanner) return;
      var token = global.ClarityAuth.getToken();
      var user = global.ClarityAuth.getUser();
      if (token && user && !global.ClarityAuth.isTokenExpired()) {
        sessionUserEl.textContent = user.name + ' (' + (user.role === 'dosen' ? 'Dosen' : 'Mahasiswa') + ')';
        sessionDashLink.href = global.ClarityAPI.pageUrl(
          global.ClarityAuth.dashboardFilenameForRole(user.role)
        );
        sessionBanner.hidden = false;
      }
    }

    function setFieldError(input, errEl, message) {
      if (!input || !errEl) return;
      if (message) {
        input.classList.add('is-invalid');
        input.setAttribute('aria-invalid', 'true');
        errEl.textContent = message;
        errEl.hidden = false;
      } else {
        input.classList.remove('is-invalid');
        input.removeAttribute('aria-invalid');
        errEl.textContent = '';
        errEl.hidden = true;
      }
    }

    function validateIdentifier() {
      var val = identifierInput.value.trim();
      if (!val) {
        setFieldError(identifierInput, identifierErr, 'NIM, NIP, atau email kampus wajib diisi.');
        return false;
      }
      setFieldError(identifierInput, identifierErr, '');
      return true;
    }

    function validatePassword() {
      var val = passwordInput.value;
      if (!val) {
        setFieldError(passwordInput, passwordErr, 'Kata sandi wajib diisi.');
        return false;
      }
      setFieldError(passwordInput, passwordErr, '');
      return true;
    }

    function showGlobalError(message) {
      if (!errAlert || !errText) return;
      errText.textContent = message;
      errAlert.hidden = false;
    }

    function hideGlobalError() {
      if (errAlert) errAlert.hidden = true;
    }

    function setLoadingState(loading) {
      isSubmitting = loading;
      submitBtn.disabled = loading;
      submitBtn.setAttribute('aria-busy', loading ? 'true' : 'false');
      if (loading) {
        submitBtn.innerHTML = SPINNER_SVG + '<span>Memverifikasi...</span>';
      } else {
        submitBtn.innerHTML = '<span>Masuk</span>';
      }
    }

    if (togglePwdBtn && passwordInput) {
      togglePwdBtn.addEventListener('click', function () {
        var isHidden = passwordInput.getAttribute('type') === 'password';
        passwordInput.setAttribute('type', isHidden ? 'text' : 'password');
        togglePwdBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        togglePwdBtn.setAttribute('aria-label', isHidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        togglePwdBtn.innerHTML = isHidden ? EYE_CLOSED : EYE_OPEN;
      });
    }

    demoPills.forEach(function (pill) {
      pill.addEventListener('click', function () {
        var demoId = pill.getAttribute('data-demo-id') || '';
        var demoPass = pill.getAttribute('data-demo-pass') || 'rahasia123';
        identifierInput.value = demoId;
        passwordInput.value = demoPass;
        demoPills.forEach(function (p) { p.classList.remove('is-selected'); });
        pill.classList.add('is-selected');
        [identifierInput, passwordInput].forEach(function (field) {
          field.classList.remove('is-autofilled');
          void field.offsetWidth;
          field.classList.add('is-autofilled');
        });
        setFieldError(identifierInput, identifierErr, '');
        setFieldError(passwordInput, passwordErr, '');
        hideGlobalError();
        identifierInput.focus();
      });
    });

    identifierInput.addEventListener('blur', validateIdentifier);
    passwordInput.addEventListener('blur', validatePassword);
    identifierInput.addEventListener('input', function () {
      if (identifierInput.classList.contains('is-invalid')) validateIdentifier();
    });
    passwordInput.addEventListener('input', function () {
      if (passwordInput.classList.contains('is-invalid')) validatePassword();
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (isSubmitting) return;

      hideGlobalError();
      var okId = validateIdentifier();
      var okPwd = validatePassword();

      if (!okId) {
        identifierInput.focus();
        return;
      }
      if (!okPwd) {
        passwordInput.focus();
        return;
      }

      setLoadingState(true);

      global.ClarityAuth.login(identifierInput.value.trim(), passwordInput.value)
        .then(function (data) {
          global.ClarityAuth.redirectToDashboard(data.user.role);
        })
        .catch(function (err) {
          setLoadingState(false);

          if (err && err.status === 429) {
            var retry = (err.details && err.details.retry_after_seconds) || 900;
            var minutes = Math.max(1, Math.ceil(retry / 60));
            showGlobalError((err.message || 'Terlalu banyak percobaan login gagal.') + ' Coba lagi dalam sekitar ' + minutes + ' menit.');
            return;
          }

          if (err && err.status === 422 && err.details) {
            if (err.details.identifier && err.details.identifier.length) {
              setFieldError(identifierInput, identifierErr, err.details.identifier[0]);
            }
            if (err.details.password && err.details.password.length) {
              setFieldError(passwordInput, passwordErr, err.details.password[0]);
            }
            showGlobalError(err.message || 'Periksa kembali isian formulir login.');
            return;
          }

          if (err && err.status === 401) {
            showGlobalError('Kredensial salah. Periksa kembali NIM/NIP, email kampus, dan kata sandi Anda.');
            passwordInput.focus();
            passwordInput.select();
            return;
          }

          showGlobalError((err && err.message) || 'Gagal terhubung ke server autentikasi.');
        });
    });

    showBannerForReason();
    showExistingSessionNotice();
  });
})(window);
