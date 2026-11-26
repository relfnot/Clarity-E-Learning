<?php

declare(strict_types=1);

namespace Database\Seeds;

use App\Services\GradeService;
use PDO;

final class AssessmentAndNotificationSeeder
{
    /**
     * @return array{quizzes: int, questions: int, grade_weights: int, notifications: int}
     */
    public static function run(PDO $pdo): array
    {
        $courses = $pdo->query('SELECT id, code, name, lecturer_id FROM courses ORDER BY id ASC')->fetchAll() ?: [];
        if ($courses === []) {
            return ['quizzes' => 0, 'questions' => 0, 'grade_weights' => 0, 'notifications' => 0];
        }

        // 1. Seed default grade_weights untuk semua mata kuliah (idempotent)
        $weightStmt = $pdo->prepare(
            'INSERT INTO grade_weights (course_id, component, weight)
             VALUES (:course_id, :component, :weight)
             ON DUPLICATE KEY UPDATE weight = weight'
        );
        $weightsCount = 0;
        foreach ($courses as $course) {
            foreach (GradeService::DEFAULT_WEIGHTS as $component => $weight) {
                $weightStmt->execute([
                    'course_id' => (int) $course['id'],
                    'component' => $component,
                    'weight' => $weight,
                ]);
                $weightsCount++;
            }
        }

        // 2. Seed quiz contoh & bank soal untuk mata kuliah pertama (idempotent)
        $firstCourseId = (int) $courses[0]['id'];
        $meetingStmt = $pdo->prepare('SELECT id FROM meetings WHERE course_id = :cid ORDER BY number ASC LIMIT 1');
        $meetingStmt->execute(['cid' => $firstCourseId]);
        $firstMeetingId = $meetingStmt->fetchColumn();
        $meetingId = $firstMeetingId !== false ? (int) $firstMeetingId : null;

        $quizTitle = 'Quiz 1: Fondasi REST API & Autentikasi JWT';
        $findQuiz = $pdo->prepare('SELECT id FROM quizzes WHERE course_id = :cid AND title = :title LIMIT 1');
        $findQuiz->execute(['cid' => $firstCourseId, 'title' => $quizTitle]);
        $quizId = $findQuiz->fetchColumn();

        $quizzesSeeded = 0;
        $questionsSeeded = 0;

        if ($quizId === false) {
            $insertQuiz = $pdo->prepare(
                'INSERT INTO quizzes (
                    course_id, meeting_id, title, description, open_at, close_at,
                    duration_min, max_attempts, shuffle, show_answers, weight
                 ) VALUES (
                    :course_id, :meeting_id, :title, :description, :open_at, :close_at,
                    :duration_min, :max_attempts, :shuffle, :show_answers, :weight
                 )'
            );
            $insertQuiz->execute([
                'course_id' => $firstCourseId,
                'meeting_id' => $meetingId,
                'title' => $quizTitle,
                'description' => 'Quiz pemahaman dasar kontrak REST API, status HTTP, dan autentikasi berbasis JWT.',
                'open_at' => '2026-11-25 08:00:00',
                'close_at' => '2026-12-10 23:59:00',
                'duration_min' => 30,
                'max_attempts' => 2,
                'shuffle' => 1,
                'show_answers' => 1,
                'weight' => 20.00,
            ]);
            $quizId = (int) $pdo->lastInsertId();
            $quizzesSeeded++;

            $sampleQuestions = [
                [
                    'type' => 'pg',
                    'text' => 'Kode status HTTP mana yang paling tepat ketika pengguna belum mengirimkan token JWT yang valid?',
                    'points' => 30.00,
                    'position' => 1,
                    'options' => [
                        ['label' => 'A', 'text' => '400 Bad Request', 'is_correct' => 0],
                        ['label' => 'B', 'text' => '401 Unauthorized', 'is_correct' => 1],
                        ['label' => 'C', 'text' => '403 Forbidden', 'is_correct' => 0],
                        ['label' => 'D', 'text' => '404 Not Found', 'is_correct' => 0],
                    ],
                ],
                [
                    'type' => 'pg',
                    'text' => 'Apa tujuan utama penggunaan PDO prepared statement pada query SQL di PHP?',
                    'points' => 30.00,
                    'position' => 2,
                    'options' => [
                        ['label' => 'A', 'text' => 'Mencegah serangan SQL Injection', 'is_correct' => 1],
                        ['label' => 'B', 'text' => 'Mengubah format JSON menjadi XML', 'is_correct' => 0],
                        ['label' => 'C', 'text' => 'Menonaktifkan autentikasi JWT', 'is_correct' => 0],
                        ['label' => 'D', 'text' => 'Menghapus indeks tabel database', 'is_correct' => 0],
                    ],
                ],
                [
                    'type' => 'esai',
                    'text' => 'Jelaskan perbedaan antara kode status 401 Unauthorized dan 403 Forbidden pada sistem otorisasi berbasis peran.',
                    'points' => 40.00,
                    'position' => 3,
                    'options' => [],
                ],
            ];

            $qStmt = $pdo->prepare(
                'INSERT INTO questions (quiz_id, type, text, points, position)
                 VALUES (:quiz_id, :type, :text, :points, :position)'
            );
            $oStmt = $pdo->prepare(
                'INSERT INTO options (question_id, label, text, is_correct, position)
                 VALUES (:question_id, :label, :text, :is_correct, :position)'
            );

            foreach ($sampleQuestions as $q) {
                $qStmt->execute([
                    'quiz_id' => $quizId,
                    'type' => $q['type'],
                    'text' => $q['text'],
                    'points' => $q['points'],
                    'position' => $q['position'],
                ]);
                $qId = (int) $pdo->lastInsertId();
                $questionsSeeded++;

                foreach ($q['options'] as $idx => $opt) {
                    $oStmt->execute([
                        'question_id' => $qId,
                        'label' => $opt['label'],
                        'text' => $opt['text'],
                        'is_correct' => $opt['is_correct'],
                        'position' => $idx + 1,
                    ]);
                }
            }
        }

        // 3. Seed notifikasi awal untuk mahasiswa pertama (idempotent)
        $studentStmt = $pdo->query("SELECT id FROM users WHERE role = 'mahasiswa' ORDER BY id ASC LIMIT 1");
        $firstStudentId = $studentStmt !== false ? $studentStmt->fetchColumn() : false;

        $notifSeeded = 0;
        if ($firstStudentId !== false) {
            $studentId = (int) $firstStudentId;
            $checkNotif = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid');
            $checkNotif->execute(['uid' => $studentId]);
            if ((int) $checkNotif->fetchColumn() === 0) {
                $nStmt = $pdo->prepare(
                    'INSERT INTO notifications (user_id, type, title, body, link)
                     VALUES (:user_id, :type, :title, :body, :link)'
                );
                $samples = [
                    [
                        'type' => 'quiz_baru',
                        'title' => 'Quiz Baru: ' . $quizTitle,
                        'body' => 'Quiz baru telah dibuka pada mata kuliah ' . $courses[0]['name'] . '.',
                        'link' => 'course-detail.php?id=' . $firstCourseId,
                    ],
                    [
                        'type' => 'pengumuman',
                        'title' => 'Selamat Datang di Clarity E-Learning',
                        'body' => 'Pantau materi, tugas, quiz, presensi, dan rekap nilai Anda melalui dashboard.',
                        'link' => 'dashboard.php',
                    ],
                ];
                foreach ($samples as $item) {
                    $nStmt->execute([
                        'user_id' => $studentId,
                        'type' => $item['type'],
                        'title' => $item['title'],
                        'body' => $item['body'],
                        'link' => $item['link'],
                    ]);
                    $notifSeeded++;
                }
            }
        }

        return [
            'quizzes' => $quizzesSeeded,
            'questions' => $questionsSeeded,
            'grade_weights' => $weightsCount,
            'notifications' => $notifSeeded,
        ];
    }
}
