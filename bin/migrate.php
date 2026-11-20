<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;

$rootPath = dirname(__DIR__);
require_once $rootPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

Env::load($rootPath);

$isFresh = in_array('--fresh', $argv ?? [], true);
$dbName = (string) Env::get('DB_DATABASE', 'clarity_elearning');

if (preg_match('/^[a-zA-Z0-9_]+$/', $dbName) !== 1) {
    fwrite(STDERR, "Nama database tidak valid: {$dbName}\n");
    exit(1);
}

$serverPdo = Database::getServerConnection();
$serverPdo->exec(
    sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        $dbName
    )
);
echo "[migrate] Database `{$dbName}` siap.\n";

$pdo = Database::getConnection();

if ($isFresh) {
    echo "[migrate] Mode --fresh: menghapus tabel M1 sebelumnya...\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('DROP TABLE IF EXISTS login_attempts');
    $pdo->exec('DROP TABLE IF EXISTS users');
    $pdo->exec('DROP TABLE IF EXISTS schema_migrations');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$executedStmt = $pdo->query('SELECT migration FROM schema_migrations');
$executed = $executedStmt !== false ? $executedStmt->fetchAll(PDO::FETCH_COLUMN) : [];
$executedMap = array_flip(is_array($executed) ? $executed : []);

$migrationFiles = glob($rootPath . '/database/migrations/*.sql') ?: [];
sort($migrationFiles, SORT_STRING);

$ranCount = 0;
foreach ($migrationFiles as $file) {
    $filename = basename($file);
    if (isset($executedMap[$filename])) {
        echo "[migrate] Lewati {$filename} (sudah dijalankan).\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        continue;
    }

    $pdo->exec($sql);

    $insertStmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
    $insertStmt->execute(['migration' => $filename]);

    echo "[migrate] Berhasil menjalankan {$filename}.\n";
    $ranCount++;
}

if ($ranCount === 0) {
    echo "[migrate] Semua migrasi sudah up-to-date.\n";
} else {
    echo "[migrate] Selesai menjalankan {$ranCount} file migrasi.\n";
}
