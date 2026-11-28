<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DateTimeHelper;
use App\Core\FileUpload;
use App\Core\HttpException;
use App\Repositories\AssignmentRepository;
use App\Repositories\CourseRepository;
use App\Repositories\SubmissionRepository;
use DateTimeImmutable;

final class SubmissionService
{
    private SubmissionRepository $submissions;
    private AssignmentRepository $assignments;
    private CourseRepository $courses;
    private CourseService $courseService;
    private XpService $xpService;
    private NotificationService $notificationService;

    public function __construct(
        ?SubmissionRepository $submissions = null,
        ?AssignmentRepository $assignments = null,
        ?CourseRepository $courses = null,
        ?CourseService $courseService = null,
        ?XpService $xpService = null,
        ?NotificationService $notificationService = null
    ) {
        $this->submissions = $submissions ?? new SubmissionRepository();
        $this->assignments = $assignments ?? new AssignmentRepository();
        $this->courses = $courses ?? new CourseRepository();
        $this->courseService = $courseService ?? new CourseService($this->courses);
        $this->xpService = $xpService ?? new XpService();
        $this->notificationService = $notificationService ?? new NotificationService();
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $file
     * @return array<string, mixed>
     */
    public function submit(int $assignmentId, array $user, array $input, ?array $file): array
    {
        $assignment = $this->assignments->findById($assignmentId);
        if ($assignment === null) {
            throw new HttpException('NOT_FOUND', 'Tugas tidak ditemukan.', 404);
        }

        $courseId = (int) $assignment['course_id'];
        $userId = (int) $user['id'];
        if (!$this->courses->isEnrolled($courseId, $userId)) {
            throw new HttpException('FORBIDDEN', 'Anda tidak terdaftar pada mata kuliah ini.', 403);
        }

        $now = DateTimeHelper::now();
        $deadline = new DateTimeImmutable((string) $assignment['deadline'], DateTimeHelper::timezone());
        $isLate = $now->getTimestamp() > $deadline->getTimestamp();

        if ($isLate && !(bool) $assignment['allow_late']) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Batas waktu pengumpulan sudah lewat dan pengumpulan terlambat tidak diizinkan.',
                422
            );
        }

        $acceptFile = (bool) $assignment['accept_file'];
        $acceptLink = (bool) $assignment['accept_link'];

        $hasFile = $file !== null && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $rawLink = trim((string) ($input['link_url'] ?? ''));
        $hasLink = $rawLink !== '';

        if (!$hasFile && !$hasLink) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Kirimkan berkas atau tautan sesuai mode pengumpulan tugas.',
                422,
                ['submission' => ['Pilih berkas atau masukkan tautan pengumpulan.']]
            );
        }

        if ($hasFile && !$acceptFile) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Tugas ini hanya menerima pengumpulan berupa tautan.',
                422,
                ['file' => ['Mode pengumpulan berkas tidak diaktifkan pada tugas ini.']]
            );
        }

        if ($hasLink && !$acceptLink) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Tugas ini hanya menerima pengumpulan berupa berkas.',
                422,
                ['link_url' => ['Mode pengumpulan tautan tidak diaktifkan pada tugas ini.']]
            );
        }

        if ($hasLink && !$this->isValidHttpUrl($rawLink)) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Tautan pengumpulan harus berupa URL http:// atau https:// yang valid.',
                422,
                ['link_url' => ['Format URL tidak valid.']]
            );
        }

        $existing = $this->submissions->findByAssignmentAndUser($assignmentId, $userId);
        $oldFilePath = $existing['file_path'] ?? null;

        $filePath = null;
        $fileName = null;
        $fileSizeBytes = null;
        $mimeType = null;
        $linkUrl = $hasLink ? $rawLink : null;

        if ($hasFile) {
            $stored = FileUpload::store($file, 'submissions');
            $filePath = $stored['file_path'];
            $fileName = $stored['file_name'];
            $fileSizeBytes = $stored['file_size_bytes'];
            $mimeType = $stored['mime_type'];
        }

        $submissionId = $this->submissions->upsert([
            'assignment_id' => $assignmentId,
            'user_id' => $userId,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size_bytes' => $fileSizeBytes,
            'mime_type' => $mimeType,
            'link_url' => $linkUrl,
            'submitted_at' => $now->format('Y-m-d H:i:s'),
            'is_late' => $isLate ? 1 : 0,
        ]);

        if (!empty($oldFilePath) && $oldFilePath !== $filePath) {
            FileUpload::delete((string) $oldFilePath);
        }

        // Hook untuk M8 & M6 (no-op sampai modul terkait aktif)
        $this->xpService->award($userId, $courseId, 'assignment_submit', $submissionId, $isLate ? 10 : 30);
        $this->notificationService->notify(
            (int) $assignment['lecturer_id'],
            'submission',
            'Pengumpulan Tugas Baru',
            sprintf('%s mengumpulkan %s', (string) ($user['name'] ?? 'Mahasiswa'), (string) $assignment['title']),
            'submissions.php'
        );

        $saved = $this->submissions->findByAssignmentAndUser($assignmentId, $userId);
        return self::formatSubmissionRow($saved ?? []);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>|null
     */
    public function getMySubmission(int $assignmentId, array $user): ?array
    {
        $assignment = $this->assignments->findById($assignmentId);
        if ($assignment === null) {
            throw new HttpException('NOT_FOUND', 'Tugas tidak ditemukan.', 404);
        }

        $courseId = (int) $assignment['course_id'];
        $userId = (int) $user['id'];
        if (!$this->courses->isEnrolled($courseId, $userId)) {
            throw new HttpException('FORBIDDEN', 'Anda tidak terdaftar pada mata kuliah ini.', 403);
        }

        $row = $this->submissions->findByAssignmentAndUser($assignmentId, $userId);
        return $row !== null ? self::formatSubmissionRow($row) : null;
    }

    /**
     * @param array<string, mixed> $user
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function listForLecturer(
        int $assignmentId,
        array $user,
        ?string $statusFilter,
        int $page,
        int $perPage,
        ?string $search = null
    ): array {
        $assignment = $this->assignments->findById($assignmentId);
        if ($assignment === null) {
            throw new HttpException('NOT_FOUND', 'Tugas tidak ditemukan.', 404);
        }

        $courseId = (int) $assignment['course_id'];
        $this->courseService->requireCourseOwner($courseId, $user);

        $roster = $this->submissions->listAllForAssignmentWithRoster($assignmentId, $courseId);
        $summary = [
            'total' => count($roster),
            'tepat_waktu' => 0,
            'terlambat' => 0,
            'belum' => 0,
            'dinilai' => 0,
        ];

        $searchLower = $search !== null && trim($search) !== '' ? mb_strtolower(trim($search), 'UTF-8') : null;
        $formattedAll = [];
        foreach ($roster as $row) {
            $item = $this->formatRosterSubmissionRow($row, $assignmentId);
            $st = $item['status'];
            if (isset($summary[$st])) {
                $summary[$st]++;
            }
            if ($statusFilter !== null && $statusFilter !== '' && $st !== $statusFilter) {
                continue;
            }
            if ($searchLower !== null) {
                $nameLower = mb_strtolower((string) ($item['student_name'] ?? ''), 'UTF-8');
                $nimLower = mb_strtolower((string) ($item['nim_nip'] ?? ''), 'UTF-8');
                if (!str_contains($nameLower, $searchLower) && !str_contains($nimLower, $searchLower)) {
                    continue;
                }
            }
            $formattedAll[] = $item;
        }

        $totalFiltered = count($formattedAll);
        $offset = ($page - 1) * $perPage;
        $paged = array_slice($formattedAll, $offset, $perPage);

        return [
            'items' => $paged,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalFiltered,
                'total_pages' => $totalFiltered > 0 ? (int) ceil($totalFiltered / $perPage) : 1,
                'summary' => $summary,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array{csv: string, filename: string}
     */
    public function exportCsvForLecturer(
        int $assignmentId,
        array $user,
        ?string $statusFilter,
        ?string $search = null
    ): array {
        $result = $this->listForLecturer($assignmentId, $user, $statusFilter, 1, 500, $search);
        $lines = ['NIM,Nama Mahasiswa,Status,Waktu Pengumpulan,Tautan,Berkas,Nilai'];

        foreach ($result['items'] as $row) {
            $cells = [
                $this->csvCell((string) ($row['nim_nip'] ?? '')),
                $this->csvCell((string) ($row['student_name'] ?? '')),
                $this->csvCell((string) ($row['status'] ?? '')),
                $this->csvCell((string) ($row['submitted_at'] ?? '-')),
                $this->csvCell((string) ($row['link_url'] ?? '-')),
                $this->csvCell((string) ($row['file_name'] ?? '-')),
                $this->csvCell($row['score'] !== null ? (string) $row['score'] : '-'),
            ];
            $lines[] = implode(',', $cells);
        }

        return [
            'csv' => implode("\r\n", $lines) . "\r\n",
            'filename' => "pengumpulan-tugas-{$assignmentId}.csv",
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array{absolute_path: string, download_name: string, mime_type: string}
     */
    public function resolveDownload(int $submissionId, array $user): array
    {
        $submission = $this->submissions->findById($submissionId);
        if ($submission === null) {
            throw new HttpException('NOT_FOUND', 'Pengumpulan tugas tidak ditemukan.', 404);
        }

        $userId = (int) $user['id'];
        $role = (string) ($user['role'] ?? '');
        $isOwnerStudent = $role === 'mahasiswa' && (int) $submission['user_id'] === $userId;
        $isCourseLecturer = $role === 'dosen' && (int) $submission['lecturer_id'] === $userId;

        if (!$isOwnerStudent && !$isCourseLecturer) {
            throw new HttpException('FORBIDDEN', 'Anda tidak memiliki akses untuk mengunduh berkas ini.', 403);
        }

        if (empty($submission['file_path'])) {
            throw new HttpException('NOT_FOUND', 'Pengumpulan ini tidak menyertakan berkas.', 404);
        }

        $absPath = FileUpload::absolutePath((string) $submission['file_path']);
        if (!is_file($absPath)) {
            throw new HttpException('NOT_FOUND', 'Berkas pengumpulan tidak ditemukan di penyimpanan.', 404);
        }

        return [
            'absolute_path' => $absPath,
            'download_name' => (string) ($submission['file_name'] ?: basename($absPath)),
            'mime_type' => (string) ($submission['mime_type'] ?: 'application/octet-stream'),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function formatSubmissionRow(array $row): array
    {
        $id = (int) $row['id'];
        $status = self::deriveStatus($row);
        $hasFile = !empty($row['file_path']);

        return [
            'id' => $id,
            'assignment_id' => (int) $row['assignment_id'],
            'user_id' => (int) $row['user_id'],
            'student_name' => (string) ($row['student_name'] ?? ''),
            'nim_nip' => (string) ($row['nim_nip'] ?? ''),
            'file_name' => $row['file_name'] ?? null,
            'file_size' => FileUpload::formatBytes(
                isset($row['file_size_bytes']) ? (int) $row['file_size_bytes'] : null
            ),
            'download_url' => $hasFile ? "/api/v1/submissions/{$id}/download" : null,
            'link_url' => $row['link_url'] ?? null,
            'submitted_at' => DateTimeHelper::toIso($row['submitted_at'] ?? null),
            'is_late' => (bool) ($row['is_late'] ?? false),
            'status' => $status,
            'score' => isset($row['score']) && $row['score'] !== null ? (float) $row['score'] : null,
            'feedback' => $row['feedback'] ?? null,
            'graded_at' => DateTimeHelper::toIso($row['graded_at'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatRosterSubmissionRow(array $row, int $assignmentId): array
    {
        if (empty($row['id'])) {
            return [
                'id' => null,
                'assignment_id' => $assignmentId,
                'user_id' => (int) $row['roster_user_id'],
                'student_name' => (string) $row['student_name'],
                'nim_nip' => (string) $row['nim_nip'],
                'file_name' => null,
                'file_size' => null,
                'download_url' => null,
                'link_url' => null,
                'submitted_at' => null,
                'is_late' => false,
                'status' => 'belum',
                'score' => null,
                'feedback' => null,
                'graded_at' => null,
            ];
        }

        return self::formatSubmissionRow($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function deriveStatus(array $row): string
    {
        if (isset($row['score']) && $row['score'] !== null) {
            return 'dinilai';
        }
        return !empty($row['is_late']) ? 'terlambat' : 'tepat_waktu';
    }

    private function isValidHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    private function csvCell(string $value): string
    {
        $escaped = str_replace('"', '""', $value);
        return '"' . $escaped . '"';
    }
}
