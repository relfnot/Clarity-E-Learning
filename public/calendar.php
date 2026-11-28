<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Kalender & Deadline';
$pageDescription = 'Jadwal pertemuan kuliah, tenggat waktu pengumpulan tugas, dan jendela quiz Clarity E-Learning dalam zona WIB.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="mahasiswa,dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="calendar" data-title="Kalender & Deadline" data-subtitle="Jadwal kuliah, tenggat tugas, dan quiz dalam zona WIB (+07:00)">
  <div id="page-content" class="space-y-6">

    <!-- Bar Kontrol Bulan, Filter Mata Kuliah & Mode Tampilan -->
    <section class="clarity-card hallmark-reveal p-5 space-y-4">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
          <button type="button" id="cal-prev-month" class="clarity-icon-btn" aria-label="Bulan sebelumnya" title="Bulan sebelumnya">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <button type="button" id="cal-today-btn" class="clarity-btn clarity-btn-outline clarity-btn-sm">Hari Ini</button>
          <button type="button" id="cal-next-month" class="clarity-icon-btn" aria-label="Bulan berikutnya" title="Bulan berikutnya">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
          <h1 id="cal-month-heading" class="text-xl sm:text-2xl font-extrabold text-ink m-0 ml-1">Memuat Bulan…</h1>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
          <div class="w-full sm:w-64">
            <label for="calendar-course-select" class="sr-only">Filter mata kuliah</label>
            <select id="calendar-course-select" class="clarity-input text-sm w-full">
              <option value="">Semua Mata Kuliah</option>
            </select>
          </div>
          <div class="filter-tab-group shrink-0" role="group" aria-label="Mode tampilan kalender">
            <button type="button" class="filter-tab-btn" data-cal-view="month" aria-pressed="false">Bulan</button>
            <button type="button" class="filter-tab-btn" data-cal-view="agenda" aria-pressed="false">Agenda</button>
          </div>
        </div>
      </div>

      <!-- Legenda & Filter Tipe Agenda (Warna + Ikon) -->
      <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-[var(--color-border-subtle)]">
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter tipe agenda">
          <button type="button" class="cal-legend-pill cal-event-lecture" data-cal-type="lecture" aria-pressed="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
            <span>Jadwal Kuliah</span>
          </button>
          <button type="button" class="cal-legend-pill cal-event-assignment" data-cal-type="assignment_deadline" aria-pressed="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span>Deadline Tugas</span>
          </button>
          <button type="button" class="cal-legend-pill cal-event-quiz" data-cal-type="quiz_window" aria-pressed="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Jadwal Quiz</span>
          </button>
        </div>
        <div id="calendar-summary-text" class="text-xs font-semibold text-muted">Memuat ringkasan…</div>
      </div>
    </section>

    <!-- Konten Utama Kalender / Agenda -->
    <section id="calendar-content" class="space-y-6" aria-live="polite"></section>

  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/calendar.js?v=17"></script>
</body>
</html>
