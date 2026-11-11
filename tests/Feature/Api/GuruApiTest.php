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
use App\Notifications\FotoProfilDiajukan;
use App\Notifications\FotoProfilDitinjau;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
        return ['latitude' => self::LAT + $offsetLat, 'longitude' => self::LNG, 'akurasi' => 8, 'mock' => false, 'embedding' => $this->wajahDari(0.05), 'liveness' => true, 'pipeline_wajah' => 2,
            // Absen wajib menyertakan identitas perangkat (satu guru satu HP).
            'perangkat_id' => 'perangkat-uji-001', 'perangkat_nama' => 'Samsung SM-A125F', 'perangkat_platform' => 'android', 'perangkat_versi_os' => '13'];
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

    public function test_admin_kepala_sekolah_tidak_ikut_mengisi_presensi_kelas_dan_sholat(): void
    {
        $sinta = User::create(['name' => 'kepsek', 'nama_lengkap' => 'Dewi Kepala Sekolah', 'username' => 'kepsek', 'email' => 'k@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif', 'jenis_kelamin' => 'P']);
        $h = $this->tokenGuru($sinta);

        // Presensi hanya untuk wali kelas: admin yang bukan wali tidak melihat kelas input dan tidak bisa mengisi.
        $this->withHeaders($h)->getJson('/api/v1/guru/presensi/kelas')->assertOk()->assertJsonCount(0, 'data');
        $this->withHeaders($h)->getJson('/api/v1/guru/presensi/kelas/'.$this->kelas->id.'/jadwal')->assertStatus(403);
        $this->withHeaders($h)->postJson('/api/v1/guru/presensi/jadwal/'.$this->jadwal->id, ['tanggal' => '2026-11-09', 'presensi' => [['siswa_id' => $this->aisyah->id, 'status' => 'H']]])->assertStatus(403);
        $this->withHeaders($h)->getJson('/api/v1/guru/sholat/kelas')->assertOk()->assertJsonCount(0, 'data');
        $this->withHeaders($h)->postJson('/api/v1/guru/sholat/kelas/'.$this->kelas->id.'/scan', ['jenis' => 'Dzuhur', 'qr' => $this->aisyah->qr_payload])->assertStatus(403);

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
        // Lokasi palsu (mock = true) diuji terpisah di LokasiPalsuTest: setelah itu absen hari itu diblokir.
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

    public function test_wajah_lebih_mirip_guru_lain_diverifikasi_tambahan_lalu_ditolak(): void
    {
        // Template sendiri lolos ambang, tetapi wajah di kamera lebih dekat ke guru lain.
        WajahGuru::create(['guru_id' => $this->guru->id, 'template' => json_encode($this->wajahDari(0.9)), 'didaftarkan_pada' => now()]);
        $this->daftarkanWajah($this->guruLain);
        $h = $this->tokenGuru();

        // Tidak langsung ditolak: aplikasi diminta mengambil beberapa foto lagi.
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $this->lokasi())->assertStatus(422)->assertJsonPath('kode', 'verifikasi_tambahan');
        $this->assertSame($this->guruLain->id, WajahGuru::where('guru_id', $this->guru->id)->first()->mirip_guru_id);

        // Setelah verifikasi tambahan wajah tetap jelas lebih cocok dengan guru lain.
        $tambahan = $this->lokasi() + ['verifikasi_tambahan' => true, 'embeddings' => [$this->wajahDari(0.04), $this->wajahDari(0.06)]];
        $res = $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $tambahan)->assertStatus(422)->assertJsonPath('kode', 'wajah_mirip_guru_lain');

        // Nama guru lain tidak dibocorkan ke yang mencoba absen.
        $this->assertStringNotContainsString('Guru Lain', $res->getContent());
        $this->assertSame(0, PresensiGuru::count());
    }

    public function test_absen_tetap_berhasil_bila_wajah_paling_mirip_diri_sendiri_dan_pasangan_ditandai(): void
    {
        $this->daftarkanWajah();
        WajahGuru::create(['guru_id' => $this->guruLain->id, 'template' => json_encode($this->wajahDari(0.9)), 'didaftarkan_pada' => now()]);

        $this->withHeaders($this->tokenGuru())->postJson('/api/v1/guru/absen/masuk', $this->lokasi())->assertOk();

        $this->assertSame($this->guruLain->id, WajahGuru::where('guru_id', $this->guru->id)->first()->mirip_guru_id);
    }

    public function test_kembar_identik_lolos_setelah_verifikasi_tambahan_dan_dicatat(): void
    {
        WajahGuru::create(['guru_id' => $this->guru->id, 'template' => json_encode($this->wajahDari(0)), 'didaftarkan_pada' => now(), 'mirip_guru_id' => $this->guruLain->id]);
        WajahGuru::create(['guru_id' => $this->guruLain->id, 'template' => json_encode($this->wajahDari(0.04)), 'didaftarkan_pada' => now(), 'mirip_guru_id' => $this->guru->id]);
        $h = $this->tokenGuru();
        $kembar = ['embedding' => $this->wajahDari(0.01)] + $this->lokasi();

        // Selisih kemiripan terlalu tipis: minta verifikasi tambahan, bukan langsung gagal.
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $kembar)->assertStatus(422)->assertJsonPath('kode', 'verifikasi_tambahan');
        // Satu foto saja tidak cukup walau menandai verifikasi tambahan.
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $kembar + ['verifikasi_tambahan' => true])->assertJsonPath('kode', 'verifikasi_tambahan');

        $tambahan = $kembar + ['verifikasi_tambahan' => true, 'embeddings' => [$this->wajahDari(0.012), $this->wajahDari(0.008), $this->wajahDari(0.011)]];
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $tambahan)->assertOk();

        $absen = PresensiGuru::where('guru_id', $this->guru->id)->first();
        $this->assertSame(PresensiGuru::HADIR, $absen->status);
        $this->assertStringContainsString('sangat mirip guru lain', $absen->keterangan);
        $this->assertStringNotContainsString('Guru Lain', $absen->keterangan);
    }

    public function test_wajah_kurang_mirip_tetap_tidak_cocok_walau_akun_ditandai_kembar(): void
    {
        WajahGuru::create(['guru_id' => $this->guru->id, 'template' => json_encode($this->wajahDari(0)), 'didaftarkan_pada' => now(), 'mirip_guru_id' => $this->guruLain->id]);
        WajahGuru::create(['guru_id' => $this->guruLain->id, 'template' => json_encode(array_map(fn ($i) => cos($i * 3), range(1, 192))), 'didaftarkan_pada' => now(), 'mirip_guru_id' => $this->guru->id]);

        $this->withHeaders($this->tokenGuru())->postJson('/api/v1/guru/absen/masuk', ['embedding' => $this->wajahDari(3)] + $this->lokasi())
            ->assertStatus(422)->assertJsonPath('kode', 'wajah_tidak_cocok');
    }

    public function test_akurasi_gps_memberi_toleransi_terbatas_di_tepi_radius(): void
    {
        // Radius 100 m; titik ~111 m dari sekolah.
        $tepi = ['latitude' => self::LAT + 0.001, 'longitude' => self::LNG];
        $h = $this->tokenGuru();
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/cek-lokasi', $tepi + ['akurasi' => 3])->assertOk()->assertJsonPath('di_dalam_area', false);
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/cek-lokasi', $tepi + ['akurasi' => 20])->assertOk()->assertJsonPath('di_dalam_area', true);
        // Toleransi dibatasi 30 m: titik ~167 m tetap di luar walau akurasi buruk.
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/cek-lokasi', ['latitude' => self::LAT + 0.0015, 'longitude' => self::LNG, 'akurasi' => 50])->assertOk()->assertJsonPath('di_dalam_area', false);
    }

    public function test_ubah_sandi_guru(): void
    {
        $h = $this->tokenGuru();
        $this->withHeaders($h)->postJson('/api/v1/guru/ubah-sandi', ['sandi_lama' => 'salah', 'sandi_baru' => 'BaruSekali1', 'sandi_baru_confirmation' => 'BaruSekali1'])->assertJsonValidationErrors('sandi_lama');
        $this->withHeaders($h)->postJson('/api/v1/guru/ubah-sandi', ['sandi_lama' => 'Rahasia123', 'sandi_baru' => 'BaruSekali1', 'sandi_baru_confirmation' => 'BaruSekali1'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['peran' => 'guru', 'username' => 'ahmad', 'password' => 'BaruSekali1'])->assertOk();
    }

    public function test_foto_guru_menunggu_persetujuan_admin(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Notification::fake();
        $admin = User::create(['name' => 'kepsek', 'nama_lengkap' => 'Kepala Sekolah', 'username' => 'kepsek', 'email' => 'k@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->daftarkanWajah();
        $h = $this->tokenGuru();

        $res = $this->withHeaders($h)->post('/api/v1/guru/profil/foto', ['foto' => UploadedFile::fake()->image('profil.jpg', 200, 200)]);
        $res->assertOk()->assertJsonPath('menunggu', true)->assertJsonPath('foto_url', null);

        // Belum aktif: foto profil dan data wajah tidak berubah.
        $guru = $this->guru->fresh();
        $this->assertNull($guru->foto_profil);
        $pengajuan = $guru->foto_pengajuan;
        Storage::disk('local')->assertExists($pengajuan);
        $this->assertSame(1, WajahGuru::where('guru_id', $guru->id)->count());
        Notification::assertSentTo($admin, FotoProfilDiajukan::class);
        $this->withHeaders($h)->getJson('/api/v1/auth/me')->assertJsonPath('akun.foto_menunggu', true);

        // Foto pengajuan hanya untuk pemilik dan admin.
        $url = parse_url($guru->fotoPengajuanUrl(), PHP_URL_PATH);
        $this->withHeaders($h)->get($url)->assertOk();
        $this->withHeaders($this->tokenGuru($this->guruLain))->get($url)->assertForbidden();

        // Hanya admin yang boleh menyetujui.
        $this->withHeaders($this->tokenGuru($this->guruLain))->postJson("/api/v1/admin/foto-pengajuan/{$guru->id}/setujui")->assertForbidden();

        $ha = $this->tokenGuru($admin);
        $this->withHeaders($ha)->get($url)->assertOk();
        $this->withHeaders($ha)->getJson('/api/v1/admin/foto-pengajuan')->assertOk()
            ->assertJsonPath('data.0.id', $guru->id)->assertJsonPath('data.0.foto_url', null);
        $this->withHeaders($ha)->postJson("/api/v1/admin/foto-pengajuan/{$guru->id}/setujui")->assertOk();

        // Foto aktif, acuan berubah sehingga wajah harus didaftarkan ulang.
        $guru->refresh();
        $this->assertSame($pengajuan, $guru->foto_profil);
        $this->assertNull($guru->foto_pengajuan);
        $this->assertSame(0, WajahGuru::where('guru_id', $guru->id)->count());
        Notification::assertSentTo($guru, FotoProfilDitinjau::class, fn ($n) => $n->disetujui);
        $this->withHeaders($ha)->postJson("/api/v1/admin/foto-pengajuan/{$guru->id}/setujui")->assertStatus(409);

        // Foto aktif bisa dibuka dengan token, tanpa login ditolak.
        $aktif = parse_url($guru->fotoUrl(), PHP_URL_PATH);
        $this->withHeaders($this->tokenGuru())->get($aktif)->assertOk();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->get($aktif, ['Accept' => 'application/json'])->assertUnauthorized();
    }

    public function test_foto_ditolak_admin_dibuang_dan_foto_lama_tetap(): void
    {
        Storage::fake('local');
        Notification::fake();
        $admin = User::create(['name' => 'kepsek', 'nama_lengkap' => 'Kepala Sekolah', 'username' => 'kepsek', 'email' => 'k@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->guru->update(['foto_profil' => 'foto_guru/lama.jpg']);
        $this->daftarkanWajah();
        $this->withHeaders($this->tokenGuru())->post('/api/v1/guru/profil/foto', ['foto' => UploadedFile::fake()->image('b.jpg', 200, 200)])->assertOk();
        $pengajuan = $this->guru->fresh()->foto_pengajuan;

        $this->withHeaders($this->tokenGuru($admin))->postJson("/api/v1/admin/foto-pengajuan/{$this->guru->id}/tolak", ['catatan' => 'Bukan foto wajah'])->assertOk();

        $guru = $this->guru->fresh();
        $this->assertSame('foto_guru/lama.jpg', $guru->foto_profil);
        $this->assertNull($guru->foto_pengajuan);
        Storage::disk('local')->assertMissing($pengajuan);
        $this->assertSame(1, WajahGuru::where('guru_id', $guru->id)->count());
        Notification::assertSentTo($guru, FotoProfilDitinjau::class, fn ($n) => ! $n->disetujui && $n->catatan === 'Bukan foto wajah');
    }

    public function test_foto_admin_langsung_aktif_dan_mewajibkan_daftar_wajah_ulang(): void
    {
        Storage::fake('local');
        $admin = User::create(['name' => 'kepsek', 'nama_lengkap' => 'Kepala Sekolah', 'username' => 'kepsek', 'email' => 'k@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->daftarkanWajah($admin);

        $this->withHeaders($this->tokenGuru($admin))->post('/api/v1/guru/profil/foto', ['foto' => UploadedFile::fake()->image('k.jpg', 200, 200)])
            ->assertOk()->assertJsonPath('menunggu', false);

        $this->assertNotNull($admin->fresh()->foto_profil);
        $this->assertSame(0, WajahGuru::where('guru_id', $admin->id)->count());
    }
}
