<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DateTimeHelper;
use App\Core\FileUpload;
use App\Core\HttpException;
use App\Repositories\CourseRepository;
use App\Repositories\MaterialRepository;

final class MaterialService
{
    private MaterialRepository $materials;
    private CourseRepository $courses;
    private CourseService $courseService;

    public function __construct(
        ?MaterialRepository $materials = null,
        ?CourseRepository $courses = null,
        ?CourseService $courseService = null
    ) {
        $this->materials = $materials ?? new MaterialRepository();
        $this->courses = $courses ?? new CourseRepository();
        $this->courseService = $courseService ?? new CourseService($this->courses);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function listByCourse(int $courseId, array $user, ?int $meetingId = null): array
    {
        $this->courseService->requireCourseAccess($courseId, $user);
        $rows = $this->materials->listByCourse($courseId, $meetingId);
        return array_map(fn (array $row): array => $this->formatMaterial($row), $rows);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $file
     * @return array<string, mixed>
     */
    public function create(int $courseId, array $user, array $input, ?array $file): array
    {
        $this->courseService->requireCourseOwner($courseId, $user);

        $meetingId = (int) ($input['meeting_id'] ?? 0);
        $meeting = $this->courses->findMeetingById($meetingId);
        if ($meeting === null || (int) $meeting['course_id'] !== $courseId) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Pertemuan tidak valid untuk mata kuliah ini.',
                422,
                ['meeting_id' => ['Pertemuan tidak ditemukan pada mata kuliah ini.']]
            );
        }

        $type = (string) ($input['type'] ?? '');
        $title = trim((string) ($input['title'] ?? ''));

        $filePath = null;
        $fileName = null;
        $fileSizeBytes = null;
        $mimeType = null;
        $url = null;

        if ($type === 'file') {
            if ($file === null) {
                throw new HttpException(
                    'VALIDATION_ERROR',
                    'Berkas materi wajib diunggah untuk tipe file.',
                    422,
                    ['file' => ['Pilih berkas materi untuk diunggah.']]
                );
            }
            $stored = FileUpload::store($file, 'materials');
            $filePath = $stored['file_path'];
            $fileName = $stored['file_name'];
            $fileSizeBytes = $stored['file_size_bytes'];
            $mimeType = $stored['mime_type'];
        } elseif ($type === 'link') {
            $rawUrl = trim((string) ($input['url'] ?? ''));
            if ($rawUrl === '' || !$this->isValidHttpUrl($rawUrl)) {
                throw new HttpException(
                    'VALIDATION_ERROR',
                    'Tautan materi harus berupa URL http/https yang valid.',
                    422,
                    ['url' => ['Masukkan URL valid yang diawali http:// atau https://.']]
                );
            }
            $url = $rawUrl;
        } else {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Tipe materi harus berupa file atau link.',
                422,
                ['type' => ['Nilai type harus file atau link.']]
            );
        }

        $id = $this->materials->create([
            'course_id' => $courseId,
            'meeting_id' => $meetingId,
            'title' => $title,
            'type' => $type,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size_bytes' => $fileSizeBytes,
            'mime_type' => $mimeType,
            'url' => $url,
            'uploaded_by' => (int) $user['id'],
        ]);

        $created = $this->materials->findById($id);
        return $this->formatMaterial($created ?? []);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function update(int $materialId, array $user, array $input): array
    {
        $material = $this->materials->findById($materialId);
        if ($material === null) {
            throw new HttpException('NOT_FOUND', 'Materi tidak ditemukan.', 404);
        }

        $courseId = (int) $material['course_id'];
        $this->courseService->requireCourseOwner($courseId, $user);

        $meetingId = isset($input['meeting_id']) ? (int) $input['meeting_id'] : (int) $material['meeting_id'];
        $meeting = $this->courses->findMeetingById($meetingId);
        if ($meeting === null || (int) $meeting['course_id'] !== $courseId) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Pertemuan tidak valid untuk mata kuliah ini.',
                422,
                ['meeting_id' => ['Pertemuan tidak ditemukan pada mata kuliah ini.']]
            );
        }

        $url = $material['url'];
        if (($material['type'] ?? '') === 'link' && array_key_exists('url', $input)) {
            $rawUrl = trim((string) ($input['url'] ?? ''));
            if ($rawUrl === '' || !$this->isValidHttpUrl($rawUrl)) {
                throw new HttpException(
                    'VALIDATION_ERROR',
                    'Tautan materi harus berupa URL http/https yang valid.',
                    422,
                    ['url' => ['Masukkan URL valid yang diawali http:// atau https://.']]
                );
            }
            $url = $rawUrl;
        }

        $this->materials->update($materialId, [
            'meeting_id' => $meetingId,
            'title' => trim((string) $input['title']),
            'url' => $url,
        ]);

        $updated = $this->materials->findById($materialId);
        return $this->formatMaterial($updated ?? []);
    }

    /**
     * @param array<string, mixed> $user
     */
    public function delete(int $materialId, array $user): void
    {
        $material = $this->materials->findById($materialId);
        if ($material === null) {
            throw new HttpException('NOT_FOUND', 'Materi tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseOwner((int) $material['course_id'], $user);

        if (!empty($material['file_path'])) {
            FileUpload::delete((string) $material['file_path']);
        }
        $this->materials->delete($materialId);
    }

    /**
     * @param array<string, mixed> $user
     * @return array{absolute_path: string, download_name: string, mime_type: string}
     */
    public function resolveDownload(int $materialId, array $user): array
    {
        $material = $this->materials->findById($materialId);
        if ($material === null) {
            throw new HttpException('NOT_FOUND', 'Materi tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseAccess((int) $material['course_id'], $user);

        if (($material['type'] ?? '') !== 'file' || empty($material['file_path'])) {
            throw new HttpException('NOT_FOUND', 'Materi ini bertipe tautan dan tidak memiliki berkas unduhan.', 404);
        }

        $absPath = FileUpload::absolutePath((string) $material['file_path']);
        if (!is_file($absPath)) {
            throw new HttpException('NOT_FOUND', 'Berkas materi tidak ditemukan di penyimpanan.', 404);
        }

        return [
            'absolute_path' => $absPath,
            'download_name' => (string) ($material['file_name'] ?: basename($absPath)),
            'mime_type' => (string) ($material['mime_type'] ?: 'application/octet-stream'),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatMaterial(array $row): array
    {
        $id = (int) $row['id'];
        $type = (string) $row['type'];

        return [
            'id' => $id,
            'course_id' => (int) $row['course_id'],
            'meeting_id' => (int) $row['meeting_id'],
            'meeting_number' => isset($row['meeting_number']) ? (int) $row['meeting_number'] : null,
            'meeting_title' => $row['meeting_title'] ?? null,
            'title' => (string) $row['title'],
            'type' => $type,
            'file_name' => $row['file_name'],
            'file_size' => FileUpload::formatBytes(
                isset($row['file_size_bytes']) ? (int) $row['file_size_bytes'] : null
            ),
            'url' => $row['url'],
            'download_url' => $type === 'file' ? "/api/v1/materials/{$id}/download" : null,
            'completed' => false,
            'created_at' => DateTimeHelper::toIso((string) $row['created_at']),
        ];
    }

    private function isValidHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }
}
