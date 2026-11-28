<?php

declare(strict_types=1);

use App\Controllers\AssignmentController;
use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\CalendarController;
use App\Controllers\CourseController;
use App\Controllers\GradeController;
use App\Controllers\HealthController;
use App\Controllers\MaterialController;
use App\Controllers\NotificationController;
use App\Controllers\QuizAttemptController;
use App\Controllers\QuizController;
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

// 6b. Kalender & Deadline (Timeline No. 18)
$router->get('/calendar', [CalendarController::class, 'index'], [$auth]);

// 7. Quiz & Bank Soal (Timeline No. 13 · PIC: Gaby)
$router->get('/courses/{id}/quizzes', [QuizController::class, 'index'], [$auth]);
$router->post('/courses/{id}/quizzes', [QuizController::class, 'store'], [$auth, $dosenOnly]);
$router->get('/quizzes/{id}', [QuizController::class, 'show'], [$auth]);
$router->put('/quizzes/{id}', [QuizController::class, 'update'], [$auth, $dosenOnly]);
$router->delete('/quizzes/{id}', [QuizController::class, 'destroy'], [$auth, $dosenOnly]);
$router->get('/quizzes/{id}/questions', [QuizController::class, 'listQuestions'], [$auth, $dosenOnly]);
$router->post('/quizzes/{id}/questions', [QuizController::class, 'storeQuestion'], [$auth, $dosenOnly]);
$router->put('/questions/{id}', [QuizController::class, 'updateQuestion'], [$auth, $dosenOnly]);
$router->delete('/questions/{id}', [QuizController::class, 'destroyQuestion'], [$auth, $dosenOnly]);

// 7b. Percobaan Quiz & Penilaian Esai (Timeline No. 20)
$router->post('/quizzes/{id}/attempts', [QuizAttemptController::class, 'start'], [$auth, $mahasiswaOnly]);
$router->get('/quizzes/{id}/attempts/me', [QuizAttemptController::class, 'myAttempts'], [$auth, $mahasiswaOnly]);
$router->get('/quizzes/{id}/attempts', [QuizAttemptController::class, 'index'], [$auth, $dosenOnly]);
$router->get('/attempts/{id}', [QuizAttemptController::class, 'show'], [$auth]);
$router->put('/attempts/{id}/answers', [QuizAttemptController::class, 'saveAnswers'], [$auth, $mahasiswaOnly]);
$router->post('/attempts/{id}/submit', [QuizAttemptController::class, 'submit'], [$auth, $mahasiswaOnly]);
$router->put('/answers/{id}/grade', [QuizAttemptController::class, 'gradeAnswer'], [$auth, $dosenOnly]);

// 8. Modul Nilai & Bobot Komponen (Timeline No. 14 · PIC: Gaby)
$router->get('/courses/{id}/grade-weights', [GradeController::class, 'getWeights'], [$auth]);
$router->put('/courses/{id}/grade-weights', [GradeController::class, 'updateWeights'], [$auth, $dosenOnly]);
$router->put('/submissions/{id}/grade', [GradeController::class, 'gradeSubmission'], [$auth, $dosenOnly]);
$router->put('/courses/{id}/grades/students/{user_id}/components', [GradeController::class, 'upsertComponentScore'], [$auth, $dosenOnly]);
$router->get('/courses/{id}/grades/me', [GradeController::class, 'myGrades'], [$auth, $mahasiswaOnly]);
$router->get('/courses/{id}/grades', [GradeController::class, 'classGrades'], [$auth, $dosenOnly]);

// 9. Notifikasi & Pengumuman (Timeline No. 15 · PIC: Gaby)
$router->get('/notifications', [NotificationController::class, 'index'], [$auth]);
$router->get('/notifications/unread-count', [NotificationController::class, 'unreadCount'], [$auth]);
$router->patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'], [$auth]);
$router->post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'], [$auth]);
$router->post('/courses/{id}/announcements', [NotificationController::class, 'sendAnnouncement'], [$auth, $dosenOnly]);

$request = Request::capture();
$router->dispatch($request);
