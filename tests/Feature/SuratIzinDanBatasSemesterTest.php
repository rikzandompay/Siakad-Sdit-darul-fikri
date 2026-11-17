<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\PengajuanIzin;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use App\Support\SuratIzin;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratIzinDanBatasSemesterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $waliKelas;

    private User $guruLain;

    private Kelas $kelas;

    private Siswa $siswa;

    private Siswa $siswaLain;

    private Wali $wali;

    private Wali $waliLain;

    private TahunAjaran $taGanjil;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta'], 'presensi_guru.kecualikan_username' => ['admin']]);
        Carbon::setTestNow(Carbon::parse('2026-11-17 08:00:00', 'Asia/Pontianak'));

        $this->admin = User::create([
            'name' => 'sinta', 'nama_lengkap' => 'Dewi Quraisyinta, S.Pd.',
            'username' => 'sinta', 'email' => 'sinta@sekolah.id',
            'password' => 'Rahasia123', 'status' => 'Aktif',
        ]);

        $this->waliKelas = User::create([
            'name' => 'fauzi', 'nama_lengkap' => 'Ahmad Fauzi, S.Pd.',
            'username' => 'fauzi', 'email' => 'fauzi@sekolah.id',
            'password' => 'Rahasia123', 'status' => 'Aktif',
        ]);

        $this->guruLain = User::create([
            'name' => 'guru2', 'nama_lengkap' => 'Guru Lain, S.Pd.',
            'username' => 'guru2', 'email' => 'guru2@sekolah.id',
            'password' => 'Rahasia123', 'status' => 'Aktif',
        ]);

        $this->taGanjil = TahunAjaran::create([
            'nama_semester' => '2026/2027 - Ganjil',
            'status_aktif' => 'Y',
        ]);

        $this->kelas = Kelas::create([
            'nama_kelas' => 'Kelas 3',
            'wali_kelas_id' => $this->waliKelas->id,
        ]);

        $this->siswa = Siswa::create([
            'nis' => '2024001', 'nisn' => '1001',
            'nama_siswa' => 'Rikzan El-Yasinta',
            'tanggal_lahir' => '2017-05-10', 'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas->id, 'status' => 'Aktif',
            'nama_wali' => 'Muhammad Yasin', 'no_hp_wali' => '081234567890',
        ]);

        $kelasLain = Kelas::create(['nama_kelas' => 'Kelas 4']);
        $this->siswaLain = Siswa::create([
            'nis' => '2024002', 'nisn' => '1002',
            'nama_siswa' => 'Anak Lain',
            'tanggal_lahir' => '2016-03-15', 'jenis_kelamin' => 'P',
            'kelas_id' => $kelasLain->id, 'status' => 'Aktif',
            'nama_wali' => 'Wali Lain', 'no_hp_wali' => '089876543210',
        ]);

        $this->wali = Wali::firstWhere('username', '2024001');
        $this->waliLain = Wali::firstWhere('username', '2024002');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function headerWali(Wali $wali): array
    {
        $this->app['auth']->forgetGuards();

        return [
            'Authorization' => 'Bearer '.$wali->createToken('test', ['wali'])->plainTextToken,
            'Accept' => 'application/json',
        ];
    }

    public function test_wali_bisa_mengajukan_izin_hingga_3_kali_dalam_satu_semester(): void
    {
        $h = $this->headerWali($this->wali);

        for ($i = 1; $i <= 3; $i++) {
            $tgl = Carbon::parse('2026-11-10')->addDays($i)->toDateString();
            $res = $this->withHeaders($h)->postJson('/api/v1/wali/izin', [
                'jenis' => 'Izin',
                'tanggal_mulai' => $tgl,
                'tanggal_selesai' => $tgl,
                'alasan' => "Acara keluarga ke-{$i}",
            ]);
            $res->assertCreated()->assertJsonPath('izin.status', 'Menunggu');
        }

        $this->assertSame(3, PengajuanIzin::hitungIzinSemester($this->siswa->id));
        $this->assertSame(0, PengajuanIzin::sisaKuotaIzin($this->siswa->id));
    }

    public function test_pengajuan_izin_keempat_dalam_satu_semester_ditolak(): void
    {
        $h = $this->headerWali($this->wali);

        for ($i = 1; $i <= 3; $i++) {
            PengajuanIzin::create([
                'siswa_id' => $this->siswa->id,
                'wali_id' => $this->wali->id,
                'jenis' => 'Izin',
                'status' => PengajuanIzin::DISETUJUI,
                'tanggal_mulai' => "2026-11-0{$i}",
                'tanggal_selesai' => "2026-11-0{$i}",
                'alasan' => "Izin {$i}",
            ]);
        }

        $res = $this->withHeaders($h)->postJson('/api/v1/wali/izin', [
            'jenis' => 'Izin',
            'tanggal_mulai' => '2026-11-18',
            'tanggal_selesai' => '2026-11-18',
            'alasan' => 'Izin ke-4 mau mudik',
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('sisa_kuota', 0)
            ->assertJsonFragment(['message' => 'Batas pengajuan izin keperluan (maksimal 3 kali per semester) sudah tercapai.']);
    }

    public function test_izin_sakit_tidak_dibatasi_kuota_semester(): void
    {
        $h = $this->headerWali($this->wali);

        // Habiskan kuota izin keperluan (3 kali)
        for ($i = 1; $i <= 3; $i++) {
            PengajuanIzin::create([
                'siswa_id' => $this->siswa->id,
                'wali_id' => $this->wali->id,
                'jenis' => 'Izin',
                'status' => PengajuanIzin::DISETUJUI,
                'tanggal_mulai' => "2026-11-0{$i}",
                'tanggal_selesai' => "2026-11-0{$i}",
                'alasan' => "Izin {$i}",
            ]);
        }

        // Pengajuan Sakit tetap harus berhasil
        $res = $this->withHeaders($h)->postJson('/api/v1/wali/izin', [
            'jenis' => 'Sakit',
            'tanggal_mulai' => '2026-11-18',
            'tanggal_selesai' => '2026-11-19',
            'alasan' => 'Demam tinggi butuh istirahat',
        ]);

        $res->assertCreated()->assertJsonPath('izin.jenis', 'Sakit');
    }

    public function test_izin_yang_ditolak_tidak_mengurangi_kuota_semester(): void
    {
        $h = $this->headerWali($this->wali);

        // Buat 2 izin disetujui, dan 1 ditolak
        PengajuanIzin::create([
            'siswa_id' => $this->siswa->id,
            'wali_id' => $this->wali->id,
            'jenis' => 'Izin',
            'status' => PengajuanIzin::DISETUJUI,
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-01',
            'alasan' => 'Izin 1',
        ]);
        PengajuanIzin::create([
            'siswa_id' => $this->siswa->id,
            'wali_id' => $this->wali->id,
            'jenis' => 'Izin',
            'status' => PengajuanIzin::DISETUJUI,
            'tanggal_mulai' => '2026-11-02',
            'tanggal_selesai' => '2026-11-02',
            'alasan' => 'Izin 2',
        ]);
        PengajuanIzin::create([
            'siswa_id' => $this->siswa->id,
            'wali_id' => $this->wali->id,
            'jenis' => 'Izin',
            'status' => PengajuanIzin::DITOLAK,
            'tanggal_mulai' => '2026-11-03',
            'tanggal_selesai' => '2026-11-03',
            'alasan' => 'Izin ditolak',
        ]);

        // Kuota terpakai harus 2, sisa 1
        $this->assertSame(2, PengajuanIzin::hitungIzinSemester($this->siswa->id));
        $this->assertSame(1, PengajuanIzin::sisaKuotaIzin($this->siswa->id));

        // Wali masih bisa mengajukan 1 kali lagi
        $res = $this->withHeaders($h)->postJson('/api/v1/wali/izin', [
            'jenis' => 'Izin',
            'tanggal_mulai' => '2026-11-18',
            'tanggal_selesai' => '2026-11-18',
            'alasan' => 'Izin pengganti yang ditolak',
        ]);
        $res->assertCreated();
    }

    public function test_izin_di_semester_lain_tidak_mengurangi_kuota_semester_aktif(): void
    {
        // Izin di semester genap lalu (mis. Maret 2026)
        PengajuanIzin::create([
            'siswa_id' => $this->siswa->id,
            'wali_id' => $this->wali->id,
            'jenis' => 'Izin',
            'status' => PengajuanIzin::DISETUJUI,
            'tanggal_mulai' => '2026-03-10',
            'tanggal_selesai' => '2026-03-10',
            'alasan' => 'Izin semester lalu',
        ]);

        // Semester aktif saat ini adalah 2026/2027 Ganjil (Juli - Desember 2026)
        $this->assertSame(0, PengajuanIzin::hitungIzinSemester($this->siswa->id));
        $this->assertSame(3, PengajuanIzin::sisaKuotaIzin($this->siswa->id));
    }

    public function test_endpoint_izin_mengembalikan_informasi_kuota_semester(): void
    {
        $h = $this->headerWali($this->wali);

        PengajuanIzin::create([
            'siswa_id' => $this->siswa->id,
            'wali_id' => $this->wali->id,
            'jenis' => 'Izin',
            'status' => PengajuanIzin::MENUNGGU,
            'tanggal_mulai' => '2026-11-10',
            'tanggal_selesai' => '2026-11-10',
            'alasan' => 'Keperluan keluarga',
        ]);

        $res = $this->withHeaders($h)->getJson('/api/v1/wali/izin');
        $res->assertOk()
            ->assertJsonPath('kuota_izin.maksimal', 3)
            ->assertJsonPath('kuota_izin.terpakai', 1)
            ->assertJsonPath('kuota_izin.sisa', 2);
    }

    public function test_wali_dapat_mengunduh_surat_izin_pdf_anaknya(): void
    {
        $h = $this->headerWali($this->wali);

        $izin = PengajuanIzin::create([
            'siswa_id' => $this->siswa->id,
            'wali_id' => $this->wali->id,
            'jenis' => 'Izin',
            'status' => PengajuanIzin::DISETUJUI,
            'tanggal_mulai' => '2026-11-17',
            'tanggal_selesai' => '2026-11-18',
            'alasan' => 'Acara Keluarga',
        ]);

        $res = $this->withHeaders($h)->get("/api/v1/wali/izin/{$izin->id}/surat");
        $res->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString('surat-izin-', $res->headers->get('Content-Disposition'));
    }

    public function test_wali_dilarang_mengunduh_surat_izin_anak_orang_lain(): void
    {
        $h = $this->headerWali($this->wali);

        $izinLain = PengajuanIzin::create([
            'siswa_id' => $this->siswaLain->id,
            'wali_id' => $this->waliLain->id,
            'jenis' => 'Izin',
            'status' => PengajuanIzin::DISETUJUI,
            'tanggal_mulai' => '2026-11-17',
            'tanggal_selesai' => '2026-11-18',
            'alasan' => 'Acara Keluarga',
        ]);

        $res = $this->withHeaders($h)->get("/api/v1/wali/izin/{$izinLain->id}/surat");
        $res->assertStatus(403);
    }

    public function test_wali_dapat_membuat_surat_izin_pdf_kustom(): void
    {
        $h = $this->headerWali($this->wali);

        $res = $this->withHeaders($h)->postJson('/api/v1/wali/izin/surat', [
            'nama_lengkap' => 'Muhammad Yasin',
            'nama_anak' => 'Rikzan El-Yasinta',
            'kelas' => '3',
            'nama_sekolah' => 'SD IT Darul Fikri',
            'nama_wali_kelas' => 'Dewi Quraisyinta',
            'kota' => 'Kab. Kubu Raya',
            'tanggal_surat' => '2026-11-17',
            'tanggal_mulai' => '2026-11-17',
            'jumlah_hari' => 2,
            'alasan' => 'Acara Keluarga',
            'kontak' => '08123456789',
        ]);

        $res->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_guru_wali_kelas_dan_admin_dapat_mengunduh_surat_izin_di_web(): void
    {
        $izin = PengajuanIzin::create([
            'siswa_id' => $this->siswa->id,
            'wali_id' => $this->wali->id,
            'jenis' => 'Izin',
            'status' => PengajuanIzin::MENUNGGU,
            'tanggal_mulai' => '2026-11-17',
            'tanggal_selesai' => '2026-11-18',
            'alasan' => 'Acara Keluarga',
        ]);

        // Wali kelas bisa membuka surat
        $this->actingAs($this->waliKelas)->get(route('izin.surat', $izin))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Admin bisa membuka surat
        $this->actingAs($this->admin)->get(route('izin.surat', $izin))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Guru lain bukan wali kelas dilarang (403)
        $this->actingAs($this->guruLain)->get(route('izin.surat', $izin))
            ->assertStatus(403);
    }

    public function test_format_alasan_kalimat_dan_data_surat(): void
    {
        $this->assertSame('sedang sakit', SuratIzin::formatAlasanKalimat('Sakit'));
        $this->assertSame('ada acara keluarga yang harus dihadiri', SuratIzin::formatAlasanKalimat('Acara Keluarga'));
        $this->assertSame('ada urusan keluarga', SuratIzin::formatAlasanKalimat('Urusan Keluarga'));
        $this->assertSame('ada keperluan mendesak', SuratIzin::formatAlasanKalimat('Keperluan Mendesak'));
        $this->assertSame('sedang demam', SuratIzin::formatAlasanKalimat('Demam'));
        $this->assertSame('ada keperluan mendadak', SuratIzin::formatAlasanKalimat('ada keperluan mendadak'));
    }
}
