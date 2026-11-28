<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\CalendarService;

final class CalendarController
{
    public function index(Request $request): never
    {
        $user = $request->user();
        if ($user === null) {
            throw new HttpException('UNAUTHENTICATED', 'Silakan login terlebih dahulu.', 401);
        }

        $fromRaw = $request->query('from');
        $toRaw = $request->query('to');
        $courseRaw = $request->query('course_id');

        $courseId = null;
        if ($courseRaw !== null && trim((string) $courseRaw) !== '') {
            $trimmed = trim((string) $courseRaw);
            if (preg_match('/^\d+$/', $trimmed) !== 1 || (int) $trimmed <= 0) {
                throw new HttpException(
                    'VALIDATION_ERROR',
                    'Parameter course_id harus berupa bilangan bulat positif.',
                    422,
                    ['course_id' => ['ID mata kuliah tidak valid.']]
                );
            }
            $courseId = (int) $trimmed;
        }

        $result = (new CalendarService())->getEvents(
            $user,
            is_string($fromRaw) ? $fromRaw : null,
            is_string($toRaw) ? $toRaw : null,
            $courseId
        );

        Response::json($result['events'], 200, $result['meta']);
    }
}
