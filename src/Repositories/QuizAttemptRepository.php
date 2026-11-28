<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class QuizAttemptRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $attemptId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT qa.*,
                    q.course_id, q.title AS quiz_title, q.description AS quiz_description,
                    q.open_at, q.close_at, q.duration_min, q.max_attempts,
                    q.shuffle, q.show_answers, q.weight,
                    c.code AS course_code, c.name AS course_name, c.lecturer_id,
                    u.name AS student_name, u.nim_nip
             FROM quiz_attempts qa
             JOIN quizzes q ON q.id = qa.quiz_id
             JOIN courses c ON c.id = q.course_id
             JOIN users u ON u.id = qa.user_id
             WHERE qa.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $attemptId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveAttempt(int $quizId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM quiz_attempts
             WHERE quiz_id = :quiz_id AND user_id = :user_id AND status = 'in_progress'
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute(['quiz_id' => $quizId, 'user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listByQuizAndUser(int $quizId, int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM quiz_attempts
             WHERE quiz_id = :quiz_id AND user_id = :user_id
             ORDER BY attempt_number DESC, id DESC'
        );
        $stmt->execute(['quiz_id' => $quizId, 'user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listInProgressByQuiz(int $quizId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, expires_at
             FROM quiz_attempts
             WHERE quiz_id = :quiz_id AND status = 'in_progress'"
        );
        $stmt->execute(['quiz_id' => $quizId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function listByQuizForLecturer(
        int $quizId,
        ?string $statusFilter = null,
        int $page = 1,
        int $perPage = 50
    ): array {
        $stmt = $this->pdo->prepare(
            "SELECT qa.*, u.name AS student_name, u.nim_nip,
                    (
                        SELECT COUNT(*)
                        FROM questions qu
                        LEFT JOIN attempt_answers aa ON aa.attempt_id = qa.id AND aa.question_id = qu.id
                        WHERE qu.quiz_id = qa.quiz_id
                          AND qu.type = 'esai'
                          AND (aa.id IS NULL OR aa.graded_at IS NULL)
                    ) AS ungraded_essay_count
             FROM quiz_attempts qa
             JOIN users u ON u.id = qa.user_id
             WHERE qa.quiz_id = :quiz_id
             ORDER BY qa.submitted_at DESC, qa.started_at DESC, qa.id DESC"
        );
        $stmt->execute(['quiz_id' => $quizId]);
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $summary = [
            'total' => count($all),
            'in_progress' => 0,
            'menunggu_penilaian' => 0,
            'graded' => 0,
        ];

        $filtered = [];
        foreach ($all as $row) {
            $st = (string) ($row['status'] ?? 'in_progress');
            if ($st === 'submitted') {
                $st = 'graded';
            }
            if (isset($summary[$st])) {
                $summary[$st]++;
            }
            if ($statusFilter === null || $statusFilter === '' || $st === $statusFilter) {
                $filtered[] = $row;
            }
        }

        $total = count($filtered);
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $items = array_slice($filtered, $offset, $perPage);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 1,
                'summary' => $summary,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createAttempt(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO quiz_attempts (
                quiz_id, user_id, attempt_number, question_order, option_orders,
                started_at, expires_at, status
             ) VALUES (
                :quiz_id, :user_id, :attempt_number, :question_order, :option_orders,
                :started_at, :expires_at, 'in_progress'
             )"
        );
        $stmt->execute([
            'quiz_id' => $data['quiz_id'],
            'user_id' => $data['user_id'],
            'attempt_number' => $data['attempt_number'],
            'question_order' => $data['question_order'],
            'option_orders' => $data['option_orders'],
            'started_at' => $data['started_at'],
            'expires_at' => $data['expires_at'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAnswersByAttempt(int $attemptId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, attempt_id, question_id, option_id, answer_text, score, graded_by, graded_at
             FROM attempt_answers
             WHERE attempt_id = :attempt_id
             ORDER BY id ASC'
        );
        $stmt->execute(['attempt_id' => $attemptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<int, array{question_id: int, option_id: ?int, answer_text: ?string}> $answers
     */
    public function upsertAnswers(int $attemptId, array $answers): int
    {
        if ($answers === []) {
            return 0;
        }

        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO attempt_answers (attempt_id, question_id, option_id, answer_text)
                 VALUES (:attempt_id, :question_id, :option_id, :answer_text)
                 ON DUPLICATE KEY UPDATE
                    option_id = VALUES(option_id),
                    answer_text = VALUES(answer_text)'
            );
            $count = 0;
            foreach ($answers as $ans) {
                $stmt->execute([
                    'attempt_id' => $attemptId,
                    'question_id' => $ans['question_id'],
                    'option_id' => $ans['option_id'],
                    'answer_text' => $ans['answer_text'],
                ]);
                $count++;
            }

            if (!$inTransaction) {
                $this->pdo->commit();
            }
            return $count;
        } catch (\Throwable $e) {
            if (!$inTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param array<int, float> $pgScoresByQuestionId
     * @param array<int, int> $essayQuestionIds
     */
    public function finalizeAttempt(
        int $attemptId,
        array $pgScoresByQuestionId,
        array $essayQuestionIds,
        string $submittedAtDb,
        float $finalScore,
        string $status
    ): void {
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            if ($pgScoresByQuestionId !== []) {
                $scoreStmt = $this->pdo->prepare(
                    'UPDATE attempt_answers
                     SET score = :score
                     WHERE attempt_id = :attempt_id AND question_id = :question_id'
                );
                foreach ($pgScoresByQuestionId as $qId => $qScore) {
                    $scoreStmt->execute([
                        'attempt_id' => $attemptId,
                        'question_id' => (int) $qId,
                        'score' => round($qScore, 2),
                    ]);
                }
            }

            if ($essayQuestionIds !== []) {
                $ensureEssayStmt = $this->pdo->prepare(
                    'INSERT IGNORE INTO attempt_answers (attempt_id, question_id, option_id, answer_text)
                     VALUES (:attempt_id, :question_id, NULL, NULL)'
                );
                foreach ($essayQuestionIds as $essayQid) {
                    $ensureEssayStmt->execute([
                        'attempt_id' => $attemptId,
                        'question_id' => (int) $essayQid,
                    ]);
                }
            }

            $attStmt = $this->pdo->prepare(
                'UPDATE quiz_attempts
                 SET submitted_at = :submitted_at,
                     score = :score,
                     status = :status
                 WHERE id = :id'
            );
            $attStmt->execute([
                'id' => $attemptId,
                'submitted_at' => $submittedAtDb,
                'score' => round($finalScore, 2),
                'status' => $status,
            ]);

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

    /**
     * @return array<string, mixed>|null
     */
    public function findAnswerById(int $answerId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT aa.*,
                    qa.quiz_id, qa.user_id AS student_id, qa.status AS attempt_status,
                    qu.type AS question_type, qu.points AS question_points,
                    q.course_id, c.lecturer_id
             FROM attempt_answers aa
             JOIN quiz_attempts qa ON qa.id = aa.attempt_id
             JOIN questions qu ON qu.id = aa.question_id
             JOIN quizzes q ON q.id = qa.quiz_id
             JOIN courses c ON c.id = q.course_id
             WHERE aa.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $answerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public function gradeEssayAnswer(int $answerId, float $score, int $gradedBy, string $gradedAtDb): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE attempt_answers
             SET score = :score, graded_by = :graded_by, graded_at = :graded_at
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $answerId,
            'score' => round($score, 2),
            'graded_by' => $gradedBy,
            'graded_at' => $gradedAtDb,
        ]);
    }

    public function updateAttemptScoreAndStatus(int $attemptId, float $score, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE quiz_attempts SET score = :score, status = :status WHERE id = :id'
        );
        $stmt->execute([
            'id' => $attemptId,
            'score' => round($score, 2),
            'status' => $status,
        ]);
    }
}
