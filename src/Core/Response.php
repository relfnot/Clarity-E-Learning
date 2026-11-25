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

    public static function download(string $absolutePath, string $downloadName, string $mimeType = 'application/octet-stream'): never
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            self::error('NOT_FOUND', 'Berkas tidak ditemukan di penyimpanan.', 404);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._\- ]+/', '_', $downloadName) ?: 'download';
        $safeName = str_replace('"', '', $safeName);
        $size = filesize($absolutePath);

        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $safeName . '"');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Expose-Headers: Content-Disposition');
            if ($size !== false) {
                header('Content-Length: ' . (string) $size);
            }
        }

        readfile($absolutePath);
        exit;
    }

    public static function csv(string $csvContent, string $downloadName): never
    {
        $safeName = preg_replace('/[^a-zA-Z0-9._\-]+/', '_', $downloadName) ?: 'export.csv';
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $safeName . '"');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-store');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Expose-Headers: Content-Disposition');
        }

        echo "\xEF\xBB\xBF" . $csvContent;
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
