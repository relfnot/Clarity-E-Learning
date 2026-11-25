<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\SubmissionService;

final class SubmissionController
{
    public function submit(Request $request): never
    {
        $assignmentId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'link_url' => 'nullable|string|max:1000',
        ]);

        $submission = (new SubmissionService())->submit(
            $assignmentId,
            $request->user() ?? [],
            $data,
            $request->file('file')
        );
        Response::json($submission, 201);
    }

    public function me(Request $request): never
    {
        $assignmentId = $request->intParam('id');
        $submission = (new SubmissionService())->getMySubmission($assignmentId, $request->user() ?? []);
        Response::json($submission, 200);
    }

    public function index(Request $request): never
    {
        $assignmentId = $request->intParam('id');
        $statusRaw = $request->query('status');
        $status = is_string($statusRaw) && trim($statusRaw) !== '' ? trim($statusRaw) : null;
        $format = $request->query('format');

        $service = new SubmissionService();
        if ($format === 'csv') {
            $export = $service->exportCsvForLecturer($assignmentId, $request->user() ?? [], $status);
            Response::csv($export['csv'], $export['filename']);
        }

        $page = $request->queryInt('page', 1, 1, 1000);
        $perPage = $request->queryInt('per_page', 50, 1, 100);

        $result = $service->listForLecturer(
            $assignmentId,
            $request->user() ?? [],
            $status,
            $page,
            $perPage
        );
        Response::json($result['items'], 200, $result['meta']);
    }

    public function download(Request $request): never
    {
        $submissionId = $request->intParam('id');
        $fileInfo = (new SubmissionService())->resolveDownload($submissionId, $request->user() ?? []);
        Response::download(
            $fileInfo['absolute_path'],
            $fileInfo['download_name'],
            $fileInfo['mime_type']
        );
    }
}
