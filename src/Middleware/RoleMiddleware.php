<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Request;

final class RoleMiddleware
{
    /** @var array<int, string> */
    private array $allowedRoles;

    /**
     * @param string|array<int, string> $roles
     */
    public function __construct(string|array $roles)
    {
        $this->allowedRoles = is_array($roles) ? $roles : [$roles];
    }

    public function handle(Request $request): void
    {
        $user = $request->user();
        if ($user === null) {
            throw new HttpException(
                'UNAUTHENTICATED',
                'Token autentikasi tidak ditemukan, tidak valid, atau sudah kedaluwarsa.',
                401
            );
        }

        if (!in_array((string) ($user['role'] ?? ''), $this->allowedRoles, true)) {
            throw new HttpException(
                'FORBIDDEN',
                'Anda tidak memiliki izin akses untuk resource ini.',
                403
            );
        }
    }
}
