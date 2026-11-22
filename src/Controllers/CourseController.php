<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CourseRepository;

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
     * Daftar peserta kelas mahasiswa.
     */
    public function participants(Request $request): never
    {
        $user = $request->user();
        if ($user === null) {
            throw new HttpException('UNAUTHENTICATED', 'Silakan login terlebih dahulu.', 401);
        }

        $id = (int) $request->param('id');
        if ($id <= 0) {
            throw new HttpException('VALIDATION_ERROR', 'ID mata kuliah tidak valid.', 400);
        }

        // Pastikan mata kuliah ada dan user memiliki akses
        $course = $this->courseRepository->findById(
            $id,
            (int) $user['id'],
            (string) $user['role']
        );

        if ($course === null) {
            throw new HttpException('NOT_FOUND', 'Mata kuliah tidak ditemukan atau Anda tidak memiliki akses.', 404);
        }

        $participants = $this->courseRepository->getParticipants($id);

        Response::json($participants, 200, ['total' => count($participants)]);
    }
}
