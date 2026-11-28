<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Dashboard Mahasiswa';
$pageDescription = 'Ringkasan progres mata kuliah, kalender aktivitas, tugas mendatang, dan nilai mahasiswa Clarity E-Learning.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="mahasiswa" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="dashboard" data-title="Dashboard" data-subtitle="Ringkasan belajar dan jadwal akademik">
  <div id="page-content" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      <!-- Kolom Kiri (8/12): Banner Selamat Datang, Mata Kuliah & Grafik Nilai -->
      <div class="lg:col-span-8 space-y-6">

        <!-- Banner Merah Minimalis Clarity -->
        <section class="clarity-welcome-banner hallmark-reveal">
          <div class="min-w-0 space-y-4">
            <div class="space-y-1.5">
              <h1 class="text-xl sm:text-2xl font-extrabold text-inherit m-0">
                Selamat datang kembali, <span data-user-first-name>Mahasiswa</span>
              </h1>
              <p class="text-sm text-inherit opacity-90 max-w-lg m-0">
                Progres belajarmu mencapai 78% semester ini. Lanjutkan satu tugas hari ini agar tetap tepat waktu.
              </p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
              <a class="btn-banner-white" href="assignment.php">Kumpulkan Tugas</a>
              <a class="inline-flex items-center min-h-[44px] px-2 text-sm font-semibold text-inherit underline underline-offset-4 hover:opacity-85" href="attendance.php">Isi Presensi</a>
            </div>
          </div>
          <img class="clarity-welcome-mascot" src="<?= $assetPrefix ?>assets/img/Target-Swift Owl Mascot.png" alt="Maskot Clarity menyemangati belajar" width="128" height="128">
        </section>

        <!-- Grid Mata Kuliah Kamu (Putih + Tint Lembut + Progress Merah) -->
        <section class="hallmark-reveal stagger-1 space-y-4">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-lg font-extrabold text-ink m-0">Mata Kuliah Kamu</h2>
            <a class="clarity-inline-link" href="courses.php">Lihat semua</a>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-2 gap-4">
            <a class="course-tile-card" href="course-detail.php?id=1">
              <div class="course-tile-cover course-tile-cover-1">
                <div class="flex items-center justify-between gap-2 text-xs font-semibold text-accent">
                  <span class="font-mono">07228204</span>
                  <span>3 SKS</span>
                </div>
                <div class="text-xs font-medium text-secondary">T. Brenda Chandrawati</div>
              </div>
              <div class="px-2 pb-2 space-y-3">
                <div class="text-base font-bold text-ink leading-snug">Manajemen Proyek 07</div>
                <div class="space-y-1.5">
                  <div class="course-progress-bar"><div class="course-progress-fill" style="width:78%"></div></div>
                  <div class="flex items-center justify-between text-xs font-semibold text-muted">
                    <span>Selesai 78%</span>
                    <span>Pertemuan 10/14</span>
                  </div>
                </div>
              </div>
            </a>

            <a class="course-tile-card" href="course-detail.php?id=2">
              <div class="course-tile-cover course-tile-cover-2">
                <div class="flex items-center justify-between gap-2 text-xs font-semibold text-accent">
                  <span class="font-mono">07228239</span>
                  <span>3 SKS</span>
                </div>
                <div class="text-xs font-medium text-secondary">Stephani Inggrit S. D.</div>
              </div>
              <div class="px-2 pb-2 space-y-3">
                <div class="text-base font-bold text-ink leading-snug">Teknologi Keuangan 05</div>
                <div class="space-y-1.5">
                  <div class="course-progress-bar"><div class="course-progress-fill" style="width:65%"></div></div>
                  <div class="flex items-center justify-between text-xs font-semibold text-muted">
                    <span>Selesai 65%</span>
                    <span>Pertemuan 9/14</span>
                  </div>
                </div>
              </div>
            </a>

            <a class="course-tile-card" href="course-detail.php?id=3">
              <div class="course-tile-cover course-tile-cover-3">
                <div class="flex items-center justify-between gap-2 text-xs font-semibold text-accent">
                  <span class="font-mono">07228203</span>
                  <span>3 SKS</span>
                </div>
                <div class="text-xs font-medium text-secondary">Yimmy Iskandar</div>
              </div>
              <div class="px-2 pb-2 space-y-3">
                <div class="text-base font-bold text-ink leading-snug">Etika Profesi 07</div>
                <div class="space-y-1.5">
                  <div class="course-progress-bar"><div class="course-progress-fill" style="width:50%"></div></div>
                  <div class="flex items-center justify-between text-xs font-semibold text-muted">
                    <span>Selesai 50%</span>
                    <span>Pertemuan 7/14</span>
                  </div>
                </div>
              </div>
            </a>

            <a class="course-tile-card" href="course-detail.php?id=4">
              <div class="course-tile-cover course-tile-cover-4">
                <div class="flex items-center justify-between gap-2 text-xs font-semibold text-accent">
                  <span class="font-mono">07228250</span>
                  <span>3 SKS</span>
                </div>
                <div class="text-xs font-medium text-secondary">Andre Kurniawan P.</div>
              </div>
              <div class="px-2 pb-2 space-y-3">
                <div class="text-base font-bold text-ink leading-snug">Kota Cerdas 03</div>
                <div class="space-y-1.5">
                  <div class="course-progress-bar"><div class="course-progress-fill" style="width:90%"></div></div>
                  <div class="flex items-center justify-between text-xs font-semibold text-muted">
                    <span>Selesai 90%</span>
                    <span>Pertemuan 12/14</span>
                  </div>
                </div>
              </div>
            </a>
          </div>
        </section>

        <!-- Performa Nilai vs Rata-Rata Kelas -->
        <section class="clarity-card hallmark-reveal stagger-2 overflow-hidden">
          <div class="clarity-section-head">
            <div>
              <h2 class="text-lg font-extrabold text-ink m-0">Aktivitas & Nilai Kamu</h2>
              <p class="text-xs text-muted m-0">Perbandingan capaian nilai dengan rata-rata kelas</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold text-muted">
              <span class="inline-flex items-center gap-1.5"><span class="legend-swatch legend-swatch-self"></span>Saya</span>
              <span class="inline-flex items-center gap-1.5"><span class="legend-swatch legend-swatch-class"></span>Kelas</span>
              <a class="clarity-inline-link" href="grades.php">Detail nilai</a>
            </div>
          </div>

          <div class="p-6 space-y-4">
            <div class="space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="font-bold text-ink truncate">Manajemen Proyek 07</span>
                <span class="font-mono tabular-nums"><strong class="text-accent">86</strong> <span class="text-muted">/ 79</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:79%"></div>
                <div class="grade-bar-self" style="width:86%"></div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="font-bold text-ink truncate">Teknologi Keuangan 05</span>
                <span class="font-mono tabular-nums"><strong class="text-accent">82</strong> <span class="text-muted">/ 76</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:76%"></div>
                <div class="grade-bar-self" style="width:82%"></div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="font-bold text-ink truncate">Etika Profesi 07</span>
                <span class="font-mono tabular-nums"><strong class="text-accent">78</strong> <span class="text-muted">/ 75</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:75%"></div>
                <div class="grade-bar-self" style="width:78%"></div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="font-bold text-ink truncate">Kota Cerdas 03</span>
                <span class="font-mono tabular-nums"><strong class="text-accent">91</strong> <span class="text-muted">/ 81</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:81%"></div>
                <div class="grade-bar-self" style="width:91%"></div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Kolom Kanan (4/12): Mini Kalender + Tugas Mendatang + Notifikasi -->
      <div class="lg:col-span-4 space-y-6">

        <!-- Mini Kalender Progres -->
        <section class="clarity-card hallmark-reveal p-5 space-y-4">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-extrabold text-ink m-0">Kalender Belajar</h2>
            <a class="clarity-inline-link text-xs" id="mini-cal-month" href="calendar.php">Bulan ini</a>
          </div>
          <div id="mini-cal-grid" class="mini-cal-grid" aria-label="Kalender aktivitas bulan ini"></div>
          <div class="flex items-center justify-end gap-2 text-xs text-muted pt-1">
            <span>Rendah</span>
            <span class="mini-cal-swatch bg-[var(--color-accent-soft)]"></span>
            <span class="mini-cal-swatch bg-[var(--color-accent-soft-2)]"></span>
            <span class="mini-cal-swatch bg-[var(--color-accent)]"></span>
            <span>Padat</span>
          </div>
        </section>

        <!-- Tugas & Quiz Mendatang (Tanpa Sticker Tag) -->
        <section class="hallmark-reveal stagger-1 space-y-3">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-lg font-extrabold text-ink m-0">Tugas Mendatang</h2>
            <div class="filter-tab-group" role="group" aria-label="Filter Tugas">
              <button type="button" class="filter-tab-btn is-active" data-task-filter="pending" aria-pressed="true">Aktif</button>
              <button type="button" class="filter-tab-btn" data-task-filter="done" aria-pressed="false">Selesai</button>
              <button type="button" class="filter-tab-btn" data-task-filter="all" aria-pressed="false">Semua</button>
            </div>
          </div>

          <div class="task-float-list">
            <a class="task-float-item" href="assignment.php" data-task-item="urgent pending">
              <div class="flex items-center gap-3 min-w-0">
                <span class="task-icon-box task-icon-red" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-bold text-ink truncate">Tugas 4: REST API Auth & JWT</div>
                  <div class="text-xs text-muted truncate"><time>Besok, 23:59 WIB</time> | Manajemen Proyek</div>
                </div>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted shrink-0" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>

            <a class="task-float-item" href="quiz-intro.php" data-task-item="pending">
              <div class="flex items-center gap-3 min-w-0">
                <span class="task-icon-box task-icon-amber" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-bold text-ink truncate">Quiz 1: Fondasi REST API</div>
                  <div class="text-xs text-muted truncate"><time>Tutup 10 Des</time> | 30 menit</div>
                </div>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted shrink-0" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>

            <a class="task-float-item" href="assignment.php" data-task-item="pending">
              <div class="flex items-center gap-3 min-w-0">
                <span class="task-icon-box task-icon-slate" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-bold text-ink truncate">Diagram Sequence Payment</div>
                  <div class="text-xs text-muted truncate"><time>Selasa, 8 Des</time> | Teknologi Keuangan</div>
                </div>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted shrink-0" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>

            <a class="task-float-item" href="assignment.php" data-task-item="done" hidden>
              <div class="flex items-center gap-3 min-w-0">
                <span class="task-icon-box task-icon-green" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-bold text-ink truncate">Laporan Etika Sistem Cerdas</div>
                  <div class="text-xs text-success truncate">Sudah dikumpulkan</div>
                </div>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted shrink-0" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </div>
        </section>

        <!-- Ringkasan Kehadiran & Notifikasi Cepat -->
        <section class="hallmark-reveal stagger-2 space-y-3">
          <div class="flex items-center justify-between gap-2">
            <h2 class="text-base font-extrabold text-ink m-0">Notifikasi Terbaru</h2>
            <a class="clarity-inline-link text-xs" href="notifications.php">Lihat semua</a>
          </div>

          <div class="task-float-list">
            <a class="task-float-item" href="attendance.php">
              <div class="flex items-center gap-3 min-w-0">
                <span class="task-icon-box task-icon-red" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-semibold text-ink truncate">Presensi Pertemuan 10 Dibuka</div>
                  <div class="text-xs text-muted truncate">Check-in sebelum 15:00 WIB</div>
                </div>
              </div>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted shrink-0" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>

            <a class="task-float-item" href="grades.php">
              <div class="flex items-center gap-3 min-w-0">
                <span class="task-icon-box task-icon-green" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-semibold text-ink truncate">Nilai Tugas 3 Keluar (92)</div>
                  <div class="text-xs text-muted truncate">Manajemen Proyek 07</div>
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
