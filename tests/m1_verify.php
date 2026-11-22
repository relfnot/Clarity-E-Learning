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

// 1. Cek Seeder DB langsung
$pdo = Database::getConnection();
$dosenCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'dosen'")->fetchColumn();
$mhsCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();
assertTrue($dosenCount >= 2, "DB memiliki minimal 2 user dosen (aktual: {$dosenCount})", $failures, $assertions);
assertTrue($mhsCount === 10, "DB memiliki 10 user mahasiswa (aktual: {$mhsCount})", $failures, $assertions);

$sampleHash = (string) $pdo->query("SELECT password_hash FROM users WHERE nim_nip = '24.N1.0026'")->fetchColumn();
assertTrue($sampleHash !== 'rahasia123' && password_verify('rahasia123', $sampleHash), 'Password tersimpan sebagai hash bcrypt valid', $failures, $assertions);

// 2. Gunakan --url=<base> bila diberikan, atau jalankan PHP built-in server lokal sementara
$customUrl = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--url=')) {
        $customUrl = rtrim(substr($arg, 6), '/');
    }
}

$process = null;
$pipes = [];
if ($customUrl !== null) {
    $baseUrl = $customUrl;
} else {
    $port = 18765;
    $baseUrl = "http://127.0.0.1:{$port}";
    $cmd = sprintf(
        'php -S 127.0.0.1:%d -t %s %s',
        $port,
        escapeshellarg($rootPath . '/public'),
        escapeshellarg($rootPath . '/public/router.php')
    );

    $nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['file', $nullDevice, 'w'],
        2 => ['file', $nullDevice, 'w'],
    ];
    $process = proc_open($cmd, $descriptors, $pipes, $rootPath);
    if (!is_resource($process)) {
        fwrite(STDERR, "Gagal memulai server PHP sementara.\n");
        exit(1);
    }
}

try {
    $ready = false;
    for ($i = 0; $i < 25; $i++) {
        $probe = httpRequest('GET', "{$baseUrl}/api/v1/health");
        if ($probe['status'] === 200) {
            $ready = true;
            break;
        }
        usleep(150000);
    }

    assertTrue($ready, 'Server lokal & /api/v1/health siap merespons', $failures, $assertions);
    if (!$ready) {
        exit(1);
    }

    // 3. Health check
    $health = httpRequest('GET', "{$baseUrl}/api/v1/health");
    assertTrue($health['status'] === 200, 'GET /api/v1/health -> 200', $failures, $assertions);
    assertTrue(($health['json']['data']['status'] ?? '') === 'ok', 'GET /api/v1/health status = ok', $failures, $assertions);
    assertTrue(($health['json']['data']['database'] ?? '') === 'connected', 'GET /api/v1/health database = connected', $failures, $assertions);
    assertTrue(($health['json']['data']['timezone'] ?? '') === 'Asia/Jakarta', 'Timezone Asia/Jakarta aktif', $failures, $assertions);

    // 4. Auth/me tanpa token & token invalid
    $meNoToken = httpRequest('GET', "{$baseUrl}/api/v1/auth/me");
    assertTrue($meNoToken['status'] === 401, 'GET /api/v1/auth/me tanpa token -> 401', $failures, $assertions);
    assertTrue(($meNoToken['json']['error']['code'] ?? '') === 'UNAUTHENTICATED', 'Kode error 401 = UNAUTHENTICATED', $failures, $assertions);

    $meBadToken = httpRequest('GET', "{$baseUrl}/api/v1/auth/me", null, ['Authorization: Bearer invalid.token.here']);
    assertTrue($meBadToken['status'] === 401, 'GET /api/v1/auth/me token tidak valid -> 401', $failures, $assertions);

    // 5. Validasi input kosong (422)
    $loginEmpty = httpRequest('POST', "{$baseUrl}/api/v1/auth/login", ['identifier' => '', 'password' => '']);
    assertTrue($loginEmpty['status'] === 422, 'POST /api/v1/auth/login kosong -> 422', $failures, $assertions);
    assertTrue(($loginEmpty['json']['error']['code'] ?? '') === 'VALIDATION_ERROR', 'Kode error 422 = VALIDATION_ERROR', $failures, $assertions);

    // 6. Login Mahasiswa via NIM
    $loginMhs = httpRequest('POST', "{$baseUrl}/api/v1/auth/login", [
        'identifier' => '24.N1.0026',
        'password' => 'rahasia123',
    ]);
    assertTrue($loginMhs['status'] === 200, 'Login mahasiswa NIM 24.N1.0026 -> 200', $failures, $assertions);
    $mhsToken = (string) ($loginMhs['json']['data']['token'] ?? '');
    assertTrue($mhsToken !== '', 'Respons login mahasiswa mengembalikan token JWT', $failures, $assertions);
    assertTrue(str_ends_with((string) ($loginMhs['json']['data']['expires_at'] ?? ''), '+07:00'), 'expires_at format ISO 8601 +07:00', $failures, $assertions);
    assertTrue(($loginMhs['json']['data']['user']['role'] ?? '') === 'mahasiswa', 'Role user = mahasiswa', $failures, $assertions);
    assertTrue(!isset($loginMhs['json']['data']['user']['password_hash']), 'password_hash tidak terekspos di respons JSON', $failures, $assertions);

    // 7. Login Dosen via Email
    $loginDsn = httpRequest('POST', "{$baseUrl}/api/v1/auth/login", [
        'identifier' => 'brenda.chandrawati@unika.ac.id',
        'password' => 'rahasia123',
    ]);
    assertTrue($loginDsn['status'] === 200, 'Login dosen via email -> 200', $failures, $assertions);
    $dsnToken = (string) ($loginDsn['json']['data']['token'] ?? '');
    assertTrue(($loginDsn['json']['data']['user']['role'] ?? '') === 'dosen', 'Role user = dosen', $failures, $assertions);

    // 8. /auth/me dengan token valid
    $meOk = httpRequest('GET', "{$baseUrl}/api/v1/auth/me", null, ["Authorization: Bearer {$mhsToken}"]);
    assertTrue($meOk['status'] === 200 && ($meOk['json']['data']['nim_nip'] ?? '') === '24.N1.0026', 'GET /api/v1/auth/me dengan token mahasiswa -> 200 & profil cocok', $failures, $assertions);

    // 9. RoleMiddleware('dosen'): mahasiswa ditolak 403, dosen diizinkan 200
    $roleMhsDenied = httpRequest('GET', "{$baseUrl}/api/v1/auth/dosen-only", null, ["Authorization: Bearer {$mhsToken}"]);
    assertTrue($roleMhsDenied['status'] === 403 && ($roleMhsDenied['json']['error']['code'] ?? '') === 'FORBIDDEN', 'RoleMiddleware(dosen) menolak mahasiswa -> 403 FORBIDDEN', $failures, $assertions);

    $roleDsnAllowed = httpRequest('GET', "{$baseUrl}/api/v1/auth/dosen-only", null, ["Authorization: Bearer {$dsnToken}"]);
    assertTrue($roleDsnAllowed['status'] === 200, 'RoleMiddleware(dosen) mengizinkan dosen -> 200', $failures, $assertions);

    // 10. Logout
    $logoutRes = httpRequest('POST', "{$baseUrl}/api/v1/auth/logout", [], ["Authorization: Bearer {$mhsToken}"]);
    assertTrue($logoutRes['status'] === 200, 'POST /api/v1/auth/logout -> 200', $failures, $assertions);

    // 11. Rate limit: 5 gagal -> 401 ("Kredensial salah"), percobaan ke-6 -> 429 RATE_LIMITED
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $failRes = httpRequest('POST', "{$baseUrl}/api/v1/auth/login", [
            'identifier' => '24.N1.0001',
            'password' => 'salah_password',
        ]);
        assertTrue(
            $failRes['status'] === 401 && ($failRes['json']['error']['message'] ?? '') === 'Kredensial salah',
            "Percobaan gagal ke-{$attempt} -> 401 pesan generik 'Kredensial salah'",
            $failures,
            $assertions
        );
    }

    $sixthAttempt = httpRequest('POST', "{$baseUrl}/api/v1/auth/login", [
        'identifier' => '24.N1.0001',
        'password' => 'salah_password',
    ]);
    assertTrue(
        $sixthAttempt['status'] === 429 && ($sixthAttempt['json']['error']['code'] ?? '') === 'RATE_LIMITED',
        'Percobaan login ke-6 beruntun -> 429 RATE_LIMITED',
        $failures,
        $assertions
    );

    // 12. Uji halaman frontend PHP & kompatibilitas .html + aset statis
    $staticPaths = [
        '/',
        '/index.php',
        '/login.php',
        '/dashboard.php',
        '/dashboard-dosen.php',
        '/403.php',
        '/404.php',
        '/pages/login.php',
        '/pages/dashboard.php',
        '/pages/dashboard-dosen.php',
        '/pages/403.php',
        '/pages/404.php',
        '/pages/login.html',
        '/login.html',
        '/assets/css/tokens.css',
        '/assets/css/components.css',
        '/assets/js/api.js',
        '/assets/js/auth.js',
        '/assets/js/layout.js',
        '/assets/js/login.js',
        '/assets/js/dashboard.js',
        '/assets/img/logo.svg',
    ];
    foreach ($staticPaths as $staticPath) {
        $res = httpRequest('GET', $baseUrl . $staticPath);
        assertTrue($res['status'] === 200, "GET {$staticPath} -> 200 (actual: {$res['status']})", $failures, $assertions);
    }

    $loginHtml = httpRequest('GET', "{$baseUrl}/login.php");
    assertTrue(
        str_contains($loginHtml['raw'], 'basecoat.cdn.min.css') && str_contains($loginHtml['raw'], 'cdn.tailwindcss.com'),
        'Halaman login.php memuat Basecoat UI CDN & Tailwind CSS CDN',
        $failures,
        $assertions
    );

    $dashHtml = httpRequest('GET', "{$baseUrl}/dashboard.php");
    assertTrue(
        str_contains($dashHtml['raw'], 'basecoat.cdn.min.css') && str_contains($dashHtml['raw'], 'data-task-filter'),
        'Halaman dashboard.php menggunakan tampilan PHP baru (Basecoat + Tailwind + filter interaktif)',
        $failures,
        $assertions
    );

    $notFoundPage = httpRequest('GET', "{$baseUrl}/halaman-tidak-ada.html");
    assertTrue($notFoundPage['status'] === 404, "GET halaman tidak ada -> 404 (actual: {$notFoundPage['status']})", $failures, $assertions);

    if ($customUrl !== null && str_ends_with($customUrl, '/public')) {
        $projectRootUrl = substr($customUrl, 0, -strlen('/public'));
        $rootApiHealth = httpRequest('GET', "{$projectRootUrl}/api/v1/health");
        assertTrue($rootApiHealth['status'] === 200 && ($rootApiHealth['json']['data']['status'] ?? '') === 'ok', "GET {$projectRootUrl}/api/v1/health (root .htaccess) -> 200", $failures, $assertions);
        $swaggerUi = httpRequest('GET', "{$projectRootUrl}/docs/api/");
        assertTrue($swaggerUi['status'] === 200, "GET {$projectRootUrl}/docs/api/ (Swagger UI) -> 200", $failures, $assertions);
    }

    // Bersihkan login_attempts sisa pengujian rate limit
    $pdo->exec('DELETE FROM login_attempts');
} finally {
    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
}

echo "\nTotal assertions: {$assertions}, Gagal: " . count($failures) . "\n";
exit($failures === [] ? 0 : 1);
