<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Services\QuizService;

final class QuizController
{
    public function index(Request $request): never
    {
        $courseId = $request->intParam('id');
        $items = (new QuizService())->listByCourse($courseId, $request->user() ?? []);
        Response::json($items, 200);
    }

    public function store(Request $request): never
    {
        $courseId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'meeting_id' => 'nullable',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'open_at' => 'required|string|max:50',
            'close_at' => 'required|string|max:50',
            'duration_min' => 'required',
            'max_attempts' => 'required',
            'shuffle' => 'nullable',
            'show_answers' => 'nullable',
            'weight' => 'nullable',
        ]);

        $quiz = (new QuizService())->create($courseId, $request->user() ?? [], $data);
        Response::json($quiz, 201);
    }

    public function show(Request $request): never
    {
        $quizId = $request->intParam('id');
        $quiz = (new QuizService())->getDetail($quizId, $request->user() ?? []);
        Response::json($quiz, 200);
    }

    public function update(Request $request): never
    {
        $quizId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'meeting_id' => 'nullable',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'open_at' => 'required|string|max:50',
            'close_at' => 'required|string|max:50',
            'duration_min' => 'required',
            'max_attempts' => 'required',
            'shuffle' => 'nullable',
            'show_answers' => 'nullable',
            'weight' => 'nullable',
        ]);

        $quiz = (new QuizService())->update($quizId, $request->user() ?? [], $data);
        Response::json($quiz, 200);
    }

    public function destroy(Request $request): never
    {
        $quizId = $request->intParam('id');
        (new QuizService())->delete($quizId, $request->user() ?? []);
        Response::noContent();
    }

    public function listQuestions(Request $request): never
    {
        $quizId = $request->intParam('id');
        $items = (new QuizService())->listQuestions($quizId, $request->user() ?? []);
        Response::json($items, 200);
    }

    public function storeQuestion(Request $request): never
    {
        $quizId = $request->intParam('id');
        $body = $request->body();
        $data = Validator::validate($body, [
            'type' => 'required|string|in:pg,esai',
            'text' => 'required|string|max:5000',
            'points' => 'required',
            'position' => 'required',
        ]);
        $data['options'] = $body['options'] ?? [];

        $question = (new QuizService())->addQuestion($quizId, $request->user() ?? [], $data);
        Response::json($question, 201);
    }

    public function updateQuestion(Request $request): never
    {
        $questionId = $request->intParam('id');
        $body = $request->body();
        $data = Validator::validate($body, [
            'type' => 'required|string|in:pg,esai',
            'text' => 'required|string|max:5000',
            'points' => 'required',
            'position' => 'required',
        ]);
        $data['options'] = $body['options'] ?? [];
        $force = filter_var($body['force'] ?? $request->query('force', false), FILTER_VALIDATE_BOOLEAN);

        $question = (new QuizService())->updateQuestion($questionId, $request->user() ?? [], $data, $force);
        Response::json($question, 200);
    }

    public function destroyQuestion(Request $request): never
    {
        $questionId = $request->intParam('id');
        $force = filter_var($request->query('force', false), FILTER_VALIDATE_BOOLEAN);
        (new QuizService())->deleteQuestion($questionId, $request->user() ?? [], $force);
        Response::noContent();
    }
}
