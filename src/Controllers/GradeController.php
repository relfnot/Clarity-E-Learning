<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\GradeService;

final class GradeController
{
    public function getWeights(Request $request): never
    {
        $courseId = $request->intParam('id');
        $weights = (new GradeService())->getWeights($courseId, $request->user() ?? []);
        Response::json($weights, 200);
    }

    public function updateWeights(Request $request): never
    {
        $courseId = $request->intParam('id');
        $weights = (new GradeService())->updateWeights($courseId, $request->user() ?? [], $request->body());
        Response::json($weights, 200);
    }

    public function gradeSubmission(Request $request): never
    {
        $submissionId = $request->intParam('id');
        $submission = (new GradeService())->gradeSubmission($submissionId, $request->user() ?? [], $request->body());
        Response::json($submission, 200);
    }

    public function upsertComponentScore(Request $request): never
    {
        $courseId = $request->intParam('id');
        $studentId = $request->intParam('user_id');
        $saved = (new GradeService())->upsertExamComponentScore(
            $courseId,
            $studentId,
            $request->user() ?? [],
            $request->body()
        );
        Response::json($saved, 200);
    }

    public function myGrades(Request $request): never
    {
        $courseId = $request->intParam('id');
        $summary = (new GradeService())->getStudentSummary($courseId, $request->user() ?? []);
        Response::json($summary, 200);
    }

    public function classGrades(Request $request): never
    {
        $courseId = $request->intParam('id');
        $result = (new GradeService())->getCourseClassGrades($courseId, $request->user() ?? []);
        Response::json($result['items'], 200, $result['meta']);
    }
}
