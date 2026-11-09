<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\PresensiSholat;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiSholatTest extends TestCase
{
    use RefreshDatabase;

    private User $wali;

    private User $guruLain;

    private User $admin;

    private Kelas $kelas;

    private Siswa $andi;

    private Siswa $budi;

    private Siswa $citra;

    protected function setUp(): void
    {
        parent::setUp();

        config(['admin.usernames' => ['kepsek']]);

        $this->wali = $this->makeUser('waliA');
        $this->guruLain = $this->makeUser('guruB');
        $this->admin = $this->makeUser('kepsek');

        // Kelas 1 sekarang ikut Dzuhur (aturan lama hanya kelas 3-6).
        $this->kelas = Kelas::create(['nama_kelas' => 'Kelas 1A', 'wali_kelas_id' => $this->wali->id]);
        $this->andi = $this->makeSiswa('1001', 'Andi');
        $this->budi = $this->makeSiswa('1002', 'Budi');
        $this->citra = $this->makeSiswa('1003', 'Citra');
    }

    private function makeUser(string $username): User
    {
        return User::create([
            'name' => $username, 'nama_lengkap' => ucfirst($username), 'username' => $username,
            'email' => $username.'@s.id', 'password' => 'Password123', 'status' => 'Aktif', 'jenis_kelamin' => 'L',
        ]);
    }

    private function makeSiswa(string $nis, string $nama, string $status = 'Aktif'): Siswa
    {
        return Siswa::create([
            'nis' => $nis, 'nisn' => '9'.$nis, 'nama_siswa' => $nama, 'tanggal_lahir' => '2018-01-01',
            'jenis_kelamin' => 'L', 'kelas_id' => $this->kelas->id, 'status' => $status,
        ]);
    }

    private function statusSholat(Siswa $siswa, string $jenis, string $tanggal): ?string
    {
        return PresensiSholat::where('siswa_id', $siswa->id)
            ->where('jenis_sholat', $jenis)
            ->whereDate('tanggal', $tanggal)
            ->value('status_kehadiran');
    }

    public function test_wali_kelas_menyimpan_hadir_dan_tidak_hadir_dan_melewati_yang_kosong(): void
    {
        $this->actingAs($this->wali)->post('/presensi-sholat', [
            'kelas_id' => $this->kelas->id,
            'jenis_sholat' => 'Dhuha',
            'tanggal' => '2026-11-02',
            'presensi' => [
                ['siswa_id' => $this->andi->id, 'status' => 'Hadir'],
                ['siswa_id' => $this->budi->id, 'status' => 'Tidak Hadir'],
                ['siswa_id' => $this->citra->id],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Hadir', $this->statusSholat($this->andi, 'Dhuha', '2026-11-02'));
        $this->assertSame('Tidak Hadir', $this->statusSholat($this->budi, 'Dhuha', '2026-11-02'));
        $this->assertNull($this->statusSholat($this->citra, 'Dhuha', '2026-11-02'));
    }

    public function test_status_dan_jenis_lama_ditolak(): void
    {
        $this->actingAs($this->wali)->post('/presensi-sholat', [
            'kelas_id' => $this->kelas->id,
            'jenis_sholat' => 'Ashar',
            'tanggal' => '2026-11-02',
            'presensi' => [['siswa_id' => $this->andi->id, 'status' => 'S']],
        ])->assertSessionHasErrors(['jenis_sholat', 'presensi.0.status']);

        $this->assertSame(0, PresensiSholat::count());
    }

    public function test_tanggal_masa_depan_ditolak(): void
    {
        $this->actingAs($this->wali)->post('/presensi-sholat', [
            'kelas_id' => $this->kelas->id,
            'jenis_sholat' => 'Dzuhur',
            'tanggal' => now()->addDays(3)->toDateString(),
            'presensi' => [['siswa_id' => $this->andi->id, 'status' => 'Hadir']],
        ])->assertSessionHasErrors('tanggal');
    }

    public function test_guru_bukan_wali_tidak_bisa_mengisi_atau_membuka_kelas(): void
    {
        $this->actingAs($this->guruLain)->post('/presensi-sholat', [
            'kelas_id' => $this->kelas->id,
            'jenis_sholat' => 'Dzuhur',
            'tanggal' => '2026-11-02',
            'presensi' => [['siswa_id' => $this->andi->id, 'status' => 'Hadir']],
        ])->assertStatus(403);

        $this->actingAs($this->guruLain)->get('/presensi-sholat/'.$this->kelas->id)->assertStatus(403);
        $this->actingAs($this->admin)->get('/presensi-sholat/'.$this->kelas->id.'?jenis=Ashar')->assertOk();
    }

    public function test_halaman_kelas_menerima_jenis_lama_zuhur(): void
    {
        $this->actingAs($this->wali)->get('/presensi-sholat?jenis=Zuhur')
            ->assertOk()
            ->assertSee('Presensi Sholat Dzuhur')
            ->assertSee('Kelas 1A');
    }

    public function test_command_mencatat_tidak_hadir_hanya_untuk_yang_belum_tercatat(): void
    {
        $senin = '2026-11-02';
        $this->makeSiswa('1004', 'Dodi', 'Pindah');
        PresensiSholat::create([
            'siswa_id' => $this->andi->id, 'kelas_id' => $this->kelas->id, 'tanggal' => $senin,
            'jenis_sholat' => 'Dzuhur', 'status_kehadiran' => 'Hadir', 'recorded_by' => $this->wali->id,
        ]);

        $this->artisan('sholat:tandai-tidak-hadir', ['jenis' => 'Dzuhur', '--tanggal' => $senin])->assertSuccessful();

        $this->assertSame('Hadir', $this->statusSholat($this->andi, 'Dzuhur', $senin));
        $this->assertSame('Tidak Hadir', $this->statusSholat($this->budi, 'Dzuhur', $senin));
        $this->assertSame('Tidak Hadir', $this->statusSholat($this->citra, 'Dzuhur', $senin));
        $this->assertSame(3, PresensiSholat::count(), 'Siswa pindah tidak ikut dicatat');

        // Jalan dua kali tidak menggandakan data.
        $this->artisan('sholat:tandai-tidak-hadir', ['jenis' => 'Dzuhur', '--tanggal' => $senin])->assertSuccessful();
        $this->assertSame(3, PresensiSholat::count());
    }

    public function test_command_melewati_hari_minggu_dan_jenis_tidak_dikenal(): void
    {
        $this->artisan('sholat:tandai-tidak-hadir', ['jenis' => 'Dzuhur', '--tanggal' => '2026-11-08'])->assertSuccessful();
        $this->assertSame(0, PresensiSholat::count());

        $this->artisan('sholat:tandai-tidak-hadir', ['jenis' => 'Ashar', '--tanggal' => '2026-11-02'])->assertFailed();
    }

    public function test_rekap_menghitung_hadir_dan_tidak_hadir_termasuk_arsip_dhuha(): void
    {
        foreach (['2026-11-02' => 'Hadir', '2026-11-03' => 'Hadir', '2026-11-04' => 'Tidak Hadir'] as $tanggal => $status) {
            PresensiSholat::create([
                'siswa_id' => $this->andi->id, 'kelas_id' => $this->kelas->id, 'tanggal' => $tanggal,
                'jenis_sholat' => 'Dzuhur', 'status_kehadiran' => $status,
            ]);
        }
        PresensiSholat::create([
            'siswa_id' => $this->andi->id, 'kelas_id' => $this->kelas->id, 'tanggal' => '2026-10-01',
            'jenis_sholat' => 'Dhuha', 'status_kehadiran' => 'Tidak Hadir',
        ]);

        $this->actingAs($this->wali)
            ->get('/presensi-sholat/rekap?jenis=Dzuhur&rentang=bulan_ini&tanggal=2026-11-05&kelas_id='.$this->kelas->id)
            ->assertOk()
            ->assertViewHas('rekapData', fn ($data) => $data[$this->andi->id]['summary'] === [
                'hadir' => 2, 'tidak_hadir' => 1, 'total' => 3, 'persen' => 66.7,
            ]);

        $this->actingAs($this->wali)
            ->get('/presensi-sholat/rekap?jenis=Dhuha&rentang=bulan_ini&tanggal=2026-10-15&kelas_id='.$this->kelas->id)
            ->assertOk()
            ->assertDontSee('(arsip)')->assertDontSee('Sholat Ashar')
            ->assertViewHas('rekapData', fn ($data) => $data[$this->andi->id]['summary']['tidak_hadir'] === 1);

        $this->actingAs($this->guruLain)
            ->get('/presensi-sholat/rekap?kelas_id='.$this->kelas->id)
            ->assertStatus(403);
    }

    public function test_export_rekap_csv_dan_pdf_memakai_kolom_baru(): void
    {
        PresensiSholat::create([
            'siswa_id' => $this->andi->id, 'kelas_id' => $this->kelas->id, 'tanggal' => '2026-11-02',
            'jenis_sholat' => 'Ashar', 'status_kehadiran' => 'Hadir',
        ]);
        $query = '?jenis=Ashar&rentang=bulan_ini&tanggal=2026-11-05&kelas_id='.$this->kelas->id;

        $csv = $this->actingAs($this->wali)->get('/presensi-sholat/rekap/export/csv'.$query);
        $csv->assertOk();
        $this->assertStringContainsString('No,NIS,"Nama Siswa",Hadir,"Tidak Hadir",Total,"% Hadir"', $csv->streamedContent());
        $this->assertStringContainsString('Andi,1,0,1,100', $csv->streamedContent());

        $this->actingAs($this->wali)->get('/presensi-sholat/rekap/export/pdf'.$query)
            ->assertOk()
            ->assertSee('REKAPITULASI PRESENSI SHOLAT ASHAR')
            ->assertSee('Tidak Hadir');
    }

    public function test_siswa_baru_mendapat_qr_token_yang_tidak_ikut_terserialisasi(): void
    {
        $this->assertSame(40, strlen($this->andi->qr_token));
        $this->assertNotSame($this->andi->qr_token, $this->budi->qr_token);
        $this->assertArrayNotHasKey('qr_token', $this->andi->toArray());
        $this->assertStringStartsWith(Siswa::QR_PREFIX, $this->andi->qr_payload);
    }

    public function test_kartu_pelajar_hanya_admin_dan_ganti_qr_membatalkan_qr_lama(): void
    {
        $this->actingAs($this->wali)->get('/siswa/'.$this->andi->id.'/kartu')->assertStatus(403);
        $this->actingAs($this->wali)->post('/siswa/'.$this->andi->id.'/qr')->assertStatus(403);

        $this->actingAs($this->admin)->get('/siswa/'.$this->andi->id.'/kartu')
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee('QR SHOLAT');

        $lama = $this->andi->qr_token;
        $this->actingAs($this->admin)->post('/siswa/'.$this->andi->id.'/qr')
            ->assertRedirect('/siswa/'.$this->andi->id.'/kartu');

        $this->assertNotSame($lama, $this->andi->fresh()->qr_token);
    }
}
