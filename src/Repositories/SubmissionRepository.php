<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class SubmissionRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function findById(int $submissionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, u.name AS student_name, u.nim_nip, a.course_id, c.lecturer_id
             FROM submissions s
             JOIN users u ON u.id = s.user_id
             JOIN assignments a ON a.id = s.assignment_id
             JOIN courses c ON c.id = a.course_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $submissionId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findByAssignmentAndUser(int $assignmentId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, u.name AS student_name, u.nim_nip
             FROM submissions s
             JOIN users u ON u.id = s.user_id
             WHERE s.assignment_id = :aid AND s.user_id = :uid
             LIMIT 1'
        );
        $stmt->execute(['aid' => $assignmentId, 'uid' => $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAllForAssignmentWithRoster(int $assignmentId, int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id AS roster_user_id, u.name AS student_name, u.nim_nip,
                    s.id, s.assignment_id, s.user_id, s.file_path, s.file_name, s.file_size_bytes,
                    s.mime_type, s.link_url, s.submitted_at, s.is_late, s.score, s.feedback, s.graded_at
             FROM course_enrollments e
             JOIN users u ON u.id = e.student_id
             LEFT JOIN submissions s ON s.assignment_id = :aid AND s.user_id = u.id
             WHERE e.course_id = :cid AND e.status = 'active'
             ORDER BY u.name ASC"
        );
        $stmt->execute(['aid' => $assignmentId, 'cid' => $courseId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, string>
     */
    public function listFilePathsByAssignment(int $assignmentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT file_path FROM submissions WHERE assignment_id = :aid AND file_path IS NOT NULL'
        );
        $stmt->execute(['aid' => $assignmentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return is_array($rows) ? $rows : [];
    }

    public function upsert(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO submissions (
                assignment_id, user_id, file_path, file_name, file_size_bytes, mime_type, link_url, submitted_at, is_late
             ) VALUES (
                :assignment_id, :user_id, :file_path, :file_name, :file_size_bytes, :mime_type, :link_url, :submitted_at, :is_late
             )
             ON DUPLICATE KEY UPDATE
                file_path = VALUES(file_path),
                file_name = VALUES(file_name),
                file_size_bytes = VALUES(file_size_bytes),
                mime_type = VALUES(mime_type),
                link_url = VALUES(link_url),
                submitted_at = VALUES(submitted_at),
                is_late = VALUES(is_late)'
        );
        $stmt->execute([
            'assignment_id' => $data['assignment_id'],
            'user_id' => $data['user_id'],
            'file_path' => $data['file_path'],
            'file_name' => $data['file_name'],
            'file_size_bytes' => $data['file_size_bytes'],
            'mime_type' => $data['mime_type'],
            'link_url' => $data['link_url'],
            'submitted_at' => $data['submitted_at'],
            'is_late' => $data['is_late'],
        ]);

        $id = (int) $this->pdo->lastInsertId();
        if ($id > 0) {
            return $id;
        }
        $existing = $this->findByAssignmentAndUser((int) $data['assignment_id'], (int) $data['user_id']);
        return $existing ? (int) $existing['id'] : 0;
    }
}
