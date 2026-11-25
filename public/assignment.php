<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Tugas & Pengumpulan';
$pageDescription = 'Daftar tugas kuliah, status pengumpulan tepat waktu atau terlambat, dan form unggah berkas atau tautan.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="mahasiswa" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="assignment" data-title="Tugas & Pengumpulan" data-subtitle="Kumpulkan berkas atau tautan sebelum tenggat waktu">
  <div id="page-content" class="space-y-6">
    <section class="clarity-card p-5 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
      <div class="w-full sm:max-w-sm">
        <label for="assignment-course-filter" class="block text-sm font-semibold text-ink mb-1.5">Mata Kuliah</label>
        <select id="assignment-course-filter" class="clarity-input w-full"></select>
      </div>
      <div class="filter-tab-group" role="group" aria-label="Filter Status Tugas">
        <button type="button" class="filter-tab-btn is-active" data-status-filter="all" aria-pressed="true">Semua</button>
        <button type="button" class="filter-tab-btn" data-status-filter="belum" aria-pressed="false">Belum</button>
        <button type="button" class="filter-tab-btn" data-status-filter="submitted" aria-pressed="false">Terkirim</button>
      </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <section id="assignment-list-container" class="lg:col-span-5 space-y-3" aria-live="polite"></section>
      <section id="assignment-detail-container" class="lg:col-span-7" aria-live="polite"></section>
    </div>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/assignment.js?v=12"></script>
</body>
</html>
