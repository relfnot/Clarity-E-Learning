<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\AssignmentService;

final class AssignmentController
{
    public function index(Request $request): never
    {
        $courseId = $request->intParam('id');
        $items = (new AssignmentService())->listByCourse($courseId, $request->user() ?? []);
        Response::json($items, 200);
    }

    public function store(Request $request): never
    {
        $courseId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'meeting_id' => 'nullable',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'deadline' => 'required|string|max:50',
            'accept_file' => 'nullable',
            'accept_link' => 'nullable',
            'allow_late' => 'nullable',
            'weight' => 'required',
            'max_score' => 'required',
        ]);

        $assignment = (new AssignmentService())->create($courseId, $request->user() ?? [], $data);
        Response::json($assignment, 201);
    }

    public function show(Request $request): never
    {
        $assignmentId = $request->intParam('id');
        $assignment = (new AssignmentService())->getDetail($assignmentId, $request->user() ?? []);
        Response::json($assignment, 200);
    }

    public function update(Request $request): never
    {
        $assignmentId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'meeting_id' => 'nullable',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'deadline' => 'required|string|max:50',
            'accept_file' => 'nullable',
            'accept_link' => 'nullable',
            'allow_late' => 'nullable',
            'weight' => 'required',
            'max_score' => 'required',
        ]);

        $assignment = (new AssignmentService())->update($assignmentId, $request->user() ?? [], $data);
        Response::json($assignment, 200);
    }

    public function destroy(Request $request): never
    {
        $assignmentId = $request->intParam('id');
        (new AssignmentService())->delete($assignmentId, $request->user() ?? []);
        Response::noContent();
    }
}
