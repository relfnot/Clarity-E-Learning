<?php

declare(strict_types=1);

use App\Controllers\AssignmentController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\CourseController;
use App\Controllers\HealthController;
use App\Controllers\MaterialController;
use App\Controllers\SubmissionController;
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
$auth = new AuthMiddleware();
$dosenOnly = new RoleMiddleware('dosen');
$mahasiswaOnly = new RoleMiddleware('mahasiswa');

// 1. Health Check
$router->get('/health', [HealthController::class, 'index']);

// 2. Autentikasi JWT (Timeline No. 7 · PIC: Rizky)
$router->post('/auth/login', [AuthController::class, 'login']);
$router->get('/auth/me', [AuthController::class, 'me'], [$auth]);
$router->post('/auth/logout', [AuthController::class, 'logout'], [$auth]);
$router->get('/auth/dosen-only', static function (Request $request): never {
    Response::json([
        'message' => 'Akses khusus dosen terverifikasi.',
        'user' => $request->user(),
    ], 200);
}, [$auth, $dosenOnly]);

// 3. Modul Mata Kuliah, Peserta & Pertemuan (Timeline No. 9 & 26)
$router->get('/courses', [CourseController::class, 'index'], [$auth]);
$router->get('/courses/semesters', [CourseController::class, 'semesters'], [$auth]);
$router->post('/courses', [CourseController::class, 'store'], [$auth, $dosenOnly]);
$router->get('/courses/{id}', [CourseController::class, 'show'], [$auth]);
$router->put('/courses/{id}', [CourseController::class, 'update'], [$auth, $dosenOnly]);
$router->get('/courses/{id}/participants', [CourseController::class, 'participants'], [$auth]);
$router->post('/courses/{id}/enrollments', [CourseController::class, 'enroll'], [$auth, $dosenOnly]);
$router->delete('/courses/{id}/enrollments/{user_id}', [CourseController::class, 'unenroll'], [$auth, $dosenOnly]);
$router->get('/courses/{id}/meetings', [CourseController::class, 'meetings'], [$auth]);
$router->post('/courses/{id}/meetings', [CourseController::class, 'storeMeeting'], [$auth, $dosenOnly]);

// 4. Materi (Timeline No. 10)
$router->get('/courses/{id}/materials', [MaterialController::class, 'index'], [$auth]);
$router->post('/courses/{id}/materials', [MaterialController::class, 'store'], [$auth, $dosenOnly]);
$router->put('/materials/{id}', [MaterialController::class, 'update'], [$auth, $dosenOnly]);
$router->delete('/materials/{id}', [MaterialController::class, 'destroy'], [$auth, $dosenOnly]);
$router->get('/materials/{id}/download', [MaterialController::class, 'download'], [$auth]);

// 5. Tugas & Pengumpulan (Timeline No. 11 & 19)
$router->get('/courses/{id}/assignments', [AssignmentController::class, 'index'], [$auth]);
$router->post('/courses/{id}/assignments', [AssignmentController::class, 'store'], [$auth, $dosenOnly]);
$router->get('/assignments/{id}', [AssignmentController::class, 'show'], [$auth]);
$router->put('/assignments/{id}', [AssignmentController::class, 'update'], [$auth, $dosenOnly]);
$router->delete('/assignments/{id}', [AssignmentController::class, 'destroy'], [$auth, $dosenOnly]);

$router->post('/assignments/{id}/submissions', [SubmissionController::class, 'submit'], [$auth, $mahasiswaOnly]);
$router->get('/assignments/{id}/submissions/me', [SubmissionController::class, 'me'], [$auth, $mahasiswaOnly]);
$router->get('/assignments/{id}/submissions', [SubmissionController::class, 'index'], [$auth, $dosenOnly]);
$router->get('/submissions/{id}/download', [SubmissionController::class, 'download'], [$auth]);

// 6. Presensi (Timeline No. 12 & 17)
$router->post('/meetings/{id}/attendance-session', [AttendanceController::class, 'openSession'], [$auth, $dosenOnly]);
$router->patch('/attendance-sessions/{id}/close', [AttendanceController::class, 'closeSession'], [$auth, $dosenOnly]);
$router->post('/attendance-sessions/{id}/check-in', [AttendanceController::class, 'checkIn'], [$auth, $mahasiswaOnly]);
$router->get('/courses/{id}/attendance/summary', [AttendanceController::class, 'courseSummary'], [$auth]);
$router->get('/meetings/{id}/attendance', [AttendanceController::class, 'meetingAttendance'], [$auth, $dosenOnly]);
$router->put('/attendance-records/{id}', [AttendanceController::class, 'updateRecord'], [$auth, $dosenOnly]);

$request = Request::capture();
$router->dispatch($request);
