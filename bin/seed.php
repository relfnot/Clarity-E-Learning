<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use Database\Seeds\AcademicModulesSeeder;
use Database\Seeds\AssessmentAndNotificationSeeder;
use Database\Seeds\CoursesSeeder;
use Database\Seeds\UsersSeeder;

$rootPath = dirname(__DIR__);
require_once $rootPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once $rootPath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'UsersSeeder.php';
require_once $rootPath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'CoursesSeeder.php';
require_once $rootPath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'AcademicModulesSeeder.php';
require_once $rootPath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeds' . DIRECTORY_SEPARATOR . 'AssessmentAndNotificationSeeder.php';

Env::load($rootPath);

$pdo = Database::getConnection();
$userCounts = UsersSeeder::run($pdo);
$courseCounts = CoursesSeeder::run($pdo);
$moduleCounts = AcademicModulesSeeder::run($pdo, $rootPath);
$assessmentCounts = AssessmentAndNotificationSeeder::run($pdo);

echo sprintf(
    "[seed] Berhasil seed %d dosen dan %d mahasiswa (password demo: rahasia123).\n",
    $userCounts['dosen'],
    $userCounts['mahasiswa']
);
echo sprintf(
    "[seed] Berhasil seed %d mata kuliah dan %d pendaftaran (enrollments).\n",
    $courseCounts['courses'],
    $courseCounts['enrollments']
);
echo sprintf(
    "[seed] Berhasil seed %d pertemuan, %d materi, %d tugas, %d sesi presensi.\n",
    $moduleCounts['meetings'],
    $moduleCounts['materials'],
    $moduleCounts['assignments'],
    $moduleCounts['sessions']
);
echo sprintf(
    "[seed] Berhasil seed %d quiz baru, %d soal, %d bobot nilai, %d notifikasi.\n",
    $assessmentCounts['quizzes'],
    $assessmentCounts['questions'],
    $assessmentCounts['grade_weights'],
    $assessmentCounts['notifications']
);
echo "[seed] Tabel login_attempts dibersihkan.\n";
