<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler(static function (
            int $severity,
            string $message,
            string $file,
            int $line
        ): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e): void {
            self::handleException($e);
        });
    }

    public static function handleException(Throwable $e): never
    {
        if ($e instanceof HttpException) {
            Response::error(
                $e->getErrorCode(),
                $e->getMessage(),
                $e->getStatusCode(),
                $e->getDetails(),
                $e->getHeaders()
            );
        }

        self::logThrowable($e);

        $details = [];
        if (Env::bool('APP_DEBUG', false)) {
            $details = [
                'exception' => $e::class,
                'detail' => $e->getMessage(),
            ];
        }

        Response::error(
            'SERVER_ERROR',
            'Terjadi kesalahan internal pada server.',
            500,
            $details
        );
    }

    private static function logThrowable(Throwable $e): void
    {
        $logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $line = sprintf(
            "[%s] %s: %s in %s:%d\n",
            date('c'),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'app.log', $line, FILE_APPEND);
    }
}
