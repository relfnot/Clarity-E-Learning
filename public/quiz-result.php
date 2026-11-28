<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Hasil Pengerjaan Quiz';
$pageDescription = 'Ringkasan skor quiz, rincian jawaban per soal, kunci jawaban jika tersedia, dan penilaian esai oleh dosen.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="mahasiswa,dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="quiz" data-title="Hasil Quiz" data-subtitle="Ringkasan nilai, status penilaian esai, dan rincian jawaban">
  <div id="page-content" class="space-y-6">
    <section id="qr-container" class="space-y-6" aria-live="polite"></section>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/quiz-result.js?v=12"></script>
</body>
</html>
