<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Dashboard Dosen';
$pageDescription = 'Ringkasan kelas yang diampu, jadwal pertemuan, antrean penilaian tugas, dan mahasiswa berisiko Clarity E-Learning.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="dashboard" data-title="Dashboard Dosen" data-subtitle="Ringkasan kelas dan antrean penilaian">
  <div id="page-content" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      <!-- Kolom Kiri (8/12): Banner Dosen, Kelas Diampu & Antrean Penilaian -->
      <div class="lg:col-span-8 space-y-6">

        <!-- Banner Merah Minimalis Dosen -->
        <section class="clarity-welcome-banner hallmark-reveal">
          <div class="min-w-0 space-y-4">
            <div class="space-y-1.5">
              <h1 class="text-xl sm:text-2xl font-extrabold text-inherit m-0">
                Selamat datang, <span data-user-full-name>Dosen Pengampu</span>
              </h1>
              <p class="text-sm text-inherit opacity-90 max-w-lg m-0">
                Pantau kehadiran pertemuan, periksa pengumpulan terbaru, dan kelola quiz kelas Anda dari satu tempat.
              </p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
              <a class="btn-banner-white" href="attendance-manage.php">Kelola Presensi</a>
              <a class="inline-flex items-center min-h-[44px] px-2 text-sm font-semibold text-inherit underline underline-offset-4 hover:opacity-85" href="material-upload.php">Unggah Materi</a>
            </div>
          </div>
          <img class="clarity-welcome-mascot" src="<?= $assetPrefix ?>assets/img/Chibi Owl Mascot Waving Cheerfully.png" alt="Maskot Clarity menyapa dosen" width="128" height="128">
        </section>

        <!-- Kelas yang Diampu -->
        <section class="hallmark-reveal stagger-1 space-y-4">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-lg font-extrabold text-ink m-0">Kelas yang Diampu</h2>
            <a class="clarity-inline-link" href="course-manage.php">Kelola semua</a>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <article class="course-tile-card">
              <div class="course-tile-cover course-tile-cover-1">
                <div class="flex items-center justify-between gap-2 text-xs font-semibold text-accent">
                  <span class="font-mono">07228204</span>
                  <span>32 Mahasiswa</span>
                </div>
                <div class="text-xs font-medium text-secondary">Jumat 13:00 WIB | Ruang 8.1</div>
              </div>
              <div class="px-2 pb-2 space-y-3">
                <h3 class="text-base font-bold text-ink leading-snug m-0">Manajemen Proyek 07</h3>
                <div class="space-y-1.5">
                  <div class="course-progress-bar"><div class="course-progress-fill" style="width:72%"></div></div>
                  <div class="flex items-center justify-between text-xs font-semibold text-muted">
                    <span>Pertemuan 10/14</span>
                    <span>Kehadiran 92%</span>
                  </div>
                </div>
                <div class="flex items-center gap-2 pt-1">
                  <a class="clarity-btn clarity-btn-primary clarity-btn-sm flex-1" href="course-manage.php">Kelola Kelas</a>
                  <a class="clarity-btn clarity-btn-outline clarity-btn-sm flex-1" href="quiz-builder.php">Buat Quiz</a>
                </div>
              </div>
            </article>

            <article class="course-tile-card">
              <div class="course-tile-cover course-tile-cover-2">
                <div class="flex items-center justify-between gap-2 text-xs font-semibold text-accent">
                  <span class="font-mono">07228204</span>
                  <span>Tugas & Quiz</span>
                </div>
                <div class="text-xs font-medium text-secondary">Pantau pengumpulan tepat & telat</div>
              </div>
              <div class="px-2 pb-2 space-y-3">
                <h3 class="text-base font-bold text-ink leading-snug m-0">Rekap Pengumpulan & Nilai</h3>
                <p class="text-xs text-muted m-0">Lihat status pengumpulan seluruh peserta atau beri nilai esai quiz dan tugas mingguan.</p>
                <div class="flex items-center gap-2 pt-1">
                  <a class="clarity-btn clarity-btn-outline clarity-btn-sm flex-1" href="submissions.php">Pengumpulan</a>
                  <a class="clarity-btn clarity-btn-outline clarity-btn-sm flex-1" href="grading.php">Penilaian</a>
                </div>
              </div>
            </article>
          </div>
        </section>

        <!-- Antrean Penilaian Tugas -->
        <section class="clarity-card hallmark-reveal stagger-2 overflow-hidden">
          <div class="clarity-section-head">
            <h2 class="text-lg font-extrabold text-ink m-0">Antrean Penilaian Tugas</h2>
            <div class="filter-tab-group" role="group" aria-label="Filter Penilaian">
              <button type="button" class="filter-tab-btn is-active" data-task-filter="pending" aria-pressed="true">Perlu dinilai</button>
              <button type="button" class="filter-tab-btn" data-task-filter="done" aria-pressed="false">Selesai</button>
              <button type="button" class="filter-tab-btn" data-task-filter="all" aria-pressed="false">Semua</button>
            </div>
          </div>

          <div class="clarity-list">
            <div class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="pending">
              <div class="flex items-center gap-3 min-w-0">
                <span class="avatar-chip" aria-hidden="true">AM</span>
                <div class="min-w-0 space-y-0.5">
                  <div class="clarity-row-title text-sm font-bold text-ink truncate">Adonai Shan Matthew</div>
                  <div class="text-xs text-muted truncate"><span class="font-mono">24.N1.0043</span> | Tugas 4 REST API Auth</div>
                </div>
              </div>
              <a class="clarity-btn clarity-btn-primary clarity-btn-sm shrink-0" href="grading.php">Beri Nilai</a>
            </div>

            <div class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="pending">
              <div class="flex items-center gap-3 min-w-0">
                <span class="avatar-chip" aria-hidden="true">YS</span>
                <div class="min-w-0 space-y-0.5">
                  <div class="clarity-row-title text-sm font-bold text-ink truncate">Yohanes Setiawan</div>
                  <div class="text-xs text-muted truncate"><span class="font-mono">24.N1.0012</span> | Tugas 4 REST API Auth</div>
                </div>
              </div>
              <a class="clarity-btn clarity-btn-primary clarity-btn-sm shrink-0" href="grading.php">Beri Nilai</a>
            </div>

            <div class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="done" hidden>
              <div class="flex items-center gap-3 min-w-0">
                <span class="avatar-chip" aria-hidden="true">DR</span>
                <div class="min-w-0 space-y-0.5">
                  <div class="clarity-row-title text-sm font-bold text-ink truncate">Dimas Rizky Fajar Pamungkas</div>
                  <div class="text-xs text-muted truncate"><span class="font-mono">24.N1.0026</span> | Nilai 90</div>
                </div>
              </div>
              <a class="clarity-btn clarity-btn-outline clarity-btn-sm shrink-0" href="grading.php">Ubah Nilai</a>
            </div>

            <div class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="done" hidden>
              <div class="flex items-center gap-3 min-w-0">
                <span class="avatar-chip" aria-hidden="true">GK</span>
                <div class="min-w-0 space-y-0.5">
                  <div class="clarity-row-title text-sm font-bold text-ink truncate">Gabriel Kresnoputero W. B.</div>
                  <div class="text-xs text-muted truncate"><span class="font-mono">24.N2.0008</span> | Nilai 94</div>
                </div>
              </div>
              <a class="clarity-btn clarity-btn-outline clarity-btn-sm shrink-0" href="grading.php">Ubah Nilai</a>
            </div>
          </div>
        </section>
      </div>

      <!-- Kolom Kanan (4/12): Mini Kalender + Ringkasan + Mahasiswa Berisiko -->
      <div class="lg:col-span-4 space-y-6">

        <!-- Mini Kalender Mengajar -->
        <section class="clarity-card hallmark-reveal p-5 space-y-4">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-extrabold text-ink m-0">Kalender Mengajar</h2>
            <a class="clarity-inline-link text-xs" id="mini-cal-month" href="calendar.php">Bulan ini</a>
          </div>
          <div id="mini-cal-grid" class="mini-cal-grid" aria-label="Kalender perkuliahan bulan ini"></div>
          <div class="flex items-center justify-end gap-2 text-xs text-muted pt-1">
            <span>Rendah</span>
            <span class="mini-cal-swatch bg-[var(--color-accent-soft)]"></span>
            <span class="mini-cal-swatch bg-[var(--color-accent-soft-2)]"></span>
            <span class="mini-cal-swatch bg-[var(--color-accent)]"></span>
            <span>Padat</span>
          </div>
          <div class="grid grid-cols-2 gap-3 pt-2 border-t border-[var(--color-border-subtle)] text-sm">
            <div>
              <div class="text-xs text-muted">Presensi Aktif</div>
              <div class="font-extrabold text-ink tabular-nums">28 / 32</div>
            </div>
            <div>
              <div class="text-xs text-muted">Perlu Dinilai</div>
              <div class="font-extrabold text-accent tabular-nums">14 Berkas</div>
            </div>
          </div>
        </section>

        <!-- Mahasiswa Berisiko (Tanpa Sticker Tag) -->
        <section class="hallmark-reveal stagger-1 space-y-3">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-extrabold text-ink m-0">Perlu Perhatian</h2>
            <a class="clarity-inline-link text-xs" href="announcement.php">Kirim pengumuman</a>
          </div>

          <div class="task-float-list">
            <a class="task-float-item" href="announcement.php">
              <div class="flex items-center gap-3 min-w-0">
                <span class="task-icon-box task-icon-amber" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-bold text-ink truncate">Adonai Shan Matthew</div>
                  <div class="text-xs text-muted truncate">24.N1.0043 | Nilai rata-rata 66</div>
                </div>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted shrink-0" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>

            <a class="task-float-item" href="announcement.php">
              <div class="flex items-center gap-3 min-w-0">
                <span class="task-icon-box task-icon-red" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-bold text-ink truncate">Benedictus Kevin</div>
                  <div class="text-xs text-muted truncate">24.N1.0031 | Kehadiran 71%</div>
                </div>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted shrink-0" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </div>
        </section>

      </div>
    </div>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=14"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=14"></script>
  <script src="<?= $assetPrefix ?>assets/js/mock-data.js?v=14"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/dashboard.js?v=17"></script>
</body>
</html>
