<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class QuizRepository
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
            'SELECT q.*, c.code AS course_code, c.name AS course_name, c.lecturer_id,
                    (SELECT COUNT(*) FROM questions qu WHERE qu.quiz_id = q.id) AS total_questions,
                    (SELECT COALESCE(SUM(qu.points), 0) FROM questions qu WHERE qu.quiz_id = q.id) AS total_points,
                    (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) AS attempts_count
             FROM quizzes q
             JOIN courses c ON c.id = q.course_id
             WHERE q.course_id = :course_id
             ORDER BY q.open_at ASC, q.id ASC'
        );
        $stmt->execute(['course_id' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $quizId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT q.*, c.code AS course_code, c.name AS course_name, c.lecturer_id,
                    (SELECT COUNT(*) FROM questions qu WHERE qu.quiz_id = q.id) AS total_questions,
                    (SELECT COALESCE(SUM(qu.points), 0) FROM questions qu WHERE qu.quiz_id = q.id) AS total_points,
                    (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id) AS attempts_count
             FROM quizzes q
             JOIN courses c ON c.id = q.course_id
             WHERE q.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $quizId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function countAttemptsByUser(int $quizId, int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = :quiz_id AND user_id = :user_id'
        );
        $stmt->execute(['quiz_id' => $quizId, 'user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countAttemptsForQuiz(int $quizId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = :quiz_id');
        $stmt->execute(['quiz_id' => $quizId]);
        return (int) $stmt->fetchColumn();
    }

    public function findActiveAttemptId(int $quizId, int $userId): ?int
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM quiz_attempts
             WHERE quiz_id = :quiz_id AND user_id = :user_id AND status = 'in_progress'
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['quiz_id' => $quizId, 'user_id' => $userId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : null;
    }

    public function findBestScoreByUser(int $quizId, int $userId): ?float
    {
        $stmt = $this->pdo->prepare(
            'SELECT MAX(score) FROM quiz_attempts WHERE quiz_id = :quiz_id AND user_id = :user_id AND score IS NOT NULL'
        );
        $stmt->execute(['quiz_id' => $quizId, 'user_id' => $userId]);
        $val = $stmt->fetchColumn();
        return $val !== false && $val !== null ? round((float) $val, 2) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO quizzes (
                course_id, meeting_id, title, description, open_at, close_at,
                duration_min, max_attempts, shuffle, show_answers, weight
             ) VALUES (
                :course_id, :meeting_id, :title, :description, :open_at, :close_at,
                :duration_min, :max_attempts, :shuffle, :show_answers, :weight
             )'
        );
        $stmt->execute([
            'course_id' => $data['course_id'],
            'meeting_id' => $data['meeting_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'open_at' => $data['open_at'],
            'close_at' => $data['close_at'],
            'duration_min' => $data['duration_min'],
            'max_attempts' => $data['max_attempts'],
            'shuffle' => $data['shuffle'],
            'show_answers' => $data['show_answers'],
            'weight' => $data['weight'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $quizId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE quizzes
             SET meeting_id = :meeting_id,
                 title = :title,
                 description = :description,
                 open_at = :open_at,
                 close_at = :close_at,
                 duration_min = :duration_min,
                 max_attempts = :max_attempts,
                 shuffle = :shuffle,
                 show_answers = :show_answers,
                 weight = :weight
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $quizId,
            'meeting_id' => $data['meeting_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'open_at' => $data['open_at'],
            'close_at' => $data['close_at'],
            'duration_min' => $data['duration_min'],
            'max_attempts' => $data['max_attempts'],
            'shuffle' => $data['shuffle'],
            'show_answers' => $data['show_answers'],
            'weight' => $data['weight'],
        ]);
    }

    public function delete(int $quizId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM quizzes WHERE id = :id');
        $stmt->execute(['id' => $quizId]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listQuestionsWithOptions(int $quizId): array
    {
        $qStmt = $this->pdo->prepare(
            'SELECT id, quiz_id, type, text, points, position
             FROM questions
             WHERE quiz_id = :quiz_id
             ORDER BY position ASC, id ASC'
        );
        $qStmt->execute(['quiz_id' => $quizId]);
        $questions = $qStmt->fetchAll() ?: [];

        if ($questions === []) {
            return [];
        }

        $qIds = array_map(static fn (array $q): int => (int) $q['id'], $questions);
        $placeholders = implode(',', array_fill(0, count($qIds), '?'));
        $oStmt = $this->pdo->prepare(
            "SELECT id, question_id, label, text, is_correct, position
             FROM options
             WHERE question_id IN ({$placeholders})
             ORDER BY position ASC, id ASC"
        );
        $oStmt->execute($qIds);
        $options = $oStmt->fetchAll() ?: [];

        $optionsByQuestion = [];
        foreach ($options as $opt) {
            $qid = (int) $opt['question_id'];
            $optionsByQuestion[$qid][] = $opt;
        }

        foreach ($questions as &$q) {
            $qid = (int) $q['id'];
            $q['options'] = $optionsByQuestion[$qid] ?? [];
        }
        unset($q);

        return $questions;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findQuestionById(int $questionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT qu.id, qu.quiz_id, qu.type, qu.text, qu.points, qu.position,
                    q.course_id, c.lecturer_id
             FROM questions qu
             JOIN quizzes q ON q.id = qu.quiz_id
             JOIN courses c ON c.id = q.course_id
             WHERE qu.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $questionId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }

        $oStmt = $this->pdo->prepare(
            'SELECT id, question_id, label, text, is_correct, position
             FROM options
             WHERE question_id = :question_id
             ORDER BY position ASC, id ASC'
        );
        $oStmt->execute(['question_id' => $questionId]);
        $row['options'] = $oStmt->fetchAll() ?: [];

        return $row;
    }

    /**
     * @param array<string, mixed> $questionData
     * @param array<int, array<string, mixed>> $optionsData
     */
    public function createQuestionWithOptions(array $questionData, array $optionsData): int
    {
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO questions (quiz_id, type, text, points, position)
                 VALUES (:quiz_id, :type, :text, :points, :position)'
            );
            $stmt->execute([
                'quiz_id' => $questionData['quiz_id'],
                'type' => $questionData['type'],
                'text' => $questionData['text'],
                'points' => $questionData['points'],
                'position' => $questionData['position'],
            ]);
            $questionId = (int) $this->pdo->lastInsertId();

            $this->insertOptions($questionId, $optionsData);

            if (!$inTransaction) {
                $this->pdo->commit();
            }

            return $questionId;
        } catch (\Throwable $e) {
            if (!$inTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $questionData
     * @param array<int, array<string, mixed>> $optionsData
     */
    public function updateQuestionWithOptions(int $questionId, array $questionData, array $optionsData): void
    {
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE questions
                 SET type = :type, text = :text, points = :points, position = :position
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $questionId,
                'type' => $questionData['type'],
                'text' => $questionData['text'],
                'points' => $questionData['points'],
                'position' => $questionData['position'],
            ]);

            $delStmt = $this->pdo->prepare('DELETE FROM options WHERE question_id = :question_id');
            $delStmt->execute(['question_id' => $questionId]);

            $this->insertOptions($questionId, $optionsData);

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

    public function deleteQuestion(int $questionId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM questions WHERE id = :id');
        $stmt->execute(['id' => $questionId]);
    }

    /**
     * @param array<int, array<string, mixed>> $optionsData
     */
    private function insertOptions(int $questionId, array $optionsData): void
    {
        if ($optionsData === []) {
            return;
        }

        $optStmt = $this->pdo->prepare(
            'INSERT INTO options (question_id, label, text, is_correct, position)
             VALUES (:question_id, :label, :text, :is_correct, :position)'
        );
        foreach ($optionsData as $idx => $opt) {
            $optStmt->execute([
                'question_id' => $questionId,
                'label' => $opt['label'] ?? chr(65 + $idx),
                'text' => $opt['text'],
                'is_correct' => $opt['is_correct'] ? 1 : 0,
                'position' => $opt['position'] ?? ($idx + 1),
            ]);
        }
    }
}
