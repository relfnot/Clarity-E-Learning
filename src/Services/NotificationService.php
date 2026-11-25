<?php

declare(strict_types=1);

namespace App\Services;

final class NotificationService
{
    /**
     * Hook untuk modul notifikasi (M6). Saat ini no-op sampai tabel notifications dibuat.
     */
    public function notify(int $userId, string $type, string $title, string $body, ?string $link = null): void
    {
        // Intentionally no-op until M6.
    }
}
