<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AssignmentRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listByCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.code AS course_code, c.name AS course_name, c.lecturer_id
             FROM assignments a
             JOIN courses c ON c.id = a.course_id
             WHERE a.course_id = :cid
             ORDER BY a.deadline ASC, a.id DESC'
        );
        $stmt->execute(['cid' => $courseId]);
        return $stmt->fetchAll();
    }

    public function findById(int $assignmentId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, c.code AS course_code, c.name AS course_name, c.lecturer_id
             FROM assignments a
             JOIN courses c ON c.id = a.course_id
             WHERE a.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $assignmentId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO assignments (course_id, meeting_id, title, description, deadline, accept_file, accept_link, allow_late, weight, max_score)
             VALUES (:course_id, :meeting_id, :title, :description, :deadline, :accept_file, :accept_link, :allow_late, :weight, :max_score)'
        );
        $stmt->execute([
            'course_id' => $data['course_id'],
            'meeting_id' => $data['meeting_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'deadline' => $data['deadline'],
            'accept_file' => $data['accept_file'],
            'accept_link' => $data['accept_link'],
            'allow_late' => $data['allow_late'],
            'weight' => $data['weight'],
            'max_score' => $data['max_score'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $assignmentId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE assignments
             SET meeting_id = :meeting_id, title = :title, description = :description, deadline = :deadline,
                 accept_file = :accept_file, accept_link = :accept_link, allow_late = :allow_late,
                 weight = :weight, max_score = :max_score
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $assignmentId,
            'meeting_id' => $data['meeting_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'deadline' => $data['deadline'],
            'accept_file' => $data['accept_file'],
            'accept_link' => $data['accept_link'],
            'allow_late' => $data['allow_late'],
            'weight' => $data['weight'],
            'max_score' => $data['max_score'],
        ]);
    }

    public function delete(int $assignmentId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM assignments WHERE id = :id');
        $stmt->execute(['id' => $assignmentId]);
    }
}
