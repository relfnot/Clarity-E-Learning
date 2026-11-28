<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DateTimeHelper;
use App\Core\HttpException;
use App\Repositories\CalendarRepository;
use DateTimeImmutable;

final class CalendarService
{
    private CalendarRepository $calendar;

    public function __construct(?CalendarRepository $calendar = null)
    {
        $this->calendar = $calendar ?? new CalendarRepository();
    }

    /**
     * @param array<string, mixed> $user
     * @return array{events: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function getEvents(
        array $user,
        ?string $fromRaw,
        ?string $toRaw,
        ?int $courseId
    ): array {
        $tz = DateTimeHelper::timezone();
        $now = DateTimeHelper::now();

        $fromDate = $fromRaw !== null && trim($fromRaw) !== ''
            ? $this->parseDateStrict(trim($fromRaw), 'from')
            : $now->modify('first day of this month');
        $toDate = $toRaw !== null && trim($toRaw) !== ''
            ? $this->parseDateStrict(trim($toRaw), 'to')
            : $now->modify('last day of this month');

        if ($fromDate->getTimestamp() > $toDate->getTimestamp()) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Tanggal awal (from) tidak boleh lebih besar dari tanggal akhir (to).',
                422,
                ['from' => ['Rentang tanggal tidak valid.']]
            );
        }

        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? 'mahasiswa');
        $accessibleCourseIds = $this->calendar->getAccessibleCourseIds($userId, $role);

        if ($courseId !== null) {
            if (!$this->calendar->courseExists($courseId)) {
                throw new HttpException('NOT_FOUND', 'Mata kuliah tidak ditemukan.', 404);
            }
            if (!in_array($courseId, $accessibleCourseIds, true)) {
                throw new HttpException('FORBIDDEN', 'Anda tidak memiliki akses ke kalender mata kuliah ini.', 403);
            }
            $targetCourseIds = [$courseId];
        } else {
            $targetCourseIds = $accessibleCourseIds;
        }

        $fromStr = $fromDate->format('Y-m-d');
        $toStr = $toDate->format('Y-m-d');
        $fromDateTime = $fromStr . ' 00:00:00';
        $toDateTime = $toStr . ' 23:59:59';

        $events = [];

        foreach ($this->calendar->findMeetingsInRange($targetCourseIds, $fromStr, $toStr) as $row) {
            $meetingDate = (string) $row['meeting_date'];
            $startTime = !empty($row['start_time']) ? (string) $row['start_time'] : '08:00:00';
            $endTime = !empty($row['end_time']) ? (string) $row['end_time'] : '10:00:00';
            $startDt = new DateTimeImmutable($meetingDate . ' ' . $startTime, $tz);
            $endDt = new DateTimeImmutable($meetingDate . ' ' . $endTime, $tz);

            $events[] = [
                'id' => 'lecture-' . (int) $row['id'] . '-' . $meetingDate,
                'entity_id' => (int) $row['id'],
                'type' => 'lecture',
                'course_id' => (int) $row['course_id'],
                'course_code' => (string) $row['course_code'],
                'course_name' => (string) $row['course_name'],
                'title' => sprintf('Pertemuan %d: %s', (int) $row['number'], (string) $row['title']),
                'start_at' => $startDt->format('Y-m-d\TH:i:sP'),
                'end_at' => $endDt->format('Y-m-d\TH:i:sP'),
                'location' => !empty($row['room']) ? (string) $row['room'] : null,
            ];
        }

        foreach ($this->calendar->findAssignmentsInRange($targetCourseIds, $fromDateTime, $toDateTime) as $row) {
            $deadlineIso = DateTimeHelper::toIso((string) $row['deadline']);
            $events[] = [
                'id' => 'assignment-' . (int) $row['id'],
                'entity_id' => (int) $row['id'],
                'type' => 'assignment_deadline',
                'course_id' => (int) $row['course_id'],
                'course_code' => (string) $row['course_code'],
                'course_name' => (string) $row['course_name'],
                'title' => (string) $row['title'],
                'start_at' => $deadlineIso,
                'end_at' => $deadlineIso,
                'location' => null,
            ];
        }

        foreach ($this->calendar->findQuizzesInRange($targetCourseIds, $fromDateTime, $toDateTime) as $row) {
            $events[] = [
                'id' => 'quiz-' . (int) $row['id'],
                'entity_id' => (int) $row['id'],
                'type' => 'quiz_window',
                'course_id' => (int) $row['course_id'],
                'course_code' => (string) $row['course_code'],
                'course_name' => (string) $row['course_name'],
                'title' => (string) $row['title'],
                'start_at' => DateTimeHelper::toIso((string) $row['open_at']),
                'end_at' => DateTimeHelper::toIso((string) $row['close_at']),
                'location' => 'Online',
            ];
        }

        usort($events, static function (array $a, array $b): int {
            return strcmp((string) $a['start_at'], (string) $b['start_at']);
        });

        return [
            'events' => $events,
            'meta' => [
                'from' => $fromStr,
                'to' => $toStr,
                'timezone' => DateTimeHelper::TIMEZONE,
                'total' => count($events),
            ],
        ];
    }

    private function parseDateStrict(string $value, string $field): DateTimeImmutable
    {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value, DateTimeHelper::timezone());
        if ($dt === false || $dt->format('Y-m-d') !== $value) {
            throw new HttpException(
                'VALIDATION_ERROR',
                "Parameter {$field} harus berformat YYYY-MM-DD.",
                422,
                [$field => ['Gunakan format tanggal YYYY-MM-DD.']]
            );
        }

        return $dt;
    }
}
