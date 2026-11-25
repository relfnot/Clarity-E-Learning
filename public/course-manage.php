<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Kelola Mata Kuliah';
$pageDescription = 'Pengelolaan mata kuliah dosen: materi, jadwal pertemuan, tugas, dan daftar peserta kelas.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="course-manage" data-title="Kelola Mata Kuliah" data-subtitle="Atur materi, pertemuan, tugas, dan peserta kelas">
  <div id="page-content" class="space-y-6">
    <section class="clarity-card p-5 flex flex-col md:flex-row md:items-end justify-between gap-4">
      <div class="w-full md:max-w-md">
        <label for="manage-course-select" class="block text-sm font-semibold text-ink mb-1.5">Pilih Mata Kuliah Diampu</label>
        <select id="manage-course-select" class="clarity-input w-full"></select>
      </div>
      <div class="flex flex-wrap items-center gap-2 shrink-0">
        <button type="button" id="btn-edit-course" class="clarity-btn clarity-btn-outline clarity-btn-sm">Ubah Info Matkul</button>
        <button type="button" id="btn-new-course" class="clarity-btn clarity-btn-primary clarity-btn-sm">Buat Mata Kuliah</button>
      </div>
    </section>

    <section id="manage-course-info" aria-live="polite"></section>

    <section id="manage-tabs-section" class="space-y-4" hidden>
      <div class="filter-tab-group" role="tablist" aria-label="Kelola Komponen Mata Kuliah">
        <button type="button" role="tab" class="filter-tab-btn is-active" data-manage-tab="materials" aria-selected="true">Materi</button>
        <button type="button" role="tab" class="filter-tab-btn" data-manage-tab="meetings" aria-selected="false">Jadwal & Pertemuan</button>
        <button type="button" role="tab" class="filter-tab-btn" data-manage-tab="assignments" aria-selected="false">Tugas</button>
        <button type="button" role="tab" class="filter-tab-btn" data-manage-tab="participants" aria-selected="false">Peserta</button>
      </div>
      <div id="manage-tab-panel" aria-live="polite"></div>
    </section>
  </div>

  <!-- Modal Buat / Ubah Mata Kuliah -->
  <div id="course-modal" class="fixed inset-0 z-[400] flex items-center justify-center p-4 bg-[var(--color-backdrop)]" role="dialog" aria-modal="true" aria-labelledby="course-modal-title" hidden>
    <div class="clarity-card w-full max-w-lg max-h-[90vh] overflow-y-auto p-6 space-y-4">
      <div class="flex items-center justify-between gap-3">
        <h2 id="course-modal-title" class="text-lg font-bold text-ink m-0">Mata Kuliah</h2>
        <button type="button" id="btn-close-course-modal" class="clarity-icon-btn" aria-label="Tutup dialog">&times;</button>
      </div>
      <form id="course-modal-form" class="space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="cm-code" class="block text-sm font-semibold text-ink mb-1">Kode Matkul</label>
            <input id="cm-code" type="text" class="clarity-input w-full" required placeholder="07228204">
          </div>
          <div>
            <label for="cm-credits" class="block text-sm font-semibold text-ink mb-1">SKS</label>
            <input id="cm-credits" type="number" min="1" max="8" class="clarity-input w-full" value="3" required>
          </div>
        </div>
        <div>
          <label for="cm-name" class="block text-sm font-semibold text-ink mb-1">Nama Mata Kuliah</label>
          <input id="cm-name" type="text" class="clarity-input w-full" required placeholder="MANAJEMEN PROYEK 07">
        </div>
        <div>
          <label for="cm-semester" class="block text-sm font-semibold text-ink mb-1">Semester</label>
          <input id="cm-semester" type="text" class="clarity-input w-full" value="Ganjil 2026/2027" required>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label for="cm-day" class="block text-sm font-semibold text-ink mb-1">Hari</label>
            <input id="cm-day" type="text" class="clarity-input w-full" required placeholder="Jumat">
          </div>
          <div>
            <label for="cm-start" class="block text-sm font-semibold text-ink mb-1">Jam Mulai</label>
            <input id="cm-start" type="text" class="clarity-input w-full" required placeholder="13:00">
          </div>
          <div>
            <label for="cm-end" class="block text-sm font-semibold text-ink mb-1">Jam Selesai</label>
            <input id="cm-end" type="text" class="clarity-input w-full" required placeholder="15:00">
          </div>
        </div>
        <div>
          <label for="cm-room" class="block text-sm font-semibold text-ink mb-1">Ruang</label>
          <input id="cm-room" type="text" class="clarity-input w-full" required placeholder="Ruang 8.1">
        </div>
        <div>
          <label for="cm-desc" class="block text-sm font-semibold text-ink mb-1">Deskripsi</label>
          <textarea id="cm-desc" rows="3" class="clarity-input w-full" placeholder="Ringkasan capaian pembelajaran..."></textarea>
        </div>
        <div id="course-modal-feedback" aria-live="polite"></div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="btn-cancel-course-modal" class="clarity-btn clarity-btn-outline clarity-btn-sm">Batal</button>
          <button type="submit" id="btn-save-course-modal" class="clarity-btn clarity-btn-primary clarity-btn-sm">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/course-manage.js?v=12"></script>
</body>
</html>
