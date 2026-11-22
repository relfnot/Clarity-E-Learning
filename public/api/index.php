<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CourseController;
use App\Controllers\HealthController;
use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

$rootPath = dirname(__DIR__, 2);

require_once $rootPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

Env::load($rootPath);
ErrorHandler::register();

$router = new Router();

// 1. Health Check
$router->get('/health', [HealthController::class, 'index']);

// 2. Autentikasi JWT (Timeline No. 7 · PIC: Rizky)
$router->post('/auth/login', [AuthController::class, 'login']);
$router->get('/auth/me', [AuthController::class, 'me'], [new AuthMiddleware()]);
$router->post('/auth/logout', [AuthController::class, 'logout'], [new AuthMiddleware()]);
$router->get('/auth/dosen-only', static function (Request $request): never {
    Response::json([
        'message' => 'Akses khusus dosen terverifikasi.',
        'user' => $request->user(),
    ], 200);
}, [new AuthMiddleware(), new RoleMiddleware('dosen')]);

// 3. Modul Mata Kuliah & Peserta (Timeline No. 9 · PIC: Rizky)
$router->get('/courses', [CourseController::class, 'index'], [new AuthMiddleware()]);
$router->get('/courses/semesters', [CourseController::class, 'semesters'], [new AuthMiddleware()]);
$router->get('/courses/{id}', [CourseController::class, 'show'], [new AuthMiddleware()]);
$router->get('/courses/{id}/participants', [CourseController::class, 'participants'], [new AuthMiddleware()]);

$request = Request::capture();
$router->dispatch($request);
