<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Pengerjaan Quiz';
$pageDescription = 'Kerjakan soal pilihan ganda dan esai dengan timer server serta penyimpanan jawaban otomatis.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="mahasiswa" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="quiz" data-title="Pengerjaan Quiz" data-subtitle="Timer terverifikasi server & autosave jawaban">
  <div id="page-content" class="space-y-6">
    <section id="qa-header-card" class="clarity-card hallmark-reveal p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="min-w-0 space-y-1">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="course-code-pill font-mono" id="qa-course-code">…</span>
          <span class="clarity-tag clarity-tag-neutral" id="qa-attempt-badge">Percobaan #-</span>
        </div>
        <h1 class="text-lg sm:text-xl font-extrabold text-ink m-0" id="qa-quiz-title">Memuat Quiz…</h1>
        <p class="text-xs text-muted m-0" id="qa-autosave-status" aria-live="polite">Menyiapkan soal dan timer server…</p>
      </div>
      <div class="flex items-center gap-3 shrink-0 flex-wrap">
        <div id="qa-timer-badge" class="quiz-timer-badge" aria-live="off" aria-label="Sisa waktu pengerjaan quiz">
          <span>Sisa Waktu:</span>
          <span id="qa-timer-text">--:--</span>
        </div>
        <button type="button" id="qa-open-submit-btn" class="clarity-btn clarity-btn-primary" disabled>Kumpulkan Quiz</button>
      </div>
    </section>

    <div id="qa-alert-region" aria-live="polite" hidden></div>

    <div id="qa-workspace" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <section class="clarity-card overflow-hidden lg:col-span-8" aria-labelledby="qa-question-heading">
        <div class="clarity-section-head">
          <h2 class="text-base font-bold text-ink m-0" id="qa-question-heading">Soal <span id="qa-q-num">-</span> dari <span id="qa-q-total">-</span></h2>
          <span class="clarity-tag clarity-tag-info" id="qa-q-type-badge">-</span>
        </div>
        <div id="qa-question-container" class="p-6 space-y-4">
          <p class="text-sm text-muted m-0">Memuat soal quiz…</p>
        </div>
        <div class="px-6 py-4 border-t border-[var(--color-border-subtle)] bg-[var(--color-surface-alt)] flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2 flex-wrap">
            <button type="button" id="qa-prev-btn" class="clarity-btn clarity-btn-outline clarity-btn-sm" disabled>Sebelumnya</button>
            <button type="button" id="qa-flag-btn" class="clarity-btn clarity-btn-outline clarity-btn-sm" disabled>Tandai Ragu-ragu</button>
          </div>
          <button type="button" id="qa-next-btn" class="clarity-btn clarity-btn-primary clarity-btn-sm" disabled>Selanjutnya</button>
        </div>
      </section>

      <aside class="clarity-card p-5 lg:col-span-4 space-y-4">
        <h2 class="text-base font-bold text-ink m-0 pb-3 border-b border-[var(--color-border-subtle)]">Navigator Soal</h2>
        <div id="qa-nav-grid" class="quiz-nav-grid" role="navigation" aria-label="Navigator nomor soal"></div>
        <div class="space-y-2 text-xs text-muted pt-2 border-t border-[var(--color-border-subtle)]">
          <div class="flex items-center justify-between">
            <span class="clarity-tag clarity-tag-success">Sudah Dijawab</span>
            <strong class="text-ink font-mono" id="qa-count-answered">0 / 0</strong>
          </div>
          <div class="flex items-center justify-between">
            <span class="clarity-tag clarity-tag-warning">Ragu-ragu</span>
            <strong class="text-ink font-mono" id="qa-count-flagged">0</strong>
          </div>
        </div>
        <button type="button" id="qa-side-submit-btn" class="clarity-btn clarity-btn-gold w-full" disabled>Selesai & Kumpulkan</button>
      </aside>
    </div>
  </div>

  <!-- Modal Konfirmasi Submit -->
  <div id="qa-submit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[var(--color-backdrop)]" role="dialog" aria-modal="true" aria-labelledby="qa-modal-title" hidden>
    <div class="clarity-card w-full max-w-md p-6 space-y-4">
      <div class="flex items-center justify-between gap-2 pb-3 border-b border-[var(--color-border-subtle)]">
        <h2 id="qa-modal-title" class="text-lg font-bold text-ink m-0">Konfirmasi Pengumpulan Quiz</h2>
        <button type="button" id="qa-modal-close" class="clarity-icon-btn" aria-label="Tutup dialog">&times;</button>
      </div>
      <p class="text-sm text-secondary m-0">Setelah dikumpulkan, jawaban tidak dapat diubah lagi dan nilai Pilihan Ganda akan dihitung otomatis.</p>
      <div id="qa-modal-summary" class="clarity-alert clarity-alert-info text-sm">Memeriksa jawaban…</div>
      <div class="flex items-center justify-end gap-3 pt-2">
        <button type="button" id="qa-modal-cancel" class="clarity-btn clarity-btn-outline clarity-btn-sm">Periksa Kembali</button>
        <button type="button" id="qa-modal-confirm" class="clarity-btn clarity-btn-primary clarity-btn-sm">Ya, Kumpulkan Sekarang</button>
      </div>
    </div>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/quiz-attempt.js?v=12"></script>
</body>
</html>
