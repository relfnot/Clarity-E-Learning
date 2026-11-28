<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DateTimeHelper;
use App\Core\HttpException;
use App\Repositories\CourseRepository;
use App\Repositories\QuizRepository;
use DateTimeImmutable;

final class QuizService
{
    private QuizRepository $quizzes;
    private CourseRepository $courses;
    private CourseService $courseService;
    private NotificationService $notificationService;

    public function __construct(
        ?QuizRepository $quizzes = null,
        ?CourseRepository $courses = null,
        ?CourseService $courseService = null,
        ?NotificationService $notificationService = null
    ) {
        $this->quizzes = $quizzes ?? new QuizRepository();
        $this->courses = $courses ?? new CourseRepository();
        $this->courseService = $courseService ?? new CourseService($this->courses);
        $this->notificationService = $notificationService ?? new NotificationService();
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function listByCourse(int $courseId, array $user): array
    {
        $this->courseService->requireCourseAccess($courseId, $user);
        $rows = $this->quizzes->listByCourse($courseId);
        $role = (string) ($user['role'] ?? '');
        $userId = (int) $user['id'];

        return array_map(function (array $row) use ($role, $userId): array {
            $qId = (int) $row['id'];
            $attemptsUsed = $role === 'mahasiswa'
                ? $this->quizzes->countAttemptsByUser($qId, $userId)
                : 0;
            $extra = $role === 'mahasiswa' ? [
                'active_attempt_id' => $this->quizzes->findActiveAttemptId($qId, $userId),
                'best_score' => $this->quizzes->findBestScoreByUser($qId, $userId),
            ] : [];
            return $this->formatQuiz($row, $role, $attemptsUsed, null, $extra);
        }, $rows);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function getDetail(int $quizId, array $user): array
    {
        $quiz = $this->quizzes->findById($quizId);
        if ($quiz === null) {
            throw new HttpException('NOT_FOUND', 'Quiz tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseAccess((int) $quiz['course_id'], $user);

        $role = (string) ($user['role'] ?? '');
        $userId = (int) $user['id'];
        $attemptsUsed = $role === 'mahasiswa'
            ? $this->quizzes->countAttemptsByUser($quizId, $userId)
            : 0;
        $extra = $role === 'mahasiswa' ? [
            'active_attempt_id' => $this->quizzes->findActiveAttemptId($quizId, $userId),
            'best_score' => $this->quizzes->findBestScoreByUser($quizId, $userId),
        ] : [];

        // Soal & kunci jawaban hanya diberikan kepada dosen pengampu; tidak pernah bocor ke mahasiswa
        $questions = null;
        if ($role === 'dosen') {
            $rawQuestions = $this->quizzes->listQuestionsWithOptions($quizId);
            $questions = array_map(fn (array $q): array => $this->formatQuestion($q, true), $rawQuestions);
        }

        return $this->formatQuiz($quiz, $role, $attemptsUsed, $questions, $extra);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function create(int $courseId, array $user, array $input): array
    {
        $course = $this->courseService->requireCourseOwner($courseId, $user);
        $normalized = $this->validateAndNormalizeQuizInput($courseId, $input);

        $quizId = $this->quizzes->create($normalized + ['course_id' => $courseId]);
        $created = $this->quizzes->findById($quizId);

        // Kirim notifikasi quiz baru ke seluruh mahasiswa terdaftar
        $participants = $this->courses->getParticipants($courseId);
        $studentIds = array_map(static fn (array $p): int => (int) $p['id'], $participants);
        if ($studentIds !== []) {
            $this->notificationService->sendToUsers(
                $studentIds,
                'quiz_baru',
                sprintf('Quiz Baru: %s', $normalized['title']),
                sprintf(
                    'Quiz baru pada %s dibuka mulai %s hingga %s.',
                    (string) ($course['name'] ?? 'mata kuliah'),
                    DateTimeHelper::toIso($normalized['open_at']) ?? $normalized['open_at'],
                    DateTimeHelper::toIso($normalized['close_at']) ?? $normalized['close_at']
                ),
                sprintf('course-detail.php?id=%d', $courseId)
            );
        }

        return $this->formatQuiz($created ?? [], 'dosen', 0, []);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function update(int $quizId, array $user, array $input): array
    {
        $quiz = $this->quizzes->findById($quizId);
        if ($quiz === null) {
            throw new HttpException('NOT_FOUND', 'Quiz tidak ditemukan.', 404);
        }

        $courseId = (int) $quiz['course_id'];
        $this->courseService->requireCourseOwner($courseId, $user);

        $normalized = $this->validateAndNormalizeQuizInput($courseId, $input);

        $this->quizzes->update($quizId, $normalized);
        $updated = $this->quizzes->findById($quizId);
        $rawQuestions = $this->quizzes->listQuestionsWithOptions($quizId);
        $questions = array_map(fn (array $q): array => $this->formatQuestion($q, true), $rawQuestions);

        return $this->formatQuiz($updated ?? [], 'dosen', 0, $questions);
    }

    /**
     * @param array<string, mixed> $user
     */
    public function delete(int $quizId, array $user): void
    {
        $quiz = $this->quizzes->findById($quizId);
        if ($quiz === null) {
            throw new HttpException('NOT_FOUND', 'Quiz tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseOwner((int) $quiz['course_id'], $user);
        $this->quizzes->delete($quizId);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function listQuestions(int $quizId, array $user): array
    {
        $quiz = $this->quizzes->findById($quizId);
        if ($quiz === null) {
            throw new HttpException('NOT_FOUND', 'Quiz tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseOwner((int) $quiz['course_id'], $user);
        $rows = $this->quizzes->listQuestionsWithOptions($quizId);

        return array_map(fn (array $q): array => $this->formatQuestion($q, true), $rows);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function addQuestion(int $quizId, array $user, array $input): array
    {
        $quiz = $this->quizzes->findById($quizId);
        if ($quiz === null) {
            throw new HttpException('NOT_FOUND', 'Quiz tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseOwner((int) $quiz['course_id'], $user);
        [$questionData, $optionsData] = $this->validateAndNormalizeQuestionInput($input);

        $questionId = $this->quizzes->createQuestionWithOptions(
            $questionData + ['quiz_id' => $quizId],
            $optionsData
        );

        $saved = $this->quizzes->findQuestionById($questionId);
        return $this->formatQuestion($saved ?? [], true);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateQuestion(int $questionId, array $user, array $input, bool $force = false): array
    {
        $question = $this->quizzes->findQuestionById($questionId);
        if ($question === null) {
            throw new HttpException('NOT_FOUND', 'Soal tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseOwner((int) $question['course_id'], $user);

        $attemptsCount = $this->quizzes->countAttemptsForQuiz((int) $question['quiz_id']);
        if ($attemptsCount > 0 && !$force) {
            throw new HttpException(
                'CONFLICT',
                'Quiz ini sudah memiliki percobaan mahasiswa. Ubah soal dapat memengaruhi penilaian; kirim force=true jika yakin.',
                409
            );
        }

        [$questionData, $optionsData] = $this->validateAndNormalizeQuestionInput($input);
        $this->quizzes->updateQuestionWithOptions($questionId, $questionData, $optionsData);

        $saved = $this->quizzes->findQuestionById($questionId);
        return $this->formatQuestion($saved ?? [], true);
    }

    /**
     * @param array<string, mixed> $user
     */
    public function deleteQuestion(int $questionId, array $user, bool $force = false): void
    {
        $question = $this->quizzes->findQuestionById($questionId);
        if ($question === null) {
            throw new HttpException('NOT_FOUND', 'Soal tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseOwner((int) $question['course_id'], $user);

        $attemptsCount = $this->quizzes->countAttemptsForQuiz((int) $question['quiz_id']);
        if ($attemptsCount > 0 && !$force) {
            throw new HttpException(
                'CONFLICT',
                'Quiz ini sudah memiliki percobaan mahasiswa. Hapus soal dibatasi; kirim force=true jika yakin.',
                409
            );
        }

        $this->quizzes->deleteQuestion($questionId);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function validateAndNormalizeQuizInput(int $courseId, array $input): array
    {
        $meetingId = isset($input['meeting_id']) && $input['meeting_id'] !== '' && $input['meeting_id'] !== null
            ? (int) $input['meeting_id']
            : null;

        if ($meetingId !== null) {
            $meeting = $this->courses->findMeetingById($meetingId);
            if ($meeting === null || (int) $meeting['course_id'] !== $courseId) {
                throw new HttpException(
                    'VALIDATION_ERROR',
                    'Pertemuan tidak ditemukan pada mata kuliah ini.',
                    422,
                    ['meeting_id' => ['Pertemuan tidak valid.']]
                );
            }
        }

        $openAtDb = DateTimeHelper::toDb((string) $input['open_at'], 'open_at');
        $closeAtDb = DateTimeHelper::toDb((string) $input['close_at'], 'close_at');

        $openDt = new DateTimeImmutable($openAtDb, DateTimeHelper::timezone());
        $closeDt = new DateTimeImmutable($closeAtDb, DateTimeHelper::timezone());
        if ($closeDt->getTimestamp() <= $openDt->getTimestamp()) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Waktu tutup quiz harus setelah waktu buka.',
                422,
                ['close_at' => ['close_at harus lebih besar dari open_at.']]
            );
        }

        $durationMin = (int) ($input['duration_min'] ?? 0);
        $maxAttempts = (int) ($input['max_attempts'] ?? 0);
        $weight = (float) ($input['weight'] ?? 0);

        $errors = [];
        if ($durationMin <= 0 || $durationMin > 600) {
            $errors['duration_min'][] = 'Durasi quiz harus antara 1 sampai 600 menit.';
        }
        if ($maxAttempts <= 0 || $maxAttempts > 20) {
            $errors['max_attempts'][] = 'Batas percobaan harus antara 1 sampai 20.';
        }
        if ($weight < 0 || $weight > 100) {
            $errors['weight'][] = 'Bobot quiz harus antara 0 sampai 100.';
        }

        if ($errors !== []) {
            throw new HttpException('VALIDATION_ERROR', 'Validasi input quiz gagal.', 422, $errors);
        }

        $shuffle = filter_var($input['shuffle'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $showAnswers = filter_var($input['show_answers'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'meeting_id' => $meetingId,
            'title' => trim((string) $input['title']),
            'description' => isset($input['description']) ? trim((string) $input['description']) : null,
            'open_at' => $openAtDb,
            'close_at' => $closeAtDb,
            'duration_min' => $durationMin,
            'max_attempts' => $maxAttempts,
            'shuffle' => $shuffle ? 1 : 0,
            'show_answers' => $showAnswers ? 1 : 0,
            'weight' => round($weight, 2),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>}
     */
    private function validateAndNormalizeQuestionInput(array $input): array
    {
        $type = (string) ($input['type'] ?? '');
        if (!in_array($type, ['pg', 'esai'], true)) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Tipe soal harus berupa pg atau esai.',
                422,
                ['type' => ['Nilai yang diizinkan: pg, esai.']]
            );
        }

        $text = trim((string) ($input['text'] ?? ''));
        $points = (float) ($input['points'] ?? 0);
        $position = (int) ($input['position'] ?? 1);

        $errors = [];
        if ($text === '') {
            $errors['text'][] = 'Teks soal wajib diisi.';
        }
        if ($points <= 0 || $points > 1000) {
            $errors['points'][] = 'Poin soal harus lebih dari 0 dan maksimal 1000.';
        }
        if ($position <= 0) {
            $errors['position'][] = 'Posisi soal harus bilangan bulat positif.';
        }

        $rawOptions = $input['options'] ?? [];
        $optionsData = [];

        if ($type === 'pg') {
            if (!is_array($rawOptions) || count($rawOptions) < 2) {
                $errors['options'][] = 'Soal pilihan ganda wajib memiliki minimal 2 opsi jawaban.';
            } else {
                $correctCount = 0;
                foreach (array_values($rawOptions) as $idx => $opt) {
                    if (!is_array($opt)) {
                        $errors['options'][] = 'Format opsi jawaban tidak valid.';
                        break;
                    }
                    $optText = trim((string) ($opt['text'] ?? ''));
                    if ($optText === '') {
                        $errors['options'][] = sprintf('Teks opsi ke-%d wajib diisi.', $idx + 1);
                        continue;
                    }
                    $isCorrect = filter_var($opt['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN);
                    if ($isCorrect) {
                        $correctCount++;
                    }
                    $optionsData[] = [
                        'label' => isset($opt['label']) && trim((string) $opt['label']) !== ''
                            ? trim((string) $opt['label'])
                            : chr(65 + $idx),
                        'text' => $optText,
                        'is_correct' => $isCorrect,
                        'position' => $idx + 1,
                    ];
                }

                if ($correctCount !== 1 && !isset($errors['options'])) {
                    $errors['options'][] = 'Soal pilihan ganda wajib memiliki tepat 1 opsi jawaban benar.';
                }
            }
        }

        if ($errors !== []) {
            throw new HttpException('VALIDATION_ERROR', 'Validasi input soal gagal.', 422, $errors);
        }

        return [
            [
                'type' => $type,
                'text' => $text,
                'points' => round($points, 2),
                'position' => $position,
            ],
            $optionsData,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, array<string, mixed>>|null $questions
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function formatQuiz(array $row, string $role, int $attemptsUsed, ?array $questions, array $extra = []): array
    {
        $tz = DateTimeHelper::timezone();
        $nowTs = DateTimeHelper::now()->getTimestamp();
        $openTs = !empty($row['open_at']) ? (new DateTimeImmutable((string) $row['open_at'], $tz))->getTimestamp() : 0;
        $closeTs = !empty($row['close_at']) ? (new DateTimeImmutable((string) $row['close_at'], $tz))->getTimestamp() : 0;
        $availabilityStatus = $nowTs < $openTs ? 'belum_buka' : ($nowTs > $closeTs ? 'ditutup' : 'tersedia');

        $data = [
            'id' => (int) ($row['id'] ?? 0),
            'course_id' => (int) ($row['course_id'] ?? 0),
            'course_code' => (string) ($row['course_code'] ?? ''),
            'course_name' => (string) ($row['course_name'] ?? ''),
            'meeting_id' => isset($row['meeting_id']) && $row['meeting_id'] !== null ? (int) $row['meeting_id'] : null,
            'title' => (string) ($row['title'] ?? ''),
            'description' => $row['description'] ?? '',
            'open_at' => DateTimeHelper::toIso((string) ($row['open_at'] ?? '')),
            'close_at' => DateTimeHelper::toIso((string) ($row['close_at'] ?? '')),
            'duration_min' => (int) ($row['duration_min'] ?? 30),
            'max_attempts' => (int) ($row['max_attempts'] ?? 1),
            'attempts_used' => $attemptsUsed,
            'shuffle' => (bool) ($row['shuffle'] ?? false),
            'show_answers' => (bool) ($row['show_answers'] ?? false),
            'total_questions' => (int) ($row['total_questions'] ?? 0),
            'total_points' => round((float) ($row['total_points'] ?? 0), 2),
            'weight' => round((float) ($row['weight'] ?? 0), 2),
            'availability_status' => $availabilityStatus,
        ];

        if ($role === 'mahasiswa') {
            $data['active_attempt_id'] = $extra['active_attempt_id'] ?? null;
            $data['best_score'] = $extra['best_score'] ?? null;
        }

        if ($role === 'dosen') {
            $data['attempts_count'] = (int) ($row['attempts_count'] ?? 0);
            if ($questions !== null) {
                $data['questions'] = $questions;
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatQuestion(array $row, bool $includeCorrect): array
    {
        $options = [];
        foreach (($row['options'] ?? []) as $opt) {
            $item = [
                'id' => (int) $opt['id'],
                'label' => (string) ($opt['label'] ?? ''),
                'text' => (string) $opt['text'],
                'position' => (int) ($opt['position'] ?? 1),
            ];
            if ($includeCorrect) {
                $item['is_correct'] = (bool) $opt['is_correct'];
            }
            $options[] = $item;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'quiz_id' => (int) ($row['quiz_id'] ?? 0),
            'type' => (string) ($row['type'] ?? 'pg'),
            'text' => (string) ($row['text'] ?? ''),
            'points' => round((float) ($row['points'] ?? 0), 2),
            'position' => (int) ($row['position'] ?? 1),
            'options' => $options,
        ];
    }
}
