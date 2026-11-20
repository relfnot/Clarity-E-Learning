<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use Throwable;

final class HealthController
{
    public function index(Request $request): never
    {
        $dbStatus = 'connected';
        try {
            Database::getConnection()->query('SELECT 1');
        } catch (Throwable) {
            $dbStatus = 'disconnected';
        }

        Response::json([
            'status' => 'ok',
            'app' => (string) Env::get('APP_NAME', 'Clarity E-Learning'),
            'env' => (string) Env::get('APP_ENV', 'local'),
            'timezone' => date_default_timezone_get(),
            'server_time' => date('c'),
            'database' => $dbStatus,
        ], 200);
    }
}
