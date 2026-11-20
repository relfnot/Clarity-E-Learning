<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host = (string) Env::get('DB_HOST', '127.0.0.1');
        $port = Env::int('DB_PORT', 3306);
        $database = (string) Env::get('DB_DATABASE', 'clarity_elearning');
        $username = (string) Env::get('DB_USERNAME', 'root');
        $password = (string) Env::get('DB_PASSWORD', '');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->exec("SET time_zone = '+07:00'");
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

        self::$connection = $pdo;

        return self::$connection;
    }

    public static function getServerConnection(): PDO
    {
        $host = (string) Env::get('DB_HOST', '127.0.0.1');
        $port = Env::int('DB_PORT', 3306);
        $username = (string) Env::get('DB_USERNAME', 'root');
        $password = (string) Env::get('DB_PASSWORD', '');

        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->exec("SET time_zone = '+07:00'");

        return $pdo;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
