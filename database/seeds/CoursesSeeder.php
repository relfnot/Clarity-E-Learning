<?php

declare(strict_types=1);

namespace Database\Seeds;

use PDO;

final class CoursesSeeder
{
    public static function run(PDO $pdo): array
    {
        // 1. Ambil ID dosen berdasarkan email kampus
        $lecturerStmt = $pdo->prepare('SELECT id, email FROM users WHERE role = "dosen"');
        $lecturerStmt->execute();
        $lecturers = $lecturerStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $brendaId = $lecturers['brenda.chandrawati@unika.ac.id'] ?? 1;
        $stephaniId = $lecturers['stephani.inggrit@unika.ac.id'] ?? 2;
        $andreId = $lecturers['andre.pamudji@unika.ac.id'] ?? 3;
        $yimmyId = $lecturers['yimmy.iskandar@unika.ac.id'] ?? 4;

        $courses = [
            [
                'code' => '07228204',
                'name' => 'MANAJEMEN PROYEK 07',
                'description' => 'Mata kuliah ini akan membekali mahasiswa dengan pengetahuan dan keterampilan dalam mengelola proyek perangkat lunak secara efektif dan efisien.',
                'semester' => 'Ganjil 2026/2027',
                'credits' => 3,
                'lecturer_id' => $brendaId,
                'day' => 'Jumat',
                'start_time' => '13:00:00',
                'end_time' => '15:00:00',
                'room' => 'Ruang 8.1',
            ],
            [
                'code' => '07228239',
                'name' => 'TEKNOLOGI KEUANGAN 05',
                'description' => 'Mata kuliah ini akan membekali mahasiswa dengan pengetahuan dan keterampilan dalam memanfaatkan teknologi untuk mendukung keputusan finansial, analisis risiko, dan inovasi dalam layanan keuangan.',
                'semester' => 'Ganjil 2026/2027',
                'credits' => 3,
                'lecturer_id' => $stephaniId,
                'day' => 'Jumat',
                'start_time' => '07:00:00',
                'end_time' => '11:00:00',
                'room' => 'Ruang 8.1',
            ],
            [
                'code' => '07228203',
                'name' => 'ETIKA PROFESI 07',
                'description' => 'Mata kuliah ini akan membekali mahasiswa dengan pengetahuan dan keterampilan untuk memahami prinsip-prinsip etika, profesionalisme, dan tanggung jawab sosial dalam konteks teknologi informasi dan sistem cerdas.',
                'semester' => 'Ganjil 2026/2027',
                'credits' => 3,
                'lecturer_id' => $yimmyId,
                'day' => 'Senin',
                'start_time' => '13:00:00',
                'end_time' => '15:00:00',
                'room' => 'Ruang 8.1',
            ],
            [
                'code' => '07228250',
                'name' => 'KOTA CERDAS 03',
                'description' => 'Mata kuliah ini akan membekali mahasiswa dengan pengetahuan dan keterampilan dalam merancang, mengembangkan, dan mengelola sistem cerdas untuk mendukung pengambilan keputusan yang efektif dalam konteks kota pintar.',
                'semester' => 'Ganjil 2026/2027',
                'credits' => 3,
                'lecturer_id' => $andreId,
                'day' => 'Senin',
                'start_time' => '08:00:00',
                'end_time' => '11:00:00',
                'room' => 'Ruang 8.1',
            ],
        ];

        $courseSql = 'INSERT INTO courses (
                        code, name, description, semester, credits, lecturer_id, day, start_time, end_time, room
                      ) VALUES (
                        :code, :name, :description, :semester, :credits, :lecturer_id, :day, :start_time, :end_time, :room
                      )
                      ON DUPLICATE KEY UPDATE
                        name = VALUES(name),
                        description = VALUES(description),
                        semester = VALUES(semester),
                        credits = VALUES(credits),
                        lecturer_id = VALUES(lecturer_id),
                        day = VALUES(day),
                        start_time = VALUES(start_time),
                        end_time = VALUES(end_time),
                        room = VALUES(room)';

        $courseStmt = $pdo->prepare($courseSql);
        $courseCount = 0;
        foreach ($courses as $c) {
            $courseStmt->execute($c);
            $courseCount++;
        }

        // 2. Daftarkan mahasiswa ke semua mata kuliah tersebut (Enrollment)
        $studentsStmt = $pdo->query('SELECT id FROM users WHERE role = "mahasiswa"');
        $students = $studentsStmt !== false ? $studentsStmt->fetchAll(PDO::FETCH_COLUMN) : [];

        $allCoursesStmt = $pdo->query('SELECT id FROM courses');
        $courseIds = $allCoursesStmt !== false ? $allCoursesStmt->fetchAll(PDO::FETCH_COLUMN) : [];

        $enrollSql = 'INSERT IGNORE INTO course_enrollments (course_id, student_id, status) VALUES (:course_id, :student_id, "active")';
        $enrollStmt = $pdo->prepare($enrollSql);

        $enrollCount = 0;
        foreach ($courseIds as $cId) {
            foreach ($students as $sId) {
                $enrollStmt->execute([
                    'course_id' => $cId,
                    'student_id' => $sId,
                ]);
                $enrollCount++;
            }
        }

        return [
            'courses' => $courseCount,
            'enrollments' => $enrollCount,
        ];
    }
}
