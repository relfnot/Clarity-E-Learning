<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;

$rootPath = dirname(__DIR__);
require_once $rootPath . '/vendor/autoload.php';
Env::load($rootPath);

$assertions = 0;
$failures = [];

function assertTrue(bool $condition, string $label, array &$failures, int &$assertions): void
{
    $assertions++;
    if (!$condition) {
        $failures[] = $label;
        echo "[FAIL] {$label}\n";
    } else {
        echo "[PASS] {$label}\n";
    }
}

function httpRequest(string $method, string $url, ?array $body = null, array $headers = []): array
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

function multipartRequest(string $url, array $fields, ?array $fileField, array $headers = []): array
{
    $boundary = '----ClarityBoundary' . bin2hex(random_bytes(8));
    $eol = "\r\n";
    $body = '';

    foreach ($fields as $name => $value) {
        $body .= '--' . $boundary . $eol;
        $body .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
        $body .= (string) $value . $eol;
    }

    if ($fileField !== null) {
        $body .= '--' . $boundary . $eol;
        $body .= 'Content-Disposition: form-data; name="' . $fileField['name'] . '"; filename="' . $fileField['filename'] . '"' . $eol;
        $body .= 'Content-Type: ' . $fileField['mime'] . $eol . $eol;
        $body .= $fileField['content'] . $eol;
    }

    $body .= '--' . $boundary . '--' . $eol;

    $headerLines = array_merge([
        'Accept: application/json',
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'Connection: close',
    ], $headers);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headerLines) . "\r\n",
            'content' => $body,
            'ignore_errors' => true,
            'timeout' => 10,
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

function loginAs(string $baseUrl, string $identifier, string $password = 'rahasia123'): string
{
    $res = httpRequest('POST', "{$baseUrl}/api/v1/auth/login", [
        'identifier' => $identifier,
        'password' => $password,
    ]);
    return (string) ($res['json']['data']['token'] ?? '');
}

$pdo = Database::getConnection();

$port = 18766;
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
        $probe = httpRequest('GET', "{$baseUrl}/api/v1/health");
        if ($probe['status'] === 200) {
            $ready = true;
            break;
        }
        usleep(100000);
    }
    assertTrue($ready, 'Server PHP sementara merespons /api/v1/health', $failures, $assertions);

    $tokenDosenBrenda = loginAs($baseUrl, '5811995177');
    $tokenDosenStephani = loginAs($baseUrl, '5812023424');
    $tokenMhsEnrolled = loginAs($baseUrl, '24.N1.0026');
    $tokenMhsOther = loginAs($baseUrl, '24.N1.0007'); // not enrolled in course 3

    assertTrue($tokenDosenBrenda !== '', 'Login dosen Brenda berhasil', $failures, $assertions);
    assertTrue($tokenMhsEnrolled !== '', 'Login mahasiswa 24.N1.0026 berhasil', $failures, $assertions);
    assertTrue($tokenMhsOther !== '', 'Login mahasiswa 24.N1.0007 berhasil', $failures, $assertions);

    // M2: Course authorization (pastikan 24.N1.0007 tidak terdaftar di 07228203 sementara untuk uji 403/404)
    $c3Id = (int) $pdo->query("SELECT id FROM courses WHERE code = '07228203'")->fetchColumn();
    $c1Id = (int) $pdo->query("SELECT id FROM courses WHERE code = '07228204'")->fetchColumn();
    $m10Id = (int) $pdo->query("SELECT id FROM meetings WHERE course_id = {$c1Id} AND number = 10")->fetchColumn();
    $mhsOtherId = (int) $pdo->query("SELECT id FROM users WHERE nim_nip = '24.N1.0007'")->fetchColumn();
    $pdo->prepare("DELETE FROM course_enrollments WHERE course_id = :cid AND student_id = :sid")->execute(['cid' => $c3Id, 'sid' => $mhsOtherId]);

    $unauthCourse = httpRequest('GET', "{$baseUrl}/api/v1/courses/{$c3Id}", null, [
        "Authorization: Bearer {$tokenMhsOther}",
    ]);
    assertTrue(in_array($unauthCourse['status'], [403, 404], true), 'Mahasiswa non-peserta ditolak akses detail mata kuliah (403/404)', $failures, $assertions);

    // M2: Upload .exe ditolak (422)
    $exeUpload = multipartRequest(
        "{$baseUrl}/api/v1/courses/{$c1Id}/materials",
        ['meeting_id' => $m10Id, 'title' => 'Malware Test', 'type' => 'file'],
        ['name' => 'file', 'filename' => 'virus.exe', 'mime' => 'application/octet-stream', 'content' => 'MZtest'],
        ["Authorization: Bearer {$tokenDosenBrenda}"]
    );
    assertTrue($exeUpload['status'] === 422, 'Upload file .exe ditolak dengan status 422', $failures, $assertions);

    // M2: Upload valid txt -> 201 & downloadable
    $txtUpload = multipartRequest(
        "{$baseUrl}/api/v1/courses/{$c1Id}/materials",
        ['meeting_id' => $m10Id, 'title' => 'Materi Uji Otomatis', 'type' => 'file'],
        ['name' => 'file', 'filename' => 'uji_materi.txt', 'mime' => 'text/plain', 'content' => 'Isi materi uji Clarity'],
        ["Authorization: Bearer {$tokenDosenBrenda}"]
    );
    assertTrue($txtUpload['status'] === 201, 'Dosen berhasil upload materi txt valid (201)', $failures, $assertions);
    $uploadedMatId = (int) ($txtUpload['json']['data']['id'] ?? 0);

    $dlMat = httpRequest('GET', "{$baseUrl}/api/v1/materials/{$uploadedMatId}/download", null, [
        "Authorization: Bearer {$tokenMhsEnrolled}",
    ]);
    assertTrue($dlMat['status'] === 200 && str_contains($dlMat['raw'], 'Isi materi uji Clarity'), 'Mahasiswa peserta dapat mengunduh materi yang diunggah', $failures, $assertions);

    // Cleanup uploaded test material
    httpRequest('DELETE', "{$baseUrl}/api/v1/materials/{$uploadedMatId}", null, [
        "Authorization: Bearer {$tokenDosenBrenda}",
    ]);

    // M3: Tugas mode link-only menolak file (422)
    $linkOnlyAsgId = (int) $pdo->query("SELECT id FROM assignments WHERE course_id = {$c1Id} AND accept_file = 0 AND accept_link = 1 LIMIT 1")->fetchColumn();
    $rejectFileSub = multipartRequest(
        "{$baseUrl}/api/v1/assignments/{$linkOnlyAsgId}/submissions",
        [],
        ['name' => 'file', 'filename' => 'jawaban.txt', 'mime' => 'text/plain', 'content' => 'test'],
        ["Authorization: Bearer {$tokenMhsEnrolled}"]
    );
    assertTrue($rejectFileSub['status'] === 422, 'Tugas mode hanya-tautan menolak unggahan file (422)', $failures, $assertions);

    // M3: Submit file valid pada Tugas 4 -> 201, tidak bisa diunduh mahasiswa lain / dosen lain (403)
    $bothAsgId = (int) $pdo->query("SELECT id FROM assignments WHERE course_id = {$c1Id} AND accept_file = 1 LIMIT 1")->fetchColumn();
    $validSub = multipartRequest(
        "{$baseUrl}/api/v1/assignments/{$bothAsgId}/submissions",
        [],
        ['name' => 'file', 'filename' => 'jawaban_mhs.txt', 'mime' => 'text/plain', 'content' => 'Jawaban REST API'],
        ["Authorization: Bearer {$tokenMhsEnrolled}"]
    );
    assertTrue($validSub['status'] === 201, 'Mahasiswa berhasil mengumpulkan berkas tugas (201)', $failures, $assertions);
    $subId = (int) ($validSub['json']['data']['id'] ?? 0);

    $otherStudentDl = httpRequest('GET', "{$baseUrl}/api/v1/submissions/{$subId}/download", null, [
        "Authorization: Bearer {$tokenMhsOther}",
    ]);
    assertTrue($otherStudentDl['status'] === 403, 'Mahasiswa lain tidak dapat mengunduh berkas pengumpulan (403)', $failures, $assertions);

    $otherDosenDl = httpRequest('GET', "{$baseUrl}/api/v1/submissions/{$subId}/download", null, [
        "Authorization: Bearer {$tokenDosenStephani}",
    ]);
    assertTrue($otherDosenDl['status'] === 403, 'Dosen mata kuliah lain tidak dapat mengunduh berkas pengumpulan (403)', $failures, $assertions);

    $lecturerSubs = httpRequest('GET', "{$baseUrl}/api/v1/assignments/{$bothAsgId}/submissions", null, [
        "Authorization: Bearer {$tokenDosenBrenda}",
    ]);
    assertTrue(
        $lecturerSubs['status'] === 200 && isset($lecturerSubs['json']['meta']['summary']['belum']),
        'Dosen dapat melihat tabel pengumpulan beserta summary tepat_waktu/terlambat/belum',
        $failures,
        $assertions
    );

    // M4: Presensi (uji pada pertemuan 8 di course 07228204 milik Brenda, dan uji non-peserta di course 07228203)
    $c1M9Id = (int) $pdo->query("SELECT id FROM meetings WHERE course_id = {$c1Id} AND number = 9")->fetchColumn();
    $openSess = httpRequest('POST', "{$baseUrl}/api/v1/meetings/{$c1M9Id}/attendance-session", [
        'passcode' => 'MANPRO9',
    ], ["Authorization: Bearer {$tokenDosenBrenda}"]);
    assertTrue($openSess['status'] === 201, 'Dosen berhasil membuka sesi presensi pertemuan (201)', $failures, $assertions);
    $sessId = (int) ($openSess['json']['data']['id'] ?? 0);

    $dupSess = httpRequest('POST', "{$baseUrl}/api/v1/meetings/{$c1M9Id}/attendance-session", [], [
        "Authorization: Bearer {$tokenDosenBrenda}",
    ]);
    assertTrue($dupSess['status'] === 409, 'Membuka sesi ganda pada pertemuan yang sama ditolak (409)', $failures, $assertions);

    $validCheckin = httpRequest('POST', "{$baseUrl}/api/v1/attendance-sessions/{$sessId}/check-in", [
        'passcode' => 'MANPRO9',
    ], ["Authorization: Bearer {$tokenMhsEnrolled}"]);
    assertTrue($validCheckin['status'] === 201, 'Mahasiswa peserta berhasil check-in hadir (201)', $failures, $assertions);

    $dupCheckin = httpRequest('POST', "{$baseUrl}/api/v1/attendance-sessions/{$sessId}/check-in", [
        'passcode' => 'MANPRO9',
    ], ["Authorization: Bearer {$tokenMhsEnrolled}"]);
    assertTrue($dupCheckin['status'] === 409, 'Check-in ganda ditolak dengan 409 CONFLICT', $failures, $assertions);

    $closeSess = httpRequest('PATCH', "{$baseUrl}/api/v1/attendance-sessions/{$sessId}/close", [], [
        "Authorization: Bearer {$tokenDosenBrenda}",
    ]);
    assertTrue($closeSess['status'] === 200, 'Dosen berhasil menutup sesi presensi (200)', $failures, $assertions);

    $closedCheckin = httpRequest('POST', "{$baseUrl}/api/v1/attendance-sessions/{$sessId}/check-in", [
        'passcode' => 'MANPRO9',
    ], ["Authorization: Bearer {$tokenMhsEnrolled}"]);
    assertTrue($closedCheckin['status'] === 422, 'Check-in pada sesi tertutup ditolak (422)', $failures, $assertions);

    // Cleanup test attendance session & restore enrollment
    $pdo->prepare('DELETE FROM attendance_sessions WHERE id = :id')->execute(['id' => $sessId]);
    $pdo->prepare("INSERT IGNORE INTO course_enrollments (course_id, student_id, status) VALUES (:cid, :sid, 'active')")->execute(['cid' => $c3Id, 'sid' => $mhsOtherId]);
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
