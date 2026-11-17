<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\PengajuanIzin;
use App\Models\Pengumuman;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Izin keperluan: batas 3x per semester (hanya yang disetujui), surat PDF dari orang
 * tua untuk wali kelas, nomor surat otomatis; pengumuman sebagai surat resmi.
 */
class SuratIzinDanBatasSemesterTest extends TestCase
{
    use RefreshDatabase;

    private User $kepsek;

    private User $guru;

    private Kelas $kelas;

    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta'], 'presensi_guru.kecualikan_username' => ['admin']]);
        Carbon::setTestNow(Carbon::parse('2026-11-09 09:00:00', 'Asia/Pontianak'));

        $this->kepsek = $this->makeUser('sinta', 'Dewi Quraisyinta, S.Pd.');
        $this->guru = $this->makeUser('ahmad', 'Ust. Ahmad Fauzi');
        $this->kelas = Kelas::create(['nama_kelas' => 'Kelas 3', 'wali_kelas_id' => $this->guru->id]);
        $this->siswa = Siswa::create([
            'nis' => '3001', 'nisn' => '93001', 'nama_siswa' => 'Rikzan El-Yasinta', 'tanggal_lahir' => '2017-01-01',
            'jenis_kelamin' => 'L', 'kelas_id' => $this->kelas->id, 'status' => 'Aktif', 'nama_wali' => 'Muhammad Yasin',
            'no_hp_wali' => '081234567890',
        ]);
        // Akun wali dibuat otomatis oleh model Siswa.
        $wali = Wali::where('username', '3001')->firstOrFail();
        $wali->update(['password' => 'Rahasia123', 'status' => 'Aktif']);

        TahunAjaran::create(['nama_semester' => '2026/2027 - Ganjil', 'status_aktif' => 'Y']);
    }

    private function makeUser(string $username, string $nama): User
    {
        return User::create([
            'name' => $username, 'nama_lengkap' => $nama, 'username' => $username,
            'email' => $username.'@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif', 'jenis_kelamin' => 'P',
        ]);
    }

    /** Permintaan API memakai token Bearer sesuai peran. */
    private function api(User|Wali $akun, string $peran, string $method, string $uri, array $data = [])
    {
        $this->app['auth']->forgetGuards();
        $headers = ['Authorization' => 'Bearer '.$akun->createToken('t', [$peran])->plainTextToken, 'Accept' => 'application/json'];

        return $this->withHeaders($headers)->json($method, $uri, $data);
    }

    private function ajukanKeperluan(string $mulai = '2026-11-10', array $tambahan = []): PengajuanIzin
    {
        return PengajuanIzin::create([
            'siswa_id' => $this->siswa->id, 'wali_id' => Wali::first()->id, 'jenis' => 'Izin',
            'tanggal_mulai' => $mulai, 'tanggal_selesai' => $mulai, 'alasan' => 'Acara Keluarga',
            'status' => PengajuanIzin::MENUNGGU,
            ...$tambahan,
        ]);
    }

    public function test_batas_tiga_izin_keperluan_hanya_menghitung_yang_disetujui(): void
    {
        $this->assertSame('2026/2027 - Ganjil', TahunAjaran::getAktif()?->nama_semester);
        $this->ajukanKeperluan('2026-11-03', ['status' => PengajuanIzin::DITOLAK]);
        $this->ajukanKeperluan('2026-11-04', ['status' => PengajuanIzin::DISETUJUI]);
        $this->ajukanKeperluan('2026-11-05', ['status' => PengajuanIzin::DISETUJUI]);
        $this->ajukanKeperluan('2026-11-06', ['status' => PengajuanIzin::DISETUJUI]);
        // Izin sakit tidak dihitung.
        $this->ajukanKeperluan('2026-11-07', ['jenis' => 'Sakit', 'status' => PengajuanIzin::DISETUJUI]);

        $this->assertSame(3, PengajuanIzin::hitungIzinSemester($this->siswa->id));
        $this->assertSame(0, PengajuanIzin::sisaKuotaIzin($this->siswa->id));
        $this->assertSame(PengajuanIzin::DITOLAK, PengajuanIzin::whereDate('tanggal_mulai', '2026-11-03')->value('status'));
    }

    public function test_semester_lain_tidak_memakai_kuota_semester_ini(): void
    {
        $this->ajukanKeperluan('2026-06-15', ['status' => PengajuanIzin::DISETUJUI]); // semester Genap lalu
        $this->assertSame(0, PengajuanIzin::hitungIzinSemester($this->siswa->id));
        $this->assertSame(3, PengajuanIzin::sisaKuotaIzin($this->siswa->id));
    }

    public function test_wali_mengajukan_izin_keperluan_hanya_sampai_kuota_habis_dan_sakit_tetap_bisa(): void
    {
        $wali = Wali::first();
        for ($i = 1; $i <= 3; $i++) {
            $this->api($wali, 'wali', 'POST', '/api/v1/wali/izin', [
                'jenis' => 'Izin', 'tanggal_mulai' => "2026-11-1{$i}", 'tanggal_selesai' => "2026-11-1{$i}",
                'alasan' => 'Acara Keluarga', 'nama_lengkap' => 'Muhammad Yasin', 'kota' => 'Kab. Kubu Raya',
                'tanggal_surat' => '2026-11-09', 'jumlah_hari' => 1, 'kontak' => '081234567890',
            ])->assertCreated();
            PengajuanIzin::latest('id')->first()->setujui($this->guru);
        }

        $this->api($wali, 'wali', 'GET', '/api/v1/wali/izin')->assertOk()
            ->assertJsonPath('kuota_izin.maksimal', 3)->assertJsonPath('kuota_izin.terpakai', 3)->assertJsonPath('kuota_izin.sisa', 0);

        $this->api($wali, 'wali', 'POST', '/api/v1/wali/izin', [
            'jenis' => 'Izin', 'tanggal_mulai' => '2026-11-20', 'tanggal_selesai' => '2026-11-20', 'alasan' => 'Urusan Penting',
        ])->assertStatus(422)->assertJsonPath('sisa_kuota', 0);

        $this->api($wali, 'wali', 'POST', '/api/v1/wali/izin', [
            'jenis' => 'Sakit', 'tanggal_mulai' => '2026-11-20', 'tanggal_selesai' => '2026-11-20', 'alasan' => 'Demam',
        ])->assertCreated();
    }

    public function test_izin_keperluan_mendapat_nomor_surat_dan_pdf_dibuka_wali_kelas(): void
    {
        $wali = Wali::first();
        $this->api($wali, 'wali', 'POST', '/api/v1/wali/izin', [
            'jenis' => 'Izin', 'tanggal_mulai' => '2026-11-10', 'tanggal_selesai' => '2026-11-11',
            'alasan' => 'Acara Keluarga', 'nama_lengkap' => 'Muhammad Yasin', 'kota' => 'Kab. Kubu Raya',
            'tanggal_surat' => '2026-11-09', 'jumlah_hari' => 2, 'kontak' => '081234567890',
        ])->assertCreated()->assertJsonPath('izin.nomor_surat', '001/SDIT-DF/IZIN/XI/2026');

        $izin = PengajuanIzin::firstOrFail();
        $this->assertSame('Muhammad Yasin', $izin->nama_orang_tua);
        $this->assertTrue($izin->punyaSurat());

        // Wali kelas bisa membuka surat sejak status masih Menunggu (web & aplikasi).
        $res = $this->actingAs($this->guru)->get('/izin/'.$izin->id.'/surat');
        $res->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->api($this->guru, 'guru', 'GET', '/api/v1/guru/izin/'.$izin->id.'/surat')->assertOk();

        $this->actingAs($this->guru)->get('/izin')->assertOk()->assertSee('Surat Izin PDF');

        // Guru lain ditolak; izin sakit tidak punya surat.
        $guruLain = $this->makeUser('budi', 'Budi Lain');
        $this->actingAs($guruLain)->get('/izin/'.$izin->id.'/surat')->assertStatus(403);
        $sakit = $this->ajukanKeperluan('2026-11-12', ['jenis' => 'Sakit']);
        $this->actingAs($this->guru)->get('/izin/'.$sakit->id.'/surat')->assertStatus(404);
    }

    public function test_pratinjau_surat_tersedia_untuk_wali(): void
    {
        $this->api(Wali::first(), 'wali', 'POST', '/api/v1/wali/izin/pratinjau-surat', [
            'nama_lengkap' => 'Muhammad Yasin', 'tanggal_mulai' => '2026-11-10', 'jumlah_hari' => 2, 'alasan' => 'Acara Keluarga',
        ])->assertOk()
            ->assertJsonPath('data.nama_anak', 'Rikzan El-Yasinta')
            ->assertJsonPath('data.kelas', 'Kelas 3')
            ->assertJsonPath('data.alasan_teks', 'ada acara keluarga yang harus dihadiri')
            ->assertJsonPath('sisa_kuota', 3);
    }

    public function test_pengumuman_menjadi_surat_resmi_bernomor_dan_berpdf(): void
    {
        $this->actingAs($this->kepsek)->post('/pengumuman', [
            'judul' => 'Libur Semester', 'isi' => 'Libur tanggal 28-01-2027.', 'sasaran' => 'Semua',
            'tanggal_surat' => '2026-11-17', 'tempat' => 'Kab. Kubu Raya',
            'jadwal_hari_tanggal' => 'Senin, 1 Desember 2026', 'jadwal_waktu' => '08.00 WIB', 'jadwal_tempat' => 'Aula',
        ])->assertRedirect('/pengumuman');

        $p = Pengumuman::firstOrFail();
        $this->assertSame('001/SDIT-DF/PENG/XI/2026', $p->nomor_surat);
        $this->assertSame('2026-11-17', $p->tanggal_surat->toDateString());

        $this->actingAs($this->kepsek)->get('/pengumuman/'.$p->id.'/pdf')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // Pengumuman kedua melanjutkan urutan; nomor tetap saat diubah.
        $this->actingAs($this->kepsek)->post('/pengumuman', ['judul' => 'Rapat', 'isi' => 'Rapat', 'sasaran' => 'Guru']);
        $this->assertSame('002/SDIT-DF/PENG/XI/2026', Pengumuman::where('judul', 'Rapat')->value('nomor_surat'));
        $this->actingAs($this->kepsek)->put('/pengumuman/'.$p->id, ['judul' => 'Libur Semester', 'isi' => 'Baru', 'sasaran' => 'Semua']);
        $this->assertSame('001/SDIT-DF/PENG/XI/2026', $p->fresh()->nomor_surat);
    }

    public function test_api_pengumuman_menyimpan_data_surat_dan_pdf_bisa_diunduh_dari_aplikasi(): void
    {
        $this->api($this->kepsek, 'guru', 'POST', '/api/v1/admin/pengumuman', [
            'judul' => 'Libur Semester', 'isi' => 'Libur akhir semester.', 'sasaran' => 'Semua',
            'tanggal_surat' => '2026-11-17', 'tempat' => 'Kab. Kubu Raya',
            'jadwal_hari_tanggal' => 'Senin, 1 Desember 2026', 'jadwal_waktu' => '08.00 WIB', 'jadwal_tempat' => 'Aula',
        ])->assertCreated()
            ->assertJsonPath('data.nomor_surat', '001/SDIT-DF/PENG/XI/2026')
            ->assertJsonPath('data.tanggal_surat', '2026-11-17')
            ->assertJsonPath('data.jadwal_waktu', '08.00 WIB');

        $p = Pengumuman::firstOrFail();
        $this->api($this->kepsek, 'guru', 'GET', '/api/v1/admin/pengumuman')->assertOk()
            ->assertJsonPath('data.0.tempat', 'Kab. Kubu Raya');
        $this->api($this->kepsek, 'guru', 'GET', '/api/v1/admin/pengumuman/'.$p->id.'/pdf')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        // Wali & guru membaca nomor surat dan jadwal dari aplikasi.
        $this->api(Wali::first(), 'wali', 'GET', '/api/v1/wali/pengumuman')->assertOk()
            ->assertJsonPath('data.0.nomor_surat', '001/SDIT-DF/PENG/XI/2026')
            ->assertJsonPath('data.0.jadwal_tempat', 'Aula');
        $this->api($this->guru, 'guru', 'GET', '/api/v1/guru/pengumuman')->assertOk()
            ->assertJsonPath('data.0.nomor_surat', '001/SDIT-DF/PENG/XI/2026');

        // Ketuk kartu di aplikasi → surat PDF dibuka (wali & guru), pengumuman
        // khusus sasaran lain tidak bisa dibuka.
        $this->api(Wali::first(), 'wali', 'GET', '/api/v1/wali/pengumuman/'.$p->id.'/pdf')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        // Bentuk lama juga dilayani: /pengumuman/surat/{id}.
        $this->api(Wali::first(), 'wali', 'GET', '/api/v1/wali/pengumuman/surat/'.$p->id)
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->api($this->guru, 'guru', 'GET', '/api/v1/guru/pengumuman/'.$p->id.'/pdf')
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $rahasia = Pengumuman::create(['judul' => 'Khusus Wali', 'isi' => 'b', 'sasaran' => 'Wali Murid', 'dibuat_oleh' => $this->kepsek->id]);
        $this->api($this->guru, 'guru', 'GET', '/api/v1/guru/pengumuman/'.$rahasia->id.'/pdf')->assertStatus(404);

        // Nomor tetap saat diubah lewat aplikasi; PDF hanya untuk admin.
        $this->api($this->kepsek, 'guru', 'PUT', '/api/v1/admin/pengumuman/'.$p->id, [
            'judul' => 'Libur Semester', 'isi' => 'Diperbarui.', 'sasaran' => 'Semua',
        ])->assertCreated()->assertJsonPath('data.nomor_surat', '001/SDIT-DF/PENG/XI/2026');
        $this->api($this->guru, 'guru', 'GET', '/api/v1/admin/pengumuman/'.$p->id.'/pdf')->assertStatus(403);
    }

    public function test_filter_sasaran_dihapus_tetapi_guru_tetap_hanya_melihat_semua_dan_guru(): void
    {
        Pengumuman::create(['judul' => 'Untuk Semua', 'isi' => 'a', 'sasaran' => 'Semua', 'dibuat_oleh' => $this->kepsek->id]);
        Pengumuman::create(['judul' => 'Khusus Wali', 'isi' => 'b', 'sasaran' => 'Wali Murid', 'dibuat_oleh' => $this->kepsek->id]);

        $this->actingAs($this->guru)->get('/pengumuman?sasaran=Wali+Murid')->assertOk()
            ->assertSee('Untuk Semua')->assertDontSee('Khusus Wali')->assertDontSee('Semua sasaran');
        $this->actingAs($this->kepsek)->get('/pengumuman')->assertOk()
            ->assertSee('Untuk Semua')->assertSee('Khusus Wali')->assertDontSee('Semua sasaran');
    }
}
