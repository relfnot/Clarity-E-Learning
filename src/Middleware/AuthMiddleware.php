<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Jwt;
use App\Core\Request;
use App\Repositories\UserRepository;

final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        $token = $request->bearerToken();
        if ($token === null) {
            throw new HttpException(
                'UNAUTHENTICATED',
                'Token autentikasi tidak ditemukan, tidak valid, atau sudah kedaluwarsa.',
                401
            );
        }

        $claims = Jwt::verify($token);
        $userId = (int) $claims['sub'];

        $user = (new UserRepository())->findById($userId);
        if ($user === null) {
            throw new HttpException(
                'UNAUTHENTICATED',
                'Token autentikasi tidak ditemukan, tidak valid, atau sudah kedaluwarsa.',
                401
            );
        }

        $request->setUser($user);
    }
}
