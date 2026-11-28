<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Buat Quiz & Bank Soal';
$pageDescription = 'Buat pengaturan jadwal quiz, kelola soal pilihan ganda dan esai, serta pantau atau nilai percobaan mahasiswa.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="quiz-builder" data-title="Buat Quiz & Bank Soal" data-subtitle="Atur jadwal, durasi, batas percobaan, soal PG/Esai, dan evaluasi percobaan">
  <div id="page-content" class="space-y-6">
    <!-- Bar Pilih Mata Kuliah & Quiz -->
    <section class="clarity-card hallmark-reveal p-5 space-y-4">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
          <div>
            <label for="qb-course-select" class="block text-sm font-semibold text-ink mb-1.5">Mata Kuliah</label>
            <select id="qb-course-select" class="clarity-input w-full"></select>
          </div>
          <div>
            <label for="qb-quiz-select" class="block text-sm font-semibold text-ink mb-1.5">Pilih Quiz</label>
            <select id="qb-quiz-select" class="clarity-input w-full">
              <option value="new">+ Buat Quiz Baru</option>
            </select>
          </div>
        </div>
        <div class="flex items-center gap-3 shrink-0 flex-wrap">
          <button type="button" id="qb-btn-new-quiz" class="clarity-btn clarity-btn-outline clarity-btn-sm">+ Quiz Baru</button>
          <button type="button" id="qb-btn-delete-quiz" class="clarity-inline-link text-sm text-accent bg-transparent border-0" hidden>Hapus Quiz</button>
        </div>
      </div>

      <div id="qb-tabs-bar" class="flex items-center gap-2 pt-3 border-t border-[var(--color-border-subtle)] flex-wrap">
        <h1 id="qb-page-heading" class="text-lg font-extrabold text-ink m-0 mr-auto">Pengaturan & Bank Soal Quiz</h1>
        <div class="filter-tab-group" role="tablist" aria-label="Bagian pengelola quiz">
          <button type="button" class="filter-tab-btn is-active" role="tab" aria-selected="true" data-qb-tab="builder">Pengaturan & Bank Soal</button>
          <button type="button" class="filter-tab-btn" role="tab" aria-selected="false" data-qb-tab="attempts">Percobaan Mahasiswa</button>
        </div>
      </div>
    </section>

    <div id="qb-status-region" aria-live="polite" hidden></div>

    <!-- Panel 1: Pengaturan Quiz & Bank Soal -->
    <section id="qb-panel-builder" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- Form Pengaturan Quiz -->
      <article class="clarity-card p-6 lg:col-span-5 space-y-4">
        <h2 id="qb-meta-title" class="text-base font-bold text-ink m-0 pb-3 border-b border-[var(--color-border-subtle)]">Buat Quiz Baru</h2>
        <form id="qb-meta-form" class="space-y-3">
          <div>
            <label for="qb-title" class="block text-sm font-semibold text-ink mb-1">Judul Quiz</label>
            <input id="qb-title" type="text" class="clarity-input w-full" maxlength="200" placeholder="Contoh: Quiz 2: Autentikasi JWT" required>
          </div>
          <div>
            <label for="qb-meeting" class="block text-sm font-semibold text-ink mb-1">Terkait Pertemuan (Opsional)</label>
            <select id="qb-meeting" class="clarity-input w-full">
              <option value="">Tidak terikat pertemuan</option>
            </select>
          </div>
          <div>
            <label for="qb-desc" class="block text-sm font-semibold text-ink mb-1">Deskripsi / Petunjuk</label>
            <textarea id="qb-desc" rows="2" class="clarity-input w-full" placeholder="Tuliskan aturan singkat pengerjaan…"></textarea>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="qb-open" class="block text-sm font-semibold text-ink mb-1">Waktu Buka (WIB)</label>
              <input id="qb-open" type="datetime-local" class="clarity-input w-full" required>
            </div>
            <div>
              <label for="qb-close" class="block text-sm font-semibold text-ink mb-1">Waktu Tutup (WIB)</label>
              <input id="qb-close" type="datetime-local" class="clarity-input w-full" required>
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label for="qb-duration" class="block text-sm font-semibold text-ink mb-1">Durasi (Menit)</label>
              <input id="qb-duration" type="number" min="1" max="600" class="clarity-input w-full" value="30" required>
            </div>
            <div>
              <label for="qb-attempts" class="block text-sm font-semibold text-ink mb-1">Maks Percobaan</label>
              <input id="qb-attempts" type="number" min="1" max="20" class="clarity-input w-full" value="2" required>
            </div>
            <div>
              <label for="qb-weight" class="block text-sm font-semibold text-ink mb-1">Bobot (%)</label>
              <input id="qb-weight" type="number" step="0.5" min="0" max="100" class="clarity-input w-full" value="20" required>
            </div>
          </div>
          <div class="space-y-2 pt-1">
            <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
              <input id="qb-shuffle" type="checkbox" checked>
              <span>Acak urutan soal dan opsi per percobaan</span>
            </label>
            <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
              <input id="qb-show-answers" type="checkbox" checked>
              <span>Tampilkan kunci jawaban setelah jendela quiz ditutup</span>
            </label>
          </div>
          <div id="qb-meta-feedback" hidden></div>
          <button type="submit" id="qb-meta-submit" class="clarity-btn clarity-btn-primary w-full">Simpan Quiz</button>
        </form>
      </article>

      <!-- Editor Bank Soal -->
      <div class="lg:col-span-7 space-y-6">
        <div id="qb-attempts-warning" class="clarity-alert clarity-alert-warning" hidden>
          <span><strong>Perhatian:</strong> Quiz ini sudah memiliki percobaan mahasiswa. Mengubah atau menghapus soal dapat memengaruhi penilaian dan membutuhkan konfirmasi tambahan.</span>
        </div>

        <article id="qb-question-form-card" class="clarity-card p-6 space-y-4">
          <div class="flex items-center justify-between gap-2 pb-3 border-b border-[var(--color-border-subtle)]">
            <h2 id="qb-qform-title" class="text-base font-bold text-ink m-0">Tambah Soal ke Bank Soal</h2>
            <button type="button" id="qb-q-cancel-edit" class="clarity-btn clarity-btn-outline clarity-btn-sm" hidden>Batal Edit</button>
          </div>
          <div id="qb-q-locked-notice" class="text-sm text-muted" hidden>
            Simpan pengaturan quiz baru di sebelah kiri terlebih dahulu untuk mulai menambahkan soal.
          </div>
          <form id="qb-question-form" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label for="qb-q-type" class="block text-sm font-semibold text-ink mb-1">Tipe Soal</label>
                <select id="qb-q-type" class="clarity-input w-full">
                  <option value="pg">Pilihan Ganda (PG)</option>
                  <option value="esai">Esai</option>
                </select>
              </div>
              <div>
                <label for="qb-q-points" class="block text-sm font-semibold text-ink mb-1">Poin</label>
                <input id="qb-q-points" type="number" step="0.5" min="1" max="1000" class="clarity-input w-full" value="20" required>
              </div>
              <div>
                <label for="qb-q-pos" class="block text-sm font-semibold text-ink mb-1">Urutan</label>
                <input id="qb-q-pos" type="number" min="1" class="clarity-input w-full" value="1" required>
              </div>
            </div>
            <div>
              <label for="qb-q-text" class="block text-sm font-semibold text-ink mb-1">Pertanyaan</label>
              <textarea id="qb-q-text" rows="3" class="clarity-input w-full" placeholder="Tulis pertanyaan dengan jelas…" required></textarea>
            </div>
            <div id="qb-options-section" class="space-y-3">
              <div class="flex items-center justify-between gap-2">
                <span class="text-sm font-semibold text-ink">Opsi Jawaban (Pilih 1 Kunci Jawaban)</span>
                <button type="button" id="qb-add-opt-btn" class="clarity-btn clarity-btn-outline clarity-btn-sm">+ Tambah Opsi</button>
              </div>
              <div id="qb-options-rows" class="space-y-2"></div>
            </div>
            <div id="qb-q-feedback" hidden></div>
            <button type="submit" id="qb-q-submit" class="clarity-btn clarity-btn-primary clarity-btn-sm">Simpan Soal</button>
          </form>
        </article>

        <article class="clarity-card overflow-hidden">
          <div class="clarity-section-head">
            <h2 class="text-base font-bold text-ink m-0">Daftar Soal Terpasang (<span id="qb-q-count">0</span>)</h2>
            <span class="text-xs font-semibold text-muted" id="qb-q-points-sum">Total 0 Poin</span>
          </div>
          <div id="qb-questions-list" class="divide-y divide-[var(--color-border-subtle)]" aria-live="polite"></div>
        </article>
      </div>
    </section>

    <!-- Panel 2: Percobaan Mahasiswa -->
    <section id="qb-panel-attempts" class="space-y-6" hidden>
      <div id="qb-att-summary"></div>
      <article class="clarity-card overflow-hidden">
        <div class="clarity-section-head">
          <h2 class="text-base font-bold text-ink m-0">Daftar Percobaan Mahasiswa</h2>
          <div class="filter-tab-group overflow-x-auto" role="group" aria-label="Filter status percobaan">
            <button type="button" class="filter-tab-btn is-active" data-qb-att-status="" aria-pressed="true">Semua</button>
            <button type="button" class="filter-tab-btn" data-qb-att-status="menunggu_penilaian" aria-pressed="false">Perlu Nilai Esai</button>
            <button type="button" class="filter-tab-btn" data-qb-att-status="graded" aria-pressed="false">Selesai</button>
            <button type="button" class="filter-tab-btn" data-qb-att-status="in_progress" aria-pressed="false">Berlangsung</button>
          </div>
        </div>
        <div id="qb-att-table" aria-live="polite"></div>
      </article>
    </section>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/quiz-builder.js?v=12"></script>
</body>
</html>
