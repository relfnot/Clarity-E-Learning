/* ==========================================================================
   Clarity E-Learning — Layout Injector, State Handler & UI Helpers
   Kompatibel dengan protokol file:// dan server lokal (tanpa ES module CORS)
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
    layers: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>',
    menu: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>',
    close: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    sun: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>',
    logout: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>'
  };

  function getQueryParam(key) {
    try {
      var params = new URLSearchParams(window.location.search);
      return params.get(key);
    } catch (e) {
      return null;
    }
  }

  function buildMenus(role, base) {
    var rootPrefix = base === '.' ? '' : '../';
    var mhsPrefix = base === '.' ? 'mahasiswa/' : (role === 'mahasiswa' ? '' : '../mahasiswa/');
    var dsnPrefix = base === '.' ? 'dosen/' : (role === 'dosen' ? '' : '../dosen/');

    if (role === 'dosen') {
      return {
        primary: [
          { id: 'dashboard', label: 'Dashboard Dosen', href: dsnPrefix + 'dashboard.html', icon: 'home' },
          { id: 'course-manage', label: 'Kelola Mata Kuliah', href: dsnPrefix + 'course-manage.html', icon: 'book' },
          { id: 'material-upload', label: 'Upload Materi', href: dsnPrefix + 'material-upload.html', icon: 'upload' },
          { id: 'quiz-builder', label: 'Buat Quiz & Soal', href: dsnPrefix + 'quiz-builder.html', icon: 'quiz' },
          { id: 'submissions', label: 'Daftar Pengumpulan', href: dsnPrefix + 'submissions.html', icon: 'file', badge: '4' },
          { id: 'grading', label: 'Penilaian Tugas', href: dsnPrefix + 'grading.html', icon: 'chart' },
          { id: 'attendance-manage', label: 'Kelola Presensi', href: dsnPrefix + 'attendance-manage.html', icon: 'check' },
          { id: 'announcement', label: 'Kirim Pengumuman', href: dsnPrefix + 'announcement.html', icon: 'megaphone' }
        ],
        bottom: [
          { id: 'dashboard', label: 'Beranda', href: dsnPrefix + 'dashboard.html', icon: 'home' },
          { id: 'course-manage', label: 'Matkul', href: dsnPrefix + 'course-manage.html', icon: 'book' },
          { id: 'submissions', label: 'Tugas', href: dsnPrefix + 'submissions.html', icon: 'file' },
          { id: 'attendance-manage', label: 'Presensi', href: dsnPrefix + 'attendance-manage.html', icon: 'check' }
        ],
        utility: [
          { id: 'index', label: 'Peta Halaman F4', href: rootPrefix + 'index.html', icon: 'layers' },
          { id: 'components', label: 'Katalog Komponen', href: rootPrefix + 'components.html', icon: 'layers' },
          { id: 'switch-role', label: 'Lihat Mode Mahasiswa', href: mhsPrefix + 'dashboard.html', icon: 'award' }
        ]
      };
    }

    return {
      primary: [
        { id: 'dashboard', label: 'Dashboard', href: mhsPrefix + 'dashboard.html', icon: 'home' },
        { id: 'courses', label: 'Mata Kuliah', href: mhsPrefix + 'courses.html', icon: 'book' },
        { id: 'assignment', label: 'Tugas', href: mhsPrefix + 'assignment.html', icon: 'file', badge: '2' },
        { id: 'quiz', label: 'Quiz', href: mhsPrefix + 'quiz-intro.html', icon: 'quiz' },
        { id: 'attendance', label: 'Presensi', href: mhsPrefix + 'attendance.html', icon: 'check' },
        { id: 'calendar', label: 'Kalender & Deadline', href: mhsPrefix + 'calendar.html', icon: 'calendar' },
        { id: 'grades', label: 'Nilai', href: mhsPrefix + 'grades.html', icon: 'chart' },
        { id: 'chat', label: 'Diskusi & Kontak', href: mhsPrefix + 'chat.html', icon: 'chat' },
        { id: 'profile', label: 'Profil & Gamifikasi', href: mhsPrefix + 'profile.html', icon: 'award' },
        { id: 'notifications', label: 'Notifikasi', href: mhsPrefix + 'notifications.html', icon: 'bell', badge: '3' }
      ],
      bottom: [
        { id: 'dashboard', label: 'Beranda', href: mhsPrefix + 'dashboard.html', icon: 'home' },
        { id: 'courses', label: 'Matkul', href: mhsPrefix + 'courses.html', icon: 'book' },
        { id: 'assignment', label: 'Tugas', href: mhsPrefix + 'assignment.html', icon: 'file' },
        { id: 'profile', label: 'Profil', href: mhsPrefix + 'profile.html', icon: 'award' }
      ],
      utility: [
        { id: 'index', label: 'Peta Halaman F4', href: rootPrefix + 'index.html', icon: 'layers' },
        { id: 'components', label: 'Katalog Komponen', href: rootPrefix + 'components.html', icon: 'layers' },
        { id: 'switch-role', label: 'Lihat Mode Dosen', href: dsnPrefix + 'dashboard.html', icon: 'award' }
      ]
    };
  }

  function renderNavItems(items, activePage) {
    return items.map(function (item) {
      var activeClass = item.id === activePage ? ' is-active' : '';
      var ariaCurrent = item.id === activePage ? ' aria-current="page"' : '';
      var badgeHtml = item.badge ? '<span class="sidebar-badge">' + item.badge + '</span>' : '';
      return '<li><a class="sidebar-link' + activeClass + '" href="' + item.href + '"' + ariaCurrent + '>' +
        '<span class="sidebar-link-main">' + (ICONS[item.icon] || '') + '<span>' + item.label + '</span></span>' +
        badgeHtml + '</a></li>';
    }).join('');
  }

  function getStateHtml(state, pageTitle, role, base) {
    var rootPrefix = base === '.' ? '' : '../';
    if (state === 'loading') {
      return '<div class="stack" aria-busy="true" aria-label="Memuat konten halaman">' +
        '<div class="alert alert-info"><strong>Mode Loading (Skeleton):</strong> Simulasi pengambilan data dari API <code>/api/v1</code>...</div>' +
        '<div class="skeleton skeleton-title"></div>' +
        '<div class="grid grid-3">' +
        '  <div class="card card-body"><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line" style="width:70%"></div><div class="skeleton skeleton-card mt-2"></div></div>' +
        '  <div class="card card-body"><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line" style="width:65%"></div><div class="skeleton skeleton-card mt-2"></div></div>' +
        '  <div class="card card-body"><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line" style="width:80%"></div><div class="skeleton skeleton-card mt-2"></div></div>' +
        '</div>' +
        '<div class="card card-body"><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line" style="width:50%"></div></div>' +
        '</div>';
    }

    if (state === 'empty') {
      var backHref = role === 'dosen' ? (base === '.' ? 'dosen/dashboard.html' : 'dashboard.html') : (base === '.' ? 'mahasiswa/dashboard.html' : 'dashboard.html');
      return '<div class="empty-state" role="status">' +
        '<img class="mascot-empty-img" src="' + rootPrefix + 'assets/img/Target-Swift Owl Mascot.png" alt="Maskot Burung Hantu Clarity membawa anak panah">' +
        '<h2 class="empty-state-title">Belum Ada Data pada ' + pageTitle + '</h2>' +
        '<p class="empty-state-text">Saat ini belum ada data yang tersedia atau sesuai dengan filter yang Anda pilih. Silakan periksa kembali nanti atau muat ulang tampilan normal.</p>' +
        '<div class="flex flex-wrap" style="justify-content:center">' +
        '  <a class="btn btn-primary" href="?state=normal">Tampilkan Data Normal</a>' +
        '  <a class="btn btn-outline" href="' + backHref + '">Kembali ke Dashboard</a>' +
        '</div>' +
        '</div>';
    }

    if (state === 'error') {
      return '<div class="card card-body stack">' +
        '<div class="alert alert-danger" role="alert">' +
        '  <div><strong>Gagal Memuat Data (500 / Koneksi Terputus):</strong> Terjadi gangguan saat menghubungi layanan akademik. Pastikan koneksi internet Anda stabil dan coba lagi.</div>' +
        '</div>' +
        '<div class="flex flex-wrap">' +
        '  <a class="btn btn-primary" href="?state=normal">Coba Lagi</a>' +
        '  <a class="btn btn-outline" href="' + rootPrefix + '404.html?type=offline">Lihat Halaman Offline</a>' +
        '</div>' +
        '</div>';
    }
    return null;
  }

  function injectShell() {
    var body = document.body;
    var role = body.getAttribute('data-role');
    if (!role || role === 'auth') {
      initTheme();
      initStateOverrideForAuth();
      return;
    }

    var base = body.getAttribute('data-base') || '..';
    var pageId = body.getAttribute('data-page') || 'dashboard';
    var pageTitle = body.getAttribute('data-title') || 'Clarity E-Learning';
    var pageSubtitle = body.getAttribute('data-subtitle') || 'Semester Ganjil 2026/2027';
    var rootPrefix = base === '.' ? '' : '../';

    var data = global.ClarityData || {};
    var user = role === 'dosen' ? (data.users && data.users.dosen) : (data.users && data.users.mahasiswa);
    user = user || { name: 'Pengguna Demo', initials: 'PD', nim_nip: '000000' };

    var menus = buildMenus(role, base);
    var state = getQueryParam('state') || 'normal';

    var contentEl = document.getElementById('page-content');
    if (!contentEl) return;

    var stateReplacement = getStateHtml(state, pageTitle, role, base);
    if (stateReplacement) {
      contentEl.innerHTML = stateReplacement;
    }

    var notifHref = role === 'dosen'
      ? (base === '.' ? 'dosen/announcement.html' : 'announcement.html')
      : (base === '.' ? 'mahasiswa/notifications.html' : 'notifications.html');
    var profileHref = role === 'dosen'
      ? (base === '.' ? 'dosen/dashboard.html' : 'dashboard.html')
      : (base === '.' ? 'mahasiswa/profile.html' : 'profile.html');
    var loginHref = rootPrefix + 'login.html';

    var shell = document.createElement('div');
    shell.className = 'app-shell';

    var sidebarHtml =
      '<div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>' +
      '<aside class="app-sidebar" id="app-sidebar" aria-label="Navigasi Utama">' +
      '  <div class="sidebar-header">' +
      '    <a class="sidebar-brand" href="' + rootPrefix + 'index.html">' +
      '      <span class="brand-wordmark">Clarity E-Learning</span>' +
      '    </a>' +
      '    <button type="button" class="sidebar-close-btn" id="sidebar-close-btn" aria-label="Tutup menu">' + ICONS.close + '</button>' +
      '  </div>' +
      '  <nav class="sidebar-nav" aria-label="Menu ' + (role === 'dosen' ? 'Dosen' : 'Mahasiswa') + '">' +
      '    <div class="sidebar-section-label">Menu ' + (role === 'dosen' ? 'Dosen Pengampu' : 'Mahasiswa') + '</div>' +
      '    <ul class="sidebar-list">' + renderNavItems(menus.primary, pageId) + '</ul>' +
      '    <div class="sidebar-section-label">Prototipe & Navigasi</div>' +
      '    <ul class="sidebar-list">' + renderNavItems(menus.utility, pageId) + '</ul>' +
      '  </nav>' +
      '  <div class="sidebar-footer">' +
      '    <a class="btn btn-sm btn-outline btn-block" href="' + loginHref + '">' + ICONS.logout + '<span>Keluar Akun</span></a>' +
      '  </div>' +
      '</aside>';

    var topbarHtml =
      '<header class="app-topbar">' +
      '  <div class="topbar-left">' +
      '    <button type="button" class="topbar-menu-btn" id="topbar-menu-btn" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="app-sidebar">' + ICONS.menu + '</button>' +
      '    <div class="topbar-title-group">' +
      '      <div class="topbar-title">' + pageTitle + '</div>' +
      '    </div>' +
      '  </div>' +
      '  <div class="topbar-actions">' +
      '    <button type="button" class="topbar-icon-btn" id="theme-toggle-btn" aria-label="Ubah mode terang atau gelap" title="Ubah Tema">' + ICONS.sun + '</button>' +
      '    <a class="topbar-icon-btn" href="' + notifHref + '" aria-label="Notifikasi (3 belum dibaca)">' + ICONS.bell + '<span class="topbar-notif-dot">3</span></a>' +
      '    <a class="topbar-user" href="' + profileHref + '">' +
      '      <span class="avatar avatar-sm" aria-hidden="true">' + user.initials + '</span>' +
      '      <span class="topbar-user-info">' +
      '        <span class="topbar-user-name">' + user.name + '</span>' +
      '        <span class="topbar-user-role">' + (role === 'dosen' ? 'Dosen' : 'Level 7 · 1.450 XP') + '</span>' +
      '      </span>' +
      '    </a>' +
      '  </div>' +
      '</header>';

    var protoBarHtml =
      '<div class="proto-toolbar" role="region" aria-label="Kontrol State Prototipe">' +
      '  <div class="proto-toolbar-group">' +
      '    <span class="font-semibold">State Halaman:</span>' +
      '    <a class="proto-pill' + (state === 'normal' ? ' is-active' : '') + '" href="?state=normal">Normal</a>' +
      '    <a class="proto-pill' + (state === 'loading' ? ' is-active' : '') + '" href="?state=loading">Loading</a>' +
      '    <a class="proto-pill' + (state === 'empty' ? ' is-active' : '') + '" href="?state=empty">Empty</a>' +
      '    <a class="proto-pill' + (state === 'error' ? ' is-active' : '') + '" href="?state=error">Error</a>' +
      '  </div>' +
      '  <div class="proto-toolbar-group">' +
      '    <a class="proto-pill" href="' + rootPrefix + 'index.html">Daftar Semua Halaman F4</a>' +
      '  </div>' +
      '</div>';

    var bottomNavHtml = '<nav class="mobile-bottom-nav" aria-label="Navigasi Cepat Mobile">' +
      menus.bottom.map(function (b) {
        var cls = b.id === pageId ? ' is-active' : '';
        return '<a class="bottom-nav-item' + cls + '" href="' + b.href + '">' + (ICONS[b.icon] || '') + '<span>' + b.label + '</span></a>';
      }).join('') +
      '</nav>';

    var mainWrap = document.createElement('div');
    mainWrap.className = 'app-main';
    mainWrap.innerHTML = topbarHtml + protoBarHtml;

    var mainEl = document.createElement('main');
    mainEl.id = 'main-content';
    mainEl.className = 'app-content';
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

    bindSidebarEvents();
    initTheme();
    initTabs();
    initModals();
  }

  function initStateOverrideForAuth() {
    var state = getQueryParam('state');
    if (state === 'error') {
      var errBox = document.getElementById('login-error-alert');
      if (errBox) errBox.hidden = false;
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

  function initTabs() {
    var tabLists = document.querySelectorAll('[data-tabs]');
    tabLists.forEach(function (container) {
      var triggers = container.querySelectorAll('.tab-trigger');
      var panels = container.querySelectorAll('.tab-panel');
      triggers.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var targetId = btn.getAttribute('data-tab-target');
          triggers.forEach(function (t) {
            t.classList.remove('is-active');
            t.setAttribute('aria-selected', 'false');
          });
          panels.forEach(function (p) {
            p.classList.remove('is-active');
          });
          btn.classList.add('is-active');
          btn.setAttribute('aria-selected', 'true');
          var targetPanel = container.querySelector('#' + targetId);
          if (targetPanel) targetPanel.classList.add('is-active');
        });
      });
    });
  }

  function initModals() {
    document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = btn.getAttribute('data-modal-open');
        var modal = document.getElementById(id);
        if (modal) modal.classList.add('is-open');
      });
    });

    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var modal = btn.closest('.modal-overlay');
        if (modal) modal.classList.remove('is-open');
      });
    });

    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.classList.remove('is-open');
      });
    });
  }

  function showToast(message, variant) {
    var region = document.getElementById('toast-region');
    if (!region) return;
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + (variant || 'info');
    toast.setAttribute('role', 'status');
    toast.innerHTML = '<span>' + message + '</span>' +
      '<button type="button" aria-label="Tutup notifikasi" style="background:none;border:0;color:inherit;cursor:pointer;font-weight:bold">✕</button>';
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
      '  <circle cx="' + (s / 2) + '" cy="' + (s / 2) + '" r="' + r + '" fill="none" stroke="var(--color-primary)" stroke-width="' + stroke + '" stroke-linecap="round" stroke-dasharray="' + circ.toFixed(1) + '" stroke-dashoffset="' + offset.toFixed(1) + '" transform="rotate(-90 ' + (s / 2) + ' ' + (s / 2) + ')"/>' +
      '</svg>' +
      '<span class="progress-ring-text">' + percent + '%</span>' +
      '</div>';
  }

  global.ClarityUI = {
    showToast: showToast,
    renderProgressRing: renderProgressRing,
    getQueryParam: getQueryParam,
    initTabs: initTabs,
    initModals: initModals
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectShell);
  } else {
    injectShell();
  }
})(window);
