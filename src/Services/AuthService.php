<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\HttpException;
use App\Core\Jwt;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;

final class AuthService
{
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    private UserRepository $users;
    private LoginAttemptRepository $attempts;

    public function __construct(
        ?UserRepository $users = null,
        ?LoginAttemptRepository $attempts = null
    ) {
        $this->users = $users ?? new UserRepository();
        $this->attempts = $attempts ?? new LoginAttemptRepository();
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $identifier, string $password, string $ipAddress): array
    {
        $normalizedIdentifier = strtolower(trim($identifier));
        $user = $this->users->findByIdentifier($normalizedIdentifier);
        $attemptKey = $user !== null
            ? 'uid:' . (int) $user['id']
            : 'id:' . mb_substr($normalizedIdentifier, 0, 140);

        $maxAttempts = Env::int('LOGIN_RATE_LIMIT_MAX', 5);
        $windowSeconds = Env::int('LOGIN_RATE_LIMIT_WINDOW', 900);

        $status = $this->attempts->countRecent($attemptKey, $ipAddress, $windowSeconds);
        if ($status['count'] >= $maxAttempts) {
            $retryAfter = $status['retry_after_seconds'];
            throw new HttpException(
                'RATE_LIMITED',
                'Terlalu banyak percobaan login gagal (maksimal 5 kali per 15 menit). Silakan coba lagi nanti.',
                429,
                ['retry_after_seconds' => $retryAfter],
                ['Retry-After' => (string) $retryAfter]
            );
        }

        $hash = $user !== null ? (string) $user['password_hash'] : self::DUMMY_HASH;
        $isValidPassword = password_verify($password, $hash);

        if ($user === null || !$isValidPassword) {
            $this->attempts->recordFailure($attemptKey, $ipAddress);
            throw new HttpException(
                'UNAUTHENTICATED',
                'Kredensial salah',
                401
            );
        }

        $this->attempts->clear($attemptKey, $ipAddress);

        $issued = Jwt::issue((int) $user['id'], (string) $user['role']);

        return [
            'token' => $issued['token'],
            'token_type' => $issued['token_type'],
            'expires_in' => $issued['expires_in'],
            'expires_at' => $issued['expires_at'],
            'user' => UserRepository::formatUser($user),
        ];
    }
}
