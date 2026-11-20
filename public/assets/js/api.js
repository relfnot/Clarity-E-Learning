/* ==========================================================================
   Clarity E-Learning — API Fetch Wrapper & Handler 401 Global
   ========================================================================== */

(function (global) {
  'use strict';

  var TOKEN_KEY = 'clarity_token';
  var EXPIRES_KEY = 'clarity_expires_at';
  var USER_KEY = 'clarity_user';

  function getPublicRoot() {
    var pathname = window.location.pathname || '/';
    var dir = pathname.replace(/\/[^/]*$/, '');
    var baseAttr = document.body ? document.body.getAttribute('data-base') : null;

    if (baseAttr === '..') {
      dir = dir.replace(/\/[^/]+$/, '');
    }

    return dir === '/' ? '' : dir;
  }

  function getApiBaseUrl() {
    return getPublicRoot() + '/api/v1';
  }

  function getCurrentDir() {
    var pathname = window.location.pathname || '/';
    var dir = pathname.replace(/\/[^/]*$/, '');
    return dir === '/' ? '' : dir;
  }

  function pageUrl(filename) {
    return getCurrentDir() + '/' + filename;
  }

  function assetUrl(relativeAssetPath) {
    return getPublicRoot() + '/' + relativeAssetPath.replace(/^\/+/, '');
  }

  function clearStoredSession() {
    try {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(EXPIRES_KEY);
      localStorage.removeItem(USER_KEY);
    } catch (e) {}
  }

  function getStoredToken() {
    try {
      return localStorage.getItem(TOKEN_KEY);
    } catch (e) {
      return null;
    }
  }

  function redirectToLogin(reason) {
    var loginPath = pageUrl('login.php');
    var target = reason ? loginPath + '?reason=' + encodeURIComponent(reason) : loginPath;
    if (/\/login\.(php|html)$/i.test(window.location.pathname)) {
      return;
    }
    window.location.replace(target);
  }

  function buildError(status, payload) {
    var err = (payload && payload.error) || {};
    return {
      status: status,
      code: err.code || 'SERVER_ERROR',
      message: err.message || 'Terjadi kesalahan saat menghubungi server.',
      details: err.details || {}
    };
  }

  function request(path, options) {
    var opts = options || {};
    var normalizedPath = path.charAt(0) === '/' ? path : '/' + path;
    var url = getApiBaseUrl() + normalizedPath;

    var headers = Object.assign({
      'Accept': 'application/json'
    }, opts.headers || {});

    var fetchOpts = {
      method: (opts.method || 'GET').toUpperCase(),
      headers: headers
    };

    if (opts.body !== undefined) {
      if (opts.body instanceof FormData) {
        fetchOpts.body = opts.body;
      } else {
        headers['Content-Type'] = 'application/json';
        fetchOpts.body = JSON.stringify(opts.body);
      }
    }

    var token = getStoredToken();
    if (token && !headers['Authorization']) {
      headers['Authorization'] = 'Bearer ' + token;
    }

    return fetch(url, fetchOpts).then(function (response) {
      if (response.status === 204) {
        return { data: null, meta: {} };
      }

      return response.text().then(function (text) {
        var parsed = null;
        if (text) {
          try {
            parsed = JSON.parse(text);
          } catch (e) {
            parsed = null;
          }
        }

        if (!response.ok) {
          if (
            response.status === 401 &&
            !opts.skipAuthRedirect &&
            normalizedPath !== '/auth/login'
          ) {
            clearStoredSession();
            redirectToLogin('expired');
          }
          throw buildError(response.status, parsed);
        }

        return parsed || { data: null, meta: {} };
      });
    });
  }

  global.ClarityAPI = {
    request: request,
    get: function (path, opts) {
      return request(path, Object.assign({}, opts, { method: 'GET' }));
    },
    post: function (path, body, opts) {
      return request(path, Object.assign({}, opts, { method: 'POST', body: body }));
    },
    put: function (path, body, opts) {
      return request(path, Object.assign({}, opts, { method: 'PUT', body: body }));
    },
    patch: function (path, body, opts) {
      return request(path, Object.assign({}, opts, { method: 'PATCH', body: body }));
    },
    del: function (path, opts) {
      return request(path, Object.assign({}, opts, { method: 'DELETE' }));
    },
    getApiBaseUrl: getApiBaseUrl,
    getPublicRoot: getPublicRoot,
    pageUrl: pageUrl,
    assetUrl: assetUrl
  };
})(window);
