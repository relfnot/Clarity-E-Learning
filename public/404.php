<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = '404 Halaman Tidak Ditemukan';
$pageDescription = 'Halaman informasi tautan tidak ditemukan atau modul belum tersedia di Clarity E-Learning.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="public" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-error-page="404">
  <main class="min-h-screen min-h-[100dvh] flex items-center justify-center p-4 sm:p-6">
    <div class="clarity-card hallmark-reveal w-full max-w-lg p-8 sm:p-10 text-center flex flex-col items-center gap-4">
      <img class="w-32 h-32 object-contain" src="<?= $assetPrefix ?>assets/img/Target-Swift Owl Mascot.png" alt="Maskot Burung Hantu Clarity" width="128" height="128">
      <span class="clarity-tag clarity-tag-warning">Status halaman</span>
      <div class="error-display-code" id="err-code">404</div>
      <h1 class="text-xl font-bold text-ink m-0" id="err-title">Halaman Tidak Ditemukan</h1>
      <p class="text-sm text-muted max-w-md m-0" id="err-desc">
        Tautan yang Anda buka tidak tersedia atau halaman modul belum dirilis.
      </p>
      <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
        <a class="clarity-btn clarity-btn-primary" id="error-back-dashboard" href="login.php">Kembali ke Dashboard</a>
        <a class="clarity-btn clarity-btn-outline" href="login.php">Halaman Login</a>
      </div>
    </div>
  </main>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=11"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=11"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=11"></script>
  <script src="<?= $assetPrefix ?>assets/js/error-page.js?v=11"></script>
</body>
</html>
