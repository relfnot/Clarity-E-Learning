<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class UserRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByIdentifier(string $identifier): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nim_nip, name, email, password_hash, role, program_studi, semester, department, initials, avatar_path, created_at
             FROM users
             WHERE LOWER(nim_nip) = LOWER(:nim_nip) OR LOWER(email) = LOWER(:email)
             LIMIT 1'
        );
        $stmt->execute([
            'nim_nip' => trim($identifier),
            'email' => trim($identifier),
        ]);

        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nim_nip, name, email, role, program_studi, semester, department, initials, avatar_path, created_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();
        return is_array($row) ? self::formatUser($row) : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function formatUser(array $row): array
    {
        $initials = (string) ($row['initials'] ?? '');
        if ($initials === '' && isset($row['name'])) {
            $initials = self::deriveInitials((string) $row['name']);
        }

        return [
            'id' => (int) $row['id'],
            'nim_nip' => (string) $row['nim_nip'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'role' => (string) $row['role'],
            'program_studi' => $row['program_studi'] !== null ? (string) $row['program_studi'] : null,
            'semester' => $row['semester'] !== null ? (int) $row['semester'] : null,
            'department' => $row['department'] !== null ? (string) $row['department'] : null,
            'initials' => $initials,
            'avatar_url' => $row['avatar_path'] !== null ? (string) $row['avatar_path'] : null,
            'created_at' => self::formatIsoDate((string) ($row['created_at'] ?? 'now')),
        ];
    }

    private static function deriveInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $letters .= mb_strtoupper(mb_substr($part, 0, 1));
            }
        }

        return $letters !== '' ? $letters : 'CL';
    }

    private static function formatIsoDate(string $datetime): string
    {
        try {
            $tz = new DateTimeZone('Asia/Jakarta');
            return (new DateTimeImmutable($datetime, $tz))->setTimezone($tz)->format('c');
        } catch (\Throwable) {
            return date('c');
        }
    }
}
