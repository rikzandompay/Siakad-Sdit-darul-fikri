<?php

namespace Tests\Feature\Api;

use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\PengaturanSekolah;
use App\Models\Presensi;
use App\Models\PresensiGuru;
use App\Models\PresensiSholat;
use App\Models\Siswa;
use App\Models\User;
use App\Models\WajahGuru;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruApiTest extends TestCase
{
    use RefreshDatabase;

    private User $guru;

    private User $guruLain;

    private Kelas $kelas;

    private Kelas $kelasLain;

    private JadwalPelajaran $jadwal;

    private Siswa $aisyah;

    private Siswa $bilal;

    private Siswa $luar;

    // Titik sekolah dan titik ~42 m darinya.
    private const LAT = -0.0263;

    private const LNG = 109.3425;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['kepsek'], 'presensi_guru.kecualikan_username' => ['admin']]);
        // Senin, 9 Nov 2026 07:05 WIB.
        Carbon::setTestNow(Carbon::parse('2026-11-09 07:05:00', 'Asia/Pontianak'));

        $this->guru = User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ust. Ahmad Fauzi, S.Pd.', 'username' => 'ahmad', 'nip' => '19870512', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif', 'jenis_kelamin' => 'L']);
        $this->guruLain = User::create(['name' => 'lain', 'nama_lengkap' => 'Guru Lain', 'username' => 'lain', 'email' => 'l@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif', 'jenis_kelamin' => 'P']);
        $this->kelas = Kelas::create(['nama_kelas' => 'Kelas 5A', 'wali_kelas_id' => $this->guru->id]);
        $this->kelasLain = Kelas::create(['nama_kelas' => 'Kelas 4B', 'wali_kelas_id' => $this->guruLain->id]);
        $mtk = MataPelajaran::create(['kode_pelajaran' => 'MTK', 'nama_pelajaran' => 'Matematika']);
        $this->jadwal = JadwalPelajaran::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $mtk->id, 'guru_id' => $this->guru->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00']);
        $this->aisyah = $this->siswa('2024001', 'Aisyah Putri', $this->kelas, 'Siti Aminah');
        $this->bilal = $this->siswa('2024002', 'Bilal', $this->kelas, 'Budi');
        $this->luar = $this->siswa('2024003', 'Orang Luar', $this->kelasLain, 'Wali Luar');

        PengaturanSekolah::simpan([PengaturanSekolah::LATITUDE => self::LAT, PengaturanSekolah::LONGITUDE => self::LNG, PengaturanSekolah::RADIUS => 100, PengaturanSekolah::JAM_MASUK => '07:00']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function siswa(string $nis, string $nama, Kelas $kelas, string $wali): Siswa
    {
        return Siswa::create(['nis' => $nis, 'nisn' => '00'.$nis, 'nama_siswa' => $nama, 'tanggal_lahir' => '2016-01-01', 'jenis_kelamin' => 'P', 'kelas_id' => $kelas->id, 'status' => 'Aktif', 'nama_wali' => $wali]);
    }

    private function tokenGuru(?User $user = null): array
    {
        // Guard menyimpan user dari request sebelumnya; reset agar token baru benar-benar dipakai.
        $this->app['auth']->forgetGuards();
        $token = ($user ?? $this->guru)->createToken('test', ['guru'])->plainTextToken;

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    private function lokasi(float $offsetLat = 0.00038): array
    {
        return ['latitude' => self::LAT + $offsetLat, 'longitude' => self::LNG, 'akurasi' => 8, 'mock' => false, 'embedding' => $this->wajahDari(0.05), 'liveness' => true];
    }

    /** Template = sin(i); embedding kamera = template + derau kecil (bukan salinan). */
    private function wajahDari(float $derau): array
    {
        return array_map(fn ($i) => sin($i) + $derau * cos($i * 7), range(1, 192));
    }

    private function daftarkanWajah(?User $user = null): void
    {
        WajahGuru::create(['guru_id' => ($user ?? $this->guru)->id, 'template' => json_encode(array_map(fn ($i) => sin($i), range(1, 192))), 'didaftarkan_pada' => now()]);
    }

    // ── Login ─────────────────────────────────────────────────────────

    public function test_login_guru_dengan_username_atau_nip_dan_profil_kartu(): void
    {
        $res = $this->postJson('/api/v1/auth/login', ['peran' => 'guru', 'username' => '19870512', 'password' => 'Rahasia123', 'device_name' => 'Pixel']);
        $res->assertOk()->assertJsonPath('peran', 'guru')
            ->assertJsonPath('akun.nama', 'Ust. Ahmad Fauzi, S.Pd.')
            ->assertJsonPath('akun.inisial', 'AF')
            ->assertJsonPath('akun.jabatan', 'Guru')
            ->assertJsonPath('akun.wali_kelas.0', 'Kelas 5A')
            ->assertJsonPath('akun.mata_pelajaran.0', 'Matematika');

        $this->withHeader('Authorization', 'Bearer '.$res->json('token'))->getJson('/api/v1/guru/dashboard')->assertOk();
        $this->postJson('/api/v1/auth/login', ['peran' => 'guru', 'username' => 'ahmad', 'password' => 'Rahasia123'])->assertOk();
    }

    public function test_login_salah_lima_kali_mengunci_akun_dan_guru_nonaktif_ditolak(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['peran' => 'guru', 'username' => 'ahmad', 'password' => 'salah'])->assertStatus(422);
        }
        $this->postJson('/api/v1/auth/login', ['peran' => 'guru', 'username' => 'ahmad', 'password' => 'Rahasia123'])
            ->assertStatus(429)->assertJsonValidationErrors('username');

        $this->guruLain->update(['status' => 'Pensiun']);
        $this->postJson('/api/v1/auth/login', ['peran' => 'guru', 'username' => 'lain', 'password' => 'Rahasia123'])->assertStatus(422);
    }

    public function test_login_wali_nis_dan_tanggal_lahir_tidak_bisa_akses_endpoint_guru(): void
    {
        $res = $this->postJson('/api/v1/auth/login', ['peran' => 'wali', 'username' => '2024001', 'password' => '16-01-01']);
        $res->assertOk()->assertJsonPath('peran', 'wali')->assertJsonPath('akun.anak.0.nama', 'Aisyah Putri');
        $this->assertStringStartsWith(Siswa::QR_PREFIX, $res->json('akun.anak.0.qr'));

        $this->withHeader('Authorization', 'Bearer '.$res->json('token'))->getJson('/api/v1/guru/dashboard')->assertStatus(403);
        $this->postJson('/api/v1/auth/login', ['peran' => 'wali', 'username' => '2024001', 'password' => 'Siti Aminah'])->assertStatus(422);
        $this->postJson('/api/v1/auth/login', ['peran' => 'wali', 'username' => '2024001', 'password' => '16-01-02'])->assertStatus(422);
        // Guru tidak bisa login di tab wali dan sebaliknya.
        $this->postJson('/api/v1/auth/login', ['peran' => 'wali', 'username' => 'ahmad', 'password' => 'Rahasia123'])->assertStatus(422);
    }

    public function test_tanpa_token_ditolak_dan_logout_mencabut_token(): void
    {
        $this->getJson('/api/v1/guru/dashboard')->assertStatus(401);
        $h = $this->tokenGuru();
        $this->withHeaders($h)->postJson('/api/v1/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withHeaders($h)->getJson('/api/v1/guru/dashboard')->assertStatus(401);
    }

    // ── Dashboard & presensi kelas ────────────────────────────────────

    public function test_dashboard_dan_alur_presensi_kelas(): void
    {
        $h = $this->tokenGuru();

        $this->withHeaders($h)->getJson('/api/v1/guru/dashboard')->assertOk()
            ->assertJsonPath('hari', 'Senin')
            ->assertJsonPath('jadwal_hari_ini.0.kelas', 'Kelas 5A')
            ->assertJsonPath('presensi_saya.status', null);

        $this->withHeaders($h)->getJson('/api/v1/guru/presensi/kelas')->assertOk()
            ->assertJsonPath('data.0.singkatan', '5A')->assertJsonPath('data.0.jumlah_siswa', 2)->assertJsonCount(1, 'data');
        $this->withHeaders($h)->getJson('/api/v1/guru/presensi/kelas/'.$this->kelas->id.'/jadwal')->assertOk()
            ->assertJsonPath('data.0.sudah_diabsen', false);
        $this->withHeaders($h)->getJson('/api/v1/guru/presensi/jadwal/'.$this->jadwal->id)->assertOk()->assertJsonCount(2, 'data');

        $this->withHeaders($h)->postJson('/api/v1/guru/presensi/jadwal/'.$this->jadwal->id, ['tanggal' => '2026-11-09', 'presensi' => [
            ['siswa_id' => $this->aisyah->id, 'status' => 'H'], ['siswa_id' => $this->bilal->id, 'status' => 'S'],
        ]])->assertOk()->assertJsonPath('rekap.hadir', 1)->assertJsonPath('rekap.sakit', 1);

        $this->assertSame(2, Presensi::count());
        $this->withHeaders($h)->getJson('/api/v1/guru/presensi/kelas/'.$this->kelas->id.'/jadwal')->assertJsonPath('data.0.sudah_diabsen', true);
        $this->withHeaders($h)->getJson('/api/v1/guru/dashboard')->assertJsonPath('kehadiran_siswa.persen_hadir', 50);

        // Siswa kelas lain dan jadwal milik orang lain ditolak.
        $this->withHeaders($h)->postJson('/api/v1/guru/presensi/jadwal/'.$this->jadwal->id, ['tanggal' => '2026-11-09', 'presensi' => [['siswa_id' => $this->luar->id, 'status' => 'H']]])
            ->assertJsonValidationErrors('presensi.0.siswa_id');
        $this->withHeaders($this->tokenGuru($this->guruLain))->postJson('/api/v1/guru/presensi/jadwal/'.$this->jadwal->id, ['tanggal' => '2026-11-09', 'presensi' => [['siswa_id' => $this->aisyah->id, 'status' => 'A']]])
            ->assertStatus(403);
    }

    public function test_admin_kepala_sekolah_punya_akses_penuh_ke_semua_kelas_dan_jadwal(): void
    {
        $sinta = User::create(['name' => 'kepsek', 'nama_lengkap' => 'Dewi Kepala Sekolah', 'username' => 'kepsek', 'email' => 'k@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif', 'jenis_kelamin' => 'P']);
        $h = $this->tokenGuru($sinta);

        // Sinta tidak mengajar apa pun, tetapi admin melihat semua kelas.
        $this->withHeaders($h)->getJson('/api/v1/guru/presensi/kelas')->assertOk()->assertJsonCount(2, 'data');
        $this->withHeaders($h)->getJson('/api/v1/guru/presensi/kelas/'.$this->kelas->id.'/jadwal')->assertOk()
            ->assertJsonPath('semua_guru', true)->assertJsonPath('data.0.guru', 'Ust. Ahmad Fauzi, S.Pd.');
        $this->withHeaders($h)->postJson('/api/v1/guru/presensi/jadwal/'.$this->jadwal->id, ['tanggal' => '2026-11-09', 'presensi' => [['siswa_id' => $this->aisyah->id, 'status' => 'H']]])->assertOk();
        $this->withHeaders($h)->getJson('/api/v1/guru/sholat/kelas')->assertOk()->assertJsonCount(2, 'data');
        $this->withHeaders($h)->postJson('/api/v1/guru/sholat/kelas/'.$this->kelas->id.'/scan', ['jenis' => 'Dzuhur', 'qr' => $this->aisyah->qr_payload])->assertOk();

        // Guru biasa tetap terbatas.
        // Guru biasa hanya melihat kelas yang dia walikan (Kelas 4B), bukan kelas 5A.
        $this->withHeaders($this->tokenGuru($this->guruLain))->getJson('/api/v1/guru/presensi/kelas')
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.nama', 'Kelas 4B');
    }

    public function test_pengajar_bukan_wali_kelas_tidak_bisa_mengisi_presensi(): void
    {
        // Guru Lain mengajar di kelas 5A tetapi bukan wali kelasnya.
        $mtk2 = MataPelajaran::create(['kode_pelajaran' => 'IPA', 'nama_pelajaran' => 'IPA']);
        $jadwalLain = JadwalPelajaran::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $mtk2->id, 'guru_id' => $this->guruLain->id, 'hari' => 'Senin', 'jam_mulai' => '09:30', 'jam_selesai' => '10:30']);
        $payload = ['tanggal' => '2026-11-09', 'presensi' => [['siswa_id' => $this->aisyah->id, 'status' => 'H']]];

        $this->withHeaders($this->tokenGuru($this->guruLain))->postJson('/api/v1/guru/presensi/jadwal/'.$jadwalLain->id, $payload)->assertStatus(403);
        $this->withHeaders($this->tokenGuru($this->guruLain))->getJson('/api/v1/guru/presensi/kelas/'.$this->kelas->id.'/jadwal')->assertStatus(403);

        // Wali kelas mengisi jadwal guru lain di kelasnya.
        $this->withHeaders($this->tokenGuru())->postJson('/api/v1/guru/presensi/jadwal/'.$jadwalLain->id, $payload)->assertOk();
    }

    // ── Sholat (scan QR) ──────────────────────────────────────────────

    public function test_scan_qr_mencatat_hadir_dan_menolak_qr_tidak_valid(): void
    {
        $h = $this->tokenGuru();
        $url = '/api/v1/guru/sholat/kelas/'.$this->kelas->id;

        $this->withHeaders($h)->getJson('/api/v1/guru/sholat/kelas')->assertOk()->assertJsonCount(1, 'data');
        $this->withHeaders($h)->getJson($url)->assertOk()->assertJsonPath('data.1.jenis', 'Dzuhur')->assertJsonPath('data.1.sudah_masuk_waktu', false);

        $this->withHeaders($h)->postJson($url.'/scan', ['jenis' => 'Dzuhur', 'qr' => $this->aisyah->qr_payload])->assertOk()
            ->assertJsonPath('siswa.nama', 'Aisyah Putri')->assertJsonPath('status', 'Hadir')
            ->assertJsonPath('sudah_tercatat_sebelumnya', false)->assertJsonPath('hadir', 1)->assertJsonPath('total', 2);
        $this->withHeaders($h)->postJson($url.'/scan', ['jenis' => 'Dzuhur', 'qr' => $this->aisyah->qr_payload])->assertOk()
            ->assertJsonPath('sudah_tercatat_sebelumnya', true);
        $this->assertSame(1, PresensiSholat::count());
        $this->assertSame($this->guru->id, PresensiSholat::first()->recorded_by);

        $this->withHeaders($h)->postJson($url.'/scan', ['jenis' => 'Dzuhur', 'qr' => $this->luar->qr_payload])->assertJsonValidationErrors('qr');
        $this->withHeaders($h)->postJson($url.'/scan', ['jenis' => 'Dzuhur', 'qr' => 'MYDARFIK:SISWA:palsu'])->assertJsonValidationErrors('qr');
        $this->withHeaders($h)->postJson($url.'/scan', ['jenis' => 'Dzuhur', 'qr' => $this->aisyah->qr_token])->assertJsonValidationErrors('qr');
        $this->withHeaders($h)->postJson($url.'/scan', ['jenis' => 'Ashar', 'qr' => $this->bilal->qr_payload])->assertJsonValidationErrors('jenis');

        // Guru bukan wali kelas ini tidak boleh.
        $this->withHeaders($this->tokenGuru($this->guruLain))->postJson($url.'/scan', ['jenis' => 'Dzuhur', 'qr' => $this->bilal->qr_payload])->assertStatus(403);

        $this->app['auth']->forgetGuards();
        $this->withHeaders($h)->getJson($url.'/siswa?jenis=Dzuhur')->assertOk()->assertJsonPath('hadir', 1)
            ->assertJsonPath('data.0.status', 'Hadir')->assertJsonPath('data.1.status', null);
        $this->withHeaders($h)->postJson($url, ['jenis' => 'Dzuhur', 'presensi' => [['siswa_id' => $this->bilal->id, 'status' => 'Tidak Hadir']]])
            ->assertOk()->assertJsonPath('rekap.hadir', 1)->assertJsonPath('rekap.tidak_hadir', 1);
    }

    // ── Absen guru ────────────────────────────────────────────────────

    public function test_absen_masuk_dan_pulang_dengan_lokasi_dan_wajah(): void
    {
        $h = $this->tokenGuru();
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $this->lokasi())->assertStatus(422)->assertJsonPath('kode', 'wajah_belum_terdaftar');

        $this->withHeaders($h)->getJson('/api/v1/guru/absen')->assertOk()
            ->assertJsonPath('hari_sekolah', true)->assertJsonPath('lokasi.radius_meter', 100)
            ->assertJsonPath('wajah_terdaftar', false)->assertJsonPath('hari_ini.status', null);
        $this->daftarkanWajah();

        $cek = $this->withHeaders($h)->postJson('/api/v1/guru/absen/cek-lokasi', $this->lokasi())->assertOk();
        $this->assertTrue($cek->json('di_dalam_area'));
        $this->assertEqualsWithDelta(42, $cek->json('jarak_meter'), 3);

        $this->withHeaders($h)->postJson('/api/v1/guru/absen/pulang', $this->lokasi())->assertStatus(422)->assertJsonPath('kode', 'belum_masuk');

        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $this->lokasi())->assertOk()
            ->assertJsonPath('hari_ini.status', 'Hadir')->assertJsonPath('hari_ini.jam_masuk', '07:05')->assertJsonPath('hari_ini.terlambat', true);
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $this->lokasi())->assertStatus(409)->assertJsonPath('kode', 'sudah_absen');

        Carbon::setTestNow(Carbon::parse('2026-11-09 14:10:00', 'Asia/Pontianak'));
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/pulang', $this->lokasi())->assertOk()->assertJsonPath('hari_ini.jam_pulang', '14:10');
        $row = PresensiGuru::firstOrFail();
        $this->assertSame('aplikasi', $row->sumber);
        $this->assertGreaterThanOrEqual(85, $row->masuk_skor_wajah, 'Skor dihitung server');

        $this->withHeaders($h)->getJson('/api/v1/guru/absen/rekap?bulan=2026-11')->assertOk()
            ->assertJsonPath('hadir', 1)->assertJsonPath('persen', 100)->assertJsonPath('data.0.jam_pulang', '14:10');
    }

    public function test_absen_ditolak_di_luar_area_wajah_rendah_tanpa_liveness_dan_lewat_batas(): void
    {
        $h = $this->tokenGuru();

        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $this->lokasi(0.0108))->assertStatus(422)
            ->assertJsonPath('kode', 'di_luar_area')->assertJsonPath('di_dalam_area', false);
        $this->daftarkanWajah();
        $h = $this->tokenGuru();
        // Wajah orang lain (vektor berbeda) ditolak walau klien dulu bisa kirim skor sendiri.
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', ['embedding' => array_map(fn ($i) => cos($i * 3), range(1, 192)), 'skor_wajah' => 100] + $this->lokasi())->assertStatus(422)
            ->assertJsonPath('kode', 'wajah_tidak_cocok');
        // Salinan persis template (bukan dari kamera) ditolak.
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', ['embedding' => $this->wajahDari(0)] + $this->lokasi())->assertStatus(422)
            ->assertJsonPath('kode', 'wajah_tidak_cocok');
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', ['liveness' => false] + $this->lokasi())->assertJsonValidationErrors('liveness');
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', ['mock' => true] + $this->lokasi())->assertJsonValidationErrors('mock');
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', ['akurasi' => 300] + $this->lokasi())->assertJsonValidationErrors('akurasi');
        $tanpa = $this->lokasi();
        unset($tanpa['akurasi'], $tanpa['mock']);
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $tanpa)->assertJsonValidationErrors(['akurasi', 'mock']);
        $this->assertSame(0, PresensiGuru::count());

        Carbon::setTestNow(Carbon::parse('2026-11-09 13:01:00', 'Asia/Pontianak'));
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $this->lokasi())->assertStatus(422)->assertJsonPath('kode', 'lewat_batas');
    }

    public function test_absen_ditolak_hari_libur_dan_lokasi_belum_diatur(): void
    {
        $h = $this->tokenGuru();
        HariLibur::create(['tanggal' => '2026-11-09', 'keterangan' => 'Libur']);
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $this->lokasi())->assertJsonValidationErrors('tanggal');
        $this->withHeaders($h)->getJson('/api/v1/guru/absen')->assertJsonPath('hari_sekolah', false);

        HariLibur::query()->delete();
        PengaturanSekolah::simpan([PengaturanSekolah::LATITUDE => null, PengaturanSekolah::LONGITUDE => null]);
        $this->withHeaders($h)->getJson('/api/v1/guru/absen')->assertJsonPath('lokasi', null);
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/cek-lokasi', $this->lokasi())->assertJsonValidationErrors('latitude');
    }

    public function test_daftar_wajah_sekali_dan_template_terenkripsi(): void
    {
        $h = $this->tokenGuru();
        $template = array_map(fn ($i) => round(sin($i) / 3, 4), range(1, 192));

        $this->withHeaders($h)->postJson('/api/v1/guru/wajah', ['template' => [1, 2]])->assertJsonValidationErrors('template');
        $this->withHeaders($h)->postJson('/api/v1/guru/wajah', ['template' => $template])->assertCreated();
        $this->withHeaders($h)->postJson('/api/v1/guru/wajah', ['template' => $template])->assertStatus(409)->assertJsonPath('kode', 'sudah_terdaftar');

        $this->assertStringNotContainsString((string) $template[0], \DB::table('wajah_guru')->value('template'));
        // Template tidak pernah dikirim balik ke HP.
        $res = $this->withHeaders($h)->getJson('/api/v1/guru/wajah')->assertOk()->assertJsonPath('terdaftar', true);
        $this->assertArrayNotHasKey('template', $res->json());
        $this->withHeaders($this->tokenGuru($this->guruLain))->getJson('/api/v1/guru/wajah')->assertJsonPath('terdaftar', false);
        $this->assertSame(1, WajahGuru::count());
    }

    public function test_ubah_sandi_guru(): void
    {
        $h = $this->tokenGuru();
        $this->withHeaders($h)->postJson('/api/v1/guru/ubah-sandi', ['sandi_lama' => 'salah', 'sandi_baru' => 'BaruSekali1', 'sandi_baru_confirmation' => 'BaruSekali1'])->assertJsonValidationErrors('sandi_lama');
        $this->withHeaders($h)->postJson('/api/v1/guru/ubah-sandi', ['sandi_lama' => 'Rahasia123', 'sandi_baru' => 'BaruSekali1', 'sandi_baru_confirmation' => 'BaruSekali1'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['peran' => 'guru', 'username' => 'ahmad', 'password' => 'BaruSekali1'])->assertOk();
    }

    public function test_upload_foto_guru(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::fake('local');
        $h = $this->tokenGuru();
        $file = \Illuminate\Http\UploadedFile::fake()->image('profil.jpg', 200, 200);

        $res = $this->withHeaders($h)->post('/api/v1/guru/profil/foto', ['foto' => $file]);
        $res->assertOk()->assertJsonStructure(['message', 'foto_url']);
        $this->assertNotNull($this->guru->fresh()->foto_profil);
        $path = $this->guru->fresh()->foto_profil;
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($path);

        // Foto hanya bisa dibuka dengan token; tanpa login ditolak.
        $url = parse_url($res->json('foto_url'), PHP_URL_PATH);
        $this->withHeaders($this->tokenGuru())->get($url)->assertOk();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->get($url, ['Accept' => 'application/json'])->assertUnauthorized();
    }
}
