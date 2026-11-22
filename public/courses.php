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
<body class="overflow-x-clip" data-role="mahasiswa,dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="courses" data-title="Daftar Mata Kuliah" data-subtitle="Filter semester, jadwal, progres belajar, dan dosen pengampu">
  <div id="page-content" class="space-y-6">

    <!-- Breadcrumb Navigasi -->
    <nav class="flex items-center text-xs font-medium text-muted gap-2" aria-label="Breadcrumb">
      <a class="text-secondary hover:text-ink no-underline" href="dashboard.php">Dashboard</a>
      <span aria-hidden="true">/</span>
      <span class="text-ink font-semibold">Mata Kuliah</span>
    </nav>

    <!-- Header & Ringkasan -->
    <section class="clarity-card hallmark-reveal p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="space-y-1">
        <div class="flex items-center gap-3">
          <h1 class="text-xl sm:text-2xl font-extrabold text-ink m-0">Katalog Mata Kuliah</h1>
          <span class="clarity-tag clarity-tag-info" id="courses-count">Memuat...</span>
        </div>
        <p class="text-sm text-secondary m-0">
          Akses materi perkuliahan, antrean tugas, presensi pertemuan, dan informasi dosen pengampu.
        </p>
      </div>
      <div class="shrink-0 flex items-center gap-2">
        <a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="calendar.php">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline-block mr-1.5" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Lihat Kalender
        </a>
      </div>
    </section>

    <!-- Bar Kontrol & Filter Server-Side -->
    <section class="clarity-card p-5 hallmark-reveal">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        <div class="md:col-span-5 space-y-1.5">
          <label class="block text-xs font-bold text-ink uppercase tracking-wider" for="filter-search">Cari Mata Kuliah / Dosen</label>
          <div class="relative">
            <input class="w-full h-10 px-3.5 pl-9 text-sm rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] text-ink placeholder-[var(--color-muted)] focus:outline-none focus:border-[var(--color-accent)] focus:ring-1 focus:ring-[var(--color-accent)] transition-all" id="filter-search" type="search" placeholder="Ketik kode, nama mata kuliah, atau dosen...">
            <svg class="absolute left-3 top-3 text-muted pointer-events-none" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </div>
        </div>

        <div class="md:col-span-4 space-y-1.5">
          <label class="block text-xs font-bold text-ink uppercase tracking-wider" for="filter-semester">Semester</label>
          <select class="w-full h-10 px-3 text-sm rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] text-ink focus:outline-none focus:border-[var(--color-accent)] focus:ring-1 focus:ring-[var(--color-accent)] transition-all" id="filter-semester">
            <option value="Ganjil 2026/2027" selected>Ganjil 2026/2027 (Aktif)</option>
            <option value="Genap 2025/2026">Genap 2025/2026</option>
            <option value="">Semua Semester</option>
          </select>
        </div>

        <div class="md:col-span-3 space-y-1.5">
          <label class="block text-xs font-bold text-ink uppercase tracking-wider" for="filter-day">Hari Kuliah</label>
          <select class="w-full h-10 px-3 text-sm rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] text-ink focus:outline-none focus:border-[var(--color-accent)] focus:ring-1 focus:ring-[var(--color-accent)] transition-all" id="filter-day">
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
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5" id="courses-grid">
        <!-- Dirender secara dinamis oleh courses.js via /api/v1/courses -->
      </div>
    </section>

  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/mock-data.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/courses.js?v=12"></script>
</body>
</html>
