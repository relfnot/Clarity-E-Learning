<?php

declare(strict_types=1);

namespace App\Services;

final class XpService
{
    /**
     * Hook untuk modul gamifikasi (M8). Saat ini no-op sampai tabel xp_events dibuat.
     */
    public function award(int $userId, int $courseId, string $source, int $refId, int $xp): void
    {
        // Intentionally no-op until M8.
    }
}
