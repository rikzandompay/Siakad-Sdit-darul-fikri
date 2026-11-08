<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_security_guards_block_inactive_login_and_cross_class_tampering(): void
    {
        $inactive = User::create([
            'name' => 'Pensiun', 'nama_lengkap' => 'Guru Pensiun', 'username' => 'gpensiun',
            'email' => 'p@s.id', 'password' => 'Password123', 'status' => 'Pensiun', 'jenis_kelamin' => 'L',
        ]);
        $this->post('/login', ['username' => 'gpensiun', 'password' => 'Password123'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();

        $guruA = User::create([
            'name' => 'GuruA', 'nama_lengkap' => 'Guru A', 'username' => 'gurua',
            'email' => 'a@s.id', 'password' => 'Password123', 'status' => 'Aktif', 'jenis_kelamin' => 'L',
        ]);
        $guruB = User::create([
            'name' => 'GuruB', 'nama_lengkap' => 'Guru B', 'username' => 'gurub',
            'email' => 'b@s.id', 'password' => 'Password123', 'status' => 'Aktif', 'jenis_kelamin' => 'L',
        ]);
        $ta = TahunAjaran::create(['nama_semester' => '2025/2026 - Ganjil', 'status_aktif' => 'Y']);
        $mapel = MataPelajaran::create(['kode_pelajaran' => 'MTK', 'nama_pelajaran' => 'Matematika']);
        $kelasA = Kelas::create(['nama_kelas' => 'Kelas 1', 'wali_kelas_id' => $guruA->id]);
        $kelasB = Kelas::create(['nama_kelas' => 'Kelas 2', 'wali_kelas_id' => $guruB->id]);
        $jadwalA = JadwalPelajaran::create([
            'kelas_id' => $kelasA->id, 'pelajaran_id' => $mapel->id, 'guru_id' => $guruA->id,
            'hari' => 'Senin', 'jam_mulai' => '07:00', 'jam_selesai' => '08:00',
        ]);
        $siswaB = Siswa::create([
            'nis' => '1002', 'nisn' => '2002', 'nama_siswa' => '=CMD()', 'tanggal_lahir' => '2016-01-01',
            'jenis_kelamin' => 'L', 'kelas_id' => $kelasB->id, 'status' => 'Aktif',
        ]);

        // Non-zero key bypass attempt on nilai.store must return 403
        $bab = ['tp1' => 80, 'tp2' => 80, 'tp3' => 80, 'tp4' => 80, 'uh' => 80];
        $this->actingAs($guruA)->post('/nilai', [
            'tahun_ajaran_id' => $ta->id,
            'pelajaran_id' => $mapel->id,
            'nilai' => [5 => [
                'siswa_id' => $siswaB->id,
                'formatif' => ['bab1' => $bab, 'bab2' => $bab, 'bab3' => $bab, 'bab4' => $bab],
                'sas' => 80, 'kehadiran' => 20,
            ]],
        ])->assertStatus(403);

        // Cross-class siswa_id in presensi.store must fail validation
        $this->actingAs($guruA)->post('/presensi', [
            'jadwal_id' => $jadwalA->id,
            'tanggal' => '2026-07-20',
            'presensi' => [['siswa_id' => $siswaB->id, 'status' => 'H']],
        ])->assertSessionHasErrors('presensi.0.siswa_id');

        $this->actingAs($guruA)->put('/siswa/'.$siswaB->id, [])->assertStatus(403);
        $this->actingAs($guruA)->delete('/siswa/'.$siswaB->id)->assertStatus(403);

        $reserved = User::create([
            'name' => 'Guru Biasa', 'nama_lengkap' => 'Guru Biasa', 'username' => 'guru_biasa',
            'email' => 'biasa@s.id', 'password' => 'Password123', 'status' => 'Aktif', 'jenis_kelamin' => 'P',
        ]);
        $this->assertFalse($reserved->isAdmin());
    }
}
