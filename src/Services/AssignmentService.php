<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DateTimeHelper;
use App\Core\FileUpload;
use App\Core\HttpException;
use App\Repositories\AssignmentRepository;
use App\Repositories\CourseRepository;
use App\Repositories\SubmissionRepository;

final class AssignmentService
{
    private AssignmentRepository $assignments;
    private SubmissionRepository $submissions;
    private CourseRepository $courses;
    private CourseService $courseService;

    public function __construct(
        ?AssignmentRepository $assignments = null,
        ?SubmissionRepository $submissions = null,
        ?CourseRepository $courses = null,
        ?CourseService $courseService = null
    ) {
        $this->assignments = $assignments ?? new AssignmentRepository();
        $this->submissions = $submissions ?? new SubmissionRepository();
        $this->courses = $courses ?? new CourseRepository();
        $this->courseService = $courseService ?? new CourseService($this->courses);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function listByCourse(int $courseId, array $user): array
    {
        $this->courseService->requireCourseAccess($courseId, $user);
        $rows = $this->assignments->listByCourse($courseId);
        $role = (string) ($user['role'] ?? '');
        $userId = (int) $user['id'];

        $result = [];
        foreach ($rows as $row) {
            $mySubmission = null;
            if ($role === 'mahasiswa') {
                $sub = $this->submissions->findByAssignmentAndUser((int) $row['id'], $userId);
                if ($sub !== null) {
                    $mySubmission = SubmissionService::formatSubmissionRow($sub);
                }
            }
            $result[] = $this->formatAssignment($row, $mySubmission);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function getDetail(int $assignmentId, array $user): array
    {
        $assignment = $this->assignments->findById($assignmentId);
        if ($assignment === null) {
            throw new HttpException('NOT_FOUND', 'Tugas tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseAccess((int) $assignment['course_id'], $user);

        $mySubmission = null;
        if (($user['role'] ?? '') === 'mahasiswa') {
            $sub = $this->submissions->findByAssignmentAndUser($assignmentId, (int) $user['id']);
            if ($sub !== null) {
                $mySubmission = SubmissionService::formatSubmissionRow($sub);
            }
        }

        return $this->formatAssignment($assignment, $mySubmission);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function create(int $courseId, array $user, array $input): array
    {
        $this->courseService->requireCourseOwner($courseId, $user);
        $normalized = $this->validateAndNormalizeInput($courseId, $input);

        $id = $this->assignments->create($normalized + ['course_id' => $courseId]);
        $created = $this->assignments->findById($id);
        return $this->formatAssignment($created ?? [], null);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function update(int $assignmentId, array $user, array $input): array
    {
        $assignment = $this->assignments->findById($assignmentId);
        if ($assignment === null) {
            throw new HttpException('NOT_FOUND', 'Tugas tidak ditemukan.', 404);
        }

        $courseId = (int) $assignment['course_id'];
        $this->courseService->requireCourseOwner($courseId, $user);
        $normalized = $this->validateAndNormalizeInput($courseId, $input);

        $this->assignments->update($assignmentId, $normalized);
        $updated = $this->assignments->findById($assignmentId);
        return $this->formatAssignment($updated ?? [], null);
    }

    /**
     * @param array<string, mixed> $user
     */
    public function delete(int $assignmentId, array $user): void
    {
        $assignment = $this->assignments->findById($assignmentId);
        if ($assignment === null) {
            throw new HttpException('NOT_FOUND', 'Tugas tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseOwner((int) $assignment['course_id'], $user);

        foreach ($this->submissions->listFilePathsByAssignment($assignmentId) as $path) {
            FileUpload::delete($path);
        }
        $this->assignments->delete($assignmentId);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function validateAndNormalizeInput(int $courseId, array $input): array
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

        $acceptFile = filter_var($input['accept_file'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $acceptLink = filter_var($input['accept_link'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $allowLate = array_key_exists('allow_late', $input)
            ? filter_var($input['allow_late'], FILTER_VALIDATE_BOOLEAN)
            : true;

        if (!$acceptFile && !$acceptLink) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Minimal satu mode pengumpulan (file atau tautan) harus diaktifkan.',
                422,
                ['accept_file' => ['Aktifkan minimal accept_file atau accept_link.']]
            );
        }

        $weight = (float) ($input['weight'] ?? 0);
        $maxScore = (int) ($input['max_score'] ?? 100);
        if ($weight < 0 || $weight > 100 || $maxScore <= 0) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Bobot harus 0-100 dan nilai maksimal harus lebih dari 0.',
                422
            );
        }

        $deadlineDb = DateTimeHelper::toDb((string) $input['deadline'], 'deadline');

        return [
            'meeting_id' => $meetingId,
            'title' => trim((string) $input['title']),
            'description' => $input['description'] ?? null,
            'deadline' => $deadlineDb,
            'accept_file' => $acceptFile ? 1 : 0,
            'accept_link' => $acceptLink ? 1 : 0,
            'allow_late' => $allowLate ? 1 : 0,
            'weight' => $weight,
            'max_score' => $maxScore,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed>|null $mySubmission
     * @return array<string, mixed>
     */
    private function formatAssignment(array $row, ?array $mySubmission): array
    {
        return [
            'id' => (int) $row['id'],
            'course_id' => (int) $row['course_id'],
            'course_code' => (string) ($row['course_code'] ?? ''),
            'course_name' => (string) ($row['course_name'] ?? ''),
            'meeting_id' => $row['meeting_id'] !== null ? (int) $row['meeting_id'] : null,
            'title' => (string) $row['title'],
            'description' => $row['description'] ?? '',
            'deadline' => DateTimeHelper::toIso((string) $row['deadline']),
            'accept_file' => (bool) $row['accept_file'],
            'accept_link' => (bool) $row['accept_link'],
            'allow_late' => (bool) $row['allow_late'],
            'weight' => (float) $row['weight'],
            'max_score' => (int) $row['max_score'],
            'urgency' => DateTimeHelper::computeUrgency((string) $row['deadline']),
            'my_submission' => $mySubmission,
        ];
    }
}
