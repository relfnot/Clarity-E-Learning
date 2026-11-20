<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use Database\Seeds\UsersSeeder;

$rootPath = dirname(__DIR__);
require_once $rootPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once $rootPath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'UsersSeeder.php';

Env::load($rootPath);

$pdo = Database::getConnection();
$counts = UsersSeeder::run($pdo);

echo sprintf(
    "[seed] Berhasil seed %d dosen dan %d mahasiswa (password demo: rahasia123).\n",
    $counts['dosen'],
    $counts['mahasiswa']
);
echo "[seed] Tabel login_attempts dibersihkan.\n";
