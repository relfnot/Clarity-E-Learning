<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Upload Materi';
$pageDescription = 'Unggah berkas materi (maks 10 MB) atau tautan video/referensi untuk setiap pertemuan kuliah.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="material-upload" data-title="Upload Materi" data-subtitle="Unggah berkas perkuliahan atau tautan referensi">
  <div id="page-content" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <section class="clarity-card p-6 lg:col-span-5 space-y-4">
      <h2 class="text-lg font-bold text-ink m-0">Tambah Materi Baru</h2>
      <form id="material-upload-form" class="space-y-4">
        <div>
          <label for="mu-course" class="block text-sm font-semibold text-ink mb-1">Mata Kuliah</label>
          <select id="mu-course" class="clarity-input w-full" required></select>
        </div>
        <div>
          <label for="mu-meeting" class="block text-sm font-semibold text-ink mb-1">Pertemuan</label>
          <select id="mu-meeting" class="clarity-input w-full" required></select>
        </div>
        <div>
          <label for="mu-title" class="block text-sm font-semibold text-ink mb-1">Judul Materi</label>
          <input id="mu-title" type="text" class="clarity-input w-full" required placeholder="Contoh: Slide Pertemuan 10 Keamanan Web">
        </div>
        <div>
          <span class="block text-sm font-semibold text-ink mb-1.5">Tipe Materi</span>
          <div class="filter-tab-group" role="tablist" aria-label="Tipe Materi">
            <button type="button" role="tab" class="filter-tab-btn is-active" data-mat-type="file" aria-selected="true">Berkas (Maks 10 MB)</button>
            <button type="button" role="tab" class="filter-tab-btn" data-mat-type="link" aria-selected="false">Tautan / Video</button>
          </div>
        </div>
        <div id="mu-file-group">
          <label for="mu-file" class="block text-sm font-semibold text-ink mb-1">Pilih Berkas (pdf, doc, docx, ppt, pptx, xls, xlsx, zip, jpg, png, txt)</label>
          <input id="mu-file" type="file" class="clarity-input w-full">
        </div>
        <div id="mu-link-group" hidden>
          <label for="mu-url" class="block text-sm font-semibold text-ink mb-1">URL Tautan (http:// atau https://)</label>
          <input id="mu-url" type="url" class="clarity-input w-full" placeholder="https://youtu.be/...">
        </div>
        <div id="mu-feedback" aria-live="polite"></div>
        <button type="submit" id="btn-upload-material" class="clarity-btn clarity-btn-primary w-full">Simpan Materi</button>
      </form>
    </section>

    <section class="lg:col-span-7 space-y-4">
      <div id="mu-list-container" aria-live="polite"></div>
    </section>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/material-upload.js?v=12"></script>
</body>
</html>
