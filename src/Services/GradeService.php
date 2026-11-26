<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DateTimeHelper;
use App\Core\HttpException;
use App\Repositories\AssignmentRepository;
use App\Repositories\CourseRepository;
use App\Repositories\GradeRepository;
use App\Repositories\SubmissionRepository;

final class GradeService
{
    public const COMPONENTS = ['tugas', 'quiz', 'presensi', 'uts', 'uas'];

    public const DEFAULT_WEIGHTS = [
        'tugas' => 30.0,
        'quiz' => 20.0,
        'presensi' => 10.0,
        'uts' => 20.0,
        'uas' => 20.0,
    ];

    private GradeRepository $grades;
    private CourseRepository $courses;
    private CourseService $courseService;
    private SubmissionRepository $submissions;
    private AssignmentRepository $assignments;
    private NotificationService $notificationService;

    public function __construct(
        ?GradeRepository $grades = null,
        ?CourseRepository $courses = null,
        ?CourseService $courseService = null,
        ?SubmissionRepository $submissions = null,
        ?AssignmentRepository $assignments = null,
        ?NotificationService $notificationService = null
    ) {
        $this->grades = $grades ?? new GradeRepository();
        $this->courses = $courses ?? new CourseRepository();
        $this->courseService = $courseService ?? new CourseService($this->courses);
        $this->submissions = $submissions ?? new SubmissionRepository();
        $this->assignments = $assignments ?? new AssignmentRepository();
        $this->notificationService = $notificationService ?? new NotificationService();
    }

    /**
     * Rumus nilai akhir berbobot (PRD M6 FR3):
     * - Komponen dengan nilai null ditandai "belum_ada" dan tidak dihitung 0 secara diam-diam.
     * - final_score = Σ(average × weight) / Σ(weight komponen yang sudah tersedia).
     * - raw_weighted_score = Σ(average × weight) / 100.
     *
     * @param array<string, float|null> $componentAverages
     * @param array<string, float> $weights
     * @return array{
     *   components: array<string, array{average: float|null, weight: float, status: string}>,
     *   final_score: float|null,
     *   raw_weighted_score: float,
     *   completed_weight_percent: float,
     *   letter_grade: string|null
     * }
     */
    public static function calculateFinalScore(array $componentAverages, array $weights): array
    {
        $components = [];
        $weightedSum = 0.0;
        $activeWeight = 0.0;

        foreach (self::COMPONENTS as $component) {
            $weight = round((float) ($weights[$component] ?? 0.0), 2);
            $avg = array_key_exists($component, $componentAverages) && $componentAverages[$component] !== null
                ? round((float) $componentAverages[$component], 2)
                : null;

            $hasData = $avg !== null;
            if ($hasData && $weight > 0) {
                $weightedSum += $avg * $weight;
                $activeWeight += $weight;
            }

            $components[$component] = [
                'average' => $avg,
                'weight' => $weight,
                'status' => $hasData ? 'tersedia' : 'belum_ada',
            ];
        }

        $finalScore = $activeWeight > 0 ? round($weightedSum / $activeWeight, 2) : null;
        $rawWeightedScore = round($weightedSum / 100.0, 2);

        return [
            'components' => $components,
            'final_score' => $finalScore,
            'raw_weighted_score' => $rawWeightedScore,
            'completed_weight_percent' => round($activeWeight, 2),
            'letter_grade' => $finalScore !== null ? self::toLetterGrade($finalScore) : null,
        ];
    }

    public static function toLetterGrade(float $score): string
    {
        if ($score >= 85.0) {
            return 'A';
        }
        if ($score >= 80.0) {
            return 'A-';
        }
        if ($score >= 75.0) {
            return 'B+';
        }
        if ($score >= 70.0) {
            return 'B';
        }
        if ($score >= 65.0) {
            return 'B-';
        }
        if ($score >= 60.0) {
            return 'C+';
        }
        if ($score >= 55.0) {
            return 'C';
        }
        if ($score >= 45.0) {
            return 'D';
        }
        return 'E';
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, float|bool>
     */
    public function getWeights(int $courseId, array $user): array
    {
        $this->courseService->requireCourseAccess($courseId, $user);
        $stored = $this->grades->getWeightsByCourse($courseId);
        if ($stored === []) {
            return self::DEFAULT_WEIGHTS + ['is_default' => true];
        }

        $resolved = [];
        foreach (self::COMPONENTS as $component) {
            $resolved[$component] = round((float) ($stored[$component] ?? 0.0), 2);
        }
        $resolved['is_default'] = false;
        return $resolved;
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, float|bool>
     */
    public function updateWeights(int $courseId, array $user, array $input): array
    {
        $this->courseService->requireCourseOwner($courseId, $user);

        $weights = [];
        $errors = [];
        $total = 0.0;

        foreach (self::COMPONENTS as $component) {
            if (!array_key_exists($component, $input) || !is_numeric($input[$component])) {
                $errors[$component][] = "Bobot komponen {$component} wajib berupa angka.";
                continue;
            }
            $value = round((float) $input[$component], 2);
            if ($value < 0 || $value > 100) {
                $errors[$component][] = "Bobot komponen {$component} harus antara 0 sampai 100.";
                continue;
            }
            $weights[$component] = $value;
            $total += $value;
        }

        if ($errors !== []) {
            throw new HttpException('VALIDATION_ERROR', 'Validasi bobot nilai gagal.', 422, $errors);
        }

        if (abs($total - 100.0) > 0.001) {
            throw new HttpException(
                'VALIDATION_ERROR',
                sprintf('Total bobot semua komponen wajib tepat 100%% (saat ini %.2f%%).', $total),
                422,
                ['total' => ['Jumlah bobot tugas + quiz + presensi + uts + uas harus tepat 100.']]
            );
        }

        $this->grades->upsertWeights($courseId, $weights);
        return $weights + ['is_default' => false];
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function gradeSubmission(int $submissionId, array $user, array $input): array
    {
        $submission = $this->submissions->findById($submissionId);
        if ($submission === null) {
            throw new HttpException('NOT_FOUND', 'Pengumpulan tugas tidak ditemukan.', 404);
        }

        $courseId = (int) $submission['course_id'];
        $this->courseService->requireCourseOwner($courseId, $user);

        $assignment = $this->assignments->findById((int) $submission['assignment_id']);
        $maxScore = (float) ($assignment['max_score'] ?? 100);

        if (!isset($input['score']) || !is_numeric($input['score'])) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Nilai (score) wajib berupa angka.',
                422,
                ['score' => ['Nilai harus berupa angka.']]
            );
        }

        $score = round((float) $input['score'], 2);
        if ($score < 0 || $score > $maxScore) {
            throw new HttpException(
                'VALIDATION_ERROR',
                sprintf('Nilai harus berada pada rentang 0 sampai %.0f.', $maxScore),
                422,
                ['score' => [sprintf('Nilai harus antara 0 dan %.0f.', $maxScore)]]
            );
        }

        $feedback = isset($input['feedback']) && trim((string) $input['feedback']) !== ''
            ? trim((string) $input['feedback'])
            : null;
        $gradedAtDb = DateTimeHelper::now()->format('Y-m-d H:i:s');

        $this->grades->updateSubmissionGrade($submissionId, $score, $feedback, $gradedAtDb);

        // Kirim notifikasi nilai keluar ke mahasiswa
        $this->notificationService->send(
            (int) $submission['user_id'],
            'nilai_keluar',
            sprintf('Nilai Keluar: %s', (string) ($assignment['title'] ?? 'Tugas')),
            sprintf(
                'Tugas "%s" Anda telah dinilai dengan skor %.2f/%.0f.',
                (string) ($assignment['title'] ?? 'Tugas'),
                $score,
                $maxScore
            ),
            sprintf('assignment.php?id=%d', (int) $submission['assignment_id'])
        );

        $updated = $this->submissions->findById($submissionId);
        return SubmissionService::formatSubmissionRow($updated ?? []);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function upsertExamComponentScore(int $courseId, int $studentId, array $user, array $input): array
    {
        $course = $this->courseService->requireCourseOwner($courseId, $user);
        if (!$this->courses->isEnrolled($courseId, $studentId)) {
            throw new HttpException('NOT_FOUND', 'Mahasiswa tidak terdaftar pada mata kuliah ini.', 404);
        }

        $component = strtolower(trim((string) ($input['component'] ?? '')));
        if (!in_array($component, ['uts', 'uas'], true)) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Komponen ujian harus berupa uts atau uas.',
                422,
                ['component' => ['Nilai yang diizinkan: uts, uas.']]
            );
        }

        if (!isset($input['score']) || !is_numeric($input['score'])) {
            throw new HttpException('VALIDATION_ERROR', 'Skor wajib berupa angka.', 422, ['score' => ['Skor wajib angka.']]);
        }

        $score = round((float) $input['score'], 2);
        if ($score < 0 || $score > 100) {
            throw new HttpException('VALIDATION_ERROR', 'Skor harus antara 0 sampai 100.', 422, ['score' => ['Rentang 0-100.']]);
        }

        $feedback = isset($input['feedback']) && trim((string) $input['feedback']) !== ''
            ? trim((string) $input['feedback'])
            : null;
        $gradedAtDb = DateTimeHelper::now()->format('Y-m-d H:i:s');

        $this->grades->upsertComponentScore(
            $courseId,
            $studentId,
            $component,
            $score,
            $feedback,
            (int) $user['id'],
            $gradedAtDb
        );

        $this->notificationService->send(
            $studentId,
            'nilai_keluar',
            sprintf('Nilai %s Keluar: %s', strtoupper($component), (string) ($course['name'] ?? '')),
            sprintf('Nilai %s Anda pada %s adalah %.2f.', strtoupper($component), (string) ($course['name'] ?? ''), $score),
            sprintf('course-detail.php?id=%d', $courseId)
        );

        return [
            'course_id' => $courseId,
            'student_id' => $studentId,
            'component' => $component,
            'score' => $score,
            'feedback' => $feedback,
            'graded_at' => DateTimeHelper::toIso($gradedAtDb),
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function getStudentSummary(int $courseId, array $user): array
    {
        $course = $this->courseService->requireCourseAccess($courseId, $user);
        $studentId = (int) $user['id'];
        $weights = $this->resolveWeightsMap($courseId);

        $summary = $this->buildStudentGradeSummary($course, [
            'id' => $studentId,
            'name' => (string) ($user['name'] ?? ''),
            'nim' => (string) ($user['nim_nip'] ?? ''),
        ], $weights, true);

        // Hitung rata-rata kelas secara anonim (tanpa membocorkan nilai individu lain)
        $participants = $this->courses->getParticipants($courseId);
        $classScores = [];
        foreach ($participants as $p) {
            $s = $this->buildStudentGradeSummary($course, $p, $weights, false);
            if ($s['final_score'] !== null) {
                $classScores[] = (float) $s['final_score'];
            }
        }
        $summary['class_average'] = $classScores !== []
            ? round(array_sum($classScores) / count($classScores), 2)
            : null;

        return $summary;
    }

    /**
     * @param array<string, mixed> $user
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function getCourseClassGrades(int $courseId, array $user): array
    {
        $course = $this->courseService->requireCourseOwner($courseId, $user);
        $weights = $this->resolveWeightsMap($courseId);
        $participants = $this->courses->getParticipants($courseId);

        $items = [];
        $scores = [];
        foreach ($participants as $p) {
            $summary = $this->buildStudentGradeSummary($course, $p, $weights, false);
            if ($summary['final_score'] !== null) {
                $scores[] = (float) $summary['final_score'];
            }
            $items[] = $summary;
        }

        $classAverage = $scores !== [] ? round(array_sum($scores) / count($scores), 2) : null;
        foreach ($items as &$item) {
            $item['class_average'] = $classAverage;
        }
        unset($item);

        return [
            'items' => $items,
            'meta' => [
                'total' => count($items),
                'class_average' => $classAverage,
                'weights' => $weights,
            ],
        ];
    }

    /**
     * @return array<string, float>
     */
    private function resolveWeightsMap(int $courseId): array
    {
        $stored = $this->grades->getWeightsByCourse($courseId);
        if ($stored === []) {
            return self::DEFAULT_WEIGHTS;
        }

        $resolved = [];
        foreach (self::COMPONENTS as $component) {
            $resolved[$component] = round((float) ($stored[$component] ?? 0.0), 2);
        }
        return $resolved;
    }

    /**
     * @param array<string, mixed> $course
     * @param array<string, mixed> $student
     * @param array<string, float> $weights
     * @return array<string, mixed>
     */
    private function buildStudentGradeSummary(array $course, array $student, array $weights, bool $includeBreakdown): array
    {
        $courseId = (int) $course['id'];
        $studentId = (int) $student['id'];

        $examScores = $this->grades->getComponentScoresForStudent($courseId, $studentId);
        $averages = [
            'tugas' => $this->grades->getAssignmentAverageForStudent($courseId, $studentId),
            'quiz' => $this->grades->getQuizAverageForStudent($courseId, $studentId),
            'presensi' => $this->grades->getAttendanceAverageForStudent($courseId, $studentId),
            'uts' => $examScores['uts'] ?? null,
            'uas' => $examScores['uas'] ?? null,
        ];

        $calc = self::calculateFinalScore($averages, $weights);

        $result = [
            'course_id' => $courseId,
            'course_code' => (string) ($course['code'] ?? ''),
            'course_name' => (string) ($course['name'] ?? ''),
            'student_id' => $studentId,
            'student_name' => (string) ($student['name'] ?? ''),
            'nim_nip' => (string) ($student['nim'] ?? ($student['nim_nip'] ?? '')),
            'components' => $calc['components'],
            'final_score' => $calc['final_score'],
            'raw_weighted_score' => $calc['raw_weighted_score'],
            'completed_weight_percent' => $calc['completed_weight_percent'],
            'letter_grade' => $calc['letter_grade'],
        ];

        if ($includeBreakdown) {
            $assignments = $this->grades->getGradedSubmissionsForStudentInCourse($courseId, $studentId);
            $quizzes = $this->grades->getQuizBreakdownForStudent($courseId, $studentId);

            $result['breakdown'] = [
                'assignments' => array_map(static fn (array $r): array => [
                    'assignment_id' => (int) $r['assignment_id'],
                    'title' => (string) $r['title'],
                    'max_score' => (int) $r['max_score'],
                    'score' => $r['score'] !== null ? round((float) $r['score'], 2) : null,
                    'feedback' => $r['feedback'],
                    'graded_at' => DateTimeHelper::toIso($r['graded_at']),
                ], $assignments),
                'quizzes' => array_map(static fn (array $r): array => [
                    'quiz_id' => (int) $r['quiz_id'],
                    'title' => (string) $r['title'],
                    'best_score' => $r['best_score'] !== null ? round((float) $r['best_score'], 2) : null,
                    'attempts_count' => (int) $r['attempts_count'],
                ], $quizzes),
            ];
        }

        return $result;
    }
}
