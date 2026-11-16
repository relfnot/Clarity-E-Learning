/* ==========================================================================
   Clarity E-Learning — Mock Data Statis (Konteks Desember 2026)
   Seluruh field menggunakan snake_case selaras dengan draft model data & PRD F5
   ========================================================================== */

(function (global) {
  'use strict';

  var ClarityData = {
    meta: {
      current_date: '2026-12-05T10:00:00+07:00',
      semester_active: 'Ganjil 2026/2027',
      campus_name: 'Universitas Katolik Soegijapranata'
    },

    users: {
      mahasiswa: {
        id: 101,
        nim_nip: '24.N1.0026',
        name: 'Dimas Rizky Fajar Pamungkas',
        email: '24n10026@student.unika.ac.id',
        role: 'mahasiswa',
        program_studi: 'Sistem Informasi (S1)',
        semester: 5,
        initials: 'DR',
        gamification: {
          level: 7,
          title: 'Scholar Tekun',
          xp: 1450,
          next_level_xp: 2000,
          rank_in_class: 2
        },
        settings: {
          leaderboard_anonymous: false,
          leaderboard_hidden: false,
          data_saver: false
        }
      },
      dosen: {
        id: 1,
        nim_nip: '5811995177',
        name: 'T. BRENDA CHANDRAWATI',
        email: 'brenda.chandrawati@unika.ac.id',
        role: 'dosen',
        department: 'Fakultas Ilmu Komputer',
        initials: 'TBC'
      }
    },

    courses: [
      {
        id: 1,
        code: '07228204',
        name: 'MANAJEMEN PROYEK 07',
        description: 'Mata kuliah ini akan membekali mahasiswa dengan pengetahuan dan keterampilan dalam mengelola proyek perangkat lunak secara efektif dan efisien.',
        semester: 'Ganjil 2026/2027',
        credits: 3,
        lecturer_id: 1,
        lecturer_name: 'T. BRENDA CHANDRAWATI',
        day: 'Jumat',
        start_time: '13:00',
        end_time: '15:00',
        room: 'Ruang 8.1',
        enrolled_count: 32,
        progress_percent: 78,
        student_score: 86,
        class_average: 79,
        attendance_percent: 92
      },
      {
        id: 2,
        code: '07228239',
        name: 'TEKNOLOGI KEUANGAN 05',
        description: 'Mata kuliah ini akan membekali mahasiswa dengan pengetahuan dan keterampilan dalam memanfaatkan teknologi untuk mendukung keputusan finansial, analisis risiko, dan inovasi dalam layanan keuangan.',
        semester: 'Ganjil 2026/2027',
        credits: 3,
        lecturer_id: 2,
        lecturer_name: 'STEPHANI INGGRIT SWASTINI DEWI',
        day: 'Jumat',
        start_time: '07:00',
        end_time: '11:00',
        room: 'Ruang 8.1',
        enrolled_count: 35,
        progress_percent: 65,
        student_score: 82,
        class_average: 76,
        attendance_percent: 100
      },
      {
        id: 3,
        code: '07228203',
        name: 'ETIKA PROFESI 07',
        description: 'Mata kuliah ini akan membekali mahasiswa dengan pengetahuan dan keterampilan untuk memahami prinsip-prinsip etika, profesionalisme, dan tanggung jawab sosial dalam konteks teknologi informasi dan sistem cerdas.',
        semester: 'Ganjil 2026/2027',
        credits: 3,
        lecturer_id: 4,
        lecturer_name: 'YIMMY ISKANDAR',
        day: 'Senin',
        start_time: '13:00',
        end_time: '15:00',
        room: 'Ruang 8.1',
        enrolled_count: 28,
        progress_percent: 50,
        student_score: 78,
        class_average: 75,
        attendance_percent: 85
      },
      {
        id: 4,
        code: '07228250',
        name: 'KOTA CERDAS 03',
        description: 'Mata kuliah ini akan membekali mahasiswa dengan pengetahuan dan keterampilan dalam merancang, mengembangkan, dan mengelola sistem cerdas untuk mendukung pengambilan keputusan yang efektif dalam konteks kota pintar.',
        semester: 'Ganjil 2026/2027',
        credits: 3,
        lecturer_id: 3,
        lecturer_name: 'ANDRE KURNIAWAN PAMUDJI',
        day: 'Senin',
        start_time: '08:00',
        end_time: '11:00',
        room: 'Ruang 8.1',
        enrolled_count: 30,
        progress_percent: 90,
        student_score: 91,
        class_average: 81,
        attendance_percent: 95
      }
    ],

    upcoming_tasks: [
      {
        id: 11,
        course_id: 1,
        course_code: '07228204',
        course_name: 'MANAJEMEN PROYEK 07',
        title: 'Tugas 4: Implementasi REST API Auth & JWT',
        deadline: '2026-12-06T23:59:00+07:00',
        deadline_label: 'Besok, 6 Des 2026 · 23:59 WIB',
        urgency: 'h1',
        urgency_text: 'Mendesak (H-1)',
        status: 'belum'
      },
      {
        id: 12,
        course_id: 2,
        course_code: '07228239',
        course_name: 'TEKNOLOGI KEUANGAN 05',
        title: 'Studi Kasus Diagram Sequence & Kontrak API',
        deadline: '2026-12-08T23:59:00+07:00',
        deadline_label: 'Selasa, 8 Des 2026 · 23:59 WIB',
        urgency: 'h3',
        urgency_text: 'Segera (H-3)',
        status: 'belum'
      },
      {
        id: 13,
        course_id: 3,
        course_code: '07228203',
        course_name: 'ETIKA PROFESI 07',
        title: 'Laporan Studi Kasus Etika Sistem Cerdas',
        deadline: '2026-12-12T23:59:00+07:00',
        deadline_label: 'Sabtu, 12 Des 2026 · 23:59 WIB',
        urgency: 'normal',
        urgency_text: 'Terjadwal (H-7)',
        status: 'dikumpulkan'
      },
      {
        id: 14,
        course_id: 4,
        course_code: '07228250',
        course_name: 'KOTA CERDAS 03',
        title: 'Rancangan Arsitektur Sensor & Dashboard Kota Pintar',
        deadline: '2026-12-15T23:59:00+07:00',
        deadline_label: 'Selasa, 15 Des 2026 · 23:59 WIB',
        urgency: 'normal',
        urgency_text: 'Terjadwal (H-10)',
        status: 'dinilai'
      }
    ],

    meetings: [
      { id: 1, course_id: 1, number: 8, title: 'Arsitektur REST API & Routing PHP Native', meeting_date: '2026-11-23' },
      { id: 2, course_id: 1, number: 9, title: 'Autentikasi JWT & Middleware Role', meeting_date: '2026-11-30' },
      { id: 3, course_id: 1, number: 10, title: 'Keamanan Input, PDO Prepared Statement & Upload File', meeting_date: '2026-12-05' },
      { id: 4, course_id: 1, number: 11, title: 'Optimasi Query & Caching Sederhana', meeting_date: '2026-12-12' }
    ],

    materials: [
      { id: 201, course_id: 1, meeting_id: 1, meeting_number: 8, title: 'Slide Pertemuan 8: Prinsip RESTful API', type: 'file', file_name: 'P08_REST_API.pdf', file_size: '2.4 MB', created_at: '2026-11-22T09:00:00+07:00', completed: true },
      { id: 202, course_id: 1, meeting_id: 2, meeting_number: 9, title: 'Modul Praktikum: JWT HS256 & Middleware', type: 'file', file_name: 'P09_Modul_JWT.pdf', file_size: '1.8 MB', created_at: '2026-11-29T10:15:00+07:00', completed: true },
      { id: 203, course_id: 1, meeting_id: 2, meeting_number: 9, title: 'Video Rekaman Pembahasan Auth & Token', type: 'link', url: 'https://video.unika.ac.id/07228204-p09', created_at: '2026-11-30T14:00:00+07:00', completed: false },
      { id: 204, course_id: 1, meeting_id: 3, meeting_number: 10, title: 'Panduan Upload Aman & Validasi MIME', type: 'file', file_name: 'P10_Upload_Security.pptx', file_size: '3.1 MB', created_at: '2026-12-04T19:00:00+07:00', completed: false }
    ],

    participants: [
      { id: 101, nim_nip: '24.N1.0026', name: 'Dimas Rizky Fajar Pamungkas', role: 'mahasiswa', email: '24n10026@student.unika.ac.id' },
      { id: 102, nim_nip: '24.N2.0008', name: 'Gabriel Kresnoputero W. B.', role: 'mahasiswa', email: '24n20008@student.unika.ac.id' },
      { id: 103, nim_nip: '24.N1.0043', name: 'Adonai Shan Matthew', role: 'mahasiswa', email: '24n10043@student.unika.ac.id' }
    ],

    notifications: [
      {
        id: 301,
        type: 'reminder',
        title: 'Pengingat H-1: Tugas 4 REST API Auth & JWT',
        body: 'Batas pengumpulan besok pukul 23:59 WIB. Kumpulkan tepat waktu untuk bonus +30 XP.',
        link: 'assignment.html',
        read_at: null,
        created_at: '5 Des 2026 · 08:00 WIB'
      },
      {
        id: 302,
        type: 'grade',
        title: 'Nilai Keluar: Rancangan Sistem Kota Pintar',
        body: 'ANDRE KURNIAWAN PAMUDJI memberi nilai 92/100 pada tugas mata kuliah KOTA CERDAS 03.',
        link: 'grades.html',
        read_at: null,
        created_at: '4 Des 2026 · 16:40 WIB'
      },
      {
        id: 303,
        type: 'announcement',
        title: 'Pengumuman 07228204: Sesi Presensi Pertemuan 10 Dibuka',
        body: 'Silakan lakukan check-in presensi sebelum pukul 15:00 WIB melalui menu Presensi.',
        link: 'attendance.html',
        read_at: null,
        created_at: '5 Des 2026 · 07:55 WIB'
      },
      {
        id: 304,
        type: 'quiz',
        title: 'Quiz 2 MANAJEMEN PROYEK 07 Dijadwalkan',
        body: 'Quiz dibuka 5–7 Des 2026, durasi 30 menit, maksimal 2 kali percobaan.',
        link: 'quiz-intro.html',
        read_at: '2026-12-04T11:00:00+07:00',
        created_at: '3 Des 2026 · 11:00 WIB'
      }
    ],

    quiz: {
      id: 401,
      course_id: 1,
      course_code: '07228204',
      course_name: 'MANAJEMEN PROYEK 07',
      title: 'Quiz 2: Autentikasi JWT, REST API & Keamanan Web',
      open_at: '5 Des 2026, 08:00 WIB',
      close_at: '7 Des 2026, 23:59 WIB',
      duration_min: 30,
      max_attempts: 2,
      attempts_used: 1,
      shuffle: true,
      total_questions: 5,
      questions: [
        {
          id: 1,
          type: 'pg',
          points: 20,
          text: 'Kode status HTTP mana yang paling tepat dikembalikan server ketika pengguna belum mengirimkan token autentikasi JWT yang valid?',
          options: [
            { id: 'a', label: 'A', text: '400 Bad Request' },
            { id: 'b', label: 'B', text: '401 Unauthorized' },
            { id: 'c', label: 'C', text: '403 Forbidden' },
            { id: 'd', label: 'D', text: '404 Not Found' }
          ],
          correct_option: 'b',
          selected_option: 'b'
        },
        {
          id: 2,
          type: 'pg',
          points: 20,
          text: 'Mengapa seluruh query database pada backend wajib menggunakan PDO prepared statement?',
          options: [
            { id: 'a', label: 'A', text: 'Mempercepat koneksi jaringan antar server web dan browser' },
            { id: 'b', label: 'B', text: 'Mencegah kerentanan SQL Injection dengan memisahkan struktur query dan parameter' },
            { id: 'c', label: 'C', text: 'Mengubah format respons database langsung menjadi JSON' },
            { id: 'd', label: 'D', text: 'Menghapus kebutuhan indeks pada tabel MySQL' }
          ],
          correct_option: 'b',
          selected_option: 'b'
        },
        {
          id: 3,
          type: 'pg',
          points: 20,
          text: 'Di mana berkas unggahan tugas mahasiswa sebaiknya disimpan pada arsitektur web yang aman?',
          options: [
            { id: 'a', label: 'A', text: 'Di folder public/uploads agar bisa diakses langsung lewat URL' },
            { id: 'b', label: 'B', text: 'Di dalam localStorage browser pengguna' },
            { id: 'c', label: 'C', text: 'Di direktori luar public (storage/uploads) dan diakses via endpoint terotorisasi' },
            { id: 'd', label: 'D', text: 'Di dalam file HTML sebagai komentar' }
          ],
          correct_option: 'c',
          selected_option: 'c'
        },
        {
          id: 4,
          type: 'pg',
          points: 20,
          text: 'Kode status HTTP yang digunakan ketika mahasiswa mencoba melakukan check-in presensi dua kali pada pertemuan yang sama adalah...',
          options: [
            { id: 'a', label: 'A', text: '201 Created' },
            { id: 'b', label: 'B', text: '409 Conflict' },
            { id: 'c', label: 'C', text: '422 Unprocessable Entity' },
            { id: 'd', label: 'D', text: '500 Internal Server Error' }
          ],
          correct_option: 'b',
          selected_option: 'a'
        },
        {
          id: 5,
          type: 'esai',
          points: 20,
          text: 'Jelaskan perbedaan antara kode status 401 dan 403 dalam otorisasi berbasis peran (mahasiswa vs dosen)!',
          sample_answer: '401 menandakan klien belum terautentikasi (token kosong/tidak valid/kedaluwarsa), sedangkan 403 menandakan identitas terverifikasi tetapi peran akun tidak memiliki hak akses ke resource tersebut.'
        }
      ]
    },

    leaderboard: [
      { rank: 1, user_id: 102, name: 'Gabriel Kresnoputero W. B.', nim: '24.N2.0008', xp: 1780, level: 8, title: 'Arsitek Kode', is_me: false },
      { rank: 2, user_id: 101, name: 'Dimas Rizky Fajar Pamungkas', nim: '24.N1.0026', xp: 1450, level: 7, title: 'Scholar Tekun', is_me: true },
      { rank: 3, user_id: 103, name: 'Adonai Shan Matthew', nim: '24.N1.0043', xp: 1390, level: 7, title: 'Scholar Tekun', is_me: false }
    ],

    badges: [
      { id: 1, code: 'ON_TIME_5', name: 'Disiplin 5x', desc: 'Mengumpulkan 5 tugas berturut-turut sebelum batas waktu.', earned: true, earned_at: '28 Nov 2026' },
      { id: 2, code: 'QUIZ_MASTER', name: 'Penakluk Quiz', desc: 'Meraih nilai ≥ 85 pada 2 quiz berbeda.', earned: true, earned_at: '2 Des 2026' },
      { id: 3, code: 'ATTEND_100', name: 'Hadir Penuh', desc: 'Kehadiran 100% dalam 1 bulan perkuliahan.', earned: true, earned_at: '30 Nov 2026' },
      { id: 4, code: 'HELPER', name: 'Diskusi Aktif', desc: 'Aktif berdiskusi di forum grup mata kuliah minimal 15 pesan.', earned: false, earned_at: null }
    ],

    at_risk_students: [
      {
        user_id: 103,
        name: 'Adonai Shan Matthew',
        nim_nip: '24.N1.0043',
        course_code: '07228203',
        course_name: 'ETIKA PROFESI 07',
        risk_level: 'Sedang',
        reasons: ['Nilai turun 22% pada Quiz 2'],
        last_login: '3 Des 2026',
        current_avg: 66
      }
    ],

    submissions_list: [
      { id: 501, student_name: 'Dimas Rizky Fajar Pamungkas', nim: '24.N1.0026', status: 'tepat_waktu', submitted_at: '5 Des 2026 · 14:20 WIB', type: 'file', artifact: 'tugas4-rest-api-24N10026.zip', score: 90 },
      { id: 502, student_name: 'Gabriel Kresnoputero W. B.', nim: '24.N2.0008', status: 'tepat_waktu', submitted_at: '5 Des 2026 · 11:05 WIB', type: 'link', artifact: 'https://github.com/gabriel/07228204-jwt', score: 94 },
      { id: 503, student_name: 'Adonai Shan Matthew', nim: '24.N1.0043', status: 'tepat_waktu', submitted_at: '5 Des 2026 · 19:45 WIB', type: 'file', artifact: 'auth-jwt-addon.pdf', score: null }
    ]
  };

  global.ClarityData = ClarityData;
})(window);
