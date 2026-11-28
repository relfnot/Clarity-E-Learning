<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DateTimeHelper;
use App\Core\HttpException;
use App\Repositories\CourseRepository;
use App\Repositories\QuizAttemptRepository;
use App\Repositories\QuizRepository;
use DateTimeImmutable;

final class QuizAttemptService
{
    private QuizAttemptRepository $attempts;
    private QuizRepository $quizzes;
    private CourseRepository $courses;
    private CourseService $courseService;
    private XpService $xpService;

    public function __construct(
        ?QuizAttemptRepository $attempts = null,
        ?QuizRepository $quizzes = null,
        ?CourseRepository $courses = null,
        ?CourseService $courseService = null,
        ?XpService $xpService = null
    ) {
        $this->attempts = $attempts ?? new QuizAttemptRepository();
        $this->quizzes = $quizzes ?? new QuizRepository();
        $this->courses = $courses ?? new CourseRepository();
        $this->courseService = $courseService ?? new CourseService($this->courses);
        $this->xpService = $xpService ?? new XpService();
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function startAttempt(int $quizId, array $user): array
    {
        $quiz = $this->quizzes->findById($quizId);
        if ($quiz === null) {
            throw new HttpException('NOT_FOUND', 'Quiz tidak ditemukan.', 404);
        }

        $courseId = (int) $quiz['course_id'];
        $this->courseService->requireCourseAccess($courseId, $user);

        $rawQuestions = $this->quizzes->listQuestionsWithOptions($quizId);
        if ($rawQuestions === []) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Quiz belum memiliki soal sehingga belum dapat dikerjakan.',
                422
            );
        }

        $tz = DateTimeHelper::timezone();
        $now = DateTimeHelper::now();
        $openAt = new DateTimeImmutable((string) $quiz['open_at'], $tz);
        $closeAt = new DateTimeImmutable((string) $quiz['close_at'], $tz);

        if ($now->getTimestamp() < $openAt->getTimestamp()) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Quiz belum dibuka sesuai jadwal.',
                422,
                ['open_at' => ['Quiz baru dibuka pada ' . DateTimeHelper::toIso((string) $quiz['open_at']) . '.']]
            );
        }

        if ($now->getTimestamp() > $closeAt->getTimestamp()) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Jendela pengerjaan quiz sudah ditutup.',
                422,
                ['close_at' => ['Batas waktu quiz telah berakhir.']]
            );
        }

        $userId = (int) $user['id'];
        $active = $this->attempts->findActiveAttempt($quizId, $userId);
        if ($active !== null) {
            $activeExpires = new DateTimeImmutable((string) $active['expires_at'], $tz);
            if ($now->getTimestamp() > $activeExpires->getTimestamp()) {
                $this->finalizeAttemptInternal((int) $active['id'], $activeExpires);
            } else {
                throw new HttpException(
                    'CONFLICT',
                    'Anda masih memiliki percobaan quiz yang sedang berlangsung.',
                    409,
                    ['active_attempt_id' => (int) $active['id']]
                );
            }
        }

        $attemptsUsed = $this->quizzes->countAttemptsByUser($quizId, $userId);
        $maxAttempts = (int) $quiz['max_attempts'];
        if ($attemptsUsed >= $maxAttempts) {
            throw new HttpException(
                'CONFLICT',
                'Batas maksimal percobaan quiz telah tercapai.',
                409,
                ['max_attempts' => ["Maksimal {$maxAttempts} percobaan."]]
            );
        }

        $durationMin = max(1, (int) $quiz['duration_min']);
        $durationEnd = $now->modify("+{$durationMin} minutes");
        $expiresAt = $durationEnd->getTimestamp() < $closeAt->getTimestamp() ? $durationEnd : $closeAt;

        $shouldShuffle = (bool) ($quiz['shuffle'] ?? false);
        $questionOrder = array_map(static fn (array $q): int => (int) $q['id'], $rawQuestions);
        if ($shouldShuffle && count($questionOrder) > 1) {
            shuffle($questionOrder);
        }

        $optionOrders = [];
        foreach ($rawQuestions as $q) {
            $optIds = array_map(static fn (array $o): int => (int) $o['id'], $q['options'] ?? []);
            if ($shouldShuffle && count($optIds) > 1) {
                shuffle($optIds);
            }
            $optionOrders[(string) $q['id']] = $optIds;
        }

        $attemptId = $this->attempts->createAttempt([
            'quiz_id' => $quizId,
            'user_id' => $userId,
            'attempt_number' => $attemptsUsed + 1,
            'question_order' => json_encode($questionOrder, JSON_THROW_ON_ERROR),
            'option_orders' => json_encode($optionOrders, JSON_THROW_ON_ERROR),
            'started_at' => $now->format('Y-m-d H:i:s'),
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        return $this->getAttempt($attemptId, $user);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function getAttempt(int $attemptId, array $user): array
    {
        $attempt = $this->requireAttemptAccess($attemptId, $user);
        $tz = DateTimeHelper::timezone();
        $now = DateTimeHelper::now();

        if (($attempt['status'] ?? '') === 'in_progress') {
            $expiresAt = new DateTimeImmutable((string) $attempt['expires_at'], $tz);
            if ($now->getTimestamp() > $expiresAt->getTimestamp()) {
                $this->finalizeAttemptInternal($attemptId, $expiresAt);
                $attempt = $this->attempts->findById($attemptId) ?? $attempt;
            }
        }

        $quizId = (int) $attempt['quiz_id'];
        $role = (string) ($user['role'] ?? 'mahasiswa');
        $status = (string) ($attempt['status'] ?? 'in_progress');
        $closeAt = new DateTimeImmutable((string) $attempt['close_at'], $tz);
        $canViewAnswers = $role === 'dosen'
            || ($status !== 'in_progress' && (bool) ($attempt['show_answers'] ?? false) && $now->getTimestamp() >= $closeAt->getTimestamp());

        $rawQuestions = $this->quizzes->listQuestionsWithOptions($quizId);
        $orderedQuestions = $this->applyStoredOrder(
            $rawQuestions,
            $attempt['question_order'] ?? null,
            $attempt['option_orders'] ?? null
        );

        $answerRows = $this->attempts->listAnswersByAttempt($attemptId);
        $answersByQuestion = [];
        foreach ($answerRows as $ans) {
            $answersByQuestion[(int) $ans['question_id']] = $ans;
        }

        $formattedQuestions = [];
        $formattedAnswers = [];

        foreach ($orderedQuestions as $q) {
            $qId = (int) $q['id'];
            $ans = $answersByQuestion[$qId] ?? null;

            $options = [];
            $correctOptionId = null;
            foreach (($q['options'] ?? []) as $opt) {
                $optId = (int) $opt['id'];
                if ((bool) $opt['is_correct']) {
                    $correctOptionId = $optId;
                }
                $optPayload = [
                    'id' => $optId,
                    'label' => (string) ($opt['label'] ?? ''),
                    'text' => (string) $opt['text'],
                    'position' => (int) ($opt['position'] ?? 1),
                ];
                if ($canViewAnswers) {
                    $optPayload['is_correct'] = (bool) $opt['is_correct'];
                }
                $options[] = $optPayload;
            }

            $qPayload = [
                'id' => $qId,
                'quiz_id' => $quizId,
                'type' => (string) $q['type'],
                'text' => (string) $q['text'],
                'points' => round((float) $q['points'], 2),
                'position' => (int) $q['position'],
                'options' => $options,
                'selected_option_id' => isset($ans['option_id']) && $ans['option_id'] !== null ? (int) $ans['option_id'] : null,
                'answer_text' => $ans['answer_text'] ?? null,
            ];

            if ($status !== 'in_progress' || $role === 'dosen') {
                $qPayload['answer_id'] = isset($ans['id']) ? (int) $ans['id'] : null;
                $qPayload['earned_score'] = isset($ans['score']) && $ans['score'] !== null ? round((float) $ans['score'], 2) : null;
                $qPayload['graded_at'] = DateTimeHelper::toIso($ans['graded_at'] ?? null);
            }
            if ($canViewAnswers && $q['type'] === 'pg') {
                $qPayload['correct_option_id'] = $correctOptionId;
            }

            $formattedQuestions[] = $qPayload;

            if ($ans !== null) {
                $formattedAnswers[] = [
                    'id' => (int) $ans['id'],
                    'question_id' => $qId,
                    'option_id' => $ans['option_id'] !== null ? (int) $ans['option_id'] : null,
                    'answer_text' => $ans['answer_text'],
                    'score' => $ans['score'] !== null ? round((float) $ans['score'], 2) : null,
                    'graded_at' => DateTimeHelper::toIso($ans['graded_at'] ?? null),
                ];
            }
        }

        $expiresAt = new DateTimeImmutable((string) $attempt['expires_at'], $tz);
        $remainingSeconds = $status === 'in_progress'
            ? max(0, $expiresAt->getTimestamp() - $now->getTimestamp())
            : 0;

        return [
            'id' => (int) $attempt['id'],
            'quiz_id' => $quizId,
            'quiz_title' => (string) ($attempt['quiz_title'] ?? ''),
            'course_id' => (int) ($attempt['course_id'] ?? 0),
            'course_code' => (string) ($attempt['course_code'] ?? ''),
            'course_name' => (string) ($attempt['course_name'] ?? ''),
            'user_id' => (int) $attempt['user_id'],
            'student_name' => (string) ($attempt['student_name'] ?? ''),
            'nim_nip' => (string) ($attempt['nim_nip'] ?? ''),
            'attempt_number' => (int) $attempt['attempt_number'],
            'duration_min' => (int) ($attempt['duration_min'] ?? 30),
            'max_attempts' => (int) ($attempt['max_attempts'] ?? 1),
            'show_answers' => (bool) ($attempt['show_answers'] ?? false),
            'can_view_answers' => $canViewAnswers,
            'answers_reveal_at' => DateTimeHelper::toIso((string) $attempt['close_at']),
            'started_at' => DateTimeHelper::toIso((string) $attempt['started_at']),
            'expires_at' => DateTimeHelper::toIso((string) $attempt['expires_at']),
            'submitted_at' => DateTimeHelper::toIso($attempt['submitted_at'] ?? null),
            'server_now' => $now->format('Y-m-d\TH:i:sP'),
            'remaining_seconds' => $remainingSeconds,
            'score' => $attempt['score'] !== null ? round((float) $attempt['score'], 2) : null,
            'status' => $status,
            'questions' => $formattedQuestions,
            'answers' => $formattedAnswers,
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @param array<int, mixed> $answersInput
     * @return array<string, mixed>
     */
    public function saveAnswers(int $attemptId, array $user, array $answersInput): array
    {
        $attempt = $this->requireAttemptOwner($attemptId, $user);

        if (($attempt['status'] ?? '') !== 'in_progress') {
            throw new HttpException('CONFLICT', 'Percobaan quiz sudah dikumpulkan dan tidak dapat diubah.', 409);
        }

        $tz = DateTimeHelper::timezone();
        $now = DateTimeHelper::now();
        $expiresAt = new DateTimeImmutable((string) $attempt['expires_at'], $tz);

        if ($now->getTimestamp() > $expiresAt->getTimestamp()) {
            $this->finalizeAttemptInternal($attemptId, $expiresAt);
            throw new HttpException(
                'VALIDATION_ERROR',
                'Waktu pengerjaan quiz telah habis. Jawaban terakhir yang tersimpan telah dinilai otomatis.',
                422,
                ['expired' => true, 'attempt_id' => $attemptId]
            );
        }

        $normalized = $this->validateAndNormalizeAnswers((int) $attempt['quiz_id'], $answersInput);
        $savedCount = $this->attempts->upsertAnswers($attemptId, $normalized);

        return [
            'saved_count' => $savedCount,
            'saved_at' => $now->format('Y-m-d\TH:i:sP'),
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @param array<int, mixed>|null $finalAnswers
     * @return array<string, mixed>
     */
    public function submitAttempt(int $attemptId, array $user, ?array $finalAnswers = null): array
    {
        $attempt = $this->requireAttemptOwner($attemptId, $user);

        if (($attempt['status'] ?? '') !== 'in_progress') {
            throw new HttpException('CONFLICT', 'Percobaan quiz ini sudah dikumpulkan sebelumnya.', 409);
        }

        $tz = DateTimeHelper::timezone();
        $now = DateTimeHelper::now();
        $expiresAt = new DateTimeImmutable((string) $attempt['expires_at'], $tz);

        if ($now->getTimestamp() > $expiresAt->getTimestamp()) {
            $this->finalizeAttemptInternal($attemptId, $expiresAt);
            throw new HttpException(
                'VALIDATION_ERROR',
                'Waktu pengerjaan quiz telah habis. Jawaban terakhir yang tersimpan telah dinilai otomatis.',
                422,
                ['expired' => true, 'attempt_id' => $attemptId]
            );
        }

        if (is_array($finalAnswers) && $finalAnswers !== []) {
            $normalized = $this->validateAndNormalizeAnswers((int) $attempt['quiz_id'], $finalAnswers);
            $this->attempts->upsertAnswers($attemptId, $normalized);
        }

        $this->finalizeAttemptInternal($attemptId, $now);

        return $this->getAttempt($attemptId, $user);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function listMyAttempts(int $quizId, array $user): array
    {
        $quiz = $this->quizzes->findById($quizId);
        if ($quiz === null) {
            throw new HttpException('NOT_FOUND', 'Quiz tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseAccess((int) $quiz['course_id'], $user);

        $userId = (int) $user['id'];
        $tz = DateTimeHelper::timezone();
        $now = DateTimeHelper::now();

        $active = $this->attempts->findActiveAttempt($quizId, $userId);
        if ($active !== null) {
            $exp = new DateTimeImmutable((string) $active['expires_at'], $tz);
            if ($now->getTimestamp() > $exp->getTimestamp()) {
                $this->finalizeAttemptInternal((int) $active['id'], $exp);
            }
        }

        $rows = $this->attempts->listByQuizAndUser($quizId, $userId);
        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'quiz_id' => (int) $row['quiz_id'],
                'attempt_number' => (int) $row['attempt_number'],
                'started_at' => DateTimeHelper::toIso((string) $row['started_at']),
                'expires_at' => DateTimeHelper::toIso((string) $row['expires_at']),
                'submitted_at' => DateTimeHelper::toIso($row['submitted_at'] ?? null),
                'score' => $row['score'] !== null ? round((float) $row['score'], 2) : null,
                'status' => (string) $row['status'],
            ];
        }, $rows);
    }

    /**
     * @param array<string, mixed> $user
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function listAttemptsForLecturer(
        int $quizId,
        array $user,
        ?string $statusFilter,
        int $page,
        int $perPage
    ): array {
        $quiz = $this->quizzes->findById($quizId);
        if ($quiz === null) {
            throw new HttpException('NOT_FOUND', 'Quiz tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseOwner((int) $quiz['course_id'], $user);

        $tz = DateTimeHelper::timezone();
        $now = DateTimeHelper::now();
        foreach ($this->attempts->listInProgressByQuiz($quizId) as $active) {
            $exp = new DateTimeImmutable((string) $active['expires_at'], $tz);
            if ($now->getTimestamp() > $exp->getTimestamp()) {
                $this->finalizeAttemptInternal((int) $active['id'], $exp);
            }
        }

        $res = $this->attempts->listByQuizForLecturer($quizId, $statusFilter, $page, $perPage);
        $items = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'quiz_id' => (int) $row['quiz_id'],
                'user_id' => (int) $row['user_id'],
                'student_name' => (string) ($row['student_name'] ?? ''),
                'nim_nip' => (string) ($row['nim_nip'] ?? ''),
                'attempt_number' => (int) $row['attempt_number'],
                'started_at' => DateTimeHelper::toIso((string) $row['started_at']),
                'expires_at' => DateTimeHelper::toIso((string) $row['expires_at']),
                'submitted_at' => DateTimeHelper::toIso($row['submitted_at'] ?? null),
                'score' => $row['score'] !== null ? round((float) $row['score'], 2) : null,
                'status' => (string) $row['status'],
                'ungraded_essay_count' => (int) ($row['ungraded_essay_count'] ?? 0),
            ];
        }, $res['items']);

        return [
            'items' => $items,
            'meta' => $res['meta'],
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function gradeEssayAnswer(int $answerId, array $user, float $score): array
    {
        $answer = $this->attempts->findAnswerById($answerId);
        if ($answer === null) {
            throw new HttpException('NOT_FOUND', 'Jawaban soal tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseOwner((int) $answer['course_id'], $user);

        if (($answer['question_type'] ?? '') !== 'esai') {
            throw new HttpException('VALIDATION_ERROR', 'Penilaian manual hanya berlaku untuk soal tipe esai.', 422);
        }

        if (($answer['attempt_status'] ?? '') === 'in_progress') {
            throw new HttpException('CONFLICT', 'Percobaan quiz masih berlangsung dan belum dikumpulkan.', 409);
        }

        $maxPoints = (float) ($answer['question_points'] ?? 0);
        if ($score < 0 || $score > $maxPoints) {
            throw new HttpException(
                'VALIDATION_ERROR',
                sprintf('Skor soal harus berada antara 0 sampai %.2f.', $maxPoints),
                422,
                ['score' => ['Nilai melebihi poin maksimal soal atau bernilai negatif.']]
            );
        }

        $nowDb = DateTimeHelper::now()->format('Y-m-d H:i:s');
        $this->attempts->gradeEssayAnswer($answerId, $score, (int) $user['id'], $nowDb);

        $attemptId = (int) $answer['attempt_id'];
        $quizId = (int) $answer['quiz_id'];
        [$normalizedScore, $newStatus] = $this->recalculateAttemptTotals($attemptId, $quizId);
        $this->attempts->updateAttemptScoreAndStatus($attemptId, $normalizedScore, $newStatus);

        return $this->getAttempt($attemptId, $user);
    }

    /**
     * @return array<string, mixed>
     */
    private function requireAttemptAccess(int $attemptId, array $user): array
    {
        $attempt = $this->attempts->findById($attemptId);
        if ($attempt === null) {
            throw new HttpException('NOT_FOUND', 'Percobaan quiz tidak ditemukan.', 404);
        }

        $role = (string) ($user['role'] ?? '');
        $userId = (int) ($user['id'] ?? 0);

        if ($role === 'dosen') {
            if ((int) $attempt['lecturer_id'] !== $userId) {
                throw new HttpException('FORBIDDEN', 'Anda bukan dosen pengampu mata kuliah ini.', 403);
            }
            return $attempt;
        }

        if ((int) $attempt['user_id'] !== $userId) {
            throw new HttpException('FORBIDDEN', 'Anda tidak berhak mengakses percobaan quiz mahasiswa lain.', 403);
        }

        return $attempt;
    }

    /**
     * @return array<string, mixed>
     */
    private function requireAttemptOwner(int $attemptId, array $user): array
    {
        $attempt = $this->attempts->findById($attemptId);
        if ($attempt === null) {
            throw new HttpException('NOT_FOUND', 'Percobaan quiz tidak ditemukan.', 404);
        }

        if (($user['role'] ?? '') !== 'mahasiswa' || (int) $attempt['user_id'] !== (int) ($user['id'] ?? 0)) {
            throw new HttpException('FORBIDDEN', 'Hanya mahasiswa pemilik percobaan yang dapat mengubah jawaban.', 403);
        }

        return $attempt;
    }

    private function finalizeAttemptInternal(int $attemptId, DateTimeImmutable $submittedAt): void
    {
        $attempt = $this->attempts->findById($attemptId);
        if ($attempt === null || ($attempt['status'] ?? '') !== 'in_progress') {
            return;
        }

        $quizId = (int) $attempt['quiz_id'];
        $questions = $this->quizzes->listQuestionsWithOptions($quizId);
        $answerRows = $this->attempts->listAnswersByAttempt($attemptId);
        $answersByQ = [];
        foreach ($answerRows as $ans) {
            $answersByQ[(int) $ans['question_id']] = $ans;
        }

        $totalPoints = 0.0;
        $earnedPoints = 0.0;
        $pgScoresByQuestionId = [];
        $essayQuestionIds = [];

        foreach ($questions as $q) {
            $qId = (int) $q['id'];
            $pts = (float) $q['points'];
            $totalPoints += $pts;

            if (($q['type'] ?? '') === 'esai') {
                $essayQuestionIds[] = $qId;
                continue;
            }

            $correctOptionId = null;
            foreach (($q['options'] ?? []) as $opt) {
                if ((bool) $opt['is_correct']) {
                    $correctOptionId = (int) $opt['id'];
                    break;
                }
            }

            $ans = $answersByQ[$qId] ?? null;
            $selectedOptionId = isset($ans['option_id']) && $ans['option_id'] !== null ? (int) $ans['option_id'] : null;
            $isCorrect = $correctOptionId !== null && $selectedOptionId === $correctOptionId;
            $qScore = $isCorrect ? $pts : 0.0;

            if ($ans !== null) {
                $pgScoresByQuestionId[$qId] = $qScore;
            }
            $earnedPoints += $qScore;
        }

        $finalScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0.0;
        $status = $essayQuestionIds !== [] ? 'menunggu_penilaian' : 'graded';

        $this->attempts->finalizeAttempt(
            $attemptId,
            $pgScoresByQuestionId,
            $essayQuestionIds,
            $submittedAt->format('Y-m-d H:i:s'),
            $finalScore,
            $status
        );

        $this->xpService->award((int) $attempt['user_id'], (int) $attempt['course_id'], 'quiz_complete', $attemptId, 50);
    }

    /**
     * @return array{0: float, 1: string}
     */
    private function recalculateAttemptTotals(int $attemptId, int $quizId): array
    {
        $questions = $this->quizzes->listQuestionsWithOptions($quizId);
        $answerRows = $this->attempts->listAnswersByAttempt($attemptId);
        $answersByQ = [];
        foreach ($answerRows as $ans) {
            $answersByQ[(int) $ans['question_id']] = $ans;
        }

        $totalPoints = 0.0;
        $earnedPoints = 0.0;
        $hasPendingEssay = false;

        foreach ($questions as $q) {
            $qId = (int) $q['id'];
            $pts = (float) $q['points'];
            $totalPoints += $pts;
            $ans = $answersByQ[$qId] ?? null;

            if (($q['type'] ?? '') === 'esai') {
                if ($ans === null || empty($ans['graded_at'])) {
                    $hasPendingEssay = true;
                }
                if ($ans !== null && $ans['score'] !== null) {
                    $earnedPoints += (float) $ans['score'];
                }
            } else {
                if ($ans !== null && $ans['score'] !== null) {
                    $earnedPoints += (float) $ans['score'];
                }
            }
        }

        $normalized = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0.0;
        return [$normalized, $hasPendingEssay ? 'menunggu_penilaian' : 'graded'];
    }

    /**
     * @param array<int, array<string, mixed>> $rawQuestions
     * @return array<int, array<string, mixed>>
     */
    private function applyStoredOrder(array $rawQuestions, mixed $questionOrderJson, mixed $optionOrdersJson): array
    {
        $questionMap = [];
        foreach ($rawQuestions as $q) {
            $questionMap[(int) $q['id']] = $q;
        }

        $qOrder = is_string($questionOrderJson) ? json_decode($questionOrderJson, true) : null;
        $optOrders = is_string($optionOrdersJson) ? json_decode($optionOrdersJson, true) : null;

        $ordered = [];
        if (is_array($qOrder)) {
            foreach ($qOrder as $qIdRaw) {
                $qId = (int) $qIdRaw;
                if (isset($questionMap[$qId])) {
                    $q = $questionMap[$qId];
                    unset($questionMap[$qId]);
                    $ordered[] = $q;
                }
            }
        }
        foreach ($questionMap as $remaining) {
            $ordered[] = $remaining;
        }

        if (is_array($optOrders)) {
            foreach ($ordered as &$q) {
                $qIdKey = (string) $q['id'];
                if (!isset($optOrders[$qIdKey]) || !is_array($optOrders[$qIdKey])) {
                    continue;
                }
                $optMap = [];
                foreach (($q['options'] ?? []) as $opt) {
                    $optMap[(int) $opt['id']] = $opt;
                }
                $reorderedOpts = [];
                foreach ($optOrders[$qIdKey] as $optIdRaw) {
                    $optId = (int) $optIdRaw;
                    if (isset($optMap[$optId])) {
                        $reorderedOpts[] = $optMap[$optId];
                        unset($optMap[$optId]);
                    }
                }
                foreach ($optMap as $remOpt) {
                    $reorderedOpts[] = $remOpt;
                }
                $q['options'] = $reorderedOpts;
            }
            unset($q);
        }

        return $ordered;
    }

    /**
     * @param array<int, mixed> $answersInput
     * @return array<int, array{question_id: int, option_id: ?int, answer_text: ?string}>
     */
    private function validateAndNormalizeAnswers(int $quizId, array $answersInput): array
    {
        $questions = $this->quizzes->listQuestionsWithOptions($quizId);
        $questionMap = [];
        foreach ($questions as $q) {
            $optIds = [];
            foreach (($q['options'] ?? []) as $opt) {
                $optIds[(int) $opt['id']] = true;
            }
            $questionMap[(int) $q['id']] = [
                'type' => (string) $q['type'],
                'options' => $optIds,
            ];
        }

        $normalized = [];
        foreach ($answersInput as $idx => $entry) {
            if (!is_array($entry) || empty($entry['question_id'])) {
                throw new HttpException(
                    'VALIDATION_ERROR',
                    "Format jawaban ke-" . ($idx + 1) . ' tidak valid.',
                    422,
                    ['answers' => ['Setiap item jawaban wajib menyertakan question_id.']]
                );
            }

            $qId = (int) $entry['question_id'];
            if (!isset($questionMap[$qId])) {
                throw new HttpException(
                    'VALIDATION_ERROR',
                    "Soal dengan ID {$qId} bukan bagian dari quiz ini.",
                    422,
                    ['answers' => ['Soal tidak valid untuk quiz ini.']]
                );
            }

            $meta = $questionMap[$qId];
            $optionId = isset($entry['option_id']) && $entry['option_id'] !== null && $entry['option_id'] !== ''
                ? (int) $entry['option_id']
                : null;
            $answerText = isset($entry['answer_text']) && $entry['answer_text'] !== null
                ? (string) $entry['answer_text']
                : null;

            if ($meta['type'] === 'pg') {
                if ($optionId !== null && !isset($meta['options'][$optionId])) {
                    throw new HttpException(
                        'VALIDATION_ERROR',
                        "Opsi jawaban {$optionId} tidak valid untuk soal {$qId}.",
                        422,
                        ['answers' => ['Opsi pilihan tidak cocok dengan soal.']]
                    );
                }
                $answerText = null;
            } else {
                $optionId = null;
            }

            $normalized[] = [
                'question_id' => $qId,
                'option_id' => $optionId,
                'answer_text' => $answerText,
            ];
        }

        return $normalized;
    }
}
