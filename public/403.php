<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = '403 Akses Ditolak';
$pageDescription = 'Halaman informasi pembatasan hak akses peran mahasiswa dan dosen di Clarity E-Learning.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="public" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-error-page="403">
  <main class="min-h-screen min-h-[100dvh] flex items-center justify-center p-4 sm:p-6">
    <div class="clarity-card hallmark-reveal w-full max-w-lg p-8 sm:p-10 text-center flex flex-col items-center gap-4">
      <img class="w-32 h-32 object-contain" src="<?= $assetPrefix ?>assets/img/Target-Swift Owl Mascot.png" alt="Maskot Burung Hantu Clarity menjaga akses halaman" width="128" height="128">
      <span class="clarity-tag clarity-tag-danger">Akses terbatas</span>
      <div class="error-display-code">403</div>
      <h1 class="text-xl font-bold text-ink m-0">Akses Ditolak</h1>
      <p class="text-sm text-muted max-w-md m-0">
        Halaman yang Anda buka hanya diperuntukkan bagi peran akun tertentu. Sistem melindungi fitur pengelolaan dosen dan aktivitas mahasiswa sesuai hak aksesnya.
      </p>
      <p class="text-xs font-semibold text-ink m-0" id="error-user-context" hidden></p>
      <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
        <a class="clarity-btn clarity-btn-primary" id="error-back-dashboard" href="login.php">Kembali ke Dashboard</a>
        <button type="button" class="clarity-btn clarity-btn-outline" id="error-switch-account" hidden>Ganti Akun</button>
      </div>
    </div>
  </main>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=11"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=11"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=11"></script>
  <script src="<?= $assetPrefix ?>assets/js/error-page.js?v=11"></script>
</body>
</html>
