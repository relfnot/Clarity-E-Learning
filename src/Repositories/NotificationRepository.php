<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class NotificationRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function create(int $userId, string $type, string $title, string $body, ?string $link = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, type, title, body, link)
             VALUES (:user_id, :type, :title, :body, :link)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<int, int> $userIds
     */
    public function createBulk(array $userIds, string $type, string $title, string $body, ?string $link = null): int
    {
        $uniqueIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));
        if ($uniqueIds === []) {
            return 0;
        }

        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO notifications (user_id, type, title, body, link)
                 VALUES (:user_id, :type, :title, :body, :link)'
            );
            foreach ($uniqueIds as $uid) {
                $stmt->execute([
                    'user_id' => $uid,
                    'type' => $type,
                    'title' => $title,
                    'body' => $body,
                    'link' => $link,
                ]);
            }

            if (!$inTransaction) {
                $this->pdo->commit();
            }
            return count($uniqueIds);
        } catch (\Throwable $e) {
            if (!$inTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function listForUser(int $userId, bool $unreadOnly, int $page, int $perPage): array
    {
        $where = 'user_id = :user_id';
        if ($unreadOnly) {
            $where .= ' AND read_at IS NULL';
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE {$where}");
        $countStmt->execute(['user_id' => $userId]);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            "SELECT id, user_id, type, title, body, link, read_at, created_at
             FROM notifications
             WHERE {$where}
             ORDER BY created_at DESC, id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll() ?: [];

        return [
            'rows' => $rows,
            'total' => $total,
        ];
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL'
        );
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByIdForUser(int $notificationId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, user_id, type, title, body, link, read_at, created_at
             FROM notifications
             WHERE id = :id AND user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $notificationId, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function markAsRead(int $notificationId, int $userId, string $readAtDb): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notifications
             SET read_at = COALESCE(read_at, :read_at)
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId,
            'read_at' => $readAtDb,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function markAllAsRead(int $userId, string $readAtDb): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notifications
             SET read_at = :read_at
             WHERE user_id = :user_id AND read_at IS NULL'
        );
        $stmt->execute([
            'user_id' => $userId,
            'read_at' => $readAtDb,
        ]);
        return $stmt->rowCount();
    }
}
