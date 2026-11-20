<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\AuthService;

final class AuthController
{
    public function login(Request $request): never
    {
        $data = Validator::validate($request->body(), [
            'identifier' => 'required|string|max:150',
            'password' => 'required|string|max:255',
        ]);

        $result = (new AuthService())->login(
            (string) $data['identifier'],
            (string) $data['password'],
            $request->clientIp()
        );

        Response::json($result, 200);
    }

    public function me(Request $request): never
    {
        Response::json($request->user(), 200);
    }

    public function logout(Request $request): never
    {
        Response::json([
            'message' => 'Berhasil keluar dari sesi.',
        ], 200);
    }
}
