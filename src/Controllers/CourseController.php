<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\CourseRepository;
use App\Services\CourseService;

final class CourseController
{
    private CourseRepository $courseRepository;

    public function __construct(?CourseRepository $courseRepository = null)
    {
        $this->courseRepository = $courseRepository ?? new CourseRepository();
    }

    /**
     * GET /courses
     * Daftar mata kuliah untuk pengguna yang sedang login (Mahasiswa / Dosen).
     * Mendukung server-side filter: ?semester=...&search=...&page=...&per_page=...
     */
    public function index(Request $request): never
    {
        $user = $request->user();
        if ($user === null) {
            throw new HttpException('UNAUTHENTICATED', 'Silakan login terlebih dahulu.', 401);
        }

        $semester = $request->query('semester');
        $search = $request->query('search');
        $page = (int) ($request->query('page') ?? 1);
        $perPage = (int) ($request->query('per_page') ?? 20);

        $result = $this->courseRepository->listForUser(
            (int) $user['id'],
            (string) $user['role'],
            is_string($semester) ? $semester : null,
            is_string($search) ? $search : null,
            $page,
            $perPage
        );

        Response::json($result['data'], 200, $result['meta']);
    }

    /**
     * GET /courses/semesters
     * Daftar semester yang tersedia untuk dropdown filter.
     */
    public function semesters(Request $request): never
    {
        $semesters = $this->courseRepository->getAvailableSemesters();
        Response::json($semesters, 200);
    }

    /**
     * GET /courses/{id}
     * Detail informasi satu mata kuliah.
     */
    public function show(Request $request): never
    {
        $user = $request->user();
        if ($user === null) {
            throw new HttpException('UNAUTHENTICATED', 'Silakan login terlebih dahulu.', 401);
        }

        $id = (int) $request->param('id');
        if ($id <= 0) {
            throw new HttpException('VALIDATION_ERROR', 'ID mata kuliah tidak valid.', 400);
        }

        $course = $this->courseRepository->findById(
            $id,
            (int) $user['id'],
            (string) $user['role']
        );

        if ($course === null) {
            throw new HttpException('NOT_FOUND', 'Mata kuliah tidak ditemukan atau Anda tidak memiliki akses.', 404);
        }

        Response::json($course, 200);
    }

    /**
     * GET /courses/{id}/participants
     * Daftar peserta kelas mahasiswa dengan filter pencarian & pagination.
     */
    public function participants(Request $request): never
    {
        $user = $request->user();
        if ($user === null) {
            throw new HttpException('UNAUTHENTICATED', 'Silakan login terlebih dahulu.', 401);
        }

        $id = $request->intParam('id');
        (new CourseService($this->courseRepository))->requireCourseAccess($id, $user);

        $searchRaw = $request->query('search') ?? $request->query('q');
        $search = is_string($searchRaw) && trim($searchRaw) !== '' ? trim($searchRaw) : null;
        $page = $request->queryInt('page', 1, 1, 1000);
        $perPage = $request->queryInt('per_page', 20, 1, 100);

        $result = $this->courseRepository->listParticipantsPaginated($id, $search, $page, $perPage);

        Response::json($result['data'], 200, $result['meta']);
    }

    public function store(Request $request): never
    {
        $data = Validator::validate($request->body(), [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:5000',
            'semester' => 'required|string|max:50',
            'credits' => 'required',
            'day' => 'required|string|max:20',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'room' => 'required|string|max:50',
        ]);

        $course = (new CourseService($this->courseRepository))->createCourse($request->user() ?? [], $data);
        Response::json($course, 201);
    }

    public function update(Request $request): never
    {
        $courseId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:5000',
            'semester' => 'required|string|max:50',
            'credits' => 'required',
            'day' => 'required|string|max:20',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'room' => 'required|string|max:50',
        ]);

        $course = (new CourseService($this->courseRepository))->updateCourse($courseId, $request->user() ?? [], $data);
        Response::json($course, 200);
    }

    public function enroll(Request $request): never
    {
        $courseId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'nim_nip' => 'required|string|max:50',
        ]);

        $student = (new CourseService($this->courseRepository))->enrollByNim(
            $courseId,
            $request->user() ?? [],
            (string) $data['nim_nip']
        );
        Response::json($student, 201);
    }

    public function unenroll(Request $request): never
    {
        $courseId = $request->intParam('id');
        $userId = $request->intParam('user_id');

        (new CourseService($this->courseRepository))->unenrollStudent($courseId, $userId, $request->user() ?? []);
        Response::noContent();
    }

    public function meetings(Request $request): never
    {
        $courseId = $request->intParam('id');
        $items = (new CourseService($this->courseRepository))->listMeetings($courseId, $request->user() ?? []);
        Response::json($items, 200);
    }

    public function storeMeeting(Request $request): never
    {
        $courseId = $request->intParam('id');
        $data = Validator::validate($request->body(), [
            'number' => 'required',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'meeting_date' => 'required|string|max:20',
        ]);

        $meeting = (new CourseService($this->courseRepository))->createMeeting($courseId, $request->user() ?? [], $data);
        Response::json($meeting, 201);
    }
}
