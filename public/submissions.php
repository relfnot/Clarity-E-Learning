<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Daftar Pengumpulan Tugas';
$pageDescription = 'Pantau pengumpulan tugas mahasiswa beserta status tepat waktu, terlambat, atau belum mengumpulkan, dan ekspor CSV.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="submissions" data-title="Daftar Pengumpulan" data-subtitle="Rekap status pengumpulan tugas mahasiswa">
  <div id="page-content" class="space-y-6">
    <section class="clarity-card p-5 grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
      <div class="md:col-span-4">
        <label for="sub-course-select" class="block text-sm font-semibold text-ink mb-1.5">Mata Kuliah</label>
        <select id="sub-course-select" class="clarity-input w-full"></select>
      </div>
      <div class="md:col-span-5">
        <label for="sub-assignment-select" class="block text-sm font-semibold text-ink mb-1.5">Tugas</label>
        <select id="sub-assignment-select" class="clarity-input w-full"></select>
      </div>
      <div class="md:col-span-3 flex justify-start md:justify-end">
        <button type="button" id="btn-export-csv" class="clarity-btn clarity-btn-outline clarity-btn-sm w-full md:w-auto" disabled>Ekspor CSV</button>
      </div>
    </section>

    <section id="sub-summary-container" aria-live="polite"></section>

    <section class="clarity-card overflow-hidden">
      <div class="clarity-section-head flex-wrap gap-3">
        <h1 class="text-base font-bold text-ink m-0">Tabel Pengumpulan Peserta</h1>
        <div class="flex flex-col md:flex-row md:items-center gap-3 w-full xl:w-auto">
          <div class="filter-tab-group overflow-x-auto" role="group" aria-label="Filter Status Pengumpulan">
            <button type="button" class="filter-tab-btn is-active" data-sub-status="" aria-pressed="true">Semua</button>
            <button type="button" class="filter-tab-btn" data-sub-status="tepat_waktu" aria-pressed="false">Tepat Waktu</button>
            <button type="button" class="filter-tab-btn" data-sub-status="terlambat" aria-pressed="false">Terlambat</button>
            <button type="button" class="filter-tab-btn" data-sub-status="dinilai" aria-pressed="false">Dinilai</button>
            <button type="button" class="filter-tab-btn" data-sub-status="belum" aria-pressed="false">Belum</button>
          </div>
          <div class="w-full md:w-60">
            <label for="sub-search-input" class="sr-only">Cari NIM atau nama mahasiswa</label>
            <input id="sub-search-input" type="search" class="clarity-input text-sm" placeholder="Cari NIM atau nama...">
          </div>
        </div>
      </div>
      <div id="sub-table-container" aria-live="polite"></div>
      <div id="sub-pagination-container"></div>
    </section>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/submissions.js?v=12"></script>
</body>
</html>
