<?php

declare(strict_types=1);

namespace Database\Seeds;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class AcademicModulesSeeder
{
    public static function run(PDO $pdo, string $rootPath): array
    {
        $tz = new DateTimeZone('Asia/Jakarta');
        $now = new DateTimeImmutable('now', $tz);

        $coursesRows = $pdo->query('SELECT id, code, lecturer_id FROM courses ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
        $courseMap = [];
        foreach ($coursesRows as $row) {
            $courseMap[(string) $row['code']] = [
                'id' => (int) $row['id'],
                'lecturer_id' => (int) $row['lecturer_id'],
            ];
        }

        if (!isset($courseMap['07228204'], $courseMap['07228239'], $courseMap['07228203'])) {
            return ['meetings' => 0, 'materials' => 0, 'assignments' => 0, 'sessions' => 0];
        }

        $meetingDefs = [
            '07228204' => [
                [8, 'Prinsip RESTful API & Kontrak OpenAPI', 'Perancangan endpoint konsisten dengan envelope data/meta.', $now->modify('-14 days')->format('Y-m-d')],
                [9, 'Autentikasi JWT & Kontrol Akses Berbasis Peran', 'Implementasi token HS256, middleware auth, dan guard role.', $now->modify('-7 days')->format('Y-m-d')],
                [10, 'Keamanan Input, PDO Prepared Statement & Upload File', 'Pencegahan SQL Injection, XSS, dan validasi MIME via finfo.', $now->format('Y-m-d')],
            ],
            '07228239' => [
                [8, 'Arsitektur Payment Gateway & Webhook', 'Alur pembayaran real-time dan verifikasi tanda tangan webhook.', $now->modify('-14 days')->format('Y-m-d')],
                [9, 'Diagram Sequence & Rekonsiliasi Transaksi', 'Pemodelan sequence transaksi dan audit trail ledger.', $now->modify('-7 days')->format('Y-m-d')],
                [10, 'Kepatuhan Keamanan Data Keuangan', 'Enkripsi data sensitif dan kontrol akses berjenjang.', $now->format('Y-m-d')],
            ],
            '07228203' => [
                [8, 'Kode Etik Profesi Rekayasa Perangkat Lunak', 'Studi kasus integritas dan akuntabilitas pengembang.', $now->modify('-14 days')->format('Y-m-d')],
                [9, 'Privasi & Perlindungan Data Pribadi', 'Prinsip minimisasi data dan persetujuan pengguna.', $now->modify('-7 days')->format('Y-m-d')],
                [10, 'Etika Sistem Cerdas & Transparansi Algoritma', 'Mitigasi bias dan kejelasan pengambilan keputusan otomatis.', $now->format('Y-m-d')],
            ],
        ];

        $meetingStmt = $pdo->prepare(
            'INSERT INTO meetings (course_id, number, title, description, meeting_date)
             VALUES (:course_id, :number, :title, :description, :meeting_date)
             ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                description = VALUES(description),
                meeting_date = VALUES(meeting_date)'
        );

        $meetingIds = [];
        $meetingCount = 0;
        foreach ($meetingDefs as $code => $rows) {
            $cid = $courseMap[$code]['id'];
            foreach ($rows as [$num, $title, $desc, $mDate]) {
                $meetingStmt->execute([
                    'course_id' => $cid,
                    'number' => $num,
                    'title' => $title,
                    'description' => $desc,
                    'meeting_date' => $mDate,
                ]);
                $mIdStmt = $pdo->prepare('SELECT id FROM meetings WHERE course_id = :cid AND number = :num');
                $mIdStmt->execute(['cid' => $cid, 'num' => $num]);
                $meetingIds[$code][$num] = (int) $mIdStmt->fetchColumn();
                $meetingCount++;
            }
        }

        // Sample material file on disk
        $materialsDir = $rootPath . '/storage/uploads/materials';
        if (!is_dir($materialsDir)) {
            mkdir($materialsDir, 0775, true);
        }
        $sampleRelPath = 'materials/seed_p08_rest_api_guide.txt';
        $sampleAbsPath = $rootPath . '/storage/uploads/' . $sampleRelPath;
        if (!is_file($sampleAbsPath)) {
            file_put_contents(
                $sampleAbsPath,
                "Clarity E-Learning - Panduan Pertemuan 8: Prinsip RESTful API\n" .
                "1. Gunakan base path /api/v1 dan format JSON UTF-8.\n" .
                "2. Semua query database wajib memakai PDO prepared statement.\n" .
                "3. File unggahan disimpan di luar folder public dengan nama acak.\n"
            );
        }
        $sampleBytes = filesize($sampleAbsPath) ?: 180;

        $materialCount = (int) $pdo->query('SELECT COUNT(*) FROM materials')->fetchColumn();
        if ($materialCount === 0) {
            $matStmt = $pdo->prepare(
                'INSERT INTO materials (course_id, meeting_id, title, type, file_path, file_name, file_size_bytes, mime_type, url, uploaded_by)
                 VALUES (:course_id, :meeting_id, :title, :type, :file_path, :file_name, :file_size_bytes, :mime_type, :url, :uploaded_by)'
            );

            $matStmt->execute([
                'course_id' => $courseMap['07228204']['id'],
                'meeting_id' => $meetingIds['07228204'][8],
                'title' => 'Panduan Pertemuan 8: Prinsip RESTful API',
                'type' => 'file',
                'file_path' => $sampleRelPath,
                'file_name' => 'P08_REST_API_Guide.txt',
                'file_size_bytes' => $sampleBytes,
                'mime_type' => 'text/plain',
                'url' => null,
                'uploaded_by' => $courseMap['07228204']['lecturer_id'],
            ]);
            $matStmt->execute([
                'course_id' => $courseMap['07228204']['id'],
                'meeting_id' => $meetingIds['07228204'][9],
                'title' => 'Referensi Resmi JWT RFC 7519',
                'type' => 'link',
                'file_path' => null,
                'file_name' => null,
                'file_size_bytes' => null,
                'mime_type' => null,
                'url' => 'https://datatracker.ietf.org/doc/html/rfc7519',
                'uploaded_by' => $courseMap['07228204']['lecturer_id'],
            ]);
            $matStmt->execute([
                'course_id' => $courseMap['07228239']['id'],
                'meeting_id' => $meetingIds['07228239'][8],
                'title' => 'Dokumentasi Standar OpenAPI & Webhook Keuangan',
                'type' => 'link',
                'file_path' => null,
                'file_name' => null,
                'file_size_bytes' => null,
                'mime_type' => null,
                'url' => 'https://swagger.io/specification/',
                'uploaded_by' => $courseMap['07228239']['lecturer_id'],
            ]);
            $materialCount = 3;
        }

        $enrolledFirstCourse = $pdo->prepare(
            "SELECT student_id FROM course_enrollments WHERE course_id = :cid AND status = 'active' ORDER BY id ASC"
        );
        $enrolledFirstCourse->execute(['cid' => $courseMap['07228204']['id']]);
        $studentIds = $enrolledFirstCourse->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $assignmentCount = (int) $pdo->query('SELECT COUNT(*) FROM assignments')->fetchColumn();
        if ($assignmentCount === 0) {
            $asgStmt = $pdo->prepare(
                'INSERT INTO assignments (course_id, meeting_id, title, description, deadline, accept_file, accept_link, allow_late, weight, max_score)
                 VALUES (:course_id, :meeting_id, :title, :description, :deadline, :accept_file, :accept_link, :allow_late, :weight, :max_score)'
            );

            $asgStmt->execute([
                'course_id' => $courseMap['07228204']['id'],
                'meeting_id' => $meetingIds['07228204'][10],
                'title' => 'Tugas 4: Implementasi REST API Auth dan JWT',
                'description' => 'Kumpulkan arsip kode sumber endpoint autentikasi atau tautan repositori GitHub beserta dokumentasi pengujian.',
                'deadline' => $now->modify('+2 days')->setTime(23, 59, 0)->format('Y-m-d H:i:s'),
                'accept_file' => 1,
                'accept_link' => 1,
                'allow_late' => 1,
                'weight' => 20.00,
                'max_score' => 100,
            ]);
            $asg1Id = (int) $pdo->lastInsertId();

            $asgStmt->execute([
                'course_id' => $courseMap['07228204']['id'],
                'meeting_id' => $meetingIds['07228204'][8],
                'title' => 'Tugas 3: Kontrak OpenAPI Modul Akademik',
                'description' => 'Kirimkan tautan dokumentasi OpenAPI atau repositori spesifikasi endpoint.',
                'deadline' => $now->modify('-2 days')->setTime(23, 59, 0)->format('Y-m-d H:i:s'),
                'accept_file' => 0,
                'accept_link' => 1,
                'allow_late' => 1,
                'weight' => 15.00,
                'max_score' => 100,
            ]);
            $asg2Id = (int) $pdo->lastInsertId();

            $asgStmt->execute([
                'course_id' => $courseMap['07228239']['id'],
                'meeting_id' => $meetingIds['07228239'][9],
                'title' => 'Studi Kasus Diagram Sequence Payment Gateway',
                'description' => 'Unggah berkas PDF diagram sequence proses otorisasi dan settlement pembayaran.',
                'deadline' => $now->modify('+4 days')->setTime(23, 59, 0)->format('Y-m-d H:i:s'),
                'accept_file' => 1,
                'accept_link' => 0,
                'allow_late' => 1,
                'weight' => 25.00,
                'max_score' => 100,
            ]);

            if (count($studentIds) >= 2) {
                $subStmt = $pdo->prepare(
                    'INSERT INTO submissions (assignment_id, user_id, file_path, file_name, file_size_bytes, mime_type, link_url, submitted_at, is_late, score, feedback, graded_at)
                     VALUES (:assignment_id, :user_id, :file_path, :file_name, :file_size_bytes, :mime_type, :link_url, :submitted_at, :is_late, :score, :feedback, :graded_at)'
                );
                $subStmt->execute([
                    'assignment_id' => $asg1Id,
                    'user_id' => (int) $studentIds[1],
                    'file_path' => null,
                    'file_name' => null,
                    'file_size_bytes' => null,
                    'mime_type' => null,
                    'link_url' => 'https://github.com/clarity-demo/tugas4-auth-jwt',
                    'submitted_at' => $now->modify('-3 hours')->format('Y-m-d H:i:s'),
                    'is_late' => 0,
                    'score' => null,
                    'feedback' => null,
                    'graded_at' => null,
                ]);
                $subStmt->execute([
                    'assignment_id' => $asg2Id,
                    'user_id' => (int) $studentIds[0],
                    'file_path' => null,
                    'file_name' => null,
                    'file_size_bytes' => null,
                    'mime_type' => null,
                    'link_url' => 'https://github.com/clarity-demo/openapi-akademik',
                    'submitted_at' => $now->modify('-3 days')->format('Y-m-d H:i:s'),
                    'is_late' => 0,
                    'score' => 92.00,
                    'feedback' => 'Struktur endpoint dan skema respons sudah sesuai konvensi.',
                    'graded_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
                ]);
            }
            $assignmentCount = 3;
        }

        $sessionCount = (int) $pdo->query('SELECT COUNT(*) FROM attendance_sessions')->fetchColumn();
        if ($sessionCount === 0) {
            $sessStmt = $pdo->prepare(
                'INSERT INTO attendance_sessions (meeting_id, opened_by, opened_at, closes_at, passcode, status)
                 VALUES (:meeting_id, :opened_by, :opened_at, :closes_at, :passcode, :status)'
            );
            $recStmt = $pdo->prepare(
                'INSERT INTO attendance_records (session_id, user_id, status, checked_at)
                 VALUES (:session_id, :user_id, :status, :checked_at)'
            );

            foreach ([8, 9] as $mNum) {
                $openedAt = $now->modify($mNum === 8 ? '-14 days' : '-7 days')->setTime(13, 0, 0);
                $sessStmt->execute([
                    'meeting_id' => $meetingIds['07228204'][$mNum],
                    'opened_by' => $courseMap['07228204']['lecturer_id'],
                    'opened_at' => $openedAt->format('Y-m-d H:i:s'),
                    'closes_at' => $openedAt->modify('+2 hours')->format('Y-m-d H:i:s'),
                    'passcode' => null,
                    'status' => 'closed',
                ]);
                $sessId = (int) $pdo->lastInsertId();
                foreach ($studentIds as $idx => $sid) {
                    $status = ($mNum === 9 && $idx === 4) ? 'izin' : 'hadir';
                    $recStmt->execute([
                        'session_id' => $sessId,
                        'user_id' => (int) $sid,
                        'status' => $status,
                        'checked_at' => $openedAt->modify('+' . ($idx + 2) . ' minutes')->format('Y-m-d H:i:s'),
                    ]);
                }
                $sessionCount++;
            }

            $sessStmt->execute([
                'meeting_id' => $meetingIds['07228204'][10],
                'opened_by' => $courseMap['07228204']['lecturer_id'],
                'opened_at' => $now->modify('-15 minutes')->format('Y-m-d H:i:s'),
                'closes_at' => $now->modify('+12 hours')->format('Y-m-d H:i:s'),
                'passcode' => 'MANPRO10',
                'status' => 'open',
            ]);
            $sessionCount++;
        }

        return [
            'meetings' => $meetingCount,
            'materials' => $materialCount,
            'assignments' => $assignmentCount,
            'sessions' => $sessionCount,
        ];
    }
}
