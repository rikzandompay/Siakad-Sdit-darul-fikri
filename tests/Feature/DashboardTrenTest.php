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

    private JadwalPelajaran $jadwalKelas1;

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

        // Kelas 1: wali dan pengajar guru A, hadir 3 hari. Kelas 2: wali dan pengajar guru B, alpa 1 hari.
        $rencana = [
            [$this->guruA, 'Kelas 1', 'H', ['2026-11-04', '2026-11-05', '2026-11-06']],
            [$this->guruB, 'Kelas 2', 'A', ['2026-11-06']],
        ];
        foreach ($rencana as $i => [$guru, $nama, $status, $hari]) {
            $kelas = Kelas::create(['nama_kelas' => $nama, 'wali_kelas_id' => $guru->id]);
            $jadwal = JadwalPelajaran::create(['kelas_id' => $kelas->id, 'pelajaran_id' => $mtk->id, 'guru_id' => $guru->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00']);
            if ($i === 0) {
                $this->jadwalKelas1 = $jadwal;
            }
            $siswa = Siswa::create(['nis' => '10'.$i, 'nisn' => '20'.$i, 'nama_siswa' => 'Siswa '.$i, 'tanggal_lahir' => '2018-01-01', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas->id, 'status' => 'Aktif', 'nama_wali' => 'Wali']);
            foreach ($hari as $tgl) {
                Presensi::create(['jadwal_id' => $jadwal->id, 'siswa_id' => $siswa->id, 'tanggal' => $tgl, 'status_kehadiran' => $status]);
            }
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_kepala_sekolah_melihat_kehadiran_per_kelas_seluruh_sekolah(): void
    {
        $res = $this->actingAs($this->kepsek)->get('/dashboard')->assertOk()
            ->assertSee('Kehadiran per kelas, 30 hari terakhir')->assertSee('Seluruh sekolah');

        $kelas = collect($res->viewData('trenKehadiran'))->keyBy('nama_kelas');
        $this->assertSame(2, $kelas->count());
        $this->assertSame([3, 3], [$kelas['Kelas 1']['total'], $kelas['Kelas 1']['hadir']]);
        $this->assertSame([1, 0, 1], [$kelas['Kelas 2']['total'], $kelas['Kelas 2']['hadir'], $kelas['Kelas 2']['alpa']]);
    }

    public function test_guru_hanya_melihat_kelasnya_sendiri(): void
    {
        $a = collect($this->actingAs($this->guruA)->get('/dashboard')->assertOk()->assertSee('Kelas Anda')->viewData('trenKehadiran'));
        $this->assertSame(['Kelas 1'], $a->pluck('nama_kelas')->all());

        $b = collect($this->actingAs($this->guruB)->get('/dashboard')->assertOk()->viewData('trenKehadiran'));
        $this->assertSame(['Kelas 2'], $b->pluck('nama_kelas')->all());
    }

    public function test_data_lebih_dari_30_hari_tidak_ikut(): void
    {
        Presensi::create(['jadwal_id' => $this->jadwalKelas1->id, 'siswa_id' => Siswa::first()->id, 'tanggal' => '2026-09-01', 'status_kehadiran' => 'H']);

        $kelas1 = collect($this->actingAs($this->kepsek)->get('/dashboard')->viewData('trenKehadiran'))->firstWhere('nama_kelas', 'Kelas 1');
        $this->assertSame(3, $kelas1['total']);
    }

    public function test_tanpa_presensi_menampilkan_pesan_kosong(): void
    {
        $guruC = User::create(['name' => 'c', 'nama_lengkap' => 'Guru C', 'username' => 'guru-c', 'email' => 'c@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);

        $this->actingAs($guruC)->get('/dashboard')->assertOk()->assertSee('Belum ada presensi kelas dalam 30 hari terakhir');
    }

    public function test_presensi_baru_langsung_masuk_ke_grafik_tanpa_delay_cache(): void
    {
        $guruC = User::create(['name' => 'c', 'nama_lengkap' => 'Guru C', 'username' => 'guru-c', 'email' => 'c@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $mtk = MataPelajaran::where('kode_pelajaran', 'MTK')->first();
        $kelasC = Kelas::create(['nama_kelas' => 'Kelas 3', 'wali_kelas_id' => $guruC->id]);
        $jadwalC = JadwalPelajaran::create(['kelas_id' => $kelasC->id, 'pelajaran_id' => $mtk->id, 'guru_id' => $guruC->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00']);
        $siswaC = Siswa::create(['nis' => '103', 'nisn' => '203', 'nama_siswa' => 'Siswa 3', 'tanggal_lahir' => '2018-01-01', 'jenis_kelamin' => 'L', 'kelas_id' => $kelasC->id, 'status' => 'Aktif', 'nama_wali' => 'Wali']);

        // Awalnya dashboard kosong
        $this->actingAs($guruC)->get('/dashboard')->assertOk()->assertSee('Belum ada presensi kelas dalam 30 hari terakhir');

        // Guru C mengisi presensi via form
        $this->actingAs($guruC)->post('/presensi', [
            'jadwal_id' => $jadwalC->id,
            'tanggal' => '2026-11-09',
            'presensi' => [
                ['siswa_id' => $siswaC->id, 'status' => 'H'],
            ],
        ])->assertRedirect();

        // Buka dashboard lagi: langsung tampil Kelas 3 100% tanpa lag cache!
        $res = $this->actingAs($guruC)->get('/dashboard')->assertOk();
        $res->assertDontSee('Belum ada presensi kelas dalam 30 hari terakhir');
        $res->assertSee('Kelas 3');
        $res->assertSee('100%');
    }
}
