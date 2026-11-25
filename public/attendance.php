<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Presensi Mahasiswa';
$pageDescription = 'Isi kehadiran pada sesi presensi terbuka dan pantau rekap kehadiran per mata kuliah.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="mahasiswa" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="attendance" data-title="Presensi Kuliah" data-subtitle="Check-in sesi terbuka dan rekap kehadiran">
  <div id="page-content" class="space-y-6">
    <section class="clarity-card p-5 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
      <div class="w-full sm:max-w-sm">
        <label for="attendance-course-select" class="block text-sm font-semibold text-ink mb-1.5">Pilih Mata Kuliah</label>
        <select id="attendance-course-select" class="clarity-input w-full"></select>
      </div>
      <div id="attendance-percent-summary" class="text-sm font-semibold text-muted"></div>
    </section>

    <section id="attendance-content" class="space-y-6" aria-live="polite"></section>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/attendance.js?v=12"></script>
</body>
</html>
