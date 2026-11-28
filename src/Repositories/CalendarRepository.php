<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class CalendarRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * @return array<int, int>
     */
    public function getAccessibleCourseIds(int $userId, string $role): array
    {
        if ($role === 'dosen') {
            $stmt = $this->pdo->prepare('SELECT id FROM courses WHERE lecturer_id = :uid ORDER BY id ASC');
            $stmt->execute(['uid' => $userId]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT course_id FROM course_enrollments WHERE student_id = :uid AND status = 'active' ORDER BY course_id ASC"
            );
            $stmt->execute(['uid' => $userId]);
        }

        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        return array_values(array_map('intval', $ids));
    }

    public function courseExists(int $courseId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $courseId]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * @param array<int, int> $courseIds
     * @return array<int, array<string, mixed>>
     */
    public function findMeetingsInRange(array $courseIds, string $fromDate, string $toDate): array
    {
        if ($courseIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $sql = "SELECT m.id, m.course_id, m.number, m.title, m.meeting_date,
                       c.code AS course_code, c.name AS course_name,
                       c.start_time, c.end_time, c.room
                FROM meetings m
                JOIN courses c ON c.id = m.course_id
                WHERE m.course_id IN ({$placeholders})
                  AND m.meeting_date BETWEEN ? AND ?
                ORDER BY m.meeting_date ASC, c.start_time ASC, m.number ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($courseIds), [$fromDate, $toDate]));
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<int, int> $courseIds
     * @return array<int, array<string, mixed>>
     */
    public function findAssignmentsInRange(array $courseIds, string $fromDateTime, string $toDateTime): array
    {
        if ($courseIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $sql = "SELECT a.id, a.course_id, a.title, a.deadline,
                       c.code AS course_code, c.name AS course_name
                FROM assignments a
                JOIN courses c ON c.id = a.course_id
                WHERE a.course_id IN ({$placeholders})
                  AND a.deadline BETWEEN ? AND ?
                ORDER BY a.deadline ASC, a.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($courseIds), [$fromDateTime, $toDateTime]));
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<int, int> $courseIds
     * @return array<int, array<string, mixed>>
     */
    public function findQuizzesInRange(array $courseIds, string $fromDateTime, string $toDateTime): array
    {
        if ($courseIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $sql = "SELECT q.id, q.course_id, q.title, q.open_at, q.close_at, q.duration_min,
                       c.code AS course_code, c.name AS course_name
                FROM quizzes q
                JOIN courses c ON c.id = q.course_id
                WHERE q.course_id IN ({$placeholders})
                  AND q.open_at <= ?
                  AND q.close_at >= ?
                ORDER BY q.open_at ASC, q.id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($courseIds), [$toDateTime, $fromDateTime]));
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
