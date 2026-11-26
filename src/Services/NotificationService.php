<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\DateTimeHelper;
use App\Core\HttpException;
use App\Repositories\CourseRepository;
use App\Repositories\NotificationRepository;
use Throwable;

final class NotificationService
{
    private NotificationRepository $notifications;
    private CourseRepository $courses;
    private CourseService $courseService;

    public function __construct(
        ?NotificationRepository $notifications = null,
        ?CourseRepository $courses = null,
        ?CourseService $courseService = null
    ) {
        $this->notifications = $notifications ?? new NotificationRepository();
        $this->courses = $courses ?? new CourseRepository();
        $this->courseService = $courseService ?? new CourseService($this->courses);
    }

    /**
     * Alias kompatibilitas mundur untuk modul M3 (SubmissionService).
     */
    public function notify(int $userId, string $type, string $title, string $body, ?string $link = null): void
    {
        $this->send($userId, $type, $title, $body, $link);
    }

    /**
     * Pengiriman notifikasi terpusat (PRD M6 FR7).
     * Aman dipanggil lintas modul (M3, M5, M6, M8); tidak menggagalkan transaksi utama jika gagal.
     */
    public function send(int $userId, string $type, string $title, string $body, ?string $link = null): ?int
    {
        if ($userId <= 0 || trim($title) === '' || trim($body) === '') {
            return null;
        }

        try {
            return $this->notifications->create(
                $userId,
                trim($type) !== '' ? trim($type) : 'pengumuman',
                mb_substr(trim($title), 0, 200),
                trim($body),
                $link !== null && trim($link) !== '' ? mb_substr(trim($link), 0, 500) : null
            );
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Kirim notifikasi massal ke daftar user ID.
     *
     * @param array<int, int> $userIds
     */
    public function sendToUsers(array $userIds, string $type, string $title, string $body, ?string $link = null): int
    {
        if ($userIds === [] || trim($title) === '' || trim($body) === '') {
            return 0;
        }

        try {
            return $this->notifications->createBulk(
                $userIds,
                trim($type) !== '' ? trim($type) : 'pengumuman',
                mb_substr(trim($title), 0, 200),
                trim($body),
                $link !== null && trim($link) !== '' ? mb_substr(trim($link), 0, 500) : null
            );
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function listForUser(int $userId, bool $unreadOnly, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $result = $this->notifications->listForUser($userId, $unreadOnly, $page, $perPage);

        $items = array_map(fn (array $row): array => $this->formatRow($row), $result['rows']);
        $total = $result['total'];

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ];
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->countUnread($userId);
    }

    /**
     * @return array<string, mixed>
     */
    public function markAsRead(int $notificationId, int $userId): array
    {
        $existing = $this->notifications->findByIdForUser($notificationId, $userId);
        if ($existing === null) {
            throw new HttpException('NOT_FOUND', 'Notifikasi tidak ditemukan.', 404);
        }

        if ($existing['read_at'] === null) {
            $nowDb = DateTimeHelper::now()->format('Y-m-d H:i:s');
            $this->notifications->markAsRead($notificationId, $userId, $nowDb);
            $existing = $this->notifications->findByIdForUser($notificationId, $userId) ?? $existing;
        }

        return $this->formatRow($existing);
    }

    public function markAllAsRead(int $userId): int
    {
        $nowDb = DateTimeHelper::now()->format('Y-m-d H:i:s');
        return $this->notifications->markAllAsRead($userId, $nowDb);
    }

    /**
     * Pengumuman dosen ke seluruh peserta mata kuliah (PRD M6 FR8).
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function sendCourseAnnouncement(int $courseId, array $user, string $title, string $body): array
    {
        $this->courseService->requireCourseOwner($courseId, $user);

        $participants = $this->courses->getParticipants($courseId);
        $studentIds = array_map(static fn (array $p): int => (int) $p['id'], $participants);
        $link = sprintf('course-detail.php?id=%d', $courseId);

        $sentCount = $this->notifications->createBulk(
            $studentIds,
            'pengumuman',
            mb_substr(trim($title), 0, 200),
            trim($body),
            $link
        );

        return [
            'course_id' => $courseId,
            'recipients_count' => $sentCount,
            'title' => trim($title),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'type' => (string) $row['type'],
            'title' => (string) $row['title'],
            'body' => (string) $row['body'],
            'link' => $row['link'] !== null ? (string) $row['link'] : null,
            'read_at' => DateTimeHelper::toIso($row['read_at'] !== null ? (string) $row['read_at'] : null),
            'created_at' => DateTimeHelper::toIso((string) $row['created_at']),
        ];
    }
}
