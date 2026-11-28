<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\DateTimeHelper;
use App\Core\Env;

$rootPath = dirname(__DIR__);
require_once $rootPath . '/vendor/autoload.php';
Env::load($rootPath);

$assertions = 0;
$failures = [];

function qCheck(bool $condition, string $label, array &$failures, int &$assertions): void
{
    $assertions++;
    if (!$condition) {
        $failures[] = $label;
        echo "[FAIL] {$label}\n";
    } else {
        echo "[PASS] {$label}\n";
    }
}

function qApi(string $method, string $url, ?array $body = null, array $headers = []): array
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

    return [
        'status' => $status,
        'raw' => is_string($raw) ? $raw : '',
        'json' => $json,
    ];
}

function qLogin(string $baseUrl, string $identifier, string $password = 'rahasia123'): string
{
    $res = qApi('POST', "{$baseUrl}/api/v1/auth/login", [
        'identifier' => $identifier,
        'password' => $password,
    ]);
    return (string) ($res['json']['data']['token'] ?? '');
}

$pdo = Database::getConnection();
$port = 18771;
$baseUrl = "http://127.0.0.1:{$port}";
$cmd = sprintf(
    'php -S 127.0.0.1:%d -t %s %s',
    $port,
    escapeshellarg($rootPath . '/public'),
    escapeshellarg($rootPath . '/public/router.php')
);

$nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
$process = proc_open($cmd, [
    0 => ['pipe', 'r'],
    1 => ['file', $nullDevice, 'w'],
    2 => ['file', $nullDevice, 'w'],
], $pipes, $rootPath);

if (!is_resource($process)) {
    fwrite(STDERR, "Gagal memulai server PHP sementara.\n");
    exit(1);
}

$quizId = 0;

try {
    $ready = false;
    for ($i = 0; $i < 25; $i++) {
        if (qApi('GET', "{$baseUrl}/api/v1/health")['status'] === 200) {
            $ready = true;
            break;
        }
        usleep(100000);
    }
    qCheck($ready, 'Server PHP sementara merespons /api/v1/health', $failures, $assertions);

    $tokenDosen = qLogin($baseUrl, '5811995177');
    $tokenMhs1 = qLogin($baseUrl, '24.N1.0026');
    $tokenMhs2 = qLogin($baseUrl, '24.N1.0007');
    qCheck($tokenDosen !== '' && $tokenMhs1 !== '' && $tokenMhs2 !== '', 'Login dosen & mahasiswa berhasil', $failures, $assertions);

    $c1Id = (int) $pdo->query("SELECT id FROM courses WHERE code = '07228204'")->fetchColumn();
    $now = DateTimeHelper::now();

    // ---------------------------------------------------------------------
    // ITEM 21: Dosen Membuat Quiz, Soal PG & Soal Esai
    // ---------------------------------------------------------------------
    $createQuiz = qApi('POST', "{$baseUrl}/api/v1/courses/{$c1Id}/quizzes", [
        'title' => 'Quiz Uji Timeline 20-21',
        'description' => 'Verifikasi pengerjaan, autosave, timer, dan penilaian otomatis/esai.',
        'open_at' => $now->modify('-1 hour')->format('Y-m-d\TH:i:sP'),
        'close_at' => $now->modify('+3 hours')->format('Y-m-d\TH:i:sP'),
        'duration_min' => 20,
        'max_attempts' => 1,
        'shuffle' => true,
        'show_answers' => false,
        'weight' => 15,
    ], ["Authorization: Bearer {$tokenDosen}"]);
    $quizId = (int) ($createQuiz['json']['data']['id'] ?? 0);
    qCheck($createQuiz['status'] === 201 && $quizId > 0, 'Item 21: Dosen membuat quiz baru (201)', $failures, $assertions);

    $addPg = qApi('POST', "{$baseUrl}/api/v1/quizzes/{$quizId}/questions", [
        'type' => 'pg',
        'text' => 'Protokol apa yang digunakan untuk autentikasi stateless?',
        'points' => 50,
        'position' => 1,
        'options' => [
            ['text' => 'FTP', 'is_correct' => false],
            ['text' => 'JWT', 'is_correct' => true],
            ['text' => 'SMTP', 'is_correct' => false],
        ],
    ], ["Authorization: Bearer {$tokenDosen}"]);
    $pgQid = (int) ($addPg['json']['data']['id'] ?? 0);
    $correctOptId = 0;
    $wrongOptId = 0;
    foreach (($addPg['json']['data']['options'] ?? []) as $opt) {
        if (!empty($opt['is_correct'])) {
            $correctOptId = (int) $opt['id'];
        } else {
            $wrongOptId = (int) $opt['id'];
        }
    }
    qCheck($addPg['status'] === 201 && $pgQid > 0 && $correctOptId > 0, 'Item 21: Dosen menambahkan soal PG dengan 1 kunci jawaban', $failures, $assertions);

    $addEsai = qApi('POST', "{$baseUrl}/api/v1/quizzes/{$quizId}/questions", [
        'type' => 'esai',
        'text' => 'Jelaskan kegunaan refresh/autosave saat mengerjakan quiz.',
        'points' => 50,
        'position' => 2,
    ], ["Authorization: Bearer {$tokenDosen}"]);
    $esaiQid = (int) ($addEsai['json']['data']['id'] ?? 0);
    qCheck($addEsai['status'] === 201 && $esaiQid > 0, 'Item 21: Dosen menambahkan soal Esai', $failures, $assertions);

    // ---------------------------------------------------------------------
    // ITEM 20: Mahasiswa Memulai Attempt, Kerahasiaan Kunci, Timer, Refresh, Autosave
    // ---------------------------------------------------------------------
    $studentDetail = qApi('GET', "{$baseUrl}/api/v1/quizzes/{$quizId}", null, [
        "Authorization: Bearer {$tokenMhs1}",
    ]);
    qCheck(
        $studentDetail['status'] === 200
        && ($studentDetail['json']['data']['availability_status'] ?? '') === 'tersedia'
        && !array_key_exists('questions', $studentDetail['json']['data'] ?? []),
        'Item 20: Detail quiz mahasiswa tidak membocorkan soal sebelum dimulai',
        $failures,
        $assertions
    );

    $startAttempt = qApi('POST', "{$baseUrl}/api/v1/quizzes/{$quizId}/attempts", [], [
        "Authorization: Bearer {$tokenMhs1}",
    ]);
    $attemptData = $startAttempt['json']['data'] ?? [];
    $attemptId = (int) ($attemptData['id'] ?? 0);
    $hasSecretKey = false;
    foreach (($attemptData['questions'] ?? []) as $q) {
        if (array_key_exists('correct_option_id', $q)) {
            $hasSecretKey = true;
        }
        foreach (($q['options'] ?? []) as $o) {
            if (array_key_exists('is_correct', $o)) {
                $hasSecretKey = true;
            }
        }
    }
    qCheck(
        $startAttempt['status'] === 201
        && $attemptId > 0
        && count($attemptData['questions'] ?? []) === 2
        && !$hasSecretKey
        && str_ends_with((string) ($attemptData['expires_at'] ?? ''), '+07:00'),
        'Item 20: Mulai attempt mengembalikan soal, expires_at +07:00, dan tanpa is_correct',
        $failures,
        $assertions
    );

    // Refresh tidak mengubah urutan soal atau expires_at
    $refresh1 = qApi('GET', "{$baseUrl}/api/v1/attempts/{$attemptId}", null, [
        "Authorization: Bearer {$tokenMhs1}",
    ]);
    $order1 = array_map(static fn (array $q): int => (int) $q['id'], $refresh1['json']['data']['questions'] ?? []);
    $orderStart = array_map(static fn (array $q): int => (int) $q['id'], $attemptData['questions'] ?? []);
    qCheck(
        $refresh1['status'] === 200
        && $order1 === $orderStart
        && ($refresh1['json']['data']['expires_at'] ?? '') === ($attemptData['expires_at'] ?? ''),
        'Item 20: Refresh halaman mempertahankan urutan soal dan timer expires_at',
        $failures,
        $assertions
    );

    // Isolasi mahasiswa lain
    $crossStudent = qApi('GET', "{$baseUrl}/api/v1/attempts/{$attemptId}", null, [
        "Authorization: Bearer {$tokenMhs2}",
    ]);
    qCheck($crossStudent['status'] === 403, 'Item 20: Mahasiswa lain ditolak mengakses attempt (403)', $failures, $assertions);

    // Autosave jawaban
    $autosave = qApi('PUT', "{$baseUrl}/api/v1/attempts/{$attemptId}/answers", [
        'answers' => [
            ['question_id' => $pgQid, 'option_id' => $correctOptId],
            ['question_id' => $esaiQid, 'answer_text' => 'Untuk menjaga jawaban tidak hilang saat refresh.'],
        ],
    ], ["Authorization: Bearer {$tokenMhs1}"]);
    qCheck(
        $autosave['status'] === 200 && (int) ($autosave['json']['data']['saved_count'] ?? 0) === 2,
        'Item 20: Autosave menyimpan 2 jawaban dengan status 200',
        $failures,
        $assertions
    );

    // Item 21 FR8: Edit soal saat sudah ada percobaan ditolak 409 tanpa force=true
    $editBlocked = qApi('PUT', "{$baseUrl}/api/v1/questions/{$pgQid}", [
        'type' => 'pg',
        'text' => 'Protokol apa yang digunakan untuk autentikasi stateless?',
        'points' => 50,
        'position' => 1,
        'options' => [
            ['text' => 'FTP', 'is_correct' => false],
            ['text' => 'JWT', 'is_correct' => true],
            ['text' => 'SMTP', 'is_correct' => false],
        ],
    ], ["Authorization: Bearer {$tokenDosen}"]);
    qCheck($editBlocked['status'] === 409, 'Item 21: Ubah soal tanpa force ditolak 409 saat sudah ada percobaan', $failures, $assertions);

    // Submit & auto-grade PG
    $submitRes = qApi('POST', "{$baseUrl}/api/v1/attempts/{$attemptId}/submit", [], [
        "Authorization: Bearer {$tokenMhs1}",
    ]);
    $submitted = $submitRes['json']['data'] ?? [];
    $resultHasKey = false;
    $essayAnswerId = 0;
    foreach (($submitted['questions'] ?? []) as $q) {
        if ((int) ($q['id'] ?? 0) === $esaiQid) {
            $essayAnswerId = (int) ($q['answer_id'] ?? 0);
        }
        foreach (($q['options'] ?? []) as $o) {
            if (array_key_exists('is_correct', $o)) {
                $resultHasKey = true;
            }
        }
    }
    qCheck(
        $submitRes['status'] === 200
        && ($submitted['status'] ?? '') === 'menunggu_penilaian'
        && (float) ($submitted['score'] ?? -1) === 50.0
        && !$resultHasKey,
        'Item 20: Submit menilai PG otomatis (50/100) dengan status menunggu_penilaian tanpa membocorkan kunci',
        $failures,
        $assertions
    );

    // Submit ulang ditolak 409
    $dupSubmit = qApi('POST', "{$baseUrl}/api/v1/attempts/{$attemptId}/submit", [], [
        "Authorization: Bearer {$tokenMhs1}",
    ]);
    qCheck($dupSubmit['status'] === 409, 'Item 20: Submit ganda ditolak dengan 409 CONFLICT', $failures, $assertions);

    // Percobaan melebihi max_attempts (1) ditolak 409
    $exceedAttempt = qApi('POST', "{$baseUrl}/api/v1/quizzes/{$quizId}/attempts", [], [
        "Authorization: Bearer {$tokenMhs1}",
    ]);
    qCheck($exceedAttempt['status'] === 409, 'Item 20: Percobaan ke-(max+1) ditolak dengan 409 CONFLICT', $failures, $assertions);

    // Dosen melihat daftar percobaan & memberi nilai esai
    $lecAttempts = qApi('GET', "{$baseUrl}/api/v1/quizzes/{$quizId}/attempts", null, [
        "Authorization: Bearer {$tokenDosen}",
    ]);
    qCheck(
        $lecAttempts['status'] === 200
        && count($lecAttempts['json']['data'] ?? []) === 1
        && (int) ($lecAttempts['json']['meta']['summary']['menunggu_penilaian'] ?? 0) === 1,
        'Item 21: Dosen melihat daftar percobaan beserta summary status esai',
        $failures,
        $assertions
    );

    $gradeEssay = qApi('PUT', "{$baseUrl}/api/v1/answers/{$essayAnswerId}/grade", [
        'score' => 40,
    ], ["Authorization: Bearer {$tokenDosen}"]);
    qCheck(
        $gradeEssay['status'] === 200
        && ($gradeEssay['json']['data']['status'] ?? '') === 'graded'
        && (float) ($gradeEssay['json']['data']['score'] ?? -1) === 90.0,
        'Item 21: Nilai esai dosen (40) memperbarui total skor menjadi 90 dan status graded',
        $failures,
        $assertions
    );

    // Uji attempt kedaluwarsa: submit setelah expires_at ditolak 422, jawaban tersimpan tetap dinilai
    qApi('PUT', "{$baseUrl}/api/v1/quizzes/{$quizId}", [
        'title' => 'Quiz Uji Timeline 20-21',
        'open_at' => $now->modify('-1 hour')->format('Y-m-d\TH:i:sP'),
        'close_at' => $now->modify('+3 hours')->format('Y-m-d\TH:i:sP'),
        'duration_min' => 20,
        'max_attempts' => 2,
        'shuffle' => false,
        'show_answers' => false,
        'weight' => 15,
    ], ["Authorization: Bearer {$tokenDosen}"]);

    $startAtt2 = qApi('POST', "{$baseUrl}/api/v1/quizzes/{$quizId}/attempts", [], [
        "Authorization: Bearer {$tokenMhs1}",
    ]);
    $att2Id = (int) ($startAtt2['json']['data']['id'] ?? 0);

    // Cari correct option ID lagi karena pgQid options punya order baru setelah test
    $q2List = qApi('GET', "{$baseUrl}/api/v1/quizzes/{$quizId}/questions", null, [
        "Authorization: Bearer {$tokenDosen}",
    ]);
    $pgCorrect2 = 0;
    foreach (($q2List['json']['data'] ?? []) as $qq) {
        if ((int) $qq['id'] === $pgQid) {
            foreach ($qq['options'] as $oo) {
                if (!empty($oo['is_correct'])) {
                    $pgCorrect2 = (int) $oo['id'];
                }
            }
        }
    }

    qApi('PUT', "{$baseUrl}/api/v1/attempts/{$att2Id}/answers", [
        'answers' => [
            ['question_id' => $pgQid, 'option_id' => $pgCorrect2],
        ],
    ], ["Authorization: Bearer {$tokenMhs1}"]);

    $pdo->prepare('UPDATE quiz_attempts SET expires_at = :exp WHERE id = :id')->execute([
        'exp' => $now->modify('-2 minutes')->format('Y-m-d H:i:s'),
        'id' => $att2Id,
    ]);

    $expiredSubmit = qApi('POST', "{$baseUrl}/api/v1/attempts/{$att2Id}/submit", [
        'answers' => [['question_id' => $pgQid, 'option_id' => $wrongOptId]],
    ], ["Authorization: Bearer {$tokenMhs1}"]);

    $expiredFetch = qApi('GET', "{$baseUrl}/api/v1/attempts/{$att2Id}", null, [
        "Authorization: Bearer {$tokenMhs1}",
    ]);
    qCheck(
        $expiredSubmit['status'] === 422
        && ($expiredFetch['json']['data']['status'] ?? '') === 'menunggu_penilaian'
        && (float) ($expiredFetch['json']['data']['score'] ?? -1) === 50.0,
        'Item 20: Submit setelah expires_at ditolak 422 dan jawaban tersimpan terakhir dinilai otomatis',
        $failures,
        $assertions
    );

    // Cleanup test quiz
    $delQuiz = qApi('DELETE', "{$baseUrl}/api/v1/quizzes/{$quizId}", null, [
        "Authorization: Bearer {$tokenDosen}",
    ]);
    qCheck($delQuiz['status'] === 204, 'Item 21: Hapus quiz uji (204)', $failures, $assertions);
    $quizId = 0;

    // Frontend HTTP 200 smoke checks
    $pages = [
        '/quiz-intro.php',
        '/pages/quiz-intro.php',
        '/quiz-attempt.php',
        '/pages/quiz-attempt.php',
        '/quiz-result.php',
        '/pages/quiz-result.php',
        '/quiz-builder.php',
        '/pages/quiz-builder.php',
    ];
    foreach ($pages as $p) {
        $pageRes = qApi('GET', $baseUrl . $p);
        qCheck($pageRes['status'] === 200 && str_contains($pageRes['raw'], '<!DOCTYPE html>'), "Halaman {$p} merespons HTTP 200", $failures, $assertions);
    }
} finally {
    if ($quizId > 0) {
        $pdo->prepare('DELETE FROM quizzes WHERE id = :id')->execute(['id' => $quizId]);
    }
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
}

echo "\nTotal assertions: {$assertions}, Gagal: " . count($failures) . "\n";
if ($failures !== []) {
    exit(1);
}
