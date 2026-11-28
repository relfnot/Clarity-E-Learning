<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Services\QuizAttemptService;

final class QuizAttemptController
{
    public function start(Request $request): never
    {
        $quizId = $request->intParam('id');
        $attempt = (new QuizAttemptService())->startAttempt($quizId, $request->user() ?? []);
        Response::json($attempt, 201);
    }

    public function myAttempts(Request $request): never
    {
        $quizId = $request->intParam('id');
        $items = (new QuizAttemptService())->listMyAttempts($quizId, $request->user() ?? []);
        Response::json($items, 200);
    }

    public function index(Request $request): never
    {
        $quizId = $request->intParam('id');
        $statusRaw = $request->query('status');
        $status = is_string($statusRaw) && trim($statusRaw) !== '' ? trim($statusRaw) : null;
        $page = $request->queryInt('page', 1, 1, 1000);
        $perPage = $request->queryInt('per_page', 50, 1, 100);

        $res = (new QuizAttemptService())->listAttemptsForLecturer(
            $quizId,
            $request->user() ?? [],
            $status,
            $page,
            $perPage
        );
        Response::json($res['items'], 200, $res['meta']);
    }

    public function show(Request $request): never
    {
        $attemptId = $request->intParam('id');
        $attempt = (new QuizAttemptService())->getAttempt($attemptId, $request->user() ?? []);
        Response::json($attempt, 200);
    }

    public function saveAnswers(Request $request): never
    {
        $attemptId = $request->intParam('id');
        $body = $request->body();
        if (!isset($body['answers']) || !is_array($body['answers'])) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Field answers wajib berupa array.',
                422,
                ['answers' => ['answers harus berupa array jawaban.']]
            );
        }

        $res = (new QuizAttemptService())->saveAnswers($attemptId, $request->user() ?? [], $body['answers']);
        Response::json($res, 200);
    }

    public function submit(Request $request): never
    {
        $attemptId = $request->intParam('id');
        $body = $request->body();
        $finalAnswers = isset($body['answers']) && is_array($body['answers']) ? $body['answers'] : null;

        $attempt = (new QuizAttemptService())->submitAttempt($attemptId, $request->user() ?? [], $finalAnswers);
        Response::json($attempt, 200);
    }

    public function gradeAnswer(Request $request): never
    {
        $answerId = $request->intParam('id');
        $body = $request->body();
        if (!array_key_exists('score', $body) || !is_numeric($body['score'])) {
            throw new HttpException(
                'VALIDATION_ERROR',
                'Field score wajib diisi dengan angka.',
                422,
                ['score' => ['Masukkan angka nilai esai yang valid.']]
            );
        }

        $attempt = (new QuizAttemptService())->gradeEssayAnswer(
            $answerId,
            $request->user() ?? [],
            (float) $body['score']
        );
        Response::json($attempt, 200);
    }
}
