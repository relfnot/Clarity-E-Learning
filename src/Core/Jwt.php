<?php

declare(strict_types=1);

namespace App\Core;

use Firebase\JWT\JWT as FirebaseJwt;
use Firebase\JWT\Key;
use RuntimeException;
use Throwable;

final class Jwt
{
    /**
     * @return array{token: string, token_type: string, expires_in: int, expires_at: string}
     */
    public static function issue(int $userId, string $role): array
    {
        $secret = self::secret();
        $ttl = Env::int('JWT_TTL', 28800);
        $issuedAt = time();
        $expiresAt = $issuedAt + $ttl;

        $payload = [
            'iss' => (string) Env::get('APP_URL', 'clarity-elearning'),
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => $userId,
            'role' => $role,
        ];

        $token = FirebaseJwt::encode($payload, $secret, 'HS256');
        $isoExpiresAt = (new \DateTimeImmutable('@' . $expiresAt))
            ->setTimezone(new \DateTimeZone('Asia/Jakarta'))
            ->format('c');

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $ttl,
            'expires_at' => $isoExpiresAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function verify(string $token): array
    {
        if (trim($token) === '') {
            throw new HttpException(
                'UNAUTHENTICATED',
                'Token autentikasi tidak ditemukan, tidak valid, atau sudah kedaluwarsa.',
                401
            );
        }

        try {
            $decoded = FirebaseJwt::decode($token, new Key(self::secret(), 'HS256'));
            $claims = (array) $decoded;
        } catch (Throwable) {
            throw new HttpException(
                'UNAUTHENTICATED',
                'Token autentikasi tidak ditemukan, tidak valid, atau sudah kedaluwarsa.',
                401
            );
        }

        if (!isset($claims['sub'], $claims['role'])) {
            throw new HttpException(
                'UNAUTHENTICATED',
                'Token autentikasi tidak valid.',
                401
            );
        }

        return $claims;
    }

    private static function secret(): string
    {
        $secret = (string) Env::get('JWT_SECRET', '');
        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET belum dikonfigurasi atau kurang dari 32 karakter.');
        }

        return $secret;
    }
}
