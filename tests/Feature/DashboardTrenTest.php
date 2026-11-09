<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardTrenTest extends TestCase
{
    use RefreshDatabase;

    private User $kepsek;

    private User $guruA;

    private User $guruB;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['admin.usernames' => ['kepsek']]);
        Carbon::setTestNow(Carbon::parse('2026-11-09 08:00:00', 'Asia/Pontianak'));

        $this->kepsek = User::create(['name' => 'kepsek', 'nama_lengkap' => 'Kepala Sekolah', 'username' => 'kepsek', 'email' => 'k@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->guruA = User::create(['name' => 'a', 'nama_lengkap' => 'Guru A', 'username' => 'guru-a', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->guruB = User::create(['name' => 'b', 'nama_lengkap' => 'Guru B', 'username' => 'guru-b', 'email' => 'b@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $mtk = MataPelajaran::create(['kode_pelajaran' => 'MTK', 'nama_pelajaran' => 'Matematika']);

        foreach ([[$this->guruA, 'Kelas 1'], [$this->guruB, 'Kelas 2']] as $i => [$guru, $nama]) {
            $kelas = Kelas::create(['nama_kelas' => $nama, 'wali_kelas_id' => $guru->id]);
            $jadwal = JadwalPelajaran::create(['kelas_id' => $kelas->id, 'pelajaran_id' => $mtk->id, 'guru_id' => $guru->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00']);
            $siswa = Siswa::create(['nis' => '10'.$i, 'nisn' => '20'.$i, 'nama_siswa' => 'Siswa '.$i, 'tanggal_lahir' => '2018-01-01', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas->id, 'status' => 'Aktif', 'nama_wali' => 'Wali']);
            // Kelas 1: hadir semua di 3 hari. Kelas 2: hanya satu hari presensi, alpa.
            $hari = $i === 0 ? ['2026-11-04', '2026-11-05', '2026-11-06'] : ['2026-11-06'];
            foreach ($hari as $tgl) {
                Presensi::create(['jadwal_id' => $jadwal->id, 'siswa_id' => $siswa->id, 'tanggal' => $tgl, 'status_kehadiran' => $i === 0 ? 'H' : 'A']);
            }
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_kepala_sekolah_melihat_tren_seluruh_sekolah(): void
    {
        $res = $this->actingAs($this->kepsek)->get('/dashboard')->assertOk()
            ->assertSee('Kehadiran siswa 30 hari terakhir')->assertSee('Seluruh sekolah');

        $tren = collect($res->viewData('trenKehadiran'));
        $this->assertSame(['2026-11-04', '2026-11-05', '2026-11-06'], $tren->pluck('tanggal')->all());
        $this->assertSame([100.0, 100.0, 50.0], $tren->pluck('persen')->all(), 'Tanggal 6: satu hadir, satu alpa');
    }

    public function test_guru_hanya_melihat_kelasnya_sendiri(): void
    {
        $a = collect($this->actingAs($this->guruA)->get('/dashboard')->assertOk()->assertSee('Kelas Anda')->viewData('trenKehadiran'));
        $this->assertCount(3, $a);
        $this->assertSame([100.0], $a->pluck('persen')->unique()->values()->all());

        // Guru B hanya punya satu hari presensi: belum cukup untuk grafik.
        Cache::flush();
        $this->actingAs($this->guruB)->get('/dashboard')->assertOk()->assertSee('Grafik muncul setelah ada presensi kelas di minimal dua hari');
    }

    public function test_data_lebih_dari_30_hari_tidak_ikut(): void
    {
        $jadwal = JadwalPelajaran::first();
        Presensi::create(['jadwal_id' => $jadwal->id, 'siswa_id' => Siswa::first()->id, 'tanggal' => '2026-09-01', 'status_kehadiran' => 'H']);

        $tren = collect($this->actingAs($this->kepsek)->get('/dashboard')->viewData('trenKehadiran'));
        $this->assertNotContains('2026-09-01', $tren->pluck('tanggal')->all());
    }
}
