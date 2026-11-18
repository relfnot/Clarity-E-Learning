# Kontrak API — Clarity E-Learning (Tahap F5)

Dokumen ini menjadi **satu sumber kebenaran (Single Source of Truth)** antara tim Frontend (**Matthew**) dan tim Backend (**Rizky & Gaby**) untuk pengembangan **Clarity E-Learning** (Universitas Katolik Soegijapranata).

> [!IMPORTANT]
> **Keamanan GitHub:** File di dalam `docs/api/` hanya berisi **spesifikasi kontrak endpoint (OpenAPI 3.0)** dan **contoh data dummy (mock JSON)**. Tidak ada password database, JWT secret key, atau kredensial asli di sini. Semua secret asli nantinya wajib disimpan di file `.env` lokal dan sudah diblokir oleh `.gitignore`.

---

## 1. Struktur Folder `docs/api/`

| File / Folder | Fungsi |
| --- | --- |
| `docs/api/openapi.yaml` | Spesifikasi OpenAPI 3.0.3 lengkap untuk seluruh endpoint `/api/v1/*` (P0 lengkap dengan skema & contoh, serta P1/P2). |
| `docs/api/index.html` | Halaman pratinjau **Swagger UI** statis untuk melihat dan menguji kontrak API langsung dari browser / Apache Laragon. |
| `docs/api/examples/` | Kumpulan file JSON contoh respons sukses & error per modul P0 yang dapat dipakai Frontend untuk mock `fetch()` sebelum backend selesai. |
| `docs/api/README.md` | Dokumen aturan kontrak, konvensi penamaan, autentikasi, format error, pagination, upload, dan penyelarasan ERD. |

---

## 2. Cara Membuka Swagger UI di Laragon Lokal

Pastikan Laragon (Apache) menyala, lalu buka di browser:
- Jika akses dari root `htdocs`: `http://localhost/manpro/github/docs/api/`
- Atau gunakan Live Server / PHP built-in server dari root repo:
  ```bash
  php -S localhost:8080
  ```
  Lalu buka `http://localhost:8080/docs/api/`.

---

## 3. Aturan Umum Kontrak API

### 3.1 Base Path, Format Data & Waktu
- **Base URL:** `/api/v1`
- **Content-Type:** `application/json; charset=utf-8` (kecuali upload berkas menggunakan `multipart/form-data` dan unduh berkas berupa binary stream).
- **Penamaan Field & Path:**
  - Seluruh key JSON dan query parameter menggunakan `snake_case` (contoh: `course_id`, `submitted_at`, `is_late`).
  - Resource path menggunakan kata benda jamak (`plural`), contoh: `/courses`, `/assignments`, `/quizzes`, kecuali sub-aksi spesifik seperti `/attendance-sessions/{id}/check-in` atau `/attempts/{id}/submit`.
- **Format Waktu:** ISO 8601 dengan offset WIB (`+07:00`), contoh: `2026-12-05T23:59:00+07:00`. Timezone server wajib `Asia/Jakarta`.

### 3.2 Autentikasi & Otorisasi (`bearerAuth`)
- Menggunakan **JWT HS256** pada header HTTP:
  ```http
  Authorization: Bearer <eyJhbGciOiJIUzI1NiIs...>
  ```
- Masa berlaku token: **8 jam** (`28800` detik), tanpa refresh token pada cakupan P0/P1.
- Klaim payload JWT minimal:
  - `sub`: ID user (integer)
  - `role`: `"mahasiswa"` atau `"dosen"`
  - `iat`: Waktu token diterbitkan (epoch)
  - `exp`: Waktu token kedaluwarsa (epoch)
- **Aturan Role di Server:**
  - `mahasiswa`: Hanya dapat mengakses mata kuliah yang diambil (`enrollments`), mengumpulkan tugas/quiz/presensi milik sendiri, dan melihat nilai sendiri.
  - `dosen`: Hanya dapat mengelola mata kuliah, pertemuan, materi, tugas, sesi presensi, quiz, dan penilaian pada mata kuliah yang diampu (`courses.lecturer_id = user.id`).

### 3.3 Amplop Respons (Response Envelope)
- **Respons Sukses (Single Object / Action):**
  ```json
  {
    "data": { ... },
    "meta": {}
  }
  ```
- **Respons Sukses (List + Pagination):**
  ```json
  {
    "data": [ ... ],
    "meta": {
      "page": 1,
      "per_page": 20,
      "total": 32
    }
  }
  ```
- **Respons Error Standar:**
  ```json
  {
    "error": {
      "code": "VALIDATION_ERROR",
      "message": "Data yang dikirim tidak valid.",
      "details": {
        "deadline": ["Format waktu harus ISO 8601 dan tidak boleh di masa lalu."]
      }
    }
  }
  ```

### 3.4 Kode Status HTTP & Kode Error
| HTTP Status | `error.code` | Kapan Digunakan |
| --- | --- | --- |
| `200 OK` | — | Request `GET`, `PUT`, `PATCH`, atau aksi berhasil. |
| `201 Created` | — | Resource baru berhasil dibuat (`POST`). |
| `204 No Content` | — | Resource berhasil dihapus (`DELETE`). |
| `400 Bad Request` | `VALIDATION_ERROR` | JSON malformed atau parameter query tidak valid. |
| `401 Unauthorized` | `UNAUTHENTICATED` | Token JWT tidak dikirim, rusak, atau sudah kedaluwarsa. |
| `403 Forbidden` | `FORBIDDEN` | Role tidak sesuai (misal mahasiswa memanggil endpoint dosen) atau bukan peserta/pengampu mata kuliah tersebut. |
| `404 Not Found` | `NOT_FOUND` | ID resource tidak ditemukan di database. |
| `409 Conflict` | `CONFLICT` | Submit ganda pada presensi pertemuan yang sama, atau melebihi `max_attempts` quiz. |
| `422 Unprocessable Entity` | `VALIDATION_ERROR` | Validasi field form gagal (detail per field ada di `error.details`). |
| `429 Too Many Requests` | `RATE_LIMITED` | Gagal login lebih dari 5 kali dalam 15 menit (per kombinasi akun + IP). |
| `500 Internal Server Error` | `SERVER_ERROR` | Kesalahan tak terduga di sisi server (pesan detail error internal tidak boleh dibocorkan ke klien). |

### 3.5 Pagination, Filter & Sorting
- Parameter pagination standar pada endpoint koleksi: `?page=1&per_page=20` (nilai `per_page` maksimal `100`).
- Filter menggunakan query parameter eksplisit: `?course_id=1&status=belum&semester=Ganjil%202026/2027`.
- Sorting menggunakan `?sort=-created_at` (awalan `-` berarti `DESC`, tanpa awalan berarti `ASC`).

### 3.6 Upload & Download Berkas
- **Upload (`multipart/form-data`):**
  - Field berkas bernama `file`.
  - Ukuran maksimal: **10 MB** (untuk video perkuliahan wajib menggunakan tipe `link` / URL eksternal).
  - Ekstensi & MIME yang diizinkan: `pdf`, `doc`, `docx`, `ppt`, `pptx`, `xls`, `xlsx`, `zip`, `jpg`, `jpeg`, `png`, `txt`.
  - Server wajib memvalidasi MIME dengan `finfo`, me-rename file secara acak, dan menyimpannya di `storage/uploads/` (di luar folder `public/`).
  - Respons JSON hanya mengembalikan metadata (`file_name`, `file_size`, `download_url`), **bukan** path sistem file server.
- **Download (`GET .../download`):**
  - Mengalirkan file binary (`application/octet-stream` atau MIME asli) setelah mengecek token JWT dan hak akses user terhadap mata kuliah/pengumpulan tersebut.

---

## 4. Penyelarasan ERD (`erd/erd.svg`) vs Konvensi API & Mockup (`mock-data.js`)

Sesuai Tugas #1 pada `02-PRD-F5-kontrak-api.md`, berikut catatan penyelarasan antara tabel ERD (Tahap F3), Mockup UI (Tahap F4), dan Kontrak JSON API (Tahap F5):

| Entitas | Kolom di `erd/erd.svg` | Field di Kontrak JSON API (`openapi.yaml`) | Catatan Penyelarasan |
| --- | --- | --- | --- |
| `users` | `role: enum(mhs, dsn)`, `program_studi`, `semester`, `department`, `initials` | `role: "mahasiswa" \| "dosen"`, `program_studi`, `semester`, `department`, `initials` | Di JSON API & Mockup UI nilai role dieja penuh (`"mahasiswa"` / `"dosen"`) agar konsisten dengan `ClarityData` di `mock-data.js`. |
| `course_enrollments` | `course_id`, `student_id`, `enrolled_at` | `course_id`, `user_id`, `enrolled_at` | Di endpoint `/courses/{id}/enrollments`, field JSON memakai `user_id` (atau `nim_nip` saat dosen menambahkan peserta). |
| `meetings` | `meeting_number`, `date`, `description` | `number`, `meeting_date`, `description` | Diselaraskan dengan `mock-data.js` (`number`, `meeting_date`) serta mendukung alias `meeting_number`. |
| `materials` | `type: enum(pdf, video)`, `file_url` | `type: "file" \| "link"`, `file_name`, `file_size`, `url`, `download_url` | Mendukung unggahan berkas (`file`) maupun tautan video (`link`) sesuai mockup `course-detail.html`. |
| `assignments` | `submission_type`, `max_points`, `weight_percent` | `accept_file`, `accept_link`, `max_score`, `weight` | Di API disediakan boolean `accept_file` & `accept_link` agar form upload mahasiswa langsung tahu input mana yang aktif. |
| `assignment_submissions` | `status: on_time \| late`, `grade`, `file_url / link` | `status: "tepat_waktu" \| "terlambat"`, `is_late` (boolean), `score`, `feedback` | `is_late` dihitung otomatis di server (`submitted_at > deadline`). |
| `attendance_sessions` | `opened_at`, `closed_at`, `passcode`, `status: open \| closed` | `opened_at`, `closes_at`, `passcode`, `status: "open" \| "closed"` | Mendukung opsional `passcode` sesuai ERD dan `closes_at` sesuai PRD F5. |
| `quizzes` | `duration_minutes`, `start_time`, `end_time`, `weight_percent` | `duration_min`, `open_at`, `close_at`, `max_attempts`, `shuffle`, `weight` | Diselaraskan dengan `ClarityData.quiz` pada mockup F4. |
| `course_final_grades` | `assignments_avg`, `quizzes_avg`, `attendance_pct`, `final_score`, `progress_percent` | Dipetakan langsung ke `GradeSummary` & `Progress` | Menjadi sumber cache/rekap untuk endpoint `/courses/{id}/grades` dan `/dashboard/student`. |

---

## 5. Keputusan Terbuka Kelompok (Disepakati untuk Kontrak F5)
1. **Pengisian Enrollment:** Seeder awal mengisi data peserta kelas (2 dosen, 10+ mahasiswa, 4 mata kuliah sesuai mockup), ditambah endpoint `POST /courses/{id}/enrollments` (P1) agar dosen tetap bisa menambah mahasiswa via NIM.
2. **Refresh Token:** Tidak menggunakan refresh token (token berlaku 8 jam sudah cukup untuk sesi kuliah harian).
3. **Chat:** Menggunakan **REST Polling** tiap 3–5 detik (`GET /courses/{id}/messages?after_id=...`) agar berjalan stabil di PHP native + Apache Laragon tanpa server WebSocket terpisah.
4. **Role Admin:** Tidak ada role admin; seluruh pengelolaan perkuliahan dilakukan oleh role `dosen`.

---

## 6. Aturan Perubahan Kontrak (Governance)
1. Setiap PR yang menambah atau mengubah endpoint backend **wajib** memperbarui `docs/api/openapi.yaml` dan menaikkan `info.version` (misal dari `1.0.0` ke `1.1.0`).
2. Tidak boleh ada *breaking change* (mengubah nama field atau menghapus endpoint P0) setelah kontrak dibekukan tanpa persetujuan 3 anggota tim (Rizky, Gaby, Matthew).
