<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\MaterialService;

final class MaterialController
{
    public function index(Request $request): never
    {
        $courseId = $request->intParam('id');
        $meetingRaw = $request->query('meeting_id');
        $meetingId = is_string($meetingRaw) && ctype_digit($meetingRaw) ? (int) $meetingRaw : null;

        $items = (new MaterialService())->listByCourse($courseId, $request->user() ?? [], $meetingId);
        Response::json($items, 200);
    }

    public function store(Request $request): never
    {
        $courseId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'meeting_id' => 'required',
            'title' => 'required|string|max:200',
            'type' => 'required|in:file,link',
            'url' => 'nullable|string|max:1000',
        ]);

        $material = (new MaterialService())->create(
            $courseId,
            $request->user() ?? [],
            $data,
            $request->file('file')
        );
        Response::json($material, 201);
    }

    public function update(Request $request): never
    {
        $materialId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'meeting_id' => 'nullable',
            'title' => 'required|string|max:200',
            'url' => 'nullable|string|max:1000',
        ]);

        $material = (new MaterialService())->update($materialId, $request->user() ?? [], $data);
        Response::json($material, 200);
    }

    public function destroy(Request $request): never
    {
        $materialId = $request->intParam('id');
        (new MaterialService())->delete($materialId, $request->user() ?? []);
        Response::noContent();
    }

    public function download(Request $request): never
    {
        $materialId = $request->intParam('id');
        $fileInfo = (new MaterialService())->resolveDownload($materialId, $request->user() ?? []);
        Response::download(
            $fileInfo['absolute_path'],
            $fileInfo['download_name'],
            $fileInfo['mime_type']
        );
    }
}
