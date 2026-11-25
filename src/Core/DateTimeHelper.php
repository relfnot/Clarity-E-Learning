<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;

final class DateTimeHelper
{
    public const TIMEZONE = 'Asia/Jakarta';

    public static function timezone(): DateTimeZone
    {
        return new DateTimeZone(self::TIMEZONE);
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::timezone());
    }

    public static function toIso(?string $dbDateTime): ?string
    {
        if ($dbDateTime === null || trim($dbDateTime) === '') {
            return null;
        }
        try {
            $dt = new DateTimeImmutable($dbDateTime, self::timezone());
            return $dt->setTimezone(self::timezone())->format('Y-m-d\TH:i:sP');
        } catch (Throwable) {
            return $dbDateTime;
        }
    }

    public static function toDb(string $input, string $fieldName = 'datetime'): string
    {
        try {
            $dt = new DateTimeImmutable($input, self::timezone());
            return $dt->setTimezone(self::timezone())->format('Y-m-d H:i:s');
        } catch (Throwable) {
            throw new HttpException(
                'VALIDATION_ERROR',
                "Format tanggal/waktu pada {$fieldName} tidak valid.",
                422,
                [$fieldName => ['Gunakan format ISO 8601 atau YYYY-MM-DD HH:MM:SS.']]
            );
        }
    }

    public static function computeUrgency(string $deadlineDb): string
    {
        $now = self::now();
        $deadline = new DateTimeImmutable($deadlineDb, self::timezone());
        $diffSeconds = $deadline->getTimestamp() - $now->getTimestamp();

        if ($diffSeconds <= 3 * 3600) {
            return 'j3';
        }
        if ($diffSeconds <= 24 * 3600) {
            return 'h1';
        }
        if ($diffSeconds <= 3 * 86400) {
            return 'h3';
        }
        return 'normal';
    }
}
