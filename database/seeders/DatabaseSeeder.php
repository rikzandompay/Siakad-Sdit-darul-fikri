<?php

namespace Database\Seeders;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. GURU (USERS) ──
        $guru1 = User::create([
            'name' => 'Dewi Quraisyinta',
            'email' => 'dewi@darulfikri.sch.id',
            'password' => Hash::make('sinta123'),
            'nip' => '198501012010011001',
            'nama_lengkap' => 'Ustadzah Dewi Quraisyinta, S.Pd.I',
            'username' => 'sinta',
            'no_hp' => '081234567890',
        ]);

        $guru2 = User::create([
            'name' => 'Fatimah',
            'email' => 'fatimah@darulfikri.sch.id',
            'password' => Hash::make('guru1234'),
            'nip' => '198602152011012002',
            'nama_lengkap' => 'Ustadzah Fatimah, S.Pd.',
            'username' => 'fatimah',
            'no_hp' => '081234567891',
        ]);

        $guru3 = User::create([
            'name' => 'Abdullah Mansur',
            'email' => 'abdullah@darulfikri.sch.id',
            'password' => Hash::make('guru1234'),
            'nip' => '198703202012011003',
            'nama_lengkap' => 'Ust. Abdullah Mansur, Lc.',
            'username' => 'abdullah',
            'no_hp' => '081234567892',
        ]);

        $guru4 = User::create([
            'name' => 'Nurul Huda',
            'email' => 'nurul@darulfikri.sch.id',
            'password' => Hash::make('guru1234'),
            'nip' => '198804252013012004',
            'nama_lengkap' => 'Ustadzah Nurul Huda, S.Pd.',
            'username' => 'nurul',
            'no_hp' => '081234567893',
        ]);

        $guru5 = User::create([
            'name' => 'Hasan Basri',
            'email' => 'hasan@darulfikri.sch.id',
            'password' => Hash::make('guru1234'),
            'nip' => '198905302014011005',
            'nama_lengkap' => 'Ust. Hasan Basri, M.Pd.',
            'username' => 'hasan',
            'no_hp' => '081234567894',
        ]);

        // ── 2. TAHUN AJARAN ──
        TahunAjaran::create([
            'nama_semester' => '2022/2023 - Ganjil',
            'status_aktif' => 'N',
        ]);
        TahunAjaran::create([
            'nama_semester' => '2022/2023 - Genap',
            'status_aktif' => 'N',
        ]);
        TahunAjaran::create([
            'nama_semester' => '2023/2024 - Ganjil',
            'status_aktif' => 'Y',
        ]);
        TahunAjaran::create([
            'nama_semester' => '2023/2024 - Genap',
            'status_aktif' => 'N',
        ]);

        // ── 3. MATA PELAJARAN ──
        $mapels = [
            ['kode_pelajaran' => 'PAI', 'nama_pelajaran' => 'Pendidikan Agama Islam'],
            ['kode_pelajaran' => 'QH', 'nama_pelajaran' => "Al-Qur'an Hadist"],
            ['kode_pelajaran' => 'FIQ', 'nama_pelajaran' => 'Fiqih'],
            ['kode_pelajaran' => 'THF', 'nama_pelajaran' => 'Tahfidz Quran'],
            ['kode_pelajaran' => 'MTK', 'nama_pelajaran' => 'Matematika'],
            ['kode_pelajaran' => 'BIN', 'nama_pelajaran' => 'Bahasa Indonesia'],
            ['kode_pelajaran' => 'BIG', 'nama_pelajaran' => 'Bahasa Inggris'],
            ['kode_pelajaran' => 'IPA', 'nama_pelajaran' => 'IPA (Sains)'],
            ['kode_pelajaran' => 'IPS', 'nama_pelajaran' => 'IPS'],
            ['kode_pelajaran' => 'PJOK', 'nama_pelajaran' => 'PJOK (Olahraga)'],
            ['kode_pelajaran' => 'SBK', 'nama_pelajaran' => 'Seni Budaya'],
            ['kode_pelajaran' => 'BAR', 'nama_pelajaran' => 'Bahasa Arab'],
        ];
        $mapelModels = [];
        foreach ($mapels as $m) {
            $mapelModels[$m['kode_pelajaran']] = MataPelajaran::create($m);
        }

        // ── 4. KELAS ──
        $kelas1 = Kelas::create(['nama_kelas' => 'Kelas 1 - Abu Bakar', 'wali_kelas_id' => $guru3->id]);
        $kelas2 = Kelas::create(['nama_kelas' => 'Kelas 2 - Umar Bin Khattab', 'wali_kelas_id' => $guru4->id]);
        $kelas3 = Kelas::create(['nama_kelas' => 'Kelas 3 - Utsman Bin Affan', 'wali_kelas_id' => $guru5->id]);
        $kelas4 = Kelas::create(['nama_kelas' => 'Kelas 4-A', 'wali_kelas_id' => $guru2->id]);
        $kelas5 = Kelas::create(['nama_kelas' => 'Kelas 5 - Khalid Bin Walid', 'wali_kelas_id' => $guru1->id]);
        $kelas6 = Kelas::create(['nama_kelas' => 'Kelas 6 - Shalahuddin Al-Ayyubi', 'wali_kelas_id' => $guru3->id]);

        // ── 5. SISWA ──
        $siswaData = [
            // Kelas 4-A
            ['nis' => '202104001', 'nama_siswa' => 'Ahmad Hidayat', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Bpk. Hidayat', 'no_hp_wali' => '08111111001'],
            ['nis' => '202104002', 'nama_siswa' => 'Fatimah Az-Zahra', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Bpk. Zahra', 'no_hp_wali' => '08111111002'],
            ['nis' => '202104003', 'nama_siswa' => 'Muhammad Khairi', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Bpk. Khairi', 'no_hp_wali' => '08111111003'],
            ['nis' => '202104004', 'nama_siswa' => 'Siti Sarah', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Ibu Sarah', 'no_hp_wali' => '08111111004'],
            ['nis' => '202104005', 'nama_siswa' => 'Zaidan Qushayyi', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Bpk. Qushayyi', 'no_hp_wali' => '08111111005'],
            ['nis' => '202104006', 'nama_siswa' => 'Abdullah Azzam', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Bpk. Azzam', 'no_hp_wali' => '08111111006'],
            ['nis' => '202104007', 'nama_siswa' => 'Balqis Humaira', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Ibu Humaira', 'no_hp_wali' => '08111111007'],
            ['nis' => '202104008', 'nama_siswa' => 'Fatih Nurrahman', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Bpk. Nurrahman', 'no_hp_wali' => '08111111008'],
            ['nis' => '202104009', 'nama_siswa' => 'Aisyah Putri', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Ibu Putri', 'no_hp_wali' => '08111111009'],
            ['nis' => '202104010', 'nama_siswa' => 'Umar Faruq', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Bpk. Faruq', 'no_hp_wali' => '08111111010'],
            // Kelas 1
            ['nis' => '202101001', 'nama_siswa' => 'Aliya Rahmah', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas1->id, 'nama_wali' => 'Ibu Rahmah', 'no_hp_wali' => '08112221001'],
            ['nis' => '202101002', 'nama_siswa' => 'Bilal Ahmad', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas1->id, 'nama_wali' => 'Bpk. Ahmad', 'no_hp_wali' => '08112221002'],
            ['nis' => '202101003', 'nama_siswa' => 'Citra Dewi', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas1->id, 'nama_wali' => 'Ibu Dewi', 'no_hp_wali' => '08112221003'],
            // Kelas 2
            ['nis' => '202102001', 'nama_siswa' => 'Dimas Pratama', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas2->id, 'nama_wali' => 'Bpk. Pratama', 'no_hp_wali' => '08113331001'],
            ['nis' => '202102002', 'nama_siswa' => 'Eva Safitri', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas2->id, 'nama_wali' => 'Ibu Safitri', 'no_hp_wali' => '08113331002'],
            // Kelas 3
            ['nis' => '202103001', 'nama_siswa' => 'Farhan Maulana', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas3->id, 'nama_wali' => 'Bpk. Maulana', 'no_hp_wali' => '08114441001'],
            ['nis' => '202103002', 'nama_siswa' => 'Ghina Azzahra', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas3->id, 'nama_wali' => 'Ibu Azzahra', 'no_hp_wali' => '08114441002'],
            // Kelas 5
            ['nis' => '202105001', 'nama_siswa' => 'Haikal Rahman', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas5->id, 'nama_wali' => 'Bpk. Rahman', 'no_hp_wali' => '08115551001'],
            ['nis' => '202105002', 'nama_siswa' => 'Inas Kamilah', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas5->id, 'nama_wali' => 'Ibu Kamilah', 'no_hp_wali' => '08115551002'],
            // Kelas 6
            ['nis' => '202106001', 'nama_siswa' => 'Jibril Hakim', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas6->id, 'nama_wali' => 'Bpk. Hakim', 'no_hp_wali' => '08116661001'],
            ['nis' => '202106002', 'nama_siswa' => 'Khadijah Nur', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas6->id, 'nama_wali' => 'Ibu Nur', 'no_hp_wali' => '08116661002'],
        ];

        foreach ($siswaData as $s) {
            Siswa::create($s);
        }

        // ── 6. JADWAL PELAJARAN (Kelas 1) ──
        $jadwalKelas1 = [
            ['kelas_id' => $kelas1->id, 'pelajaran_id' => $mapelModels['PAI']->id, 'guru_id' => $guru1->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
            ['kelas_id' => $kelas1->id, 'pelajaran_id' => $mapelModels['BIN']->id, 'guru_id' => $guru2->id, 'hari' => 'Senin', 'jam_mulai' => '09:00', 'jam_selesai' => '10:30'],
            ['kelas_id' => $kelas1->id, 'pelajaran_id' => $mapelModels['MTK']->id, 'guru_id' => $guru3->id, 'hari' => 'Selasa', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
        ];
        foreach ($jadwalKelas1 as $j) {
            JadwalPelajaran::create($j);
        }

        // ── 7. JADWAL PELAJARAN (Kelas 2) ──
        $jadwalKelas2 = [
            ['kelas_id' => $kelas2->id, 'pelajaran_id' => $mapelModels['BIN']->id, 'guru_id' => $guru4->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
            ['kelas_id' => $kelas2->id, 'pelajaran_id' => $mapelModels['MTK']->id, 'guru_id' => $guru5->id, 'hari' => 'Senin', 'jam_mulai' => '09:00', 'jam_selesai' => '10:30'],
            ['kelas_id' => $kelas2->id, 'pelajaran_id' => $mapelModels['IPA']->id, 'guru_id' => $guru1->id, 'hari' => 'Selasa', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
        ];
        foreach ($jadwalKelas2 as $j) {
            JadwalPelajaran::create($j);
        }

        // ── 8. JADWAL PELAJARAN (Kelas 3) ──
        $jadwalKelas3 = [
            ['kelas_id' => $kelas3->id, 'pelajaran_id' => $mapelModels['IPS']->id, 'guru_id' => $guru2->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
            ['kelas_id' => $kelas3->id, 'pelajaran_id' => $mapelModels['IPA']->id, 'guru_id' => $guru3->id, 'hari' => 'Senin', 'jam_mulai' => '09:00', 'jam_selesai' => '10:30'],
            ['kelas_id' => $kelas3->id, 'pelajaran_id' => $mapelModels['BIN']->id, 'guru_id' => $guru4->id, 'hari' => 'Selasa', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
        ];
        foreach ($jadwalKelas3 as $j) {
            JadwalPelajaran::create($j);
        }

        // ── 9. JADWAL PELAJARAN (Kelas 4-A) ──
        $jadwalKelas4 = [
            ['kelas_id' => $kelas4->id, 'pelajaran_id' => $mapelModels['QH']->id, 'guru_id' => $guru3->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
            ['kelas_id' => $kelas4->id, 'pelajaran_id' => $mapelModels['MTK']->id, 'guru_id' => $guru2->id, 'hari' => 'Senin', 'jam_mulai' => '09:00', 'jam_selesai' => '10:30'],
            ['kelas_id' => $kelas4->id, 'pelajaran_id' => $mapelModels['BIN']->id, 'guru_id' => $guru4->id, 'hari' => 'Selasa', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
            ['kelas_id' => $kelas4->id, 'pelajaran_id' => $mapelModels['BIG']->id, 'guru_id' => $guru4->id, 'hari' => 'Selasa', 'jam_mulai' => '09:00', 'jam_selesai' => '10:30'],
            ['kelas_id' => $kelas4->id, 'pelajaran_id' => $mapelModels['IPA']->id, 'guru_id' => $guru1->id, 'hari' => 'Rabu', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
            ['kelas_id' => $kelas4->id, 'pelajaran_id' => $mapelModels['PJOK']->id, 'guru_id' => $guru5->id, 'hari' => 'Rabu', 'jam_mulai' => '09:00', 'jam_selesai' => '10:30'],
            ['kelas_id' => $kelas4->id, 'pelajaran_id' => $mapelModels['FIQ']->id, 'guru_id' => $guru5->id, 'hari' => 'Kamis', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
            ['kelas_id' => $kelas4->id, 'pelajaran_id' => $mapelModels['THF']->id, 'guru_id' => $guru3->id, 'hari' => 'Jumat', 'jam_mulai' => '07:30', 'jam_selesai' => '08:45'],
        ];
        foreach ($jadwalKelas4 as $j) {
            JadwalPelajaran::create($j);
        }

        // ── 10. JADWAL PELAJARAN (Kelas 5) ──
        $jadwalKelas5 = [
            ['kelas_id' => $kelas5->id, 'pelajaran_id' => $mapelModels['BIG']->id, 'guru_id' => $guru1->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
            ['kelas_id' => $kelas5->id, 'pelajaran_id' => $mapelModels['BAR']->id, 'guru_id' => $guru2->id, 'hari' => 'Senin', 'jam_mulai' => '09:00', 'jam_selesai' => '10:30'],
            ['kelas_id' => $kelas5->id, 'pelajaran_id' => $mapelModels['MTK']->id, 'guru_id' => $guru3->id, 'hari' => 'Selasa', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
        ];
        foreach ($jadwalKelas5 as $j) {
            JadwalPelajaran::create($j);
        }

        // ── 11. JADWAL PELAJARAN (Kelas 6) ──
        $jadwalKelas6 = [
            ['kelas_id' => $kelas6->id, 'pelajaran_id' => $mapelModels['IPA']->id, 'guru_id' => $guru4->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
            ['kelas_id' => $kelas6->id, 'pelajaran_id' => $mapelModels['IPS']->id, 'guru_id' => $guru5->id, 'hari' => 'Senin', 'jam_mulai' => '09:00', 'jam_selesai' => '10:30'],
            ['kelas_id' => $kelas6->id, 'pelajaran_id' => $mapelModels['BIN']->id, 'guru_id' => $guru1->id, 'hari' => 'Selasa', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00'],
        ];
        foreach ($jadwalKelas6 as $j) {
            JadwalPelajaran::create($j);
        }
    }
}
