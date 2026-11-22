/* ==========================================================================
   Clarity E-Learning — Autentikasi JWT, Penyimpanan Sesi & Role Guard
   ========================================================================== */

(function (global) {
  'use strict';

  var TOKEN_KEY = 'clarity_token';
  var EXPIRES_KEY = 'clarity_expires_at';
  var USER_KEY = 'clarity_user';
  var expiryTimer = null;

  function getToken() {
    try {
      return localStorage.getItem(TOKEN_KEY);
    } catch (e) {
      return null;
    }
  }

  function getExpiresAt() {
    try {
      return localStorage.getItem(EXPIRES_KEY);
    } catch (e) {
      return null;
    }
  }

  function getUser() {
    try {
      var raw = localStorage.getItem(USER_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function saveSession(sessionData) {
    try {
      if (sessionData.token) {
        localStorage.setItem(TOKEN_KEY, sessionData.token);
      }
      if (sessionData.expires_at) {
        localStorage.setItem(EXPIRES_KEY, sessionData.expires_at);
      }
      if (sessionData.user) {
        localStorage.setItem(USER_KEY, JSON.stringify(sessionData.user));
      }
    } catch (e) {}
  }

  function clearSession() {
    try {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(EXPIRES_KEY);
      localStorage.removeItem(USER_KEY);
    } catch (e) {}
  }

  function decodeJwtPayload(token) {
    if (!token || typeof token !== 'string') return null;
    var parts = token.split('.');
    if (parts.length !== 3) return null;
    try {
      var base64 = parts[1].replace(/-/g, '+').replace(/_/g, '/');
      while (base64.length % 4) {
        base64 += '=';
      }
      var json = decodeURIComponent(
        atob(base64)
          .split('')
          .map(function (c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
          })
          .join('')
      );
      return JSON.parse(json);
    } catch (e) {
      return null;
    }
  }

  function isTokenExpired() {
    var token = getToken();
    if (!token) return true;

    var claims = decodeJwtPayload(token);
    if (!claims) return true;

    if (claims.exp && Date.now() >= claims.exp * 1000) {
      return true;
    }

    var expiresAt = getExpiresAt();
    if (expiresAt) {
      var parsed = Date.parse(expiresAt);
      if (!isNaN(parsed) && Date.now() >= parsed) {
        return true;
      }
    }

    return false;
  }

  function dashboardFilenameForRole(role) {
    return role === 'dosen' ? 'dashboard-dosen.php' : 'dashboard.php';
  }

  function redirectToDashboard(role) {
    window.location.replace(global.ClarityAPI.pageUrl(dashboardFilenameForRole(role)));
  }

  function login(identifier, password) {
    return global.ClarityAPI.post(
      '/auth/login',
      { identifier: identifier, password: password },
      { skipAuthRedirect: true }
    ).then(function (res) {
      var payload = res && res.data ? res.data : null;
      if (!payload || !payload.token || !payload.user) {
        throw {
          status: 500,
          code: 'SERVER_ERROR',
          message: 'Respons autentikasi dari server tidak lengkap.',
          details: {}
        };
      }
      saveSession(payload);
      return payload;
    });
  }

  function logout(reason) {
    var token = getToken();
    var targetReason = reason || 'logout';
    var loginHref = global.ClarityAPI.pageUrl('login.php') + '?reason=' + encodeURIComponent(targetReason);

    if (!token) {
      clearSession();
      window.location.replace(loginHref);
      return Promise.resolve();
    }

    return global.ClarityAPI.post('/auth/logout', {}, { skipAuthRedirect: true })
      .catch(function () {
        return null;
      })
      .then(function () {
        clearSession();
        window.location.replace(loginHref);
      });
  }

  function startExpiryWatcher() {
    if (expiryTimer !== null) return;
    expiryTimer = setInterval(function () {
      if (getToken() && isTokenExpired()) {
        clearSession();
        window.location.replace(global.ClarityAPI.pageUrl('login.php') + '?reason=expired');
      }
    }, 30000);
  }

  function guardPage() {
    var body = document.body;
    if (!body) return true;

    var requiredRole = body.getAttribute('data-role');
    if (!requiredRole || requiredRole === 'public') {
      return true;
    }

    var token = getToken();
    if (!token) {
      clearSession();
      window.location.replace(global.ClarityAPI.pageUrl('login.php') + '?reason=unauthenticated');
      return false;
    }

    if (isTokenExpired()) {
      clearSession();
      window.location.replace(global.ClarityAPI.pageUrl('login.php') + '?reason=expired');
      return false;
    }

    var claims = decodeJwtPayload(token);
    var cachedUser = getUser();
    var activeRole = (cachedUser && cachedUser.role) || (claims && claims.role) || null;

    function isRoleAllowed(role) {
      if (!requiredRole || requiredRole === 'auth' || requiredRole === 'all') return true;
      var allowed = requiredRole.split(',').map(function (r) { return r.trim(); });
      return allowed.indexOf(role) !== -1;
    }

    if (activeRole && !isRoleAllowed(activeRole)) {
      window.location.replace(global.ClarityAPI.pageUrl('403.php'));
      return false;
    }

    startExpiryWatcher();

    global.ClarityAPI.get('/auth/me')
      .then(function (res) {
        var freshUser = res && res.data ? res.data : null;
        if (!freshUser) return;

        try {
          localStorage.setItem(USER_KEY, JSON.stringify(freshUser));
        } catch (e) {}

        if (!isRoleAllowed(freshUser.role)) {
          window.location.replace(global.ClarityAPI.pageUrl('403.php'));
          return;
        }

        window.dispatchEvent(new CustomEvent('clarity:user-loaded', { detail: freshUser }));
      })
      .catch(function () {
        // 401 ditangani terpusat di ClarityAPI
      });

    return true;
  }

  global.ClarityAuth = {
    getToken: getToken,
    getExpiresAt: getExpiresAt,
    getUser: getUser,
    saveSession: saveSession,
    clearSession: clearSession,
    decodeJwtPayload: decodeJwtPayload,
    isTokenExpired: isTokenExpired,
    dashboardFilenameForRole: dashboardFilenameForRole,
    redirectToDashboard: redirectToDashboard,
    login: login,
    logout: logout,
    guardPage: guardPage
  };
})(window);
