<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class LoginAttemptRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * @return array{count: int, retry_after_seconds: int}
     */
    public function countRecent(string $identifierKey, string $ipAddress, int $windowSeconds): array
    {
        $cutoff = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))
            ->modify('-' . $windowSeconds . ' seconds')
            ->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total, MIN(UNIX_TIMESTAMP(attempted_at)) AS oldest_ts
             FROM login_attempts
             WHERE identifier_key = :identifier_key
               AND ip_address = :ip_address
               AND attempted_at >= :cutoff'
        );
        $stmt->execute([
            'identifier_key' => $identifierKey,
            'ip_address' => $ipAddress,
            'cutoff' => $cutoff,
        ]);

        $row = $stmt->fetch();
        $count = (int) ($row['total'] ?? 0);
        $oldestTs = isset($row['oldest_ts']) && $row['oldest_ts'] !== null ? (int) $row['oldest_ts'] : null;

        $retryAfter = $windowSeconds;
        if ($oldestTs !== null) {
            $retryAfter = max(1, ($oldestTs + $windowSeconds) - time());
        }

        return [
            'count' => $count,
            'retry_after_seconds' => $retryAfter,
        ];
    }

    public function recordFailure(string $identifierKey, string $ipAddress): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_attempts (identifier_key, ip_address, attempted_at)
             VALUES (:identifier_key, :ip_address, :attempted_at)'
        );
        $stmt->execute([
            'identifier_key' => $identifierKey,
            'ip_address' => $ipAddress,
            'attempted_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s'),
        ]);
    }

    public function clear(string $identifierKey, string $ipAddress): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM login_attempts
             WHERE identifier_key = :identifier_key AND ip_address = :ip_address'
        );
        $stmt->execute([
            'identifier_key' => $identifierKey,
            'ip_address' => $ipAddress,
        ]);
    }
}
