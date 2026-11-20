<?php

declare(strict_types=1);

/**
 * Shared <head> partial untuk seluruh halaman produksi Clarity E-Learning.
 * Memuat Google Fonts, Basecoat UI CDN, Tailwind CSS CDN, dan Design Tokens Clarity.
 */
$resolvedTitle = htmlspecialchars($pageTitle ?? 'Clarity E-Learning', ENT_QUOTES, 'UTF-8');
$resolvedDesc = htmlspecialchars($pageDescription ?? 'Portal akademik Clarity E-Learning untuk mengelola perkuliahan, tugas, quiz, presensi, dan nilai mahasiswa.', ENT_QUOTES, 'UTF-8');
$prefix = ($basePrefix ?? '.') === '..' ? '../' : '';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= $resolvedDesc ?>">
<title><?= $resolvedTitle ?> | Clarity E-Learning</title>
<link rel="icon" type="image/svg+xml" href="<?= $prefix ?>assets/img/logo.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/basecoat-css@1.1.0/dist/basecoat.cdn.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    darkMode: ['selector', '[data-theme="dark"]'],
    theme: {
      extend: {
        colors: {
          clarity: {
            crimson: '#be1a1a',
            vermilion: '#d0311e',
            gold: '#f7d87f',
            cream: '#f8ebab',
            ivory: '#f6f4ee',
            espresso: '#181311',
            sidebar: '#eef2f6',
            emerald: '#15803d',
            obsidian: '#161110'
          }
        },
        fontFamily: {
          sans: ['"Plus Jakarta Sans"', 'Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
          mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace']
        }
      }
    }
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/basecoat-css@1.1.0/dist/js/all.min.js" defer></script>
<link rel="stylesheet" href="<?= $prefix ?>assets/css/tokens.css?v=11">
<link rel="stylesheet" href="<?= $prefix ?>assets/css/base.css?v=11">
<link rel="stylesheet" href="<?= $prefix ?>assets/css/components.css?v=11">
