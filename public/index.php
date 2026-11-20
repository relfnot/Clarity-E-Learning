<?php

declare(strict_types=1);

$basePrefix = '.';
$pageTitle = 'Mengarahkan';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
  <meta http-equiv="refresh" content="1; url=login.php">
</head>
<body data-role="public" data-base=".">
  <main class="min-h-screen flex items-center justify-center p-6 bg-[var(--color-bg)]">
    <div class="clarity-card w-full max-w-md p-8 text-center flex flex-col items-center gap-4">
      <img class="w-12 h-12" src="assets/img/logo.svg" alt="Logo Clarity E-Learning" width="48" height="48">
      <h1 class="text-2xl font-extrabold tracking-tight text-[#be1a1a] m-0">Clarity E-Learning</h1>
      <p class="text-sm text-[var(--color-text-muted)] m-0">Memeriksa sesi akun kampus Anda...</p>
      <a class="clarity-btn clarity-btn-primary px-5" href="login.php">Buka Halaman Login</a>
    </div>
  </main>
  <script src="assets/js/api.js?v=4"></script>
  <script src="assets/js/auth.js?v=4"></script>
  <script>
    (function () {
      if (window.ClarityAuth && window.ClarityAuth.getToken() && !window.ClarityAuth.isTokenExpired()) {
        var user = window.ClarityAuth.getUser();
        if (user && user.role) {
          window.location.replace(user.role === 'dosen' ? 'dashboard-dosen.php' : 'dashboard.php');
          return;
        }
      }
      window.location.replace('login.php');
    })();
  </script>
</body>
</html>
