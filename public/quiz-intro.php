<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Informasi & Riwayat Quiz';
$pageDescription = 'Pilih quiz, lihat aturan durasi dan batas percobaan, mulai pengerjaan, serta pantau riwayat nilai quiz.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="mahasiswa" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="quiz" data-title="Quiz Perkuliahan" data-subtitle="Informasi jadwal, batas percobaan, dan riwayat pengerjaan">
  <div id="page-content" class="space-y-6">
    <section class="clarity-card hallmark-reveal p-5 grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
      <div>
        <label for="qi-course-select" class="block text-sm font-semibold text-ink mb-1.5">Mata Kuliah</label>
        <select id="qi-course-select" class="clarity-input w-full"></select>
      </div>
      <div>
        <label for="qi-quiz-select" class="block text-sm font-semibold text-ink mb-1.5">Pilih Quiz</label>
        <select id="qi-quiz-select" class="clarity-input w-full"></select>
      </div>
    </section>

    <section id="qi-detail-container" class="space-y-6" aria-live="polite"></section>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/quiz-intro.js?v=12"></script>
</body>
</html>
