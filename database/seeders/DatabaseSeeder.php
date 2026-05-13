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
            'name' => 'Guru Satu',
            'email' => 'guru1@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'nip' => '198001012010011001',
            'nama_lengkap' => 'Ustadz/Ustadzah Guru Satu, S.Pd.',
            'username' => 'guru1',
            'no_hp' => '08110000001',
        ]);

        $guru2 = User::create([
            'name' => 'Guru Dua',
            'email' => 'guru2@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'nip' => '198102022011012002',
            'nama_lengkap' => 'Ustadz/Ustadzah Guru Dua, S.Pd.',
            'username' => 'guru2',
            'no_hp' => '08110000002',
        ]);

        $guru3 = User::create([
            'name' => 'Guru Tiga',
            'email' => 'guru3@sekolah.sch.id',
            'password' => Hash::make('password123'),
            'nip' => '198203032012011003',
            'nama_lengkap' => 'Ustadz/Ustadzah Guru Tiga, S.Pd.',
            'username' => 'guru3',
            'no_hp' => '08110000003',
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
        // Kelas 1-2: Wali Kelas = Guru Satu
        $kelas1 = Kelas::create(['nama_kelas' => 'Kelas 1', 'wali_kelas_id' => $guru1->id]);
        $kelas2 = Kelas::create(['nama_kelas' => 'Kelas 2', 'wali_kelas_id' => $guru1->id]);
        // Kelas 3-4: Wali Kelas = Guru Dua
        $kelas3 = Kelas::create(['nama_kelas' => 'Kelas 3', 'wali_kelas_id' => $guru2->id]);
        $kelas4 = Kelas::create(['nama_kelas' => 'Kelas 4', 'wali_kelas_id' => $guru2->id]);
        // Kelas 5-6: Wali Kelas = Guru Tiga
        $kelas5 = Kelas::create(['nama_kelas' => 'Kelas 5', 'wali_kelas_id' => $guru3->id]);
        $kelas6 = Kelas::create(['nama_kelas' => 'Kelas 6', 'wali_kelas_id' => $guru3->id]);

        // ── 5. SISWA ──
        $siswaData = [
            // ── KELAS I ──
            ['nis' => '202101001', 'nisn' => '0011223344', 'nama_siswa' => 'Siswa Dummy Satu', 'tanggal_lahir' => '2018-05-10', 'alamat' => 'Jl. Pendidikan No. 1', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas1->id, 'nama_wali' => 'Wali Satu', 'no_hp_wali' => '08110000001'],
            ['nis' => '202101002', 'nisn' => '0011223345', 'nama_siswa' => 'Siswa Dummy Dua',  'tanggal_lahir' => '2018-08-15', 'alamat' => 'Jl. Pendidikan No. 2', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas1->id, 'nama_wali' => 'Wali Dua',  'no_hp_wali' => '08110000002'],
            
            // ── KELAS II ──
            ['nis' => '202102001', 'nisn' => '0021223344', 'nama_siswa' => 'Siswa Dummy Tiga', 'tanggal_lahir' => '2017-02-20', 'alamat' => 'Jl. Merdeka No. 3',    'jenis_kelamin' => 'L', 'kelas_id' => $kelas2->id, 'nama_wali' => 'Wali Tiga', 'no_hp_wali' => '08110000003'],
            ['nis' => '202102002', 'nisn' => '0021223345', 'nama_siswa' => 'Siswa Dummy Empat','tanggal_lahir' => '2017-11-12', 'alamat' => 'Jl. Merdeka No. 4',    'jenis_kelamin' => 'P', 'kelas_id' => $kelas2->id, 'nama_wali' => 'Wali Empat','no_hp_wali' => '08110000004'],

            // ── KELAS III ──
            ['nis' => '202103001', 'nisn' => '0031223344', 'nama_siswa' => 'Siswa Dummy Lima', 'tanggal_lahir' => '2016-04-05', 'alamat' => 'Jl. Pahlawan No. 5',   'jenis_kelamin' => 'L', 'kelas_id' => $kelas3->id, 'nama_wali' => 'Wali Lima', 'no_hp_wali' => '08110000005'],
            ['nis' => '202103002', 'nisn' => '0031223345', 'nama_siswa' => 'Siswa Dummy Enam', 'tanggal_lahir' => '2016-09-25', 'alamat' => 'Jl. Pahlawan No. 6',   'jenis_kelamin' => 'P', 'kelas_id' => $kelas3->id, 'nama_wali' => 'Wali Enam', 'no_hp_wali' => '08110000006'],

            // ── KELAS IV ──
            ['nis' => '202104001', 'nisn' => '0041223344', 'nama_siswa' => 'Siswa Dummy Tujuh','tanggal_lahir' => '2015-01-10', 'alamat' => 'Jl. Bangsa No. 7',     'jenis_kelamin' => 'L', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Wali Tujuh','no_hp_wali' => '08110000007'],
            ['nis' => '202104002', 'nisn' => '0041223345', 'nama_siswa' => 'Siswa Dummy Delapan','tanggal_lahir'=> '2015-07-30', 'alamat' => 'Jl. Bangsa No. 8',     'jenis_kelamin' => 'P', 'kelas_id' => $kelas4->id, 'nama_wali' => 'Wali Delapan','no_hp_wali'=> '08110000008'],

            // ── KELAS V ──
            ['nis' => '202105001', 'nisn' => '0051223344', 'nama_siswa' => 'Siswa Dummy Sembilan','tanggal_lahir'=>'2014-03-15','alamat' => 'Jl. Nusantara No. 9',  'jenis_kelamin' => 'L', 'kelas_id' => $kelas5->id, 'nama_wali' => 'Wali Sembilan','no_hp_wali'=>'08110000009'],
            ['nis' => '202105002', 'nisn' => '0051223345', 'nama_siswa' => 'Siswa Dummy Sepuluh','tanggal_lahir'=>'2014-10-20','alamat' => 'Jl. Nusantara No. 10', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas5->id, 'nama_wali' => 'Wali Sepuluh','no_hp_wali'=>'08110000010'],

            // ── KELAS VI ──
            ['nis' => '202106001', 'nisn' => '0061223344', 'nama_siswa' => 'Siswa Dummy Sebelas','tanggal_lahir'=>'2013-06-01','alamat' => 'Jl. Cerdas No. 11',    'jenis_kelamin' => 'L', 'kelas_id' => $kelas6->id, 'nama_wali' => 'Wali Sebelas','no_hp_wali'=>'08110000011'],
            ['nis' => '202106002', 'nisn' => '0061223345', 'nama_siswa' => 'Siswa Dummy Dua Belas','tanggal_lahir'=>'2013-12-15','alamat'=>'Jl. Cerdas No. 12',    'jenis_kelamin' => 'P', 'kelas_id' => $kelas6->id, 'nama_wali' => 'Wali Dua Belas','no_hp_wali'=>'08110000012'],
        ];
        foreach ($siswaData as $s) {
            Siswa::create($s);
        }

        // ── 6. JADWAL PELAJARAN ──

        // Kelas 1-2: Guru Pengampu = Guru Satu (BI, MTK, Agama Islam, P. Pancasila, Seni)
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

        // Kelas 3-4: Guru Pengampu = Guru Dua (MTK, BI, Agama Islam, IPAS, Seni Rupa, B.Ing, B.Arab, P.Pancasila, PJOK)
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

        // Kelas 5-6: Guru Pengampu = Guru Tiga (MTK, BI, Agama Islam, IPAS, Mulok, Seni Rupa, B.Ing, B.Arab, P.Pancasila, PJOK)
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
