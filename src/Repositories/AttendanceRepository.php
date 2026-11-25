<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AttendanceRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function findSessionById(int $sessionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, m.course_id, m.number AS meeting_number, m.title AS meeting_title, c.lecturer_id
             FROM attendance_sessions s
             JOIN meetings m ON m.id = s.meeting_id
             JOIN courses c ON c.id = m.course_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $sessionId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findOpenSessionByMeeting(int $meetingId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM attendance_sessions WHERE meeting_id = :mid AND status = 'open' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['mid' => $meetingId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findLatestSessionByMeeting(int $meetingId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM attendance_sessions WHERE meeting_id = :mid ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['mid' => $meetingId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function createSession(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO attendance_sessions (meeting_id, opened_by, opened_at, closes_at, passcode, status)
             VALUES (:meeting_id, :opened_by, :opened_at, :closes_at, :passcode, :status)'
        );
        $stmt->execute([
            'meeting_id' => $data['meeting_id'],
            'opened_by' => $data['opened_by'],
            'opened_at' => $data['opened_at'],
            'closes_at' => $data['closes_at'],
            'passcode' => $data['passcode'],
            'status' => 'open',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function closeSession(int $sessionId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE attendance_sessions SET status = 'closed' WHERE id = :id"
        );
        $stmt->execute(['id' => $sessionId]);
    }

    public function findRecordById(int $recordId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.name AS student_name, u.nim_nip, s.meeting_id, m.course_id, c.lecturer_id
             FROM attendance_records r
             JOIN users u ON u.id = r.user_id
             JOIN attendance_sessions s ON s.id = r.session_id
             JOIN meetings m ON m.id = s.meeting_id
             JOIN courses c ON c.id = m.course_id
             WHERE r.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $recordId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findRecordBySessionAndUser(int $sessionId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.name AS student_name, u.nim_nip, s.meeting_id
             FROM attendance_records r
             JOIN users u ON u.id = r.user_id
             JOIN attendance_sessions s ON s.id = r.session_id
             WHERE r.session_id = :sid AND r.user_id = :uid
             LIMIT 1'
        );
        $stmt->execute(['sid' => $sessionId, 'uid' => $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function createRecord(int $sessionId, int $userId, string $status, ?string $checkedAt): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO attendance_records (session_id, user_id, status, checked_at)
             VALUES (:sid, :uid, :status, :checked_at)'
        );
        $stmt->execute([
            'sid' => $sessionId,
            'uid' => $userId,
            'status' => $status,
            'checked_at' => $checkedAt,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function insertMissingAlpaForSession(int $sessionId, int $courseId): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO attendance_records (session_id, user_id, status, checked_at)
             SELECT :sid, e.student_id, 'alpa', NULL
             FROM course_enrollments e
             WHERE e.course_id = :cid
               AND e.status = 'active'
               AND NOT EXISTS (
                   SELECT 1 FROM attendance_records r
                   WHERE r.session_id = :sid2 AND r.user_id = e.student_id
               )"
        );
        $stmt->execute([
            'sid' => $sessionId,
            'cid' => $courseId,
            'sid2' => $sessionId,
        ]);
    }

    public function updateRecordStatus(int $recordId, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE attendance_records SET status = :status WHERE id = :id'
        );
        $stmt->execute(['id' => $recordId, 'status' => $status]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRecordsForStudentInCourse(int $courseId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.name AS student_name, u.nim_nip, s.meeting_id, m.number AS meeting_number, m.title AS meeting_title, m.meeting_date
             FROM attendance_records r
             JOIN attendance_sessions s ON s.id = r.session_id
             JOIN meetings m ON m.id = s.meeting_id
             JOIN users u ON u.id = r.user_id
             WHERE m.course_id = :cid AND r.user_id = :uid
             ORDER BY m.number ASC, r.id DESC'
        );
        $stmt->execute(['cid' => $courseId, 'uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOpenSessionsInCourse(int $courseId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.id, s.meeting_id, s.opened_at, s.closes_at, s.status,
                    (s.passcode IS NOT NULL AND s.passcode <> '') AS requires_passcode,
                    m.number AS meeting_number, m.title AS meeting_title, m.meeting_date,
                    (SELECT r.status FROM attendance_records r WHERE r.session_id = s.id AND r.user_id = :uid LIMIT 1) AS my_status
             FROM attendance_sessions s
             JOIN meetings m ON m.id = s.meeting_id
             WHERE m.course_id = :cid AND s.status = 'open'
             ORDER BY m.number ASC"
        );
        $stmt->execute(['cid' => $courseId, 'uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRecordsForCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.name AS student_name, u.nim_nip, s.meeting_id, m.number AS meeting_number, m.title AS meeting_title
             FROM attendance_records r
             JOIN attendance_sessions s ON s.id = r.session_id
             JOIN meetings m ON m.id = s.meeting_id
             JOIN users u ON u.id = r.user_id
             WHERE m.course_id = :cid
             ORDER BY m.number ASC, u.name ASC'
        );
        $stmt->execute(['cid' => $courseId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listMeetingRosterWithRecords(int $sessionId, int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id AS user_id, u.name AS student_name, u.nim_nip,
                    r.id AS record_id, r.status, r.checked_at
             FROM course_enrollments e
             JOIN users u ON u.id = e.student_id
             LEFT JOIN attendance_records r ON r.session_id = :sid AND r.user_id = u.id
             WHERE e.course_id = :cid AND e.status = 'active'
             ORDER BY u.name ASC"
        );
        $stmt->execute(['sid' => $sessionId, 'cid' => $courseId]);
        return $stmt->fetchAll();
    }
}
