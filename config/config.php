<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'app' => [
        'name' => Env::get('APP_NAME', 'Clarity E-Learning'),
        'env' => Env::get('APP_ENV', 'local'),
        'debug' => Env::bool('APP_DEBUG', false),
        'url' => Env::get('APP_URL', 'http://localhost/Clarity_E-Learning/public'),
        'timezone' => Env::get('APP_TIMEZONE', 'Asia/Jakarta'),
    ],
    'db' => [
        'host' => Env::get('DB_HOST', '127.0.0.1'),
        'port' => Env::int('DB_PORT', 3306),
        'database' => Env::get('DB_DATABASE', 'clarity_elearning'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    'jwt' => [
        'secret' => Env::get('JWT_SECRET', ''),
        'ttl' => Env::int('JWT_TTL', 28800),
    ],
    'rate_limit' => [
        'login_max' => Env::int('LOGIN_RATE_LIMIT_MAX', 5),
        'login_window' => Env::int('LOGIN_RATE_LIMIT_WINDOW', 900),
    ],
];
