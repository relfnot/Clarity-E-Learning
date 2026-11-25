<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DateTimeHelper;
use App\Core\HttpException;
use App\Repositories\AttendanceRepository;
use App\Repositories\CourseRepository;
use DateTimeImmutable;

final class AttendanceService
{
    private AttendanceRepository $attendance;
    private CourseRepository $courses;
    private CourseService $courseService;
    private XpService $xpService;

    public function __construct(
        ?AttendanceRepository $attendance = null,
        ?CourseRepository $courses = null,
        ?CourseService $courseService = null,
        ?XpService $xpService = null
    ) {
        $this->attendance = $attendance ?? new AttendanceRepository();
        $this->courses = $courses ?? new CourseRepository();
        $this->courseService = $courseService ?? new CourseService($this->courses);
        $this->xpService = $xpService ?? new XpService();
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function openSession(int $meetingId, array $user, array $input): array
    {
        $meeting = $this->courses->findMeetingById($meetingId);
        if ($meeting === null) {
            throw new HttpException('NOT_FOUND', 'Pertemuan tidak ditemukan.', 404);
        }

        $courseId = (int) $meeting['course_id'];
        $this->courseService->requireCourseOwner($courseId, $user);

        if ($this->attendance->findOpenSessionByMeeting($meetingId) !== null) {
            throw new HttpException(
                'CONFLICT',
                'Sesi presensi untuk pertemuan ini masih terbuka.',
                409
            );
        }

        $closesAtDb = null;
        if (!empty($input['closes_at'])) {
            $closesAtDb = DateTimeHelper::toDb((string) $input['closes_at'], 'closes_at');
        }

        $passcode = isset($input['passcode']) && trim((string) $input['passcode']) !== ''
            ? trim((string) $input['passcode'])
            : null;

        $sessionId = $this->attendance->createSession([
            'meeting_id' => $meetingId,
            'opened_by' => (int) $user['id'],
            'opened_at' => DateTimeHelper::now()->format('Y-m-d H:i:s'),
            'closes_at' => $closesAtDb,
            'passcode' => $passcode,
        ]);

        $created = $this->attendance->findSessionById($sessionId);
        return $this->formatSession($created ?? []);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function closeSession(int $sessionId, array $user): array
    {
        $session = $this->attendance->findSessionById($sessionId);
        if ($session === null) {
            throw new HttpException('NOT_FOUND', 'Sesi presensi tidak ditemukan.', 404);
        }

        $courseId = (int) $session['course_id'];
        $this->courseService->requireCourseOwner($courseId, $user);

        $this->attendance->closeSession($sessionId);
        $this->attendance->insertMissingAlpaForSession($sessionId, $courseId);

        $updated = $this->attendance->findSessionById($sessionId);
        return $this->formatSession($updated ?? []);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function checkIn(int $sessionId, array $user, array $input): array
    {
        $session = $this->attendance->findSessionById($sessionId);
        if ($session === null) {
            throw new HttpException('NOT_FOUND', 'Sesi presensi tidak ditemukan.', 404);
        }

        $courseId = (int) $session['course_id'];
        $userId = (int) $user['id'];

        if (!$this->courses->isEnrolled($courseId, $userId)) {
            throw new HttpException('FORBIDDEN', 'Anda tidak terdaftar sebagai peserta mata kuliah ini.', 403);
        }

        if (($session['status'] ?? '') !== 'open') {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Sesi presensi sudah ditutup.',
                422,
                ['session' => ['Sesi presensi tidak lagi terbuka.']]
            );
        }

        if (!empty($session['closes_at'])) {
            $now = DateTimeHelper::now();
            $closesAt = new DateTimeImmutable((string) $session['closes_at'], DateTimeHelper::timezone());
            if ($now->getTimestamp() > $closesAt->getTimestamp()) {
                throw new HttpException(
                    'VALIDATION_ERROR',
                    'Batas waktu check-in presensi telah berakhir.',
                    422,
                    ['session' => ['Waktu check-in sudah habis.']]
                );
            }
        }

        $expectedPasscode = trim((string) ($session['passcode'] ?? ''));
        if ($expectedPasscode !== '') {
            $givenPasscode = trim((string) ($input['passcode'] ?? ''));
            if (!hash_equals($expectedPasscode, $givenPasscode)) {
                throw new HttpException(
                    'VALIDATION_ERROR',
                    'Kode presensi tidak sesuai.',
                    422,
                    ['passcode' => ['Kode presensi salah.']]
                );
            }
        }

        if ($this->attendance->findRecordBySessionAndUser($sessionId, $userId) !== null) {
            throw new HttpException(
                'CONFLICT',
                'Anda sudah melakukan check-in pada sesi presensi ini.',
                409
            );
        }

        $nowDb = DateTimeHelper::now()->format('Y-m-d H:i:s');
        $recordId = $this->attendance->createRecord($sessionId, $userId, 'hadir', $nowDb);

        // Hook XP kehadiran (M8)
        $this->xpService->award($userId, $courseId, 'attendance', $recordId, 15);

        $created = $this->attendance->findRecordById($recordId);
        return $this->formatRecord($created ?? []);
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function courseSummary(int $courseId, array $user): array
    {
        $this->courseService->requireCourseAccess($courseId, $user);

        $meetings = $this->courses->listMeetings($courseId);
        $totalMeetings = count($meetings);
        $role = (string) ($user['role'] ?? '');
        $userId = (int) $user['id'];

        $counts = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];

        if ($role === 'dosen') {
            $rows = $this->attendance->listRecordsForCourse($courseId);
        } else {
            $rows = $this->attendance->listRecordsForStudentInCourse($courseId, $userId);
        }

        foreach ($rows as $row) {
            $st = (string) $row['status'];
            if (isset($counts[$st])) {
                $counts[$st]++;
            }
        }

        $totalRecords = array_sum($counts);
        $attendancePercent = $totalRecords > 0
            ? round((($counts['hadir'] + $counts['izin'] + $counts['sakit']) / $totalRecords) * 100, 1)
            : 0.0;

        $openSessions = [];
        if ($role === 'mahasiswa') {
            $openRows = $this->attendance->listOpenSessionsInCourse($courseId, $userId);
            $openSessions = array_map(static function (array $s): array {
                return [
                    'id' => (int) $s['id'],
                    'meeting_id' => (int) $s['meeting_id'],
                    'meeting_number' => (int) $s['meeting_number'],
                    'meeting_title' => (string) $s['meeting_title'],
                    'meeting_date' => (string) $s['meeting_date'],
                    'opened_at' => DateTimeHelper::toIso((string) $s['opened_at']),
                    'closes_at' => DateTimeHelper::toIso($s['closes_at']),
                    'requires_passcode' => (bool) $s['requires_passcode'],
                    'my_status' => $s['my_status'],
                    'status' => (string) $s['status'],
                ];
            }, $openRows);
        }

        return [
            'course_id' => $courseId,
            'total_meetings' => $totalMeetings,
            'attendance_percent' => $attendancePercent,
            'counts' => $counts,
            'open_sessions' => $openSessions,
            'records' => array_map(fn (array $r): array => $this->formatRecord($r), $rows),
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array{session: array<string, mixed>|null, items: array<int, array<string, mixed>>}
     */
    public function meetingAttendanceForLecturer(int $meetingId, array $user): array
    {
        $meeting = $this->courses->findMeetingById($meetingId);
        if ($meeting === null) {
            throw new HttpException('NOT_FOUND', 'Pertemuan tidak ditemukan.', 404);
        }

        $courseId = (int) $meeting['course_id'];
        $this->courseService->requireCourseOwner($courseId, $user);

        $session = $this->attendance->findLatestSessionByMeeting($meetingId);
        if ($session === null) {
            return [
                'session' => null,
                'items' => [],
            ];
        }

        $session['course_id'] = $courseId;
        $sessionId = (int) $session['id'];
        $roster = $this->attendance->listMeetingRosterWithRecords($sessionId, $courseId);

        $items = array_map(static function (array $row) use ($sessionId, $meetingId): array {
            return [
                'id' => $row['record_id'] !== null ? (int) $row['record_id'] : null,
                'session_id' => $sessionId,
                'meeting_id' => $meetingId,
                'user_id' => (int) $row['user_id'],
                'student_name' => (string) $row['student_name'],
                'nim_nip' => (string) $row['nim_nip'],
                'status' => $row['status'] ?? 'belum',
                'checked_at' => DateTimeHelper::toIso($row['checked_at']),
            ];
        }, $roster);

        return [
            'session' => $this->formatSession($session),
            'items' => $items,
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function updateRecordStatus(int $recordId, array $user, string $status): array
    {
        $record = $this->attendance->findRecordById($recordId);
        if ($record === null) {
            throw new HttpException('NOT_FOUND', 'Data kehadiran tidak ditemukan.', 404);
        }

        $this->courseService->requireCourseOwner((int) $record['course_id'], $user);
        $this->attendance->updateRecordStatus($recordId, $status);

        $updated = $this->attendance->findRecordById($recordId);
        return $this->formatRecord($updated ?? []);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatSession(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'course_id' => isset($row['course_id']) ? (int) $row['course_id'] : null,
            'meeting_id' => (int) $row['meeting_id'],
            'opened_at' => DateTimeHelper::toIso((string) $row['opened_at']),
            'closes_at' => DateTimeHelper::toIso($row['closes_at'] ?? null),
            'passcode' => $row['passcode'] ?? null,
            'status' => (string) $row['status'],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatRecord(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'session_id' => (int) $row['session_id'],
            'meeting_id' => (int) ($row['meeting_id'] ?? 0),
            'meeting_number' => isset($row['meeting_number']) ? (int) $row['meeting_number'] : null,
            'meeting_title' => $row['meeting_title'] ?? null,
            'user_id' => (int) $row['user_id'],
            'student_name' => (string) ($row['student_name'] ?? ''),
            'nim_nip' => (string) ($row['nim_nip'] ?? ''),
            'status' => (string) $row['status'],
            'checked_at' => DateTimeHelper::toIso($row['checked_at'] ?? null),
        ];
    }
}
