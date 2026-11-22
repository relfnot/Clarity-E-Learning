/* ==========================================================================
   Clarity E-Learning — Layout Shell Fixed Sidebar + Sticky Topbar
   Tailwind CSS + Basecoat UI + DESIGN.md
   ========================================================================== */

(function (global) {
  'use strict';

  var ICONS = {
    home: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    book: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    file: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
    quiz: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    check: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
    calendar: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    chart: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    chat: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    award: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>',
    bell: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
    upload: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
    megaphone: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>',
    menu: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
    close: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    sun: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>',
    logout: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>'
  };

  function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getQueryParam(key) {
    try {
      var params = new URLSearchParams(window.location.search);
      return params.get(key);
    } catch (e) {
      return null;
    }
  }

  function assetHref(path) {
    if (global.ClarityAPI && global.ClarityAPI.assetUrl) {
      return global.ClarityAPI.assetUrl(path);
    }
    var base = document.body ? (document.body.getAttribute('data-base') || '.') : '.';
    return (base === '..' ? '../' : '') + path;
  }

  function pageHref(filename) {
    if (global.ClarityAPI && global.ClarityAPI.pageUrl) {
      return global.ClarityAPI.pageUrl(filename);
    }
    return filename;
  }

  function buildMenus(role) {
    if (role === 'dosen') {
      return {
        primary: [
          { id: 'dashboard', label: 'Dashboard Dosen', href: pageHref('dashboard-dosen.php'), icon: 'home' },
          { id: 'course-manage', label: 'Kelola Mata Kuliah', href: pageHref('course-manage.php'), icon: 'book' },
          { id: 'material-upload', label: 'Upload Materi', href: pageHref('material-upload.php'), icon: 'upload' },
          { id: 'quiz-builder', label: 'Buat Quiz & Soal', href: pageHref('quiz-builder.php'), icon: 'quiz' },
          { id: 'submissions', label: 'Daftar Pengumpulan', href: pageHref('submissions.php'), icon: 'file', badge: '4' },
          { id: 'grading', label: 'Penilaian Tugas', href: pageHref('grading.php'), icon: 'chart' },
          { id: 'attendance-manage', label: 'Kelola Presensi', href: pageHref('attendance-manage.php'), icon: 'check' },
          { id: 'announcement', label: 'Kirim Pengumuman', href: pageHref('announcement.php'), icon: 'megaphone' }
        ],
        bottom: [
          { id: 'dashboard', label: 'Beranda', href: pageHref('dashboard-dosen.php'), icon: 'home' },
          { id: 'course-manage', label: 'Matkul', href: pageHref('course-manage.php'), icon: 'book' },
          { id: 'submissions', label: 'Tugas', href: pageHref('submissions.php'), icon: 'file' },
          { id: 'attendance-manage', label: 'Presensi', href: pageHref('attendance-manage.php'), icon: 'check' }
        ]
      };
    }

    return {
      primary: [
        { id: 'dashboard', label: 'Dashboard', href: pageHref('dashboard.php'), icon: 'home' },
        { id: 'courses', label: 'Mata Kuliah', href: pageHref('courses.php'), icon: 'book' },
        { id: 'assignment', label: 'Tugas', href: pageHref('assignment.php'), icon: 'file', badge: '2' },
        { id: 'quiz', label: 'Quiz', href: pageHref('quiz-intro.php'), icon: 'quiz' },
        { id: 'attendance', label: 'Presensi', href: pageHref('attendance.php'), icon: 'check' },
        { id: 'calendar', label: 'Kalender & Deadline', href: pageHref('calendar.php'), icon: 'calendar' },
        { id: 'grades', label: 'Nilai', href: pageHref('grades.php'), icon: 'chart' },
        { id: 'chat', label: 'Diskusi & Kontak', href: pageHref('chat.php'), icon: 'chat' },
        { id: 'profile', label: 'Profil & Gamifikasi', href: pageHref('profile.php'), icon: 'award' },
        { id: 'notifications', label: 'Notifikasi', href: pageHref('notifications.php'), icon: 'bell', badge: '3' }
      ],
      bottom: [
        { id: 'dashboard', label: 'Beranda', href: pageHref('dashboard.php'), icon: 'home' },
        { id: 'courses', label: 'Matkul', href: pageHref('courses.php'), icon: 'book' },
        { id: 'assignment', label: 'Tugas', href: pageHref('assignment.php'), icon: 'file' },
        { id: 'profile', label: 'Profil', href: pageHref('profile.php'), icon: 'award' }
      ]
    };
  }

  function renderNavItems(items, activePage) {
    return items.map(function (item) {
      var isActive = item.id === activePage;
      var activeClass = isActive ? ' is-active' : '';
      var ariaCurrent = isActive ? ' aria-current="page"' : '';
      var badgeHtml = item.badge
        ? '<span class="nav-count-badge">' + escapeHtml(item.badge) + '</span>'
        : '';
      return '<li><a class="clarity-sidebar-link' + activeClass + '" href="' + escapeHtml(item.href) + '"' + ariaCurrent + '>' +
        '<span class="flex items-center gap-3 min-w-0">' + (ICONS[item.icon] || '') + '<span class="truncate">' + escapeHtml(item.label) + '</span></span>' +
        badgeHtml + '</a></li>';
    }).join('');
  }

  function getStateHtml(state, pageTitle, role) {
    var backHref = role === 'dosen' ? pageHref('dashboard-dosen.php') : pageHref('dashboard.php');
    if (state === 'loading') {
      return '<div class="space-y-5" aria-busy="true" aria-label="Memuat konten halaman">' +
        '<div class="skeleton h-6 w-56"></div>' +
        '<div class="grid grid-cols-1 md:grid-cols-3 gap-5">' +
        '  <div class="clarity-card p-6 space-y-3"><div class="skeleton h-4 w-3/4"></div><div class="skeleton h-4 w-1/2"></div><div class="skeleton h-28 w-full"></div></div>' +
        '  <div class="clarity-card p-6 space-y-3"><div class="skeleton h-4 w-2/3"></div><div class="skeleton h-4 w-1/2"></div><div class="skeleton h-28 w-full"></div></div>' +
        '  <div class="clarity-card p-6 space-y-3"><div class="skeleton h-4 w-4/5"></div><div class="skeleton h-4 w-2/3"></div><div class="skeleton h-28 w-full"></div></div>' +
        '</div></div>';
    }

    if (state === 'empty') {
      return '<div class="clarity-card p-10 text-center flex flex-col items-center gap-3" role="status">' +
        '<img class="w-32 h-auto object-contain" src="' + escapeHtml(assetHref('assets/img/Target-Swift Owl Mascot.png')) + '" alt="Maskot Burung Hantu Clarity" width="128" height="128">' +
        '<h2 class="text-lg font-bold m-0">Belum Ada Data pada ' + escapeHtml(pageTitle) + '</h2>' +
        '<p class="text-sm text-muted max-w-md m-0">Saat ini belum ada data yang tersedia atau sesuai dengan filter yang Anda pilih.</p>' +
        '<a class="clarity-btn clarity-btn-primary mt-2" href="' + escapeHtml(backHref) + '">Kembali ke Dashboard</a>' +
        '</div>';
    }

    if (state === 'error') {
      return '<div class="clarity-card p-6 space-y-4">' +
        '<div class="clarity-alert clarity-alert-danger" role="alert">' +
        '  <span><strong>Gagal Memuat Data:</strong> Terjadi gangguan saat menghubungi layanan akademik. Silakan muat ulang halaman.</span>' +
        '</div>' +
        '<a class="clarity-btn clarity-btn-primary" href="?">Muat Ulang</a>' +
        '</div>';
    }

    return null;
  }

  function formatDisplayName(name) {
    if (!name) return '';
    var trimmed = String(name).trim();
    if (!trimmed) return '';
    if (trimmed === trimmed.toUpperCase() && /[A-Z]/.test(trimmed)) {
      return trimmed.toLowerCase().replace(/\b([a-z])/g, function (_, ch) {
        return ch.toUpperCase();
      });
    }
    return trimmed;
  }

  function formatRoleSubtext(user, role) {
    if (role === 'dosen') {
      return (user && user.department) ? user.department : 'Dosen Pengampu';
    }
    return (user && user.program_studi) ? user.program_studi : 'Mahasiswa';
  }

  function updateUserDisplay(user, role) {
    if (!user) return;
    var displayName = formatDisplayName(user.name) || 'Pengguna';
    var initials = user.initials || (displayName ? displayName.slice(0, 2).toUpperCase() : 'CL');
    var roleText = formatRoleSubtext(user, role);

    document.querySelectorAll('[data-shell-user-name]').forEach(function (el) {
      el.textContent = displayName;
    });
    document.querySelectorAll('[data-shell-user-id]').forEach(function (el) {
      el.textContent = user.nim_nip || '';
    });
    document.querySelectorAll('[data-shell-user-role]').forEach(function (el) {
      el.textContent = roleText;
    });
    document.querySelectorAll('[data-shell-user-initials]').forEach(function (el) {
      el.textContent = initials;
    });
    document.querySelectorAll('[data-user-first-name]').forEach(function (el) {
      var first = displayName.split(/\s+/).slice(0, 2).join(' ');
      el.textContent = first;
    });
    document.querySelectorAll('[data-user-full-name]').forEach(function (el) {
      el.textContent = displayName;
    });
    document.querySelectorAll('[data-user-nim]').forEach(function (el) {
      el.textContent = user.nim_nip || '';
    });
  }

  function injectShell() {
    var body = document.body;
    if (!body) return;

    if (global.ClarityAuth && !global.ClarityAuth.guardPage()) {
      return;
    }

    var role = body.getAttribute('data-role');
    if (!role || role === 'public') {
      initTheme();
      return;
    }

    var authUser = global.ClarityAuth ? global.ClarityAuth.getUser() : null;
    if (role === 'auth' || role === 'all' || role.indexOf(',') !== -1) {
      role = (authUser && authUser.role) || 'mahasiswa';
    }

    var pageId = body.getAttribute('data-page') || 'dashboard';
    var pageTitle = body.getAttribute('data-title') || 'Clarity E-Learning';
    var pageSubtitle = body.getAttribute('data-subtitle') || 'Semester Ganjil 2026/2027';

    var user = authUser || {
      name: role === 'dosen' ? 'Dosen Pengampu' : 'Mahasiswa',
      initials: role === 'dosen' ? 'DS' : 'MH',
      nim_nip: '',
      role: role
    };

    var menus = buildMenus(role);
    var state = getQueryParam('state') || 'normal';

    var contentEl = document.getElementById('page-content');
    if (!contentEl) return;

    var stateReplacement = getStateHtml(state, pageTitle, role);
    if (stateReplacement) {
      contentEl.innerHTML = stateReplacement;
    }

    var homeHref = role === 'dosen' ? pageHref('dashboard-dosen.php') : pageHref('dashboard.php');
    var notifHref = role === 'dosen' ? pageHref('announcement.php') : pageHref('notifications.php');
    var profileHref = role === 'dosen' ? pageHref('dashboard-dosen.php') : pageHref('profile.php');
    var logoSrc = assetHref('assets/img/logo.svg');
    var mascotFooterSrc = assetHref('assets/img/Chibi Owl Mascot Waving Cheerfully.png');

    var shell = document.createElement('div');
    shell.className = 'clarity-shell';

    var sidebarHtml =
      '<div class="clarity-sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>' +
      '<aside class="clarity-sidebar" id="app-sidebar" aria-label="Navigasi Utama">' +
      '  <div class="clarity-sidebar-header">' +
      '    <a class="flex items-center gap-2.5 no-underline hover:no-underline" href="' + escapeHtml(homeHref) + '">' +
      '      <img class="w-9 h-9 shrink-0" src="' + escapeHtml(logoSrc) + '" alt="Logo Clarity" width="36" height="36">' +
      '      <span class="clarity-sidebar-brand">Clarity</span>' +
      '    </a>' +
      '    <button type="button" class="clarity-mobile-only clarity-icon-btn" id="sidebar-close-btn" aria-label="Tutup menu">' + ICONS.close + '</button>' +
      '  </div>' +
      '  <nav class="clarity-sidebar-nav" aria-label="Menu ' + (role === 'dosen' ? 'Dosen' : 'Mahasiswa') + '">' +
      '    <ul class="space-y-1 list-none p-0 m-0">' + renderNavItems(menus.primary, pageId) + '</ul>' +
      '  </nav>' +
      '  <div class="clarity-sidebar-footer">' +
      '    <div class="clarity-sidebar-usercard">' +
      '      <div class="min-w-0 flex-1">' +
      '        <span class="block text-sm font-bold truncate" data-shell-user-name>' + escapeHtml(user.name) + '</span>' +
      '        <span class="block font-mono text-xs text-muted tabular-nums" data-shell-user-id>' + escapeHtml(user.nim_nip) + '</span>' +
      '      </div>' +
      '      <img class="w-10 h-10 object-contain shrink-0" src="' + escapeHtml(mascotFooterSrc) + '" alt="Maskot Clarity menyapa" width="40" height="40">' +
      '    </div>' +
      '    <button type="button" class="clarity-btn clarity-btn-outline clarity-btn-sm w-full" id="btn-logout-sidebar">' + ICONS.logout + '<span>Keluar Akun</span></button>' +
      '  </div>' +
      '</aside>';

    var topbarHtml =
      '<header class="clarity-topbar">' +
      '  <div class="flex items-center gap-3 min-w-0">' +
      '    <button type="button" class="clarity-mobile-only clarity-icon-btn shrink-0" id="topbar-menu-btn" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="app-sidebar">' + ICONS.menu + '</button>' +
      '    <div class="min-w-0">' +
      '      <div class="text-lg font-bold text-ink truncate">' + escapeHtml(pageTitle) + '</div>' +
      '    </div>' +
      '  </div>' +
      '  <div class="flex items-center gap-2 shrink-0">' +
      '    <span class="clarity-term-chip">Ganjil 2026/2027</span>' +
      '    <button type="button" class="clarity-icon-btn" id="theme-toggle-btn" aria-label="Ubah mode terang atau gelap" title="Ubah Tema">' + ICONS.sun + '</button>' +
      '    <a class="clarity-icon-btn" href="' + escapeHtml(notifHref) + '" aria-label="Notifikasi">' + ICONS.bell + '<span class="notif-count-badge">3</span></a>' +
      '    <a class="clarity-profile-link" href="' + escapeHtml(profileHref) + '">' +
      '      <span class="avatar-chip" aria-hidden="true" data-shell-user-initials>' + escapeHtml(user.initials || 'CL') + '</span>' +
      '      <span class="hidden md:block text-left">' +
      '        <span class="block text-sm font-semibold leading-tight max-w-[170px] truncate" data-shell-user-name>' + escapeHtml(user.name) + '</span>' +
      '        <span class="block text-xs text-muted" data-shell-user-role>' + escapeHtml(formatRoleSubtext(user, role)) + '</span>' +
      '      </span>' +
      '    </a>' +
      '  </div>' +
      '</header>';

    var bottomNavHtml = '<nav class="clarity-bottom-nav" aria-label="Navigasi Cepat Mobile">' +
      menus.bottom.map(function (b) {
        var isActive = b.id === pageId;
        var cls = isActive ? ' is-active' : '';
        return '<a class="clarity-bottom-link' + cls + '" href="' + escapeHtml(b.href) + '">' + (ICONS[b.icon] || '') + '<span>' + escapeHtml(b.label) + '</span></a>';
      }).join('') +
      '</nav>';

    var mainWrap = document.createElement('div');
    mainWrap.className = 'clarity-main';
    mainWrap.innerHTML = topbarHtml;

    var mainEl = document.createElement('main');
    mainEl.id = 'main-content';
    mainEl.className = 'w-full max-w-[1440px] mx-auto px-4 py-5 md:px-7 md:py-6 lg:px-8';
    mainEl.appendChild(contentEl);
    mainWrap.appendChild(mainEl);

    shell.innerHTML = sidebarHtml;
    shell.appendChild(mainWrap);

    var skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.className = 'skip-link';
    skipLink.textContent = 'Langsung ke konten utama';

    body.insertBefore(skipLink, body.firstChild);
    body.insertBefore(shell, skipLink.nextSibling);
    body.insertAdjacentHTML('beforeend', bottomNavHtml + '<div class="toast-region" id="toast-region" aria-live="polite" aria-atomic="true"></div>');

    updateUserDisplay(user, role);
    bindSidebarEvents();
    bindLogoutEvent();
    initTheme();

    window.addEventListener('clarity:user-loaded', function (ev) {
      if (ev && ev.detail) {
        updateUserDisplay(ev.detail, role);
      }
    });
  }

  function bindLogoutEvent() {
    var logoutBtn = document.getElementById('btn-logout-sidebar');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', function () {
        if (global.ClarityAuth) {
          logoutBtn.disabled = true;
          global.ClarityAuth.logout('logout');
        }
      });
    }
  }

  function bindSidebarEvents() {
    var sidebar = document.getElementById('app-sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');
    var openBtn = document.getElementById('topbar-menu-btn');
    var closeBtn = document.getElementById('sidebar-close-btn');
    if (!sidebar || !openBtn) return;

    function openMenu() {
      sidebar.classList.add('is-open');
      if (backdrop) backdrop.classList.add('is-open');
      openBtn.setAttribute('aria-expanded', 'true');
    }

    function closeMenu() {
      sidebar.classList.remove('is-open');
      if (backdrop) backdrop.classList.remove('is-open');
      openBtn.setAttribute('aria-expanded', 'false');
    }

    openBtn.addEventListener('click', openMenu);
    if (closeBtn) closeBtn.addEventListener('click', closeMenu);
    if (backdrop) backdrop.addEventListener('click', closeMenu);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
        closeMenu();
      }
    });
  }

  function initTheme() {
    try {
      var saved = localStorage.getItem('clarity_theme');
      if (saved === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    } catch (e) {}

    var btn = document.getElementById('theme-toggle-btn');
    if (btn) {
      btn.addEventListener('click', function () {
        var current = document.documentElement.getAttribute('data-theme');
        var next = current === 'dark' ? 'light' : 'dark';
        if (next === 'dark') {
          document.documentElement.setAttribute('data-theme', 'dark');
        } else {
          document.documentElement.removeAttribute('data-theme');
        }
        try { localStorage.setItem('clarity_theme', next); } catch (e) {}
      });
    }
  }

  function showToast(message, variant) {
    var region = document.getElementById('toast-region');
    if (!region) return;
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + (variant || 'info');
    toast.setAttribute('role', 'status');
    toast.innerHTML = '<span>' + escapeHtml(message) + '</span>' +
      '<button type="button" class="inline-flex items-center justify-center w-7 h-7 rounded border-0 bg-transparent text-inherit cursor-pointer" aria-label="Tutup notifikasi">' + ICONS.close + '</button>';
    toast.querySelector('button').addEventListener('click', function () {
      toast.remove();
    });
    region.appendChild(toast);
    setTimeout(function () {
      if (toast.parentNode) toast.remove();
    }, 4000);
  }

  function renderProgressRing(percent, size) {
    var s = size || 56;
    var stroke = 5;
    var r = (s - stroke) / 2;
    var circ = 2 * Math.PI * r;
    var offset = circ - (percent / 100) * circ;
    return '<div class="progress-ring-wrap" style="width:' + s + 'px;height:' + s + 'px">' +
      '<svg width="' + s + '" height="' + s + '" viewBox="0 0 ' + s + ' ' + s + '" aria-label="Progress ' + percent + '%">' +
      '  <circle cx="' + (s / 2) + '" cy="' + (s / 2) + '" r="' + r + '" fill="none" stroke="var(--color-surface-alt)" stroke-width="' + stroke + '"/>' +
      '  <circle class="progress-ring-meter" cx="' + (s / 2) + '" cy="' + (s / 2) + '" r="' + r + '" fill="none" stroke="var(--color-primary)" stroke-width="' + stroke + '" stroke-linecap="round" stroke-dasharray="' + circ.toFixed(1) + '" stroke-dashoffset="' + offset.toFixed(1) + '" style="--ring-circ:' + circ.toFixed(1) + '" transform="rotate(-90 ' + (s / 2) + ' ' + (s / 2) + ')"/>' +
      '</svg>' +
      '<span class="progress-ring-text" data-target-percent="' + percent + '">' + percent + '%</span>' +
      '</div>';
  }

  global.ClarityUI = {
    showToast: showToast,
    renderProgressRing: renderProgressRing,
    getQueryParam: getQueryParam,
    escapeHtml: escapeHtml
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectShell);
  } else {
    injectShell();
  }
})(window);
