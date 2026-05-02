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
            'name' => 'Dwi Robiatul Adewiyah',
            'email' => 'dwi@darulfikri.sch.id',
            'password' => Hash::make('guru1234'),
            'nip' => '198602152011012002',
            'nama_lengkap' => 'Ustadzah Dwi Robiatul Adewiyah, S.Pd.',
            'username' => 'dwi',
            'no_hp' => '081234567891',
        ]);

        $guru3 = User::create([
            'name' => 'Alfiani Wahyuningsih',
            'email' => 'alfiani@darulfikri.sch.id',
            'password' => Hash::make('guru1234'),
            'nip' => '198703202012011003',
            'nama_lengkap' => 'Ustadzah Alfiani Wahyuningsih, S.Pd.',
            'username' => 'alfiani',
            'no_hp' => '081234567892',
        ]);

        // ── 2. TAHUN AJARAN ──
        TahunAjaran::create(['nama_semester' => '2022/2023 - Ganjil', 'status_aktif' => 'N']);
        TahunAjaran::create(['nama_semester' => '2022/2023 - Genap', 'status_aktif' => 'N']);
        TahunAjaran::create(['nama_semester' => '2023/2024 - Ganjil', 'status_aktif' => 'Y']);
        TahunAjaran::create(['nama_semester' => '2023/2024 - Genap', 'status_aktif' => 'N']);

        // ── 3. MATA PELAJARAN ──
        $mapels = [
            ['kode_pelajaran' => 'PAI', 'nama_pelajaran' => 'Pendidikan Agama Islam'],
            ['kode_pelajaran' => 'MTK', 'nama_pelajaran' => 'Matematika'],
            ['kode_pelajaran' => 'BIN', 'nama_pelajaran' => 'Bahasa Indonesia'],
            ['kode_pelajaran' => 'BIG', 'nama_pelajaran' => 'Bahasa Inggris'],
            ['kode_pelajaran' => 'IPAS', 'nama_pelajaran' => 'IPAS'],
            ['kode_pelajaran' => 'PJOK', 'nama_pelajaran' => 'PJOK (Olahraga)'],
            ['kode_pelajaran' => 'SRP', 'nama_pelajaran' => 'Seni Rupa'],
            ['kode_pelajaran' => 'SEN', 'nama_pelajaran' => 'Seni'],
            ['kode_pelajaran' => 'BAR', 'nama_pelajaran' => 'Bahasa Arab'],
            ['kode_pelajaran' => 'PPK', 'nama_pelajaran' => 'P. Pancasila'],
            ['kode_pelajaran' => 'MLK', 'nama_pelajaran' => 'Mulok'],
        ];
        $mapelModels = [];
        foreach ($mapels as $m) {
            $mapelModels[$m['kode_pelajaran']] = MataPelajaran::create($m);
        }

        // ── 4. KELAS ──
        // Kelas 1-2: Wali Kelas = Dewi Quraisyinta
        $kelas1 = Kelas::create(['nama_kelas' => 'Kelas 1', 'wali_kelas_id' => $guru1->id]);
        $kelas2 = Kelas::create(['nama_kelas' => 'Kelas 2', 'wali_kelas_id' => $guru1->id]);
        // Kelas 3-4: Wali Kelas = Dwi Robiatul Adewiyah
        $kelas3 = Kelas::create(['nama_kelas' => 'Kelas 3', 'wali_kelas_id' => $guru2->id]);
        $kelas4 = Kelas::create(['nama_kelas' => 'Kelas 4', 'wali_kelas_id' => $guru2->id]);
        // Kelas 5-6: Wali Kelas = Alfiani Wahyuningsih
        $kelas5 = Kelas::create(['nama_kelas' => 'Kelas 5', 'wali_kelas_id' => $guru3->id]);
        $kelas6 = Kelas::create(['nama_kelas' => 'Kelas 6', 'wali_kelas_id' => $guru3->id]);

        // ── 5. SISWA ──
        $siswaData = [
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
            // Kelas 4
            ['nis' => '202104001', 'nama_siswa' => 'Ahmad Hidayat', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Bpk. Hidayat', 'no_hp_wali' => '08111111001'],
            ['nis' => '202104002', 'nama_siswa' => 'Fatimah Az-Zahra', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Bpk. Zahra', 'no_hp_wali' => '08111111002'],
            ['nis' => '202104003', 'nama_siswa' => 'Muhammad Khairi', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Bpk. Khairi', 'no_hp_wali' => '08111111003'],
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

        // ── 6. JADWAL PELAJARAN ──

        // Kelas 1-2: Guru Pengampu = Dewi Quraisyinta (BI, MTK, Agama Islam, P. Pancasila, Seni)
        $mapelKelas12 = ['BIN', 'MTK', 'PAI', 'PPK', 'SEN'];
        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        foreach ([$kelas1, $kelas2] as $kls) {
            foreach ($mapelKelas12 as $i => $kode) {
                JadwalPelajaran::create([
                    'kelas_id' => $kls->id,
                    'pelajaran_id' => $mapelModels[$kode]->id,
                    'guru_id' => $guru1->id,
                    'hari' => $hariList[$i % count($hariList)],
                    'jam_mulai' => '07:30',
                    'jam_selesai' => '09:00',
                ]);
            }
        }

        // Kelas 3-4: Guru Pengampu = Dwi Robiatul Adewiyah (MTK, BI, Agama Islam, IPAS, Seni Rupa, B.Ing, B.Arab, P.Pancasila, PJOK)
        $mapelKelas34 = ['MTK', 'BIN', 'PAI', 'IPAS', 'SRP', 'BIG', 'BAR', 'PPK', 'PJOK'];
        foreach ([$kelas3, $kelas4] as $kls) {
            foreach ($mapelKelas34 as $i => $kode) {
                JadwalPelajaran::create([
                    'kelas_id' => $kls->id,
                    'pelajaran_id' => $mapelModels[$kode]->id,
                    'guru_id' => $guru2->id,
                    'hari' => $hariList[$i % count($hariList)],
                    'jam_mulai' => sprintf('%02d:30', 7 + intdiv($i, count($hariList)) * 2),
                    'jam_selesai' => sprintf('%02d:00', 9 + intdiv($i, count($hariList)) * 2),
                ]);
            }
        }

        // Kelas 5-6: Guru Pengampu = Alfiani Wahyuningsih (MTK, BI, Agama Islam, IPAS, Mulok, Seni Rupa, B.Ing, B.Arab, P.Pancasila, PJOK)
        $mapelKelas56 = ['MTK', 'BIN', 'PAI', 'IPAS', 'MLK', 'SRP', 'BIG', 'BAR', 'PPK', 'PJOK'];
        foreach ([$kelas5, $kelas6] as $kls) {
            foreach ($mapelKelas56 as $i => $kode) {
                JadwalPelajaran::create([
                    'kelas_id' => $kls->id,
                    'pelajaran_id' => $mapelModels[$kode]->id,
                    'guru_id' => $guru3->id,
                    'hari' => $hariList[$i % count($hariList)],
                    'jam_mulai' => sprintf('%02d:30', 7 + intdiv($i, count($hariList)) * 2),
                    'jam_selesai' => sprintf('%02d:00', 9 + intdiv($i, count($hariList)) * 2),
                ]);
            }
        }
    }
}
