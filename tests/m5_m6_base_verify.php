<?php

declare(strict_types=1);

/**
 * Verifikasi Timeline No. 13 (Quiz & Bank Soal), No. 14 (Struktur Modul Nilai), No. 15 (Notifikasi Dasar).
 *
 * Cara jalan:
 *   - Unit test rumus nilai saja (tanpa DB/HTTP):
 *       php tests/m5_m6_base_verify.php --unit-only
 *   - Full test (unit + HTTP API terhadap server lokal):
 *       php -S 127.0.0.1:8085 public/router.php
 *       php tests/m5_m6_base_verify.php http://127.0.0.1:8085
 */

use App\Core\Env;
use App\Services\GradeService;

$rootPath = dirname(__DIR__);
require_once $rootPath . '/vendor/autoload.php';
Env::load($rootPath);

$assertions = 0;
$failures = [];

function assertCheck(bool $condition, string $label, array &$failures, int &$assertions): void
{
    $assertions++;
    if (!$condition) {
        $failures[] = $label;
        echo "[FAIL] {$label}\n";
    } else {
        echo "[PASS] {$label}\n";
    }
}

function apiCall(string $method, string $url, ?array $body = null, array $headers = []): array
{
    $headerLines = array_merge([
        'Accept: application/json',
        'Connection: close',
    ], $headers);

    $content = '';
    if ($body !== null) {
        $content = json_encode($body, JSON_THROW_ON_ERROR);
        $headerLines[] = 'Content-Type: application/json';
    }

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headerLines) . "\r\n",
            'content' => $content,
            'ignore_errors' => true,
            'timeout' => 5,
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/HTTP\/\S+\s+(\d{3})/', $http_response_header[0], $m) === 1) {
        $status = (int) $m[1];
    }

    $json = null;
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $json = $decoded;
        }
    }

    return ['status' => $status, 'raw' => is_string($raw) ? $raw : '', 'json' => $json];
}

// =========================================================================
// BAGIAN A: UNIT TEST RUMUS NILAI BERBOBOT (PRD M6 FR3 & AC2)
// =========================================================================
echo "=== [A] Unit Test Rumus Nilai Berbobot ===\n";

$weights = ['tugas' => 30.0, 'quiz' => 20.0, 'presensi' => 10.0, 'uts' => 20.0, 'uas' => 20.0];

// Kasus 1: Semua komponen tersedia -> (90*30 + 80*20 + 100*10 + 85*20 + 85*20) / 100 = 87.00 (A)
$full = GradeService::calculateFinalScore(
    ['tugas' => 90.0, 'quiz' => 80.0, 'presensi' => 100.0, 'uts' => 85.0, 'uas' => 85.0],
    $weights
);
assertCheck($full['final_score'] === 87.0, 'Rumus nilai penuh = 87.0', $failures, $assertions);
assertCheck($full['letter_grade'] === 'A', 'Huruf mutu 87.0 = A', $failures, $assertions);
assertCheck($full['completed_weight_percent'] === 100.0, 'Bobot terisi 100%', $failures, $assertions);

// Kasus 2: Sebagian komponen belum ada (null tidak dihitung 0)
// tugas=90 (30%), presensi=100 (10%) -> activeWeight=40 -> (2700 + 1000) / 40 = 92.5
$partial = GradeService::calculateFinalScore(
    ['tugas' => 90.0, 'quiz' => null, 'presensi' => 100.0, 'uts' => null, 'uas' => null],
    $weights
);
assertCheck($partial['final_score'] === 92.5, 'Rumus parsial (null tidak jadi 0) = 92.5', $failures, $assertions);
assertCheck($partial['components']['quiz']['status'] === 'belum_ada', 'Komponen null berstatus belum_ada', $failures, $assertions);
assertCheck($partial['completed_weight_percent'] === 40.0, 'Bobot terisi parsial = 40%', $failures, $assertions);

// Kasus 3: Semua komponen null -> final_score null, letter_grade null
$empty = GradeService::calculateFinalScore(
    ['tugas' => null, 'quiz' => null, 'presensi' => null, 'uts' => null, 'uas' => null],
    $weights
);
assertCheck($empty['final_score'] === null && $empty['letter_grade'] === null, 'Semua komponen kosong -> final_score & letter_grade null', $failures, $assertions);

if (in_array('--unit-only', $argv ?? [], true)) {
    echo sprintf("\n[UNIT DONE] %d assertions, %d failures.\n", $assertions, count($failures));
    exit($failures === [] ? 0 : 1);
}

// =========================================================================
// BAGIAN B: INTEGRATION TEST HTTP API (TIMELINE NO. 13, 14, 15)
// =========================================================================
$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8085', '/') . '/api/v1';
echo "\n=== [B] Integration Test API ({$baseUrl}) ===\n";

$loginDosen = apiCall('POST', "{$baseUrl}/auth/login", ['identifier' => '5811995177', 'password' => 'rahasia123']);
assertCheck($loginDosen['status'] === 200, 'Login dosen pengampu berhasil', $failures, $assertions);
$dosenToken = (string) ($loginDosen['json']['data']['token'] ?? '');
$dosenHdr = ['Authorization: Bearer ' . $dosenToken];

$loginMhs = apiCall('POST', "{$baseUrl}/auth/login", ['identifier' => '24.N1.0026', 'password' => 'rahasia123']);
assertCheck($loginMhs['status'] === 200, 'Login mahasiswa berhasil', $failures, $assertions);
$mhsToken = (string) ($loginMhs['json']['data']['token'] ?? '');
$mhsHdr = ['Authorization: Bearer ' . $mhsToken];

$coursesRes = apiCall('GET', "{$baseUrl}/courses", null, $dosenHdr);
$courseId = (int) ($coursesRes['json']['data'][0]['id'] ?? 1);

// --- NO. 13: QUIZ & BANK SOAL ---
$createQuiz = apiCall('POST', "{$baseUrl}/courses/{$courseId}/quizzes", [
    'title' => 'Quiz Uji Otomatis M5',
    'description' => 'Quiz verifikasi skema & API dasar.',
    'open_at' => '2026-11-26T08:00:00+07:00',
    'close_at' => '2026-12-15T23:59:00+07:00',
    'duration_min' => 25,
    'max_attempts' => 2,
    'shuffle' => true,
    'weight' => 15,
], $dosenHdr);
assertCheck($createQuiz['status'] === 201, '[No.13] Dosen membuat quiz baru (201)', $failures, $assertions);
$quizId = (int) ($createQuiz['json']['data']['id'] ?? 0);

// Validasi soal PG harus minimal 2 opsi & tepat 1 benar
$badPg = apiCall('POST', "{$baseUrl}/quizzes/{$quizId}/questions", [
    'type' => 'pg',
    'text' => 'Soal tidak valid',
    'points' => 10,
    'position' => 1,
    'options' => [
        ['text' => 'Opsi 1', 'is_correct' => true],
        ['text' => 'Opsi 2', 'is_correct' => true],
    ],
], $dosenHdr);
assertCheck($badPg['status'] === 422, '[No.13] PG dengan 2 jawaban benar ditolak (422)', $failures, $assertions);

$addPg = apiCall('POST', "{$baseUrl}/quizzes/{$quizId}/questions", [
    'type' => 'pg',
    'text' => 'Metode HTTP untuk memperbarui seluruh resource adalah?',
    'points' => 50,
    'position' => 1,
    'options' => [
        ['text' => 'GET', 'is_correct' => false],
        ['text' => 'PUT', 'is_correct' => true],
        ['text' => 'DELETE', 'is_correct' => false],
    ],
], $dosenHdr);
assertCheck($addPg['status'] === 201, '[No.13] Tambah soal PG valid (201)', $failures, $assertions);
$questionId = (int) ($addPg['json']['data']['id'] ?? 0);

$addEsai = apiCall('POST', "{$baseUrl}/quizzes/{$quizId}/questions", [
    'type' => 'esai',
    'text' => 'Jelaskan konsep idempotency pada REST API.',
    'points' => 50,
    'position' => 2,
], $dosenHdr);
assertCheck($addEsai['status'] === 201, '[No.13] Tambah soal Esai valid (201)', $failures, $assertions);

// Mahasiswa tidak boleh mendapat questions/is_correct di GET /quizzes/{id}
$mhsQuizDetail = apiCall('GET', "{$baseUrl}/quizzes/{$quizId}", null, $mhsHdr);
assertCheck(
    $mhsQuizDetail['status'] === 200 && !array_key_exists('questions', $mhsQuizDetail['json']['data'] ?? []),
    '[No.13] Mahasiswa GET /quizzes/{id} tidak membocorkan soal & kunci jawaban',
    $failures,
    $assertions
);

$mhsQuestionsBlocked = apiCall('GET', "{$baseUrl}/quizzes/{$quizId}/questions", null, $mhsHdr);
assertCheck($mhsQuestionsBlocked['status'] === 403, '[No.13] Mahasiswa ditolak mengakses bank soal dosen (403)', $failures, $assertions);

// Cleanup soal & quiz uji
$delQ = apiCall('DELETE', "{$baseUrl}/questions/{$questionId}", null, $dosenHdr);
assertCheck($delQ['status'] === 204, '[No.13] Dosen menghapus soal (204)', $failures, $assertions);
$delQuiz = apiCall('DELETE', "{$baseUrl}/quizzes/{$quizId}", null, $dosenHdr);
assertCheck($delQuiz['status'] === 204, '[No.13] Dosen menghapus quiz uji (204)', $failures, $assertions);

// --- NO. 14: STRUKTUR MODUL NILAI ---
$badWeights = apiCall('PUT', "{$baseUrl}/courses/{$courseId}/grade-weights", [
    'tugas' => 30, 'quiz' => 20, 'presensi' => 10, 'uts' => 20, 'uas' => 10,
], $dosenHdr);
assertCheck($badWeights['status'] === 422, '[No.14] Bobot total != 100 ditolak (422)', $failures, $assertions);

$goodWeights = apiCall('PUT', "{$baseUrl}/courses/{$courseId}/grade-weights", [
    'tugas' => 30, 'quiz' => 20, 'presensi' => 10, 'uts' => 20, 'uas' => 20,
], $dosenHdr);
assertCheck($goodWeights['status'] === 200, '[No.14] Dosen menyimpan bobot total = 100 (200)', $failures, $assertions);

$myGrades = apiCall('GET', "{$baseUrl}/courses/{$courseId}/grades/me", null, $mhsHdr);
assertCheck($myGrades['status'] === 200 && isset($myGrades['json']['data']['components']), '[No.14] Mahasiswa mengambil rekap nilai sendiri (200)', $failures, $assertions);

$mhsClassGradesBlocked = apiCall('GET', "{$baseUrl}/courses/{$courseId}/grades", null, $mhsHdr);
assertCheck($mhsClassGradesBlocked['status'] === 403, '[No.14] Mahasiswa ditolak melihat nilai seluruh kelas (403)', $failures, $assertions);

$dosenClassGrades = apiCall('GET', "{$baseUrl}/courses/{$courseId}/grades", null, $dosenHdr);
assertCheck($dosenClassGrades['status'] === 200 && is_array($dosenClassGrades['json']['data'] ?? null), '[No.14] Dosen melihat rekap nilai kelas (200)', $failures, $assertions);

// --- NO. 15: NOTIFIKASI DASAR ---
$announce = apiCall('POST', "{$baseUrl}/courses/{$courseId}/announcements", [
    'title' => 'Pengumuman Uji Integrasi',
    'body' => 'Silakan cek jadwal quiz minggu ini.',
], $dosenHdr);
assertCheck(
    $announce['status'] === 201 && (int) ($announce['json']['data']['recipients_count'] ?? 0) > 0,
    '[No.15] Dosen mengirim pengumuman ke peserta kelas (201)',
    $failures,
    $assertions
);

$unreadBefore = apiCall('GET', "{$baseUrl}/notifications/unread-count", null, $mhsHdr);
$unreadCount = (int) ($unreadBefore['json']['data']['unread_count'] ?? 0);
assertCheck($unreadBefore['status'] === 200 && $unreadCount > 0, '[No.15] Mahasiswa memiliki notifikasi belum dibaca > 0', $failures, $assertions);

$notifList = apiCall('GET', "{$baseUrl}/notifications?unread=1", null, $mhsHdr);
assertCheck($notifList['status'] === 200 && count($notifList['json']['data'] ?? []) > 0, '[No.15] GET /notifications?unread=1 mengembalikan daftar', $failures, $assertions);
$firstNotifId = (int) ($notifList['json']['data'][0]['id'] ?? 0);

// Isolasi: dosen tidak boleh bisa markAsRead notifikasi milik mahasiswa
$crossRead = apiCall('PATCH', "{$baseUrl}/notifications/{$firstNotifId}/read", null, $dosenHdr);
assertCheck($crossRead['status'] === 404, '[No.15] User lain ditolak membaca notifikasi milik mahasiswa (404)', $failures, $assertions);

$markOne = apiCall('PATCH', "{$baseUrl}/notifications/{$firstNotifId}/read", null, $mhsHdr);
assertCheck($markOne['status'] === 200 && !empty($markOne['json']['data']['read_at']), '[No.15] Tandai satu notifikasi dibaca (200)', $failures, $assertions);

$markAll = apiCall('POST', "{$baseUrl}/notifications/read-all", null, $mhsHdr);
assertCheck($markAll['status'] === 200, '[No.15] Tandai semua notifikasi dibaca (200)', $failures, $assertions);

$unreadAfter = apiCall('GET', "{$baseUrl}/notifications/unread-count", null, $mhsHdr);
assertCheck((int) ($unreadAfter['json']['data']['unread_count'] ?? -1) === 0, '[No.15] Unread count menjadi 0 setelah read-all', $failures, $assertions);

echo sprintf("\n[TOTAL] %d assertions, %d failures.\n", $assertions, count($failures));
exit($failures === [] ? 0 : 1);
