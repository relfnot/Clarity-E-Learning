<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Masuk Akun Kampus';
$pageDescription = 'Halaman masuk portal akademik Clarity E-Learning bagi mahasiswa dan dosen.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="public" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>">
  <main class="auth-page-canvas">
    <div class="auth-shell hallmark-reveal grid grid-cols-1 lg:grid-cols-12">

      <!-- Panel Brand Kiri (Logo + Nama Clarity + Maskot Vector) -->
      <section class="auth-editorial-panel lg:col-span-5" aria-label="Identitas Clarity">
        <div class="flex items-center gap-3">
          <img class="w-11 h-11 shrink-0" src="<?= $assetPrefix ?>assets/img/logo.svg" alt="Logo Clarity" width="44" height="44">
          <span class="auth-brand-wordmark">Clarity</span>
        </div>

        <div class="auth-target-stage">
          <div class="auth-mascot-float">
            <img class="auth-mascot-hero" src="<?= $assetPrefix ?>assets/img/Target-Swift Owl Mascot.png" alt="Maskot Burung Hantu Clarity" width="184" height="184">
          </div>
        </div>
      </section>

      <!-- Panel Form Login Kanan -->
      <section class="lg:col-span-7 p-6 sm:p-10 lg:p-12 flex flex-col justify-center gap-6">
        <h1 class="hallmark-reveal stagger-1 text-2xl font-extrabold text-ink m-0">Masuk</h1>

        <div id="active-session-banner" class="clarity-alert clarity-alert-info text-sm" role="status" hidden>
          <div class="flex flex-col gap-2 w-full">
            <span>Anda sedang login sebagai <strong id="active-session-user"></strong>.</span>
            <div>
              <a class="clarity-btn clarity-btn-primary clarity-btn-sm" id="active-session-dash-link" href="<?= $assetPrefix ?>dashboard.php">Lanjut ke Dashboard</a>
            </div>
          </div>
        </div>

        <div id="login-info-alert" class="clarity-alert clarity-alert-info text-sm" role="status" aria-live="polite" hidden>
          <span id="login-info-text"></span>
        </div>

        <div id="login-error-alert" class="clarity-alert clarity-alert-danger text-sm" role="alert" aria-live="assertive" hidden>
          <span id="login-error-text"></span>
        </div>

        <form id="login-form" class="hallmark-reveal stagger-2 space-y-5" novalidate>
          <div class="space-y-2">
            <label class="block text-sm font-semibold text-ink" for="login-identifier">
              NIM, NIP, atau Email <span class="text-accent">*</span>
            </label>
            <input
              class="clarity-input"
              id="login-identifier"
              name="identifier"
              type="text"
              autocomplete="username"
              spellcheck="false"
              placeholder="24.N1.0026 atau 5811995177"
              aria-describedby="identifier-error"
              required
            >
            <span class="block text-xs font-semibold text-accent" id="identifier-error" role="alert" hidden></span>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-semibold text-ink" for="login-password">
              Kata Sandi <span class="text-accent">*</span>
            </label>
            <div class="relative flex items-center">
              <input
                class="clarity-input clarity-input-password"
                id="login-password"
                name="password"
                type="password"
                autocomplete="current-password"
                placeholder="Kata sandi"
                aria-describedby="password-error"
                required
              >
              <button
                type="button"
                class="clarity-icon-btn absolute right-0 border-0 bg-transparent"
                id="toggle-password-btn"
                aria-label="Tampilkan kata sandi"
                aria-pressed="false"
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
            <span class="block text-xs font-semibold text-accent" id="password-error" role="alert" hidden></span>
          </div>

          <button
            type="submit"
            class="clarity-btn clarity-btn-primary clarity-btn-lg w-full"
            id="login-submit-btn"
          >
            <span>Masuk</span>
          </button>
        </form>
      </section>
    </div>
  </main>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=14"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=14"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/login.js?v=14"></script>
</body>
</html>
