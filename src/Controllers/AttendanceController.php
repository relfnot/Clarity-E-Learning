<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\AttendanceService;

final class AttendanceController
{
    public function openSession(Request $request): never
    {
        $meetingId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'closes_at' => 'nullable|string|max:50',
            'passcode' => 'nullable|string|max:64',
        ]);

        $session = (new AttendanceService())->openSession($meetingId, $request->user() ?? [], $data);
        Response::json($session, 201);
    }

    public function closeSession(Request $request): never
    {
        $sessionId = $request->intParam('id');
        $session = (new AttendanceService())->closeSession($sessionId, $request->user() ?? []);
        Response::json($session, 200);
    }

    public function checkIn(Request $request): never
    {
        $sessionId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'passcode' => 'nullable|string|max:64',
        ]);

        $record = (new AttendanceService())->checkIn($sessionId, $request->user() ?? [], $data);
        Response::json($record, 201);
    }

    public function courseSummary(Request $request): never
    {
        $courseId = $request->intParam('id');
        $summary = (new AttendanceService())->courseSummary($courseId, $request->user() ?? []);
        Response::json($summary, 200);
    }

    public function meetingAttendance(Request $request): never
    {
        $meetingId = $request->intParam('id');
        $result = (new AttendanceService())->meetingAttendanceForLecturer($meetingId, $request->user() ?? []);
        Response::json($result['items'], 200, [
            'session' => $result['session'],
        ]);
    }

    public function updateRecord(Request $request): never
    {
        $recordId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'status' => 'required|in:hadir,izin,sakit,alpa',
        ]);

        $record = (new AttendanceService())->updateRecordStatus(
            $recordId,
            $request->user() ?? [],
            (string) $data['status']
        );
        Response::json($record, 200);
    }
}
