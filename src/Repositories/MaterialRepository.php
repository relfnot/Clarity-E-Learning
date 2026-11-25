<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class MaterialRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listByCourse(int $courseId, ?int $meetingId = null): array
    {
        $sql = 'SELECT m.*, mt.number AS meeting_number, mt.title AS meeting_title
                FROM materials m
                JOIN meetings mt ON mt.id = m.meeting_id
                WHERE m.course_id = :cid';
        $params = ['cid' => $courseId];

        if ($meetingId !== null) {
            $sql .= ' AND m.meeting_id = :mid';
            $params['mid'] = $meetingId;
        }

        $sql .= ' ORDER BY mt.number ASC, m.id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $materialId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT m.*, mt.number AS meeting_number, mt.title AS meeting_title
             FROM materials m
             JOIN meetings mt ON mt.id = m.meeting_id
             WHERE m.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $materialId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO materials (course_id, meeting_id, title, type, file_path, file_name, file_size_bytes, mime_type, url, uploaded_by)
             VALUES (:course_id, :meeting_id, :title, :type, :file_path, :file_name, :file_size_bytes, :mime_type, :url, :uploaded_by)'
        );
        $stmt->execute([
            'course_id' => $data['course_id'],
            'meeting_id' => $data['meeting_id'],
            'title' => $data['title'],
            'type' => $data['type'],
            'file_path' => $data['file_path'],
            'file_name' => $data['file_name'],
            'file_size_bytes' => $data['file_size_bytes'],
            'mime_type' => $data['mime_type'],
            'url' => $data['url'],
            'uploaded_by' => $data['uploaded_by'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $materialId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE materials
             SET meeting_id = :meeting_id, title = :title, url = :url
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $materialId,
            'meeting_id' => $data['meeting_id'],
            'title' => $data['title'],
            'url' => $data['url'],
        ]);
    }

    public function delete(int $materialId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM materials WHERE id = :id');
        $stmt->execute(['id' => $materialId]);
    }
}
