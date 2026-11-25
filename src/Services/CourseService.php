<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DateTimeHelper;
use App\Core\HttpException;
use App\Repositories\CourseRepository;
use App\Repositories\UserRepository;

final class CourseService
{
    private CourseRepository $courses;
    private UserRepository $users;

    public function __construct(?CourseRepository $courses = null, ?UserRepository $users = null)
    {
        $this->courses = $courses ?? new CourseRepository();
        $this->users = $users ?? new UserRepository();
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createCourse(array $user, array $input): array
    {
        if ($this->courses->findByCode((string) $input['code']) !== null) {
            throw new HttpException(
                'CONFLICT',
                'Kode mata kuliah sudah digunakan.',
                409,
                ['code' => ['Kode mata kuliah sudah terdaftar.']]
            );
        }

        $courseId = $this->courses->create([
            'code' => (string) $input['code'],
            'name' => (string) $input['name'],
            'description' => $input['description'] ?? null,
            'semester' => (string) $input['semester'],
            'credits' => (int) $input['credits'],
            'lecturer_id' => (int) $user['id'],
            'day' => (string) $input['day'],
            'start_time' => (string) $input['start_time'],
            'end_time' => (string) $input['end_time'],
            'room' => (string) $input['room'],
        ]);

        return $this->courses->findById($courseId) ?? [];
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function updateCourse(int $courseId, array $user, array $input): array
    {
        $course = $this->requireCourseOwner($courseId, $user);

        $existingByCode = $this->courses->findByCode((string) $input['code']);
        if ($existingByCode !== null && (int) $existingByCode['id'] !== (int) $course['id']) {
            throw new HttpException(
                'CONFLICT',
                'Kode mata kuliah sudah digunakan oleh mata kuliah lain.',
                409,
                ['code' => ['Kode mata kuliah sudah terdaftar.']]
            );
        }

        $this->courses->update($courseId, [
            'code' => (string) $input['code'],
            'name' => (string) $input['name'],
            'description' => $input['description'] ?? null,
            'semester' => (string) $input['semester'],
            'credits' => (int) $input['credits'],
            'day' => (string) $input['day'],
            'start_time' => (string) $input['start_time'],
            'end_time' => (string) $input['end_time'],
            'room' => (string) $input['room'],
        ]);

        return $this->courses->findById($courseId) ?? [];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function enrollByNim(int $courseId, array $user, string $nimNip): array
    {
        $this->requireCourseOwner($courseId, $user);

        $student = $this->users->findByIdentifier($nimNip);
        if ($student === null || ($student['role'] ?? '') !== 'mahasiswa') {
            throw new HttpException(
                'NOT_FOUND',
                'Mahasiswa dengan NIM tersebut tidak ditemukan.',
                404
            );
        }

        $studentId = (int) $student['id'];
        if ($this->courses->isEnrolled($courseId, $studentId)) {
            throw new HttpException(
                'CONFLICT',
                'Mahasiswa sudah terdaftar pada mata kuliah ini.',
                409
            );
        }

        $this->courses->enrollStudent($courseId, $studentId);

        return [
            'id' => $studentId,
            'nim' => (string) $student['nim_nip'],
            'nim_nip' => (string) $student['nim_nip'],
            'name' => (string) $student['name'],
            'email' => (string) $student['email'],
            'role' => (string) $student['role'],
            'program_studi' => $student['program_studi'] ?? null,
            'semester' => isset($student['semester']) ? (int) $student['semester'] : null,
            'department' => $student['department'] ?? null,
            'initials' => $student['initials'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $user
     */
    public function unenrollStudent(int $courseId, int $studentUserId, array $user): void
    {
        $this->requireCourseOwner($courseId, $user);

        if (!$this->courses->removeEnrollment($courseId, $studentUserId)) {
            throw new HttpException('NOT_FOUND', 'Peserta tidak terdaftar pada mata kuliah ini.', 404);
        }
    }

    /**
     * @param array<string, mixed> $user
     * @return array<int, array<string, mixed>>
     */
    public function listMeetings(int $courseId, array $user): array
    {
        $this->requireCourseAccess($courseId, $user);
        $rows = $this->courses->listMeetings($courseId);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'course_id' => (int) $row['course_id'],
                'number' => (int) $row['number'],
                'title' => (string) $row['title'],
                'description' => $row['description'],
                'meeting_date' => (string) $row['meeting_date'],
                'attendance_session' => $row['latest_session_id'] !== null ? [
                    'id' => (int) $row['latest_session_id'],
                    'status' => (string) $row['latest_session_status'],
                    'closes_at' => DateTimeHelper::toIso($row['latest_session_closes_at']),
                ] : null,
            ];
        }, $rows);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function createMeeting(int $courseId, array $user, array $input): array
    {
        $this->requireCourseOwner($courseId, $user);

        $number = (int) $input['number'];
        if ($number <= 0) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Nomor pertemuan harus lebih besar dari 0.',
                422,
                ['number' => ['Nomor pertemuan harus positif.']]
            );
        }

        if ($this->courses->findMeetingByNumber($courseId, $number) !== null) {
            throw new HttpException(
                'CONFLICT',
                "Pertemuan ke-{$number} sudah ada pada mata kuliah ini.",
                409
            );
        }

        $meetingId = $this->courses->createMeeting($courseId, [
            'number' => $number,
            'title' => (string) $input['title'],
            'description' => $input['description'] ?? null,
            'meeting_date' => (string) $input['meeting_date'],
        ]);

        $created = $this->courses->findMeetingById($meetingId);
        return [
            'id' => (int) ($created['id'] ?? $meetingId),
            'course_id' => $courseId,
            'number' => (int) ($created['number'] ?? $number),
            'title' => (string) ($created['title'] ?? $input['title']),
            'description' => $created['description'] ?? null,
            'meeting_date' => (string) ($created['meeting_date'] ?? $input['meeting_date']),
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function requireCourseAccess(int $courseId, array $user): array
    {
        $course = $this->courses->findById($courseId);
        if ($course === null) {
            throw new HttpException('NOT_FOUND', 'Mata kuliah tidak ditemukan.', 404);
        }

        $userId = (int) $user['id'];
        $role = (string) ($user['role'] ?? '');

        if ($role === 'dosen') {
            if ((int) $course['lecturer_id'] !== $userId) {
                throw new HttpException('FORBIDDEN', 'Anda bukan dosen pengampu mata kuliah ini.', 403);
            }
            return $course;
        }

        if (!$this->courses->isEnrolled($courseId, $userId)) {
            throw new HttpException('FORBIDDEN', 'Anda tidak terdaftar sebagai peserta mata kuliah ini.', 403);
        }

        return $course;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function requireCourseOwner(int $courseId, array $user): array
    {
        $course = $this->courses->findById($courseId);
        if ($course === null) {
            throw new HttpException('NOT_FOUND', 'Mata kuliah tidak ditemukan.', 404);
        }

        if (($user['role'] ?? '') !== 'dosen' || (int) $course['lecturer_id'] !== (int) $user['id']) {
            throw new HttpException('FORBIDDEN', 'Hanya dosen pengampu yang dapat mengelola mata kuliah ini.', 403);
        }

        return $course;
    }
}
