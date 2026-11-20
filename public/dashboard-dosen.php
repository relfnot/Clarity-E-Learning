<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Dashboard Dosen';
$pageDescription = 'Ringkasan kelas yang diampu, antrean penilaian tugas, indikator mahasiswa berisiko, dan rekap nilai di Clarity E-Learning.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="dashboard" data-title="Dashboard Dosen" data-subtitle="Ringkasan kelas, penilaian, dan indikator risiko">
  <div id="page-content" class="space-y-6">

    <!-- Baris 1: Header Dosen + 4 Metrik Inti + Aksi Cepat -->
    <section class="clarity-card hallmark-reveal p-6 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">
      <div class="flex items-center gap-4 min-w-0">
        <img class="w-14 h-14 object-contain shrink-0" src="<?= $assetPrefix ?>assets/img/Chibi Owl Mascot Waving Cheerfully.png" alt="Maskot Clarity" width="56" height="56">
        <div class="min-w-0 space-y-1.5">
          <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl sm:text-2xl font-extrabold text-ink m-0">
              Halo, <span data-user-full-name>Dosen Pengampu</span>
            </h1>
            <span class="course-code-pill" data-user-nim>-</span>
          </div>
          <div class="meta-row">
            <span class="meta-item">Fakultas Ilmu Komputer</span>
            <span class="meta-item">Semester Ganjil 2026/2027</span>
          </div>
        </div>
      </div>

      <div class="stat-ledger">
        <div class="stat-cell">
          <div class="stat-label">Kelas diampu</div>
          <div class="stat-value text-ink">1</div>
        </div>
        <div class="stat-cell">
          <div class="stat-label">Perlu dinilai</div>
          <div class="stat-value text-warning">14</div>
        </div>
        <div class="stat-cell">
          <div class="stat-label">Presensi aktif</div>
          <div class="stat-value text-success">28/32</div>
        </div>
        <div class="stat-cell">
          <div class="stat-label">Berisiko</div>
          <div class="stat-value text-accent">2</div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-3 shrink-0">
        <a class="clarity-btn clarity-btn-outline" href="attendance-manage.php">Kelola presensi</a>
        <a class="clarity-btn clarity-btn-primary" href="material-upload.php">Unggah materi</a>
      </div>
    </section>

    <!-- Baris 2: Workbench Dosen 12 Kolom -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

      <!-- Kolom Kiri (7/12): Antrean Penilaian + Kelas yang Diampu -->
      <div class="lg:col-span-7 space-y-6">

        <!-- Antrean Penilaian & Pengumpulan -->
        <section class="clarity-card hallmark-reveal stagger-1 overflow-hidden">
          <div class="clarity-section-head">
            <h2 class="text-lg font-bold text-ink m-0">Antrean Penilaian Tugas</h2>
            <div class="filter-tab-group" role="group" aria-label="Filter Penilaian">
              <button type="button" class="filter-tab-btn is-active" data-task-filter="pending" aria-pressed="true">Perlu dinilai (2)</button>
              <button type="button" class="filter-tab-btn" data-task-filter="done" aria-pressed="false">Selesai (2)</button>
              <button type="button" class="filter-tab-btn" data-task-filter="all" aria-pressed="false">Semua</button>
            </div>
          </div>

          <div class="clarity-list">
            <article class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="pending">
              <div class="min-w-0 space-y-1">
                <div class="clarity-row-title text-base font-bold text-ink truncate">Adonai Shan Matthew</div>
                <div class="meta-row text-sm">
                  <span class="meta-item font-mono">24.N1.0043</span>
                  <span class="meta-item">Tugas 4 REST API Auth</span>
                  <span class="meta-item"><time>19:45 WIB</time></span>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <span class="clarity-tag clarity-tag-success" role="status">Tepat waktu</span>
                <a class="clarity-btn clarity-btn-primary clarity-btn-sm" href="grading.php">Beri nilai</a>
              </div>
            </article>

            <article class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="pending">
              <div class="min-w-0 space-y-1">
                <div class="clarity-row-title text-base font-bold text-ink truncate">Yohanes Setiawan</div>
                <div class="meta-row text-sm">
                  <span class="meta-item font-mono">24.N1.0012</span>
                  <span class="meta-item">Tugas 4 REST API Auth</span>
                  <span class="meta-item"><time>18:10 WIB</time></span>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <span class="clarity-tag clarity-tag-warning" role="status">Menunggu</span>
                <a class="clarity-btn clarity-btn-primary clarity-btn-sm" href="grading.php">Beri nilai</a>
              </div>
            </article>

            <article class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="done" hidden>
              <div class="min-w-0 space-y-1">
                <div class="clarity-row-title text-base font-bold text-ink truncate">Dimas Rizky Fajar Pamungkas</div>
                <div class="meta-row text-sm">
                  <span class="meta-item font-mono">24.N1.0026</span>
                  <span class="meta-item">Tugas 4 REST API Auth</span>
                  <span class="meta-item"><time>14:20 WIB</time></span>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <span class="clarity-tag clarity-tag-info" role="status">Nilai 90</span>
                <a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="grading.php">Ubah nilai</a>
              </div>
            </article>

            <article class="clarity-hover-row px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4" data-task-item="done" hidden>
              <div class="min-w-0 space-y-1">
                <div class="clarity-row-title text-base font-bold text-ink truncate">Gabriel Kresnoputero W. B.</div>
                <div class="meta-row text-sm">
                  <span class="meta-item font-mono">24.N2.0008</span>
                  <span class="meta-item">Tugas 4 REST API Auth</span>
                  <span class="meta-item"><time>11:05 WIB</time></span>
                </div>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <span class="clarity-tag clarity-tag-info" role="status">Nilai 94</span>
                <a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="grading.php">Ubah nilai</a>
              </div>
            </article>
          </div>
        </section>

        <!-- Kelas yang Diampu Semester Ini -->
        <section class="hallmark-reveal stagger-2 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-ink m-0">Kelas yang Diampu</h2>
            <a class="clarity-inline-link" href="course-manage.php">Kelola semua</a>
          </div>

          <div class="clarity-card clarity-hover-card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
            <div class="flex items-center gap-5 min-w-0">
              <div data-ring="78" data-ring-size="56"></div>
              <div class="min-w-0 space-y-2">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="course-code-pill">07228204</span>
                  <span class="clarity-tag clarity-tag-info">32 mahasiswa</span>
                  <span class="text-sm text-muted">Pertemuan 10 dari 14</span>
                </div>
                <h3 class="clarity-card-title text-lg font-extrabold text-ink m-0">
                  <a class="text-inherit no-underline hover:no-underline" href="course-manage.php">Manajemen Proyek 07</a>
                </h3>
                <div class="meta-row text-sm">
                  <span class="meta-item">Jumat, 13:00 - 15:00</span>
                  <span class="meta-item">Ruang 8.1</span>
                  <span class="meta-item">Kehadiran 92%</span>
                </div>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
              <a class="clarity-btn clarity-btn-gold clarity-btn-sm" href="course-manage.php">Kelola kelas</a>
              <a class="clarity-btn clarity-btn-outline clarity-btn-sm" href="quiz-builder.php">Buat quiz</a>
            </div>
          </div>
        </section>
      </div>

      <!-- Kolom Kanan (5/12): Mahasiswa Berisiko + Rekap Nilai Kelas -->
      <div class="lg:col-span-5 space-y-6">

        <!-- Indikator Mahasiswa Berisiko -->
        <section class="clarity-card hallmark-reveal stagger-2 overflow-hidden">
          <div class="clarity-section-head">
            <h2 class="text-lg font-bold text-ink m-0">Mahasiswa Berisiko</h2>
            <span class="clarity-tag clarity-tag-danger" role="status">2 perlu perhatian</span>
          </div>
          <div class="clarity-list">
            <div class="clarity-hover-row px-6 py-4 flex items-center justify-between gap-4">
              <div class="min-w-0 space-y-1">
                <div class="clarity-row-title text-base font-bold text-ink truncate">Adonai Shan Matthew</div>
                <div class="meta-row text-sm">
                  <span class="meta-item font-mono">24.N1.0043</span>
                  <span class="meta-item text-warning font-semibold">Nilai turun 22% (66)</span>
                </div>
              </div>
              <a class="clarity-btn clarity-btn-outline clarity-btn-sm shrink-0" href="announcement.php">Ingatkan</a>
            </div>

            <div class="clarity-hover-row px-6 py-4 flex items-center justify-between gap-4">
              <div class="min-w-0 space-y-1">
                <div class="clarity-row-title text-base font-bold text-ink truncate">Benedictus Kevin</div>
                <div class="meta-row text-sm">
                  <span class="meta-item font-mono">24.N1.0031</span>
                  <span class="meta-item text-accent font-semibold">Kehadiran 71%</span>
                </div>
              </div>
              <a class="clarity-btn clarity-btn-outline clarity-btn-sm shrink-0" href="announcement.php">Ingatkan</a>
            </div>
          </div>
        </section>

        <!-- Rekap Performa Kelas -->
        <section class="clarity-card hallmark-reveal stagger-3 overflow-hidden">
          <div class="clarity-section-head">
            <h2 class="text-lg font-bold text-ink m-0">Performa Kelas 07228204</h2>
            <a class="clarity-inline-link" href="submissions.php">Detail rekap</a>
          </div>
          <div class="p-6 space-y-4">
            <div class="space-y-2">
              <div class="flex items-center justify-between text-base">
                <span class="font-bold text-ink truncate">Tugas Mingguan</span>
                <span class="font-mono text-sm tabular-nums"><strong class="text-accent">84</strong> <span class="text-muted">/ 75 target</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:75%"></div>
                <div class="grade-bar-self" style="width:84%"></div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between text-base">
                <span class="font-bold text-ink truncate">Quiz Berwaktu</span>
                <span class="font-mono text-sm tabular-nums"><strong class="text-accent">78</strong> <span class="text-muted">/ 75 target</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:75%"></div>
                <div class="grade-bar-self" style="width:78%"></div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between text-base">
                <span class="font-bold text-ink truncate">Ujian Tengah Semester</span>
                <span class="font-mono text-sm tabular-nums"><strong class="text-accent">79</strong> <span class="text-muted">/ 75 target</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:75%"></div>
                <div class="grade-bar-self" style="width:79%"></div>
              </div>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between text-base">
                <span class="font-bold text-ink truncate">Tingkat Kehadiran</span>
                <span class="font-mono text-sm tabular-nums"><strong class="text-accent">92%</strong> <span class="text-muted">/ 80% min</span></span>
              </div>
              <div class="grade-bar-track">
                <div class="grade-bar-class" style="width:80%"></div>
                <div class="grade-bar-self" style="width:92%"></div>
              </div>
            </div>
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
