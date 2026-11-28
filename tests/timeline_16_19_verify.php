<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;

$rootPath = dirname(__DIR__);
require_once $rootPath . '/vendor/autoload.php';
Env::load($rootPath);

$assertions = 0;
$failures = [];

function check(bool $condition, string $label, array &$failures, int &$assertions): void
{
    $assertions++;
    if (!$condition) {
        $failures[] = $label;
        echo "[FAIL] {$label}\n";
    } else {
        echo "[PASS] {$label}\n";
    }
}

function callApi(string $method, string $url, ?array $body = null, array $headers = []): array
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
    $respHeaders = $http_response_header ?? [];
    if (isset($respHeaders[0]) && preg_match('/HTTP\/\S+\s+(\d{3})/', $respHeaders[0], $m) === 1) {
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
        'headers' => $respHeaders,
        'raw' => is_string($raw) ? $raw : '',
        'json' => $json,
    ];
}

function loginToken(string $baseUrl, string $identifier, string $password = 'rahasia123'): string
{
    $res = callApi('POST', "{$baseUrl}/api/v1/auth/login", [
        'identifier' => $identifier,
        'password' => $password,
    ]);
    return (string) ($res['json']['data']['token'] ?? '');
}

$pdo = Database::getConnection();
$port = 18769;
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

try {
    $ready = false;
    for ($i = 0; $i < 25; $i++) {
        if (callApi('GET', "{$baseUrl}/api/v1/health")['status'] === 200) {
            $ready = true;
            break;
        }
        usleep(100000);
    }
    check($ready, 'Server PHP sementara merespons /api/v1/health', $failures, $assertions);

    $tokenDosenBrenda = loginToken($baseUrl, '5811995177');
    $tokenMhsEnrolled = loginToken($baseUrl, '24.N1.0026');
    $tokenMhsOther = loginToken($baseUrl, '24.N1.0007');
    check($tokenDosenBrenda !== '' && $tokenMhsEnrolled !== '' && $tokenMhsOther !== '', 'Login demo dosen dan mahasiswa berhasil', $failures, $assertions);

    $c1Id = (int) $pdo->query("SELECT id FROM courses WHERE code = '07228204'")->fetchColumn();
    $c3Id = (int) $pdo->query("SELECT id FROM courses WHERE code = '07228203'")->fetchColumn();
    $mhsOtherId = (int) $pdo->query("SELECT id FROM users WHERE nim_nip = '24.N1.0007'")->fetchColumn();
    $pdo->prepare('DELETE FROM course_enrollments WHERE course_id = :cid AND student_id = :sid')->execute([
        'cid' => $c3Id,
        'sid' => $mhsOtherId,
    ]);

    // ---------------------------------------------------------------------
    // ITEM 16: Daftar Peserta Mata Kuliah (Search, Pagination, Role, Access)
    // ---------------------------------------------------------------------
    $partPaged = callApi('GET', "{$baseUrl}/api/v1/courses/{$c1Id}/participants?page=1&per_page=2", null, [
        "Authorization: Bearer {$tokenMhsEnrolled}",
    ]);
    $firstPart = $partPaged['json']['data'][0] ?? [];
    check(
        $partPaged['status'] === 200
        && count($partPaged['json']['data'] ?? []) === 2
        && isset($partPaged['json']['meta']['total'], $partPaged['json']['meta']['total_pages'], $partPaged['json']['meta']['lecturer'])
        && ($firstPart['role'] ?? '') === 'mahasiswa'
        && !empty($firstPart['nim_nip']),
        'Item 16: GET /courses/{id}/participants mendukung pagination, role, nim_nip, dan meta.lecturer',
        $failures,
        $assertions
    );

    $partSearch = callApi('GET', "{$baseUrl}/api/v1/courses/{$c1Id}/participants?search=24.N1.0026", null, [
        "Authorization: Bearer {$tokenDosenBrenda}",
    ]);
    check(
        $partSearch['status'] === 200
        && count($partSearch['json']['data'] ?? []) === 1
        && ($partSearch['json']['data'][0]['nim_nip'] ?? '') === '24.N1.0026',
        'Item 16: GET /courses/{id}/participants mendukung filter ?search=',
        $failures,
        $assertions
    );

    $partForbidden = callApi('GET', "{$baseUrl}/api/v1/courses/{$c3Id}/participants", null, [
        "Authorization: Bearer {$tokenMhsOther}",
    ]);
    check($partForbidden['status'] === 403, 'Item 16: Mahasiswa non-peserta ditolak melihat peserta kelas (403)', $failures, $assertions);

    // ---------------------------------------------------------------------
    // ITEM 17: Presensi Mahasiswa & Dosen (Check-in, Close->Alpa, Update Status)
    // ---------------------------------------------------------------------
    $meetingId = (int) $pdo->query("SELECT id FROM meetings WHERE course_id = {$c1Id} AND number = 9")->fetchColumn();
    $openSess = callApi('POST', "{$baseUrl}/api/v1/meetings/{$meetingId}/attendance-session", [
        'passcode' => 'UJI17',
    ], ["Authorization: Bearer {$tokenDosenBrenda}"]);
    $sessId = (int) ($openSess['json']['data']['id'] ?? 0);
    check($openSess['status'] === 201 && $sessId > 0, 'Item 17: Dosen dapat membuka sesi presensi pertemuan', $failures, $assertions);

    $checkIn = callApi('POST', "{$baseUrl}/api/v1/attendance-sessions/{$sessId}/check-in", [
        'passcode' => 'UJI17',
    ], ["Authorization: Bearer {$tokenMhsEnrolled}"]);
    check($checkIn['status'] === 201 && ($checkIn['json']['data']['status'] ?? '') === 'hadir', 'Item 17: Mahasiswa berhasil check-in Hadir', $failures, $assertions);

    $dupCheckIn = callApi('POST', "{$baseUrl}/api/v1/attendance-sessions/{$sessId}/check-in", [
        'passcode' => 'UJI17',
    ], ["Authorization: Bearer {$tokenMhsEnrolled}"]);
    check($dupCheckIn['status'] === 409, 'Item 17: Check-in ganda ditolak (409)', $failures, $assertions);

    $closeSess = callApi('PATCH', "{$baseUrl}/api/v1/attendance-sessions/{$sessId}/close", [], [
        "Authorization: Bearer {$tokenDosenBrenda}",
    ]);
    check($closeSess['status'] === 200 && ($closeSess['json']['data']['status'] ?? '') === 'closed', 'Item 17: Dosen dapat menutup sesi presensi', $failures, $assertions);

    $rosterAfterClose = callApi('GET', "{$baseUrl}/api/v1/meetings/{$meetingId}/attendance", null, [
        "Authorization: Bearer {$tokenDosenBrenda}",
    ]);
    $alpaRow = null;
    foreach (($rosterAfterClose['json']['data'] ?? []) as $row) {
        if (($row['status'] ?? '') === 'alpa') {
            $alpaRow = $row;
            break;
        }
    }
    check($alpaRow !== null && !empty($alpaRow['id']), 'Item 17: Menutup sesi otomatis menandai mahasiswa belum check-in menjadi alpa', $failures, $assertions);

    if ($alpaRow !== null) {
        $updRec = callApi('PUT', "{$baseUrl}/api/v1/attendance-records/{$alpaRow['id']}", [
            'status' => 'izin',
        ], ["Authorization: Bearer {$tokenDosenBrenda}"]);
        check($updRec['status'] === 200 && ($updRec['json']['data']['status'] ?? '') === 'izin', 'Item 17: Dosen dapat mengubah status alpa menjadi izin', $failures, $assertions);
    }

    $summary = callApi('GET', "{$baseUrl}/api/v1/courses/{$c1Id}/attendance/summary", null, [
        "Authorization: Bearer {$tokenMhsEnrolled}",
    ]);
    check($summary['status'] === 200 && isset($summary['json']['data']['attendance_percent'], $summary['json']['data']['counts']['hadir']), 'Item 17: Summary kehadiran mengembalikan counts & persentase', $failures, $assertions);

    $pdo->prepare('DELETE FROM attendance_sessions WHERE id = :id')->execute(['id' => $sessId]);

    // ---------------------------------------------------------------------
    // ITEM 18: Kalender & Deadline (GET /api/v1/calendar, WIB, Scoping)
    // ---------------------------------------------------------------------
    $calRes = callApi('GET', "{$baseUrl}/api/v1/calendar?from=2026-11-01&to=2026-12-31", null, [
        "Authorization: Bearer {$tokenMhsEnrolled}",
    ]);
    $calItems = $calRes['json']['data'] ?? [];
    $typesSeen = [];
    $allWib = true;
    foreach ($calItems as $ev) {
        $typesSeen[$ev['type'] ?? ''] = true;
        if (!str_ends_with((string) ($ev['start_at'] ?? ''), '+07:00')) {
            $allWib = false;
        }
    }
    check(
        $calRes['status'] === 200
        && isset($typesSeen['lecture'], $typesSeen['assignment_deadline'], $typesSeen['quiz_window'])
        && $allWib,
        'Item 18: GET /calendar menggabungkan lecture, assignment_deadline, quiz_window dalam zona +07:00 WIB',
        $failures,
        $assertions
    );

    $calCourseRes = callApi('GET', "{$baseUrl}/api/v1/calendar?from=2026-11-01&to=2026-12-31&course_id={$c1Id}", null, [
        "Authorization: Bearer {$tokenMhsEnrolled}",
    ]);
    $onlyC1 = true;
    foreach (($calCourseRes['json']['data'] ?? []) as $ev) {
        if ((int) ($ev['course_id'] ?? 0) !== $c1Id) {
            $onlyC1 = false;
        }
    }
    check($calCourseRes['status'] === 200 && count($calCourseRes['json']['data'] ?? []) > 0 && $onlyC1, 'Item 18: Filter ?course_id membatasi event hanya ke mata kuliah tersebut', $failures, $assertions);

    $calForbidden = callApi('GET', "{$baseUrl}/api/v1/calendar?from=2026-11-01&to=2026-12-31&course_id={$c3Id}", null, [
        "Authorization: Bearer {$tokenMhsOther}",
    ]);
    check($calForbidden['status'] === 403, 'Item 18: Akses kalender mata kuliah yang tidak diikuti ditolak (403)', $failures, $assertions);

    $calInvalidDate = callApi('GET', "{$baseUrl}/api/v1/calendar?from=2026-12-31&to=2026-11-01", null, [
        "Authorization: Bearer {$tokenMhsEnrolled}",
    ]);
    check($calInvalidDate['status'] === 422, 'Item 18: Rentang tanggal terbalik (from > to) ditolak (422)', $failures, $assertions);

    // ---------------------------------------------------------------------
    // ITEM 19: Daftar Pengumpulan Tugas (Filter, Search, Summary, CSV)
    // ---------------------------------------------------------------------
    $gradedAsgId = (int) $pdo->query("SELECT assignment_id FROM submissions WHERE score IS NOT NULL LIMIT 1")->fetchColumn();
    if ($gradedAsgId <= 0) {
        $gradedAsgId = (int) $pdo->query("SELECT id FROM assignments WHERE course_id = {$c1Id} LIMIT 1")->fetchColumn();
    }

    $subAll = callApi('GET', "{$baseUrl}/api/v1/assignments/{$gradedAsgId}/submissions", null, [
        "Authorization: Bearer {$tokenDosenBrenda}",
    ]);
    $sum = $subAll['json']['meta']['summary'] ?? [];
    check(
        $subAll['status'] === 200
        && isset($sum['total'], $sum['tepat_waktu'], $sum['terlambat'], $sum['belum'], $sum['dinilai'])
        && ((int) $sum['tepat_waktu'] + (int) $sum['terlambat'] + (int) $sum['belum'] + (int) $sum['dinilai']) === (int) $sum['total'],
        'Item 19: Summary pengumpulan memuat total, tepat_waktu, terlambat, belum, dan dinilai secara konsisten',
        $failures,
        $assertions
    );

    $subSearch = callApi('GET', "{$baseUrl}/api/v1/assignments/{$gradedAsgId}/submissions?search=24.N1.0026", null, [
        "Authorization: Bearer {$tokenDosenBrenda}",
    ]);
    check(
        $subSearch['status'] === 200
        && count($subSearch['json']['data'] ?? []) === 1
        && ($subSearch['json']['data'][0]['nim_nip'] ?? '') === '24.N1.0026',
        'Item 19: Filter ?search= pada tabel pengumpulan mengembalikan mahasiswa yang tepat',
        $failures,
        $assertions
    );

    $subCsv = callApi('GET', "{$baseUrl}/api/v1/assignments/{$gradedAsgId}/submissions?format=csv", null, [
        "Authorization: Bearer {$tokenDosenBrenda}",
    ]);
    $hasCsvHeader = false;
    foreach ($subCsv['headers'] as $h) {
        if (stripos($h, 'text/csv') !== false) {
            $hasCsvHeader = true;
        }
    }
    check(
        $subCsv['status'] === 200 && $hasCsvHeader && str_contains($subCsv['raw'], 'NIM,Nama Mahasiswa,Status'),
        'Item 19: Ekspor CSV pengumpulan mengembalikan text/csv dengan kolom standar',
        $failures,
        $assertions
    );

    // ---------------------------------------------------------------------
    // Frontend Pages HTTP 200 Smoke Check
    // ---------------------------------------------------------------------
    $pages = [
        '/calendar.php',
        '/pages/calendar.php',
        '/course-detail.php',
        '/pages/course-detail.php',
        '/pages/courses.php',
        '/attendance.php',
        '/attendance-manage.php',
        '/submissions.php',
    ];
    foreach ($pages as $pagePath) {
        $res = callApi('GET', $baseUrl . $pagePath);
        check(
            $res['status'] === 200 && str_contains($res['raw'], '<!DOCTYPE html>'),
            "Halaman {$pagePath} merespons HTTP 200",
            $failures,
            $assertions
        );
    }

    $pdo->prepare("INSERT IGNORE INTO course_enrollments (course_id, student_id, status) VALUES (:cid, :sid, 'active')")->execute([
        'cid' => $c3Id,
        'sid' => $mhsOtherId,
    ]);
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
}

echo "\nTotal assertions: {$assertions}, Gagal: " . count($failures) . "\n";
if ($failures !== []) {
    exit(1);
}
