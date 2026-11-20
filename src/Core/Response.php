<?php

declare(strict_types=1);

namespace App\Core;

use stdClass;

final class Response
{
    /**
     * @param array<string, mixed> $meta
     * @param array<string, string> $headers
     */
    public static function json(
        mixed $data,
        int $status = 200,
        array $meta = [],
        array $headers = []
    ): never {
        self::sendHeaders($status, $headers);

        $payload = [
            'data' => $data,
            'meta' => $meta === [] ? new stdClass() : $meta,
        ];

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * @param array<string, mixed> $details
     * @param array<string, string> $headers
     */
    public static function error(
        string $code,
        string $message,
        int $status = 400,
        array $details = [],
        array $headers = []
    ): never {
        self::sendHeaders($status, $headers);

        $payload = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details === [] ? new stdClass() : $details,
            ],
        ];

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * @param array<string, string> $headers
     */
    public static function noContent(array $headers = []): never
    {
        self::sendHeaders(204, $headers);
        exit;
    }

    /**
     * @param array<string, string> $headers
     */
    private static function sendHeaders(int $status, array $headers): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');

            foreach ($headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
    }
}
