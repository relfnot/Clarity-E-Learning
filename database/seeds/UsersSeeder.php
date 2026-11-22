<?php

declare(strict_types=1);

namespace Database\Seeds;

use PDO;

final class UsersSeeder
{
    public static function run(PDO $pdo): array
    {
        $passwordHash = password_hash('rahasia123', PASSWORD_BCRYPT);

        $users = [
            // 4 Dosen
            [
                'nim_nip' => '5811995177',
                'name' => 'T. BRENDA CHANDRAWATI',
                'email' => 'brenda.chandrawati@unika.ac.id',
                'role' => 'dosen',
                'program_studi' => null,
                'semester' => null,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'TBC',
            ],
            [
                'nim_nip' => '5812023424',
                'name' => 'STEPHANI INGGRIT SWASTINI DEWI',
                'email' => 'stephani.inggrit@unika.ac.id',
                'role' => 'dosen',
                'program_studi' => null,
                'semester' => null,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'SD',
            ],
            [
                'nim_nip' => '5812019311',
                'name' => 'ANDRE KURNIAWAN PAMUDJI',
                'email' => 'andre.pamudji@unika.ac.id',
                'role' => 'dosen',
                'program_studi' => null,
                'semester' => null,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'AKP',
            ],
            [
                'nim_nip' => '5812018299',
                'name' => 'YIMMY ISKANDAR',
                'email' => 'yimmy.iskandar@unika.ac.id',
                'role' => 'dosen',
                'program_studi' => null,
                'semester' => null,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'YI',
            ],

            // 10 Mahasiswa
            [
                'nim_nip' => '24.N1.0026',
                'name' => 'Dimas Rizky Fajar Pamungkas',
                'email' => '24n10026@student.unika.ac.id',
                'role' => 'mahasiswa',
                'program_studi' => 'Sistem Informasi (S1)',
                'semester' => 5,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'DR',
            ],
            [
                'nim_nip' => '24.N2.0008',
                'name' => 'Gabriel Kresnoputero W. B.',
                'email' => '24n20008@student.unika.ac.id',
                'role' => 'mahasiswa',
                'program_studi' => 'Sistem Informasi (S1)',
                'semester' => 5,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'GK',
            ],
            [
                'nim_nip' => '24.N1.0043',
                'name' => 'Adonai Shan Matthew',
                'email' => '24n10043@student.unika.ac.id',
                'role' => 'mahasiswa',
                'program_studi' => 'Sistem Informasi (S1)',
                'semester' => 5,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'AM',
            ],
            [
                'nim_nip' => '24.N1.0001',
                'name' => 'Aurelia Prameswari',
                'email' => '24n10001@student.unika.ac.id',
                'role' => 'mahasiswa',
                'program_studi' => 'Sistem Informasi (S1)',
                'semester' => 5,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'AP',
            ],
            [
                'nim_nip' => '24.N1.0002',
                'name' => 'Bagas Adi Nugroho',
                'email' => '24n10002@student.unika.ac.id',
                'role' => 'mahasiswa',
                'program_studi' => 'Sistem Informasi (S1)',
                'semester' => 5,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'BA',
            ],
            [
                'nim_nip' => '24.N1.0003',
                'name' => 'Citra Lestari Wijaya',
                'email' => '24n10003@student.unika.ac.id',
                'role' => 'mahasiswa',
                'program_studi' => 'Sistem Informasi (S1)',
                'semester' => 5,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'CL',
            ],
            [
                'nim_nip' => '24.N1.0004',
                'name' => 'Daniel Kristianto',
                'email' => '24n10004@student.unika.ac.id',
                'role' => 'mahasiswa',
                'program_studi' => 'Sistem Informasi (S1)',
                'semester' => 5,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'DK',
            ],
            [
                'nim_nip' => '24.N1.0005',
                'name' => 'Evelyn Natalia Putri',
                'email' => '24n10005@student.unika.ac.id',
                'role' => 'mahasiswa',
                'program_studi' => 'Sistem Informasi (S1)',
                'semester' => 5,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'EN',
            ],
            [
                'nim_nip' => '24.N1.0006',
                'name' => 'Farhan Maulana Siddiq',
                'email' => '24n10006@student.unika.ac.id',
                'role' => 'mahasiswa',
                'program_studi' => 'Sistem Informasi (S1)',
                'semester' => 5,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'FM',
            ],
            [
                'nim_nip' => '24.N1.0007',
                'name' => 'Grace Angelina Sutanto',
                'email' => '24n10007@student.unika.ac.id',
                'role' => 'mahasiswa',
                'program_studi' => 'Sistem Informasi (S1)',
                'semester' => 5,
                'department' => 'Fakultas Ilmu Komputer',
                'initials' => 'GA',
            ],
        ];

        $sql = 'INSERT INTO users (
                    nim_nip, name, email, password_hash, role, program_studi, semester, department, initials
                ) VALUES (
                    :nim_nip, :name, :email, :password_hash, :role, :program_studi, :semester, :department, :initials
                )
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    email = VALUES(email),
                    password_hash = VALUES(password_hash),
                    role = VALUES(role),
                    program_studi = VALUES(program_studi),
                    semester = VALUES(semester),
                    department = VALUES(department),
                    initials = VALUES(initials)';

        $stmt = $pdo->prepare($sql);

        $counts = ['dosen' => 0, 'mahasiswa' => 0];
        foreach ($users as $user) {
            $stmt->execute([
                'nim_nip' => $user['nim_nip'],
                'name' => $user['name'],
                'email' => $user['email'],
                'password_hash' => $passwordHash,
                'role' => $user['role'],
                'program_studi' => $user['program_studi'],
                'semester' => $user['semester'],
                'department' => $user['department'],
                'initials' => $user['initials'],
            ]);
            $counts[$user['role']]++;
        }

        $pdo->exec('DELETE FROM login_attempts');

        return $counts;
    }
}
