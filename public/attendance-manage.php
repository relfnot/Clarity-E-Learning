<?php

declare(strict_types=1);

$basePrefix = $basePrefix ?? '.';
$assetPrefix = $basePrefix === '..' ? '../' : '';
$pageTitle = 'Kelola Presensi';
$pageDescription = 'Buka atau tutup sesi presensi pertemuan dan ubah status kehadiran mahasiswa.';
$rootPath = dirname(__DIR__);
?>
<!DOCTYPE html>
<html lang="id" class="overflow-x-clip">
<head>
  <?php require $rootPath . '/src/Views/partials/head.php'; ?>
</head>
<body class="overflow-x-clip" data-role="dosen" data-base="<?= htmlspecialchars($basePrefix, ENT_QUOTES, 'UTF-8') ?>" data-page="attendance-manage" data-title="Kelola Presensi" data-subtitle="Buka sesi check-in dan perbarui status kehadiran kelas">
  <div id="page-content" class="space-y-6">
    <section class="clarity-card p-5 grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
      <div>
        <label for="am-course-select" class="block text-sm font-semibold text-ink mb-1.5">Mata Kuliah</label>
        <select id="am-course-select" class="clarity-input w-full"></select>
      </div>
      <div>
        <label for="am-meeting-select" class="block text-sm font-semibold text-ink mb-1.5">Pertemuan</label>
        <select id="am-meeting-select" class="clarity-input w-full"></select>
      </div>
    </section>

    <section id="am-session-container" aria-live="polite"></section>
    <section id="am-roster-container" aria-live="polite"></section>
  </div>

  <script src="<?= $assetPrefix ?>assets/js/api.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/auth.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/layout.js?v=16"></script>
  <script src="<?= $assetPrefix ?>assets/js/ui-helpers.js?v=12"></script>
  <script src="<?= $assetPrefix ?>assets/js/attendance-manage.js?v=12"></script>
</body>
</html>
