<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PresensiKepsekAksesTest extends TestCase
{
    use RefreshDatabase;

    private User $kepsek;
    private User $guruLain;
    private Kelas $kelasKepsek;
    private Kelas $kelasLain;
    private JadwalPelajaran $jadwalKepsek;
    private JadwalPelajaran $jadwalLain;
    private Siswa $siswaKepsek;
    private Siswa $siswaLain;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['admin.usernames' => ['sinta']]);
        Carbon::setTestNow(Carbon::parse('2026-11-09 08:00:00', 'Asia/Pontianak'));

        // Dewi (Kepsek) wali Kelas 1
        $this->kepsek = User::create([
            'name' => 'Dewi',
            'nama_lengkap' => 'Dewi Quraisyinta, S.Pd.',
            'username' => 'sinta',
            'email' => 'sinta@s.id',
            'password' => 'Rahasia123',
            'status' => 'Aktif',
            'jenis_kelamin' => 'P',
        ]);

        // Guru Lain wali Kelas 3
        $this->guruLain = User::create([
            'name' => 'Dwi',
            'nama_lengkap' => 'Dwi Robiatul, S.Pd.',
            'username' => 'dwi',
            'email' => 'dwi@s.id',
            'password' => 'Rahasia123',
            'status' => 'Aktif',
            'jenis_kelamin' => 'P',
        ]);

        $pai = MataPelajaran::create(['kode_pelajaran' => 'PAI', 'nama_pelajaran' => 'Pendidikan Agama Islam']);
        $ips = MataPelajaran::create(['kode_pelajaran' => 'IPS', 'nama_pelajaran' => 'IPS']);

        $this->kelasKepsek = Kelas::create(['nama_kelas' => 'Kelas 1', 'wali_kelas_id' => $this->kepsek->id]);
        $this->kelasLain = Kelas::create(['nama_kelas' => 'Kelas 3', 'wali_kelas_id' => $this->guruLain->id]);

        $this->jadwalKepsek = JadwalPelajaran::create([
            'kelas_id' => $this->kelasKepsek->id,
            'pelajaran_id' => $pai->id,
            'guru_id' => $this->kepsek->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);

        $this->jadwalLain = JadwalPelajaran::create([
            'kelas_id' => $this->kelasLain->id,
            'pelajaran_id' => $ips->id,
            'guru_id' => $this->guruLain->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);

        $this->siswaKepsek = Siswa::create([
            'nis' => '1001',
            'nisn' => '2001',
            'nama_siswa' => 'Siswa Kelas 1',
            'tanggal_lahir' => '2019-01-01',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelasKepsek->id,
            'status' => 'Aktif',
            'nama_wali' => 'Wali 1',
        ]);

        $this->siswaLain = Siswa::create([
            'nis' => '3001',
            'nisn' => '4001',
            'nama_siswa' => 'Siswa Kelas 3',
            'tanggal_lahir' => '2017-01-01',
            'jenis_kelamin' => 'P',
            'kelas_id' => $this->kelasLain->id,
            'status' => 'Aktif',
            'nama_wali' => 'Wali 3',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_kepala_sekolah_hanya_melihat_kelas_yang_diampu_di_pilih_kelas(): void
    {
        $res = $this->actingAs($this->kepsek)->get('/presensi');
        $res->assertOk();
        $res->assertSee('Kelas 1');
        $res->assertDontSee('Kelas 3');
    }

    public function test_kepala_sekolah_dilarang_membuka_form_presensi_kelas_lain(): void
    {
        // Kelas 1 (milik sendiri) boleh
        $this->actingAs($this->kepsek)->get('/presensi/' . $this->kelasKepsek->id)->assertOk();

        // Kelas 3 (bukan yang diampu) dilarang 403
        $this->actingAs($this->kepsek)->get('/presensi/' . $this->kelasLain->id)->assertStatus(403);
    }

    public function test_kepala_sekolah_dilarang_simpan_presensi_kelas_lain(): void
    {
        $payloadLain = [
            'jadwal_id' => $this->jadwalLain->id,
            'tanggal' => '2026-11-09',
            'presensi' => [
                ['siswa_id' => $this->siswaLain->id, 'status' => 'H'],
            ],
        ];

        // Ditolak 403 saat submit ke jadwal kelas lain
        $this->actingAs($this->kepsek)->post('/presensi', $payloadLain)->assertStatus(403);

        // Boleh submit ke kelas sendiri
        $payloadSendiri = [
            'jadwal_id' => $this->jadwalKepsek->id,
            'tanggal' => '2026-11-09',
            'presensi' => [
                ['siswa_id' => $this->siswaKepsek->id, 'status' => 'H'],
            ],
        ];

        $this->actingAs($this->kepsek)->post('/presensi', $payloadSendiri)->assertRedirect();
    }
}

