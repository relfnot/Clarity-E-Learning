<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class GradeRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * @return array<string, float>
     */
    public function getWeightsByCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT component, weight FROM grade_weights WHERE course_id = :course_id'
        );
        $stmt->execute(['course_id' => $courseId]);
        $rows = $stmt->fetchAll() ?: [];

        $weights = [];
        foreach ($rows as $row) {
            $weights[(string) $row['component']] = (float) $row['weight'];
        }
        return $weights;
    }

    /**
     * @param array<string, float> $weights
     */
    public function upsertWeights(int $courseId, array $weights): void
    {
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO grade_weights (course_id, component, weight)
                 VALUES (:course_id, :component, :weight)
                 ON DUPLICATE KEY UPDATE weight = VALUES(weight)'
            );
            foreach ($weights as $component => $weight) {
                $stmt->execute([
                    'course_id' => $courseId,
                    'component' => $component,
                    'weight' => round($weight, 2),
                ]);
            }

            if (!$inTransaction) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if (!$inTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateSubmissionGrade(int $submissionId, float $score, ?string $feedback, string $gradedAtDb): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE submissions
             SET score = :score, feedback = :feedback, graded_at = :graded_at
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $submissionId,
            'score' => round($score, 2),
            'feedback' => $feedback,
            'graded_at' => $gradedAtDb,
        ]);
    }

    public function upsertComponentScore(
        int $courseId,
        int $userId,
        string $component,
        float $score,
        ?string $feedback,
        int $gradedBy,
        string $gradedAtDb
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO course_component_scores (
                course_id, user_id, component, score, feedback, graded_by, graded_at
             ) VALUES (
                :course_id, :user_id, :component, :score, :feedback, :graded_by, :graded_at
             )
             ON DUPLICATE KEY UPDATE
                score = VALUES(score),
                feedback = VALUES(feedback),
                graded_by = VALUES(graded_by),
                graded_at = VALUES(graded_at)'
        );
        $stmt->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
            'component' => $component,
            'score' => round($score, 2),
            'feedback' => $feedback,
            'graded_by' => $gradedBy,
            'graded_at' => $gradedAtDb,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGradedSubmissionsForStudentInCourse(int $courseId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.id AS assignment_id, a.title, a.max_score, a.weight,
                    s.id AS submission_id, s.score, s.feedback, s.graded_at, s.is_late
             FROM assignments a
             LEFT JOIN submissions s ON s.assignment_id = a.id AND s.user_id = :user_id
             WHERE a.course_id = :course_id
             ORDER BY a.deadline ASC, a.id ASC'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Rata-rata nilai tugas (skala 0-100) dari submission yang sudah dinilai.
     */
    public function getAssignmentAverageForStudent(int $courseId, int $userId): ?float
    {
        $stmt = $this->pdo->prepare(
            'SELECT AVG((s.score / GREATEST(a.max_score, 1)) * 100) AS avg_score, COUNT(s.score) AS graded_count
             FROM submissions s
             JOIN assignments a ON a.id = s.assignment_id
             WHERE a.course_id = :course_id AND s.user_id = :user_id AND s.score IS NOT NULL'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        if (!is_array($row) || (int) ($row['graded_count'] ?? 0) === 0 || $row['avg_score'] === null) {
            return null;
        }
        return round((float) $row['avg_score'], 2);
    }

    /**
     * Rata-rata nilai quiz terbaik per quiz yang sudah memiliki skor.
     */
    public function getQuizAverageForStudent(int $courseId, int $userId): ?float
    {
        $stmt = $this->pdo->prepare(
            'SELECT AVG(best.best_score) AS avg_score, COUNT(best.quiz_id) AS quiz_count
             FROM (
                 SELECT qa.quiz_id, MAX(qa.score) AS best_score
                 FROM quiz_attempts qa
                 JOIN quizzes q ON q.id = qa.quiz_id
                 WHERE q.course_id = :course_id AND qa.user_id = :user_id AND qa.score IS NOT NULL
                 GROUP BY qa.quiz_id
             ) best'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        if (!is_array($row) || (int) ($row['quiz_count'] ?? 0) === 0 || $row['avg_score'] === null) {
            return null;
        }
        return round((float) $row['avg_score'], 2);
    }

    /**
     * Persentase kehadiran mahasiswa pada mata kuliah (0-100).
     */
    public function getAttendanceAverageForStudent(int $courseId, int $userId): ?float
    {
        $totalStmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM attendance_sessions s
             JOIN meetings m ON m.id = s.meeting_id
             WHERE m.course_id = :course_id'
        );
        $totalStmt->execute(['course_id' => $courseId]);
        $totalSessions = (int) $totalStmt->fetchColumn();
        if ($totalSessions === 0) {
            return null;
        }

        $hadirStmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM attendance_records ar
             JOIN attendance_sessions s ON s.id = ar.session_id
             JOIN meetings m ON m.id = s.meeting_id
             WHERE m.course_id = :course_id AND ar.user_id = :user_id AND ar.status = 'hadir'"
        );
        $hadirStmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
        $hadirCount = (int) $hadirStmt->fetchColumn();

        return round(($hadirCount / $totalSessions) * 100, 2);
    }

    /**
     * @return array<string, float>
     */
    public function getComponentScoresForStudent(int $courseId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT component, score
             FROM course_component_scores
             WHERE course_id = :course_id AND user_id = :user_id'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
        $rows = $stmt->fetchAll() ?: [];

        $scores = [];
        foreach ($rows as $row) {
            $scores[(string) $row['component']] = round((float) $row['score'], 2);
        }
        return $scores;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getQuizBreakdownForStudent(int $courseId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT q.id AS quiz_id, q.title, q.weight,
                    MAX(qa.score) AS best_score,
                    COUNT(qa.id) AS attempts_count
             FROM quizzes q
             LEFT JOIN quiz_attempts qa ON qa.quiz_id = q.id AND qa.user_id = :user_id
             WHERE q.course_id = :course_id
             GROUP BY q.id, q.title, q.weight
             ORDER BY q.open_at ASC, q.id ASC'
        );
        $stmt->execute(['course_id' => $courseId, 'user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }
}
