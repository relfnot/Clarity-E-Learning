<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CourseRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Mengambil daftar mata kuliah berdasarkan pengguna (Mahasiswa vs Dosen)
     * lengkap dengan server-side filtering (semester & search).
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function listForUser(
        int $userId,
        string $role,
        ?string $semester = null,
        ?string $search = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $conditions = [];
        $params = [];

        if ($role === 'dosen') {
            $conditions[] = 'c.lecturer_id = :user_id';
            $params['user_id'] = $userId;
        } else {
            $conditions[] = 'ce.student_id = :user_id';
            $params['user_id'] = $userId;
        }

        if ($semester !== null && trim($semester) !== '') {
            $conditions[] = 'c.semester = :semester';
            $params['semester'] = trim($semester);
        }

        if ($search !== null && trim($search) !== '') {
            $conditions[] = '(c.name LIKE :search OR c.code LIKE :search OR u.name LIKE :search)';
            $params['search'] = '%' . trim($search) . '%';
        }

        $whereClause = 'WHERE ' . implode(' AND ', $conditions);

        // 1. Hitung total data
        if ($role === 'dosen') {
            $countSql = "SELECT COUNT(*) FROM courses c JOIN users u ON u.id = c.lecturer_id {$whereClause}";
        } else {
            $countSql = "SELECT COUNT(*) FROM courses c 
                         JOIN course_enrollments ce ON ce.course_id = c.id 
                         JOIN users u ON u.id = c.lecturer_id 
                         {$whereClause}";
        }

        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // 2. Ambil data per halaman
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        if ($role === 'dosen') {
            $dataSql = "SELECT 
                            c.id, c.code, c.name, c.description, c.semester, c.credits,
                            c.lecturer_id, u.name AS lecturer_name, u.email AS lecturer_email,
                            c.day, c.start_time, c.end_time, c.room,
                            (SELECT COUNT(*) FROM course_enrollments ce2 WHERE ce2.course_id = c.id) AS enrolled_count
                        FROM courses c
                        JOIN users u ON u.id = c.lecturer_id
                        {$whereClause}
                        ORDER BY c.day ASC, c.start_time ASC
                        LIMIT {$perPage} OFFSET {$offset}";
        } else {
            $dataSql = "SELECT 
                            c.id, c.code, c.name, c.description, c.semester, c.credits,
                            c.lecturer_id, u.name AS lecturer_name, u.email AS lecturer_email,
                            c.day, c.start_time, c.end_time, c.room,
                            ce.enrolled_at,
                            (SELECT COUNT(*) FROM course_enrollments ce2 WHERE ce2.course_id = c.id) AS enrolled_count
                        FROM courses c
                        JOIN course_enrollments ce ON ce.course_id = c.id
                        JOIN users u ON u.id = c.lecturer_id
                        {$whereClause}
                        ORDER BY c.day ASC, c.start_time ASC
                        LIMIT {$perPage} OFFSET {$offset}";
        }

        $dataStmt = $this->pdo->prepare($dataSql);
        $dataStmt->execute($params);
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $data = array_map(function (array $row): array {
            return $this->formatCourseRow($row);
        }, $rows);

        return [
            'data' => $data,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $courseId, ?int $userId = null, ?string $role = null): ?array
    {
        $sql = 'SELECT 
                    c.id, c.code, c.name, c.description, c.semester, c.credits,
                    c.lecturer_id, u.name AS lecturer_name, u.email AS lecturer_email,
                    c.day, c.start_time, c.end_time, c.room,
                    (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id) AS enrolled_count
                FROM courses c
                JOIN users u ON u.id = c.lecturer_id
                WHERE c.id = :id
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $courseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        // Jika user diberikan, verifikasi kepemilikan/enrollment
        if ($userId !== null && $role !== null) {
            if ($role === 'dosen' && (int) $row['lecturer_id'] !== $userId) {
                return null;
            }
            if ($role === 'mahasiswa') {
                $checkStmt = $this->pdo->prepare(
                    'SELECT 1 FROM course_enrollments WHERE course_id = :course_id AND student_id = :student_id LIMIT 1'
                );
                $checkStmt->execute(['course_id' => $courseId, 'student_id' => $userId]);
                if ($checkStmt->fetchColumn() === false) {
                    return null;
                }
            }
        }

        return $this->formatCourseRow($row);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getParticipants(int $courseId): array
    {
        $sql = 'SELECT 
                    u.id, u.nim_nip AS nim, u.name, u.email, u.program_studi, u.initials, u.role,
                    ce.enrolled_at, ce.status
                FROM course_enrollments ce
                JOIN users u ON u.id = ce.student_id
                WHERE ce.course_id = :course_id
                ORDER BY u.nim_nip ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['course_id' => $courseId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static function (array $r): array {
            return [
                'id' => (int) $r['id'],
                'nim' => (string) $r['nim'],
                'name' => (string) $r['name'],
                'email' => (string) $r['email'],
                'program_studi' => (string) ($r['program_studi'] ?? 'Sistem Informasi (S1)'),
                'initials' => (string) ($r['initials'] ?? 'M'),
                'role' => (string) $r['role'],
                'enrolled_at' => (string) $r['enrolled_at'],
                'status' => (string) $r['status'],
            ];
        }, $rows);
    }

    /**
     * @return array<int, string>
     */
    public function getAvailableSemesters(): array
    {
        $stmt = $this->pdo->query('SELECT DISTINCT semester FROM courses ORDER BY semester DESC');
        return $stmt !== false ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatCourseRow(array $row): array
    {
        $startTime = isset($row['start_time']) ? substr((string) $row['start_time'], 0, 5) : null;
        $endTime = isset($row['end_time']) ? substr((string) $row['end_time'], 0, 5) : null;

        // Baseline metrik progress & nilai sesuai kontrak dan PRD
        $progress = 75;
        $studentScore = 85.0;
        $classAverage = 78.5;
        $attendance = 92.0;

        return [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'name' => (string) $row['name'],
            'description' => (string) ($row['description'] ?? ''),
            'semester' => (string) $row['semester'],
            'credits' => (int) $row['credits'],
            'lecturer_id' => (int) $row['lecturer_id'],
            'lecturer_name' => (string) ($row['lecturer_name'] ?? ''),
            'lecturer_email' => (string) ($row['lecturer_email'] ?? ''),
            'day' => (string) ($row['day'] ?? '-'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room' => (string) ($row['room'] ?? '-'),
            'enrolled_count' => (int) ($row['enrolled_count'] ?? 0),
            'progress_percent' => $progress,
            'student_score' => $studentScore,
            'class_average' => $classAverage,
            'attendance_percent' => $attendance,
        ];
    }
}
