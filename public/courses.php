<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Daftar Mata Kuliah';
$pageDescription = 'Daftar mata kuliah aktif, filter semester, jadwal perkuliahan, dan dosen pengampu Clarity E-Learning.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="mahasiswa,dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="courses" data-title="Mata Kuliah" data-subtitle="Filter semester, jadwal, dan progres belajar">
  <div id="page-content" class="space-y-6">

    <!-- Header & Ringkasan -->
    <section class="clarity-card hallmark-reveal p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="space-y-1">
        <div class="flex items-center gap-3 flex-wrap">
          <h1 class="text-xl sm:text-2xl font-extrabold text-ink m-0">Mata Kuliah Anda</h1>
          <span class="clarity-tag clarity-tag-info" id="courses-count">Memuat…</span>
        </div>
        <p class="text-sm text-muted m-0">
          Akses materi pertemuan, peserta kelas, pengumpulan tugas, dan informasi dosen pengampu.
        </p>
      </div>
      <div class="shrink-0 flex items-center gap-2">
        <a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="calendar.php">Buka Kalender</a>
      </div>
    </section>

    <!-- Bar Filter -->
    <section class="clarity-card p-5 hallmark-reveal">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        <div class="md:col-span-5 space-y-1.5">
          <label class="block text-sm font-semibold text-ink" for="filter-search">Cari Mata Kuliah / Dosen</label>
          <input class="clarity-input" id="filter-search" type="search" placeholder="Ketik kode, nama mata kuliah, atau dosen…">
        </div>

        <div class="md:col-span-4 space-y-1.5">
          <label class="block text-sm font-semibold text-ink" for="filter-semester">Semester</label>
          <select class="clarity-input" id="filter-semester">
            <option value="Ganjil 2026/2027" selected>Ganjil 2026/2027 (Aktif)</option>
            <option value="Genap 2025/2026">Genap 2025/2026</option>
            <option value="">Semua Semester</option>
          </select>
        </div>

        <div class="md:col-span-3 space-y-1.5">
          <label class="block text-sm font-semibold text-ink" for="filter-day">Hari Kuliah</label>
          <select class="clarity-input" id="filter-day">
            <option value="">Semua Hari</option>
            <option value="Senin">Senin</option>
            <option value="Selasa">Selasa</option>
            <option value="Rabu">Rabu</option>
            <option value="Kamis">Kamis</option>
            <option value="Jumat">Jumat</option>
          </select>
        </div>
      </div>
    </section>

    <!-- Grid Kartu Mata Kuliah -->
    <section>
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5" id="courses-grid"></div>
    </section>

  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=14"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=14"></script>
  <script src="<?= $assetPrefix ?>assets/js/mock-data.js?v=14"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=14"></script>
  <script src="<?= $assetPrefix ?>assets/js/courses.js?v=14"></script>
</body>
</html>
