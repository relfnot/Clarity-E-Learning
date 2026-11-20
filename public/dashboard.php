<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Dashboard Mahasiswa';
$pageDescription = 'Ringkasan antrean tugas, quiz, progres mata kuliah, perbandingan nilai, dan notifikasi mahasiswa Clarity E-Learning.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="mahasiswa" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="dashboard" data-title="Dashboard Mahasiswa" data-subtitle="Ringkasan fokus akademik minggu ini">
  <div id="page-content" class="space-y-6">

    <!-- Baris 1: Header Sapaan + XP + 4 Metrik Inti + Aksi Cepat -->
    <section class="clarity-card hallmark-reveal p-6 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">
      <div class="flex items-center gap-4 min-w-0">
        <img class="w-14 h-14 object-contain shrink-0" src="<?= $assetPrefix ?>assets/img/Target-Swift Owl Mascot.png" alt="Maskot Clarity" width="56" height="56">
        <div class="min-w-0 space-y-2">
          <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl sm:text-2xl font-extrabold text-ink m-0">
              Halo, <span data-user-first-name>Mahasiswa</span>
            </h1>
            <span class="course-code-pill" data-user-nim>-</span>
          </div>
          <div class="flex items-center gap-3 flex-wrap">
            <span class="clarity-tag clarity-tag-warning">Level 7</span>
            <div class="flex items-center gap-2">
              <div class="xp-bar-track" role="progressbar" aria-label="Progress XP Level 7" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100">
                <div class="xp-bar-fill" style="width:72%"></div>
              </div>
              <span class="text-sm font-semibold text-muted tabular-nums">1.450 / 2.000 XP</span>
            </div>
          </div>
        </div>
      </div>

      <div class="stat-ledger">
        <div class="stat-cell">
          <div class="stat-label">Matkul aktif</div>
          <div class="stat-value text-ink">4</div>
        </div>
        <div class="stat-cell">
          <div class="stat-label">Tugas belum</div>
          <div class="stat-value text-accent">2</div>
        </div>
        <div class="stat-cell">
          <div class="stat-label">Rata-rata nilai</div>
          <div class="stat-value text-success">84,3</div>
        </div>
        <div class="stat-cell">
          <div class="stat-label">Kehadiran</div>
          <div class="stat-value text-ink">93%</div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3 shrink-0">
        <a class="clarity-btn clarity-btn-outline" href="attendance.php">Isi presensi</a>
        <a class="clarity-btn clarity-btn-primary" href="assignment.php">Kumpulkan tugas</a>
      </div>
    </section>

    <!-- Baris 2: Workbench Akademik 12 Kolom -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      <!-- Kolom Kiri (7/12): Antrean Tugas & Quiz + Mata Kuliah -->
      <div class="lg:col-span-7 space-y-6">

        <!-- Antrean Tugas & Quiz Minggu Ini -->
        <section class="clarity-card hallmark-reveal stagger-1 overflow-hidden">
          <div class="clarity-section-head">
            <h2 class="text-lg font-bold text-ink m-0">Antrean Tugas dan Quiz</h2>
            <div class="filter-tab-group" role="group" aria-label="Filter Tugas">
              <button type="button" class="filter-tab-btn is-active" data-task-filter="pending" aria-pressed="true">Aktif (3)</button>
              <button type="button" class="filter-tab-btn" data-task-filter="done" aria-pressed="false">Selesai (1)</button>
              <button type="button" class="filter-tab-btn" data-task-filter="all" aria-pressed="false">Semua</button>
            </div>
          </div>

          <div class="clarity-list">
            <article class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="urgent pending">
              <div class="min-w-0 space-y-1.5">
                <h3 class="clarity-row-title text-base font-bold text-ink m-0">
                  <a class="text-inherit no-underline hover:no-underline" href="assignment.php">Tugas 4: Implementasi REST API Auth dan JWT</a>
                </h3>
                <div class="meta-row">
                  <span class="meta-item">Manajemen Proyek 07</span>
                  <span class="meta-item"><time>Besok, 23:59 WIB</time></span>
                  <span class="meta-item">+30 XP</span>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <span class="clarity-tag clarity-tag-danger" role="status">H-1</span>
                <a class="clarity-btn clarity-btn-primary clarity-btn-sm" href="assignment.php">Kumpulkan</a>
              </div>
            </article>

            <article class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="pending">
              <div class="min-w-0 space-y-1.5">
                <h3 class="clarity-row-title text-base font-bold text-ink m-0">
                  <a class="text-inherit no-underline hover:no-underline" href="assignment.php">Studi Kasus Diagram Sequence dan Kontrak API</a>
                </h3>
                <div class="meta-row">
                  <span class="meta-item">Teknologi Keuangan 05</span>
                  <span class="meta-item"><time>Selasa, 8 Des 23:59 WIB</time></span>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <span class="clarity-tag clarity-tag-warning" role="status">H-3</span>
                <a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="assignment.php">Buka tugas</a>
              </div>
            </article>

            <article class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="pending">
              <div class="min-w-0 space-y-1.5">
                <h3 class="clarity-row-title text-base font-bold text-ink m-0">
                  <a class="text-inherit no-underline hover:no-underline" href="quiz-intro.php">Quiz 2: Autentikasi JWT, REST API dan Keamanan</a>
                </h3>
                <div class="meta-row">
                  <span class="meta-item">Manajemen Proyek 07</span>
                  <span class="meta-item"><time>Tutup 7 Des</time></span>
                  <span class="meta-item">30 menit</span>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <span class="clarity-tag clarity-tag-info" role="status">Quiz</span>
                <a class="clarity-btn clarity-btn-gold clarity-btn-sm" href="quiz-intro.php">Mulai quiz</a>
              </div>
            </article>

            <article class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="done" hidden>
              <div class="min-w-0 space-y-1.5">
                <h3 class="clarity-row-title text-base font-bold text-ink m-0">
                  <a class="text-inherit no-underline hover:no-underline" href="assignment.php">Laporan Studi Kasus Etika Sistem Cerdas</a>
                </h3>
                <div class="meta-row">
                  <span class="meta-item">Etika Profesi 07</span>
                  <span class="meta-item">Sudah dikumpulkan</span>
                  <span class="meta-item"><time>Batas 12 Des</time></span>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <span class="clarity-tag clarity-tag-success" role="status">Terkirim</span>
              </div>
            </article>
          </div>
        </section>

        <!-- Mata Kuliah Diambil (Grid 2x2 Proporsional) -->
        <section class="hallmark-reveal stagger-2 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-ink m-0">Mata Kuliah Diambil</h2>
            <a class="clarity-inline-link" href="courses.php">Lihat semua</a>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a class="clarity-card clarity-hover-card p-5 flex items-center justify-between gap-4 no-underline text-inherit" href="course-detail.php">
              <div class="min-w-0 space-y-2">
                <div class="flex items-center gap-2">
                  <span class="course-code-pill">07228204</span>
                  <span class="text-xs font-semibold text-muted">3 SKS</span>
                </div>
                <div class="clarity-card-title text-base font-bold text-ink leading-snug">Manajemen Proyek 07</div>
                <div class="text-sm text-muted truncate">Jumat 13:00 (T. Brenda)</div>
              </div>
              <div data-ring="78" data-ring-size="56"></div>
            </a>

            <a class="clarity-card clarity-hover-card p-5 flex items-center justify-between gap-4 no-underline text-inherit" href="course-detail.php">
              <div class="min-w-0 space-y-2">
                <div class="flex items-center gap-2">
                  <span class="course-code-pill">07228239</span>
                  <span class="text-xs font-semibold text-muted">3 SKS</span>
                </div>
                <div class="clarity-card-title text-base font-bold text-ink leading-snug">Teknologi Keuangan 05</div>
                <div class="text-sm text-muted truncate">Jumat 07:00 (Stephani I.)</div>
              </div>
              <div data-ring="65" data-ring-size="56"></div>
            </a>

            <a class="clarity-card clarity-hover-card p-5 flex items-center justify-between gap-4 no-underline text-inherit" href="course-detail.php">
              <div class="min-w-0 space-y-2">
                <div class="flex items-center gap-2">
                  <span class="course-code-pill">07228203</span>
                  <span class="text-xs font-semibold text-muted">3 SKS</span>
                </div>
                <div class="clarity-card-title text-base font-bold text-ink leading-snug">Etika Profesi 07</div>
                <div class="text-sm text-muted truncate">Senin 13:00 (Yimmy Iskandar)</div>
              </div>
              <div data-ring="50" data-ring-size="56"></div>
            </a>

            <a class="clarity-card clarity-hover-card p-5 flex items-center justify-between gap-4 no-underline text-inherit" href="course-detail.php">
              <div class="min-w-0 space-y-2">
                <div class="flex items-center gap-2">
                  <span class="course-code-pill">07228250</span>
                  <span class="text-xs font-semibold text-muted">3 SKS</span>
                </div>
                <div class="clarity-card-title text-base font-bold text-ink leading-snug">Kota Cerdas 03</div>
                <div class="text-sm text-muted truncate">Senin 08:00 (Andre K. P.)</div>
              </div>
              <div data-ring="90" data-ring-size="56"></div>
            </a>
          </div>
        </section>
      </div>

      <!-- Kolom Kanan (5/12): Nilai vs Kelas + Notifikasi Terbaru -->
      <div class="lg:col-span-5 space-y-6">

        <!-- Perbandingan Nilai dengan Kelas -->
        <section class="clarity-card hallmark-reveal stagger-2 overflow-hidden">
          <div class="clarity-section-head">
            <h2 class="text-lg font-bold text-ink m-0">Nilai vs Rata-Rata Kelas</h2>
            <div class="flex items-center gap-3 text-sm font-semibold text-muted">
              <span class="inline-flex items-center gap-1.5"><span class="legend-swatch legend-swatch-self"></span>Saya</span>
              <span class="inline-flex items-center gap-1.5"><span class="legend-swatch legend-swatch-class"></span>Kelas</span>
            </div>
          </div>

          <div class="p-6 space-y-4">
            <div class="space-y-2">
              <div class="flex items-center justify-between text-base">
                <span class="font-bold text-ink truncate">Manajemen Proyek 07</span>
                <span class="font-mono text-sm tabular-nums"><strong class="text-accent">86</strong> <span class="text-muted">/ 79</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:79%"></div>
                <div class="grade-bar-self" style="width:86%"></div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between text-base">
                <span class="font-bold text-ink truncate">Teknologi Keuangan 05</span>
                <span class="font-mono text-sm tabular-nums"><strong class="text-accent">82</strong> <span class="text-muted">/ 76</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:76%"></div>
                <div class="grade-bar-self" style="width:82%"></div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between text-base">
                <span class="font-bold text-ink truncate">Etika Profesi 07</span>
                <span class="font-mono text-sm tabular-nums"><strong class="text-accent">78</strong> <span class="text-muted">/ 75</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:75%"></div>
                <div class="grade-bar-self" style="width:78%"></div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between text-base">
                <span class="font-bold text-ink truncate">Kota Cerdas 03</span>
                <span class="font-mono text-sm tabular-nums"><strong class="text-accent">91</strong> <span class="text-muted">/ 81</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:81%"></div>
                <div class="grade-bar-self" style="width:91%"></div>
              </div>
            </div>
          </div>
        </section>

        <!-- Notifikasi Terbaru -->
        <section class="clarity-card hallmark-reveal stagger-3 overflow-hidden">
          <div class="clarity-section-head">
            <h2 class="text-lg font-bold text-ink m-0">Notifikasi Terbaru</h2>
            <a class="clarity-inline-link" href="notifications.php">Semua (3)</a>
          </div>
          <div class="clarity-list">
            <a class="clarity-hover-row flex items-center justify-between gap-3 px-6 py-4 no-underline text-inherit" href="assignment.php">
              <div class="flex items-start gap-3 min-w-0">
                <span class="notif-indicator notif-indicator-urgent" aria-hidden="true"></span>
                <div class="min-w-0 space-y-1">
                  <div class="clarity-row-title text-base font-bold text-ink truncate">Pengingat H-1: Tugas 4 REST API Auth</div>
                  <div class="meta-row text-sm">
                    <span class="meta-item"><time>08:00 WIB</time></span>
                    <span class="meta-item">Batas besok 23:59 WIB</span>
                  </div>
                </div>
              </div>
              <span class="text-sm font-semibold text-accent shrink-0">Buka</span>
            </a>

            <a class="clarity-hover-row flex items-center justify-between gap-3 px-6 py-4 no-underline text-inherit" href="attendance.php">
              <div class="flex items-start gap-3 min-w-0">
                <span class="notif-indicator notif-indicator-urgent" aria-hidden="true"></span>
                <div class="min-w-0 space-y-1">
                  <div class="clarity-row-title text-base font-bold text-ink truncate">Presensi Pertemuan 10 (07228204) Dibuka</div>
                  <div class="meta-row text-sm">
                    <span class="meta-item"><time>07:55 WIB</time></span>
                    <span class="meta-item">Check-in sebelum 15:00 WIB</span>
                  </div>
                </div>
              </div>
              <span class="text-sm font-semibold text-accent shrink-0">Buka</span>
            </a>

            <a class="clarity-hover-row flex items-center justify-between gap-3 px-6 py-4 no-underline text-inherit" href="grades.php">
              <div class="flex items-start gap-3 min-w-0">
                <span class="notif-indicator notif-indicator-success" aria-hidden="true"></span>
                <div class="min-w-0 space-y-1">
                  <div class="clarity-row-title text-base font-bold text-ink truncate">Nilai Keluar: Kota Cerdas 03 (92/100)</div>
                  <div class="meta-row text-sm">
                    <span class="meta-item"><time>Kemarin</time></span>
                    <span class="meta-item">Dinilai Andre Kurniawan P.</span>
                  </div>
                </div>
              </div>
              <span class="text-sm font-semibold text-accent shrink-0">Buka</span>
            </a>
          </div>
        </section>
      </div>
    </div>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=11"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=11"></script>
  <script src="<?= $assetPrefix ?>assets/js/mock-data.js?v=11"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=11"></script>
  <script src="<?= $assetPrefix ?>assets/js/dashboard.js?v=11"></script>
</body>
</html>
