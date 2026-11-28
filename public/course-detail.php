<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Detail Mata Kuliah';
$pageDescription = 'Detail silabus, dosen pengampu, jadwal kuliah, peserta, dan materi perkuliahan Clarity E-Learning.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="mahasiswa,dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="courses" data-title="Detail Mata Kuliah" data-subtitle="Informasi perkuliahan, peserta kelas, dan progres akademik">
  <div id="page-content" class="space-y-6">

    <!-- Breadcrumb Navigasi -->
    <nav class="flex items-center text-xs font-medium text-muted gap-2 flex-wrap" aria-label="Breadcrumb">
      <a class="text-secondary hover:text-ink no-underline" id="breadcrumb-dashboard-link" href="dashboard.php">Dashboard</a>
      <span aria-hidden="true">/</span>
      <a class="text-secondary hover:text-ink no-underline" id="breadcrumb-courses-link" href="courses.php">Mata Kuliah</a>
      <span aria-hidden="true">/</span>
      <span class="text-ink font-semibold truncate max-w-xs sm:max-w-md" id="breadcrumb-course-name">Memuat…</span>
    </nav>

    <div id="course-detail-error" hidden></div>

    <!-- Header Informasi Utama Mata Kuliah -->
    <section class="clarity-card hallmark-reveal p-6 space-y-5" id="course-detail-header">
      <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
        <div class="space-y-3 min-w-0 flex-1">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="course-code-pill font-mono" id="course-code">…</span>
            <span class="clarity-tag clarity-tag-info" id="course-credits">3 SKS</span>
            <span class="clarity-tag clarity-tag-success">Aktif</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-ink leading-tight m-0" id="course-name">
            Memuat Mata Kuliah…
          </h1>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-secondary pt-1">
            <div class="flex items-center gap-2">
              <span class="text-muted font-medium">Dosen:</span>
              <span class="font-semibold text-ink" id="course-lecturer">-</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-muted font-medium">Jadwal:</span>
              <span class="font-semibold text-ink" id="course-schedule">-</span>
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row lg:flex-col gap-3 shrink-0">
          <div class="clarity-well p-4 space-y-2 min-w-[200px]">
            <div class="flex justify-between items-center text-xs font-semibold">
              <span class="text-muted">Progres Mahasiswa</span>
              <span class="text-accent font-bold" id="course-progress-text">0%</span>
            </div>
            <div class="w-full bg-[var(--color-surface)] rounded-full h-2 overflow-hidden">
              <div class="bg-[var(--color-accent)] h-2 rounded-full" id="course-progress-bar" style="width: 0%"></div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <a class="clarity-btn clarity-btn-primary clarity-btn-sm flex-1 text-center" id="course-attendance-link" href="attendance.php">Isi Presensi</a>
            <a class="clarity-btn clarity-btn-outline clarity-btn-sm flex-1 text-center" id="course-discussion-link" href="chat.php">Forum Diskusi</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Navigasi 4 Tab Utama -->
    <section class="clarity-card overflow-hidden hallmark-reveal" id="course-detail-tabs">
      <div class="flex items-center border-b border-[var(--color-border)] px-4 bg-[var(--color-surface-alt)] overflow-x-auto" role="tablist" aria-label="Bagian Mata Kuliah">
        <button type="button" class="filter-tab-btn is-active py-3.5 px-4 font-semibold text-sm border-b-2 border-transparent" role="tab" aria-selected="true" data-tab-target="tab-info">
          Ringkasan & Informasi
        </button>
        <button type="button" class="filter-tab-btn py-3.5 px-4 font-semibold text-sm border-b-2 border-transparent" role="tab" aria-selected="false" data-tab-target="tab-peserta">
          Peserta Kelas <span class="text-xs text-muted font-normal ml-1" id="participants-tab-count">(…)</span>
        </button>
        <button type="button" class="filter-tab-btn py-3.5 px-4 font-semibold text-sm border-b-2 border-transparent" role="tab" aria-selected="false" data-tab-target="tab-materi">
          Materi Pertemuan
        </button>
        <button type="button" class="filter-tab-btn py-3.5 px-4 font-semibold text-sm border-b-2 border-transparent" role="tab" aria-selected="false" data-tab-target="tab-tugas">
          Tugas & Quiz
        </button>
      </div>

      <!-- Tab 1: Ringkasan & Informasi -->
      <div class="tab-content-panel p-6 space-y-6" id="tab-info">
        <div class="space-y-2">
          <h2 class="text-base font-bold text-ink m-0">Deskripsi Mata Kuliah</h2>
          <p class="text-sm text-secondary leading-relaxed m-0" id="course-description">
            Memuat deskripsi…
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-[var(--color-border-subtle)]">
          <div class="p-4 rounded-lg bg-[var(--color-surface-alt)] space-y-1">
            <div class="text-xs text-muted font-semibold">Metode Pembelajaran</div>
            <div class="text-sm font-bold text-ink">Tatap Muka & Daring Terpadu</div>
          </div>
          <div class="p-4 rounded-lg bg-[var(--color-surface-alt)] space-y-1">
            <div class="text-xs text-muted font-semibold">Total Pertemuan</div>
            <div class="text-sm font-bold text-ink">14 Sesi Perkuliahan + UTS/UAS</div>
          </div>
          <div class="p-4 rounded-lg bg-[var(--color-surface-alt)] space-y-1">
            <div class="text-xs text-muted font-semibold">Bobot Standar</div>
            <div class="text-sm font-bold text-ink">Tugas 30%, Quiz 20%, Presensi 10%, Ujian 40%</div>
          </div>
        </div>
      </div>

      <!-- Tab 2: Peserta Kelas -->
      <div class="tab-content-panel hidden" id="tab-peserta">
        <div class="p-5 border-b border-[var(--color-border-subtle)] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="w-full sm:max-w-sm">
            <label for="participants-search" class="sr-only">Cari peserta berdasarkan nama atau NIM</label>
            <input id="participants-search" type="search" class="clarity-input" placeholder="Cari nama, NIM, email, atau prodi…">
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <label for="participants-per-page" class="text-xs font-semibold text-muted">Tampilkan</label>
            <select id="participants-per-page" class="clarity-input w-24 text-sm">
              <option value="10" selected>10</option>
              <option value="20">20</option>
              <option value="50">50</option>
            </select>
          </div>
        </div>
        <div id="participants-lecturer-strip"></div>
        <div id="participants-list" aria-live="polite">
          <div class="p-8 text-center text-sm text-secondary">Memuat daftar peserta…</div>
        </div>
        <div id="participants-pagination"></div>
      </div>

      <!-- Tab 3: Materi Pertemuan -->
      <div class="tab-content-panel hidden p-6" id="tab-materi">
        <div id="course-materials-list" aria-live="polite">
          <div class="text-sm text-secondary">Memuat daftar materi…</div>
        </div>
      </div>

      <!-- Tab 4: Tugas & Quiz -->
      <div class="tab-content-panel hidden p-6" id="tab-tugas">
        <div id="course-assignments-list" aria-live="polite">
          <div class="text-sm text-secondary">Memuat daftar tugas dan quiz…</div>
        </div>
      </div>
    </section>

  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/mock-data.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/course-detail.js?v=12"></script>
</body>
</html>
