<?php

namespace Tests\Feature\Api;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\KonselingPercakapan;
use App\Models\MataPelajaran;
use App\Models\NilaiRapot;
use App\Models\PengajuanIzin;
use App\Models\Pengumuman;
use App\Models\Presensi;
use App\Models\PresensiSholat;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use App\Models\Wali;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WaliAdminApiTest extends TestCase
{
    use RefreshDatabase;

    private User $sinta;

    private User $guru;

    private Kelas $kelas;

    private Siswa $zaki;

    private Siswa $lain;

    private JadwalPelajaran $jadwal;

    private MataPelajaran $mtk;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta'], 'presensi_guru.kecualikan_username' => ['admin']]);
        Carbon::setTestNow(Carbon::parse('2026-11-09 13:30:00', 'Asia/Pontianak')); // Senin
        Storage::fake('local');

        $this->sinta = User::create(['name' => 'sinta', 'nama_lengkap' => 'Dewi Quraisyinta', 'username' => 'sinta', 'email' => 's@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->guru = User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ust. Ahmad Fauzi', 'username' => 'ahmad', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->kelas = Kelas::create(['nama_kelas' => 'Kelas 5A', 'wali_kelas_id' => $this->guru->id]);
        $this->kelas->forceFill(['pengampu_konseling_id' => $this->guru->id])->save();
        $this->mtk = MataPelajaran::create(['kode_pelajaran' => 'MTK', 'nama_pelajaran' => 'Matematika']);
        $this->jadwal = JadwalPelajaran::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guru->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00']);
        JadwalPelajaran::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guru->id, 'hari' => 'Selasa', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00']);
        $this->zaki = Siswa::create(['nis' => '2024002', 'nisn' => '01', 'nama_siswa' => 'Muhammad Zaki', 'tanggal_lahir' => '2018-07-05', 'jenis_kelamin' => 'L', 'kelas_id' => $this->kelas->id, 'status' => 'Aktif', 'nama_wali' => 'Agus Ahmad']);
        $kelasLain = Kelas::create(['nama_kelas' => 'Kelas 4B']);
        $this->lain = Siswa::create(['nis' => '2024009', 'nisn' => '09', 'nama_siswa' => 'Anak Lain', 'tanggal_lahir' => '2018-01-01', 'jenis_kelamin' => 'P', 'kelas_id' => $kelasLain->id, 'status' => 'Aktif', 'nama_wali' => 'Wali Lain']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function as(User|Wali $akun, string $peran): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$akun->createToken('t', [$peran])->plainTextToken, 'Accept' => 'application/json'];
    }

    /** Reset guard agar setiap request benar-benar memakai token di header-nya. */
    private function req(array $headers): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withHeaders($headers);
    }

    private function wali(): array
    {
        return $this->as(Wali::firstWhere('username', '2024002'), 'wali');
    }

    public function test_wali_dashboard_kehadiran_jadwal_dan_tidak_bisa_melihat_anak_lain(): void
    {
        Presensi::create(['jadwal_id' => $this->jadwal->id, 'siswa_id' => $this->zaki->id, 'tanggal' => '2026-11-09', 'status_kehadiran' => 'H']);
        PresensiSholat::create(['siswa_id' => $this->zaki->id, 'kelas_id' => $this->kelas->id, 'tanggal' => '2026-11-09', 'jenis_sholat' => 'Dzuhur', 'status_kehadiran' => 'Hadir']);
        Tugas::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guru->id, 'judul' => 'Latihan 3', 'tenggat' => '2026-11-10']);
        Pengumuman::create(['judul' => 'Rapat Guru', 'isi' => 'x', 'sasaran' => 'Guru']);
        Pengumuman::create(['judul' => 'Manasik Haji', 'isi' => 'y', 'sasaran' => 'Wali Murid']);
        $h = $this->wali();

        $this->req($h)->getJson('/api/v1/wali/dashboard')->assertOk()
            ->assertJsonPath('anak.nama', 'Muhammad Zaki')->assertJsonPath('anak.kelas_singkat', '5A')
            ->assertJsonPath('aktivitas.kehadiran', 'Hadir')
            ->assertJsonPath('aktivitas.sholat.0.status', null)->assertJsonPath('aktivitas.sholat.1.status', 'Hadir')
            ->assertJsonPath('tugas.judul', 'Latihan 3')->assertJsonPath('pengumuman.judul', 'Manasik Haji');

        $this->req($h)->getJson('/api/v1/wali/kehadiran?bulan=2026-11')->assertOk()->assertJsonPath('hadir', 1)->assertJsonPath('persen_hadir', 100)
            ->assertJsonPath('data.0.sholat.Dzuhur', 'Hadir');
        $this->req($h)->getJson('/api/v1/wali/jadwal')->assertOk()->assertJsonPath('besok.hari', 'Selasa')->assertJsonPath('besok.bawa.0', 'Matematika');
        $this->req($h)->getJson('/api/v1/wali/pengumuman')->assertOk()->assertJsonCount(1, 'data');

        // Tidak bisa mengakses anak orang lain atau endpoint guru/admin.
        $this->req($h)->getJson('/api/v1/wali/dashboard?siswa_id='.$this->lain->id)->assertStatus(404);
        $this->req($h)->getJson('/api/v1/guru/dashboard')->assertStatus(403);
        $this->req($h)->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_wali_mengajukan_izin_dengan_foto_dan_guru_menyetujui(): void
    {
        $h = $this->wali();
        $res = $this->req($h)->post('/api/v1/wali/izin', [
            'jenis' => 'Sakit', 'tanggal_mulai' => '2026-11-10', 'tanggal_selesai' => '2026-11-10', 'alasan' => 'Demam',
            'lampiran' => UploadedFile::fake()->image('surat.jpg'),
        ]);
        $res->assertCreated()->assertJsonPath('izin.status', 'Menunggu');
        $izin = PengajuanIzin::firstOrFail();
        Storage::disk('local')->assertExists($izin->lampiran);

        $this->req($h)->post('/api/v1/wali/izin', ['jenis' => 'Sakit', 'tanggal_mulai' => '2026-12-31', 'tanggal_selesai' => '2026-12-31', 'alasan' => 'x'])->assertStatus(422);

        $g = $this->as($this->guru, 'guru');
        $this->req($g)->getJson('/api/v1/guru/izin')->assertOk()->assertJsonPath('data.0.siswa', 'Muhammad Zaki')->assertJsonPath('data.0.ada_lampiran', true);
        $this->req($g)->get('/api/v1/guru/izin/'.$izin->id.'/lampiran')->assertOk();
        $this->req($g)->postJson('/api/v1/guru/izin/'.$izin->id.'/setujui')->assertOk()->assertJsonPath('jumlah_presensi', 1);
        $this->assertSame('S', Presensi::where('siswa_id', $this->zaki->id)->value('status_kehadiran'));
        $this->req($g)->postJson('/api/v1/guru/izin/'.$izin->id.'/tolak', ['catatan' => 'x'])->assertStatus(409);

        $this->req($this->wali())->getJson('/api/v1/wali/izin')->assertJsonPath('data.0.status', 'Disetujui');
    }

    public function test_rapor_per_semester_dan_grafik_dua_semester(): void
    {
        $ganjil = TahunAjaran::create(['nama_semester' => '2026/2027 - Ganjil', 'status_aktif' => 'Y']);
        $genap = TahunAjaran::create(['nama_semester' => '2026/2027 - Genap', 'status_aktif' => 'N']);
        $pai = MataPelajaran::create(['kode_pelajaran' => 'PAI', 'nama_pelajaran' => 'Pendidikan Agama Islam']);
        foreach ([[$ganjil, $this->mtk, 86.5], [$ganjil, $pai, 92.0], [$genap, $this->mtk, 88.0]] as [$ta, $m, $n]) {
            NilaiRapot::create(['tahun_ajaran_id' => $ta->id, 'siswa_id' => $this->zaki->id, 'pelajaran_id' => $m->id, 'formatif_data' => NilaiRapot::getDefaultFormatifData(), 'sas' => 88, 'kehadiran' => 19, 'nilai_rapor' => $n]);
        }
        $h = $this->wali();

        $this->req($h)->getJson('/api/v1/wali/rapor')->assertOk()
            ->assertJsonPath('data.0.tahun', '2026/2027')->assertJsonPath('data.0.kelas_saat_ini', true)
            ->assertJsonPath('data.0.semester.0.semester', 'Ganjil')->assertJsonPath('data.0.semester.0.rata_rata', 89.25)->assertJsonPath('data.0.semester.0.tuntas', true);
        $this->req($h)->getJson('/api/v1/wali/rapor/'.$ganjil->id)->assertOk()
            ->assertJsonPath('predikat', 'A')->assertJsonPath('data.0.mata_pelajaran', 'Matematika')
            ->assertJsonPath('data.0.guru', 'Ust. Ahmad Fauzi')->assertJsonPath('data.0.kehadiran_persen', 95);
        $this->req($h)->getJson('/api/v1/wali/rapor/grafik?tahun=2026/2027')->assertOk()
            ->assertJsonPath('semester.1.rata_rata', 88)->assertJsonPath('mapel.0.nilai.Genap', 88);
    }

    public function test_konseling_wali_ke_pengampu_dua_arah(): void
    {
        $h = $this->wali();
        $this->req($h)->getJson('/api/v1/wali/konseling')->assertOk()->assertJsonPath('pengampu.nama', 'Ust. Ahmad Fauzi');
        $id = $this->req($h)->postJson('/api/v1/wali/konseling', ['siswa_id' => $this->zaki->id, 'topik' => 'Belajar', 'isi' => 'Assalamualaikum'])->assertCreated()->json('id');

        $g = $this->as($this->guru, 'guru');
        $this->req($g)->getJson('/api/v1/guru/konseling')->assertOk()->assertJsonPath('data.0.belum_dibaca', 1);
        $this->req($g)->getJson('/api/v1/guru/konseling/'.$id)->assertOk()->assertJsonPath('pesan.0.isi', 'Assalamualaikum');
        $this->req($g)->postJson('/api/v1/guru/konseling/'.$id, ['isi' => 'Waalaikumsalam'])->assertCreated();

        $h = $this->wali();
        $this->req($h)->getJson('/api/v1/wali/konseling')->assertJsonPath('data.0.belum_dibaca', 1);
        $this->req($h)->getJson('/api/v1/wali/konseling/'.$id)->assertOk()->assertJsonPath('pesan.1.pengirim', 'guru');
        $this->req($h)->getJson('/api/v1/wali/konseling')->assertJsonPath('data.0.belum_dibaca', 0);

        // Wali lain tidak bisa membaca percakapan ini.
        $hLain = $this->as(Wali::firstWhere('username', '2024009'), 'wali');
        $this->req($hLain)->getJson('/api/v1/wali/konseling/'.$id)->assertStatus(403);
        // Kelas tanpa pengampu: tidak bisa memulai.
        $this->req($hLain)->postJson('/api/v1/wali/konseling', ['siswa_id' => $this->lain->id, 'topik' => 'x', 'isi' => 'y'])->assertStatus(422);
        $this->assertSame(1, KonselingPercakapan::count());
    }

    public function test_wali_ubah_data_tidak_bisa_mengganti_tanggal_lahir_sandi(): void
    {
        $h = $this->wali();
        $this->req($h)->putJson('/api/v1/wali/siswa/'.$this->zaki->id, ['tanggal_lahir' => '2010-01-01', 'jenis_kelamin' => 'L', 'alamat' => 'Sui Durian', 'nama_wali' => 'Agus Ahmad Baru', 'no_hp_wali' => '0821'])
            ->assertOk()->assertJsonPath('anak.alamat', 'Sui Durian')->assertJsonPath('anak.tanggal_lahir', '2018-07-05');
        $this->req($h)->putJson('/api/v1/wali/siswa/'.$this->lain->id, ['jenis_kelamin' => 'L', 'nama_wali' => 'Xyz'])->assertStatus(403);
        $this->postJson('/api/v1/auth/login', ['peran' => 'wali', 'username' => '2024002', 'password' => '18-07-05'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['peran' => 'wali', 'username' => '2024002', 'password' => '10-01-01'])->assertStatus(422);
    }

    public function test_konseling_ubah_hapus_dan_foto_pribadi(): void
    {
        Storage::fake('local');
        $h = $this->wali();
        $id = $this->req($h)->postJson('/api/v1/wali/konseling', ['siswa_id' => $this->zaki->id, 'topik' => 'Belajar', 'isi' => 'Halo'])->assertCreated()->json('id');
        $g = $this->as($this->guru, 'guru');

        // Guru mengirim foto tanpa teks.
        $pid = $this->req($g)->post('/api/v1/guru/konseling/'.$id, ['foto' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])->assertCreated()->json('id');
        $pesan = \App\Models\KonselingPesan::find($pid);
        Storage::disk('local')->assertExists($pesan->foto);

        // Foto hanya dibuka pengampu dan wali; kepala sekolah dan wali lain tidak.
        $this->req($g)->get('/api/v1/guru/konseling/'.$id.'/pesan/'.$pid.'/foto')->assertOk();
        $this->req($this->wali())->get('/api/v1/wali/konseling/'.$id.'/pesan/'.$pid.'/foto')->assertOk();
        $this->req($this->as($this->sinta, 'guru'))->get('/api/v1/guru/konseling/'.$id.'/pesan/'.$pid.'/foto')->assertStatus(403);
        $this->req($this->as(Wali::firstWhere('username', '2024009'), 'wali'))->get('/api/v1/wali/konseling/'.$id.'/pesan/'.$pid.'/foto')->assertStatus(403);

        // Ubah pesan sendiri; pesan pihak lain ditolak.
        $wid = \App\Models\KonselingPesan::where('pengirim', 'wali')->value('id');
        $this->req($this->wali())->putJson('/api/v1/wali/konseling/'.$id.'/pesan/'.$wid, ['isi' => 'Halo, diperbaiki'])->assertOk();
        $this->assertNotNull(\App\Models\KonselingPesan::find($wid)->diedit_pada);
        $this->req($this->wali())->deleteJson('/api/v1/wali/konseling/'.$id.'/pesan/'.$pid)->assertStatus(403);
        $this->req($this->as($this->sinta, 'guru'))->putJson('/api/v1/guru/konseling/'.$id.'/pesan/'.$pid, ['isi' => 'x'])->assertStatus(403);

        // Hapus pesan foto menghapus berkasnya.
        $this->req($g)->deleteJson('/api/v1/guru/konseling/'.$id.'/pesan/'.$pid)->assertOk();
        Storage::disk('local')->assertMissing($pesan->foto);
        $this->assertNull(\App\Models\KonselingPesan::find($pid));
    }

    public function test_guru_tugas_dan_nilai_lewat_api(): void
    {
        $g = $this->as($this->guru, 'guru');
        $this->req($g)->getJson('/api/v1/guru/tugas')->assertOk()->assertJsonPath('pilihan.0.label', 'Matematika - Kelas 5A');
        $id = $this->req($g)->postJson('/api/v1/guru/tugas', ['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'judul' => 'Latihan 3', 'tenggat' => '2026-11-10'])->assertCreated()->json('id');
        $this->req($g)->postJson('/api/v1/guru/tugas/'.$id.'/nilai', ['nilai' => [['siswa_id' => $this->zaki->id, 'nilai' => 90]]])->assertOk()->assertJsonPath('tersimpan', 1);
        $this->req($g)->getJson('/api/v1/guru/tugas/'.$id)->assertJsonPath('data.0.nilai', 90);
        $this->req($this->wali())->getJson('/api/v1/wali/tugas')->assertJsonPath('data.0.nilai', 90);

        $this->req($this->as($this->sinta, 'guru'))->postJson('/api/v1/guru/tugas', ['kelas_id' => $this->lain->kelas_id, 'pelajaran_id' => $this->mtk->id, 'judul' => 'x'])->assertCreated();
        $this->req($g)->postJson('/api/v1/guru/tugas', ['kelas_id' => $this->lain->kelas_id, 'pelajaran_id' => $this->mtk->id, 'judul' => 'x'])->assertStatus(403);
    }

    public function test_admin_dashboard_rekap_dan_pengelolaan(): void
    {
        Presensi::create(['jadwal_id' => $this->jadwal->id, 'siswa_id' => $this->zaki->id, 'tanggal' => '2026-11-09', 'status_kehadiran' => 'H']);
        PresensiSholat::create(['siswa_id' => $this->zaki->id, 'kelas_id' => $this->kelas->id, 'tanggal' => '2026-11-09', 'jenis_sholat' => 'Dzuhur', 'status_kehadiran' => 'Hadir']);
        $a = $this->as($this->sinta, 'guru');

        $this->req($a)->getJson('/api/v1/admin/dashboard')->assertOk()->assertJsonPath('siswa.hadir', 1)->assertJsonPath('siswa.total', 2)->assertJsonPath('guru.total', 2);
        $this->req($a)->getJson('/api/v1/admin/rekap/absensi?kelas_id='.$this->kelas->id)->assertOk()->assertJsonPath('data.0.rekap.H', 1);
        $this->req($a)->getJson('/api/v1/admin/rekap/sholat?kelas_id='.$this->kelas->id.'&jenis=Dzuhur')->assertOk()->assertJsonPath('data.0.rekap.hadir', 1);
        $this->req($a)->getJson('/api/v1/admin/rekap/guru')->assertOk()->assertJsonCount(2, 'data');
        $this->req($a)->getJson('/api/v1/admin/siswa?q=zaki')->assertOk()->assertJsonPath('total', 1);
        $this->req($a)->getJson('/api/v1/admin/kelas')->assertOk()->assertJsonPath('data.1.nama', 'Kelas 5A')->assertJsonPath('data.1.mapel.0.nama', 'Matematika')->assertJsonPath('data.0.mapel', []);

        $this->req($a)->postJson('/api/v1/admin/pengumuman', ['judul' => 'Libur', 'isi' => 'Libur semester', 'sasaran' => 'Semua', 'penting' => true])->assertCreated();
        $this->req($a)->postJson('/api/v1/admin/tahun-ajaran', ['tahun' => '2026/2027', 'semester' => 'Ganjil', 'aktif' => true])->assertCreated();
        $this->req($a)->postJson('/api/v1/admin/tahun-ajaran', ['tahun' => '2026/2027', 'semester' => 'Ganjil'])->assertStatus(422);
        $this->assertSame('Y', TahunAjaran::first()->status_aktif);
        $this->req($a)->postJson('/api/v1/admin/mapel', ['kode' => 'mtk', 'nama' => 'Dobel'])->assertJsonValidationErrors('kode');
        $this->req($a)->deleteJson('/api/v1/admin/mapel/'.$this->mtk->id)->assertStatus(422);

        // Guru biasa tidak boleh.
        $this->req($this->as($this->guru, 'guru'))->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_admin_mengunduh_ekspor_sebagai_berkas_csv_dan_pdf(): void
    {
        Presensi::create(['jadwal_id' => $this->jadwal->id, 'siswa_id' => $this->zaki->id, 'tanggal' => '2026-11-09', 'status_kehadiran' => 'H']);
        $a = $this->as($this->sinta, 'guru');
        $q = '?kelas_id='.$this->kelas->id.'&rentang=bulan_ini&tanggal=2026-11-09';

        $csv = $this->req($a)->get('/api/v1/admin/ekspor/absensi/csv'.$q);
        $csv->assertOk()->assertHeader('content-disposition');
        $this->assertStringContainsString('attachment; filename="absensi-', $csv->headers->get('content-disposition'));
        $this->assertStringContainsString('Zaki', $csv->getContent());

        $pdf = $this->req($a)->get('/api/v1/admin/ekspor/absensi/pdf'.$q);
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $kid = '?kelas_id='.$this->kelas->id.'&jenis=Dzuhur&rentang=bulan_ini&tanggal=2026-11-09';
        foreach (['sholat', 'nilai', 'guru-presensi', 'data-guru', 'data-siswa'] as $jenis) {
            foreach (['csv', 'pdf'] as $fmt) {
                $r = $this->req($a)->get("/api/v1/admin/ekspor/{$jenis}/{$fmt}".$kid);
                $r->assertOk();
                if ($fmt === 'pdf') {
                    $this->assertStringStartsWith('%PDF', $r->getContent(), $jenis);
                }
            }
        }

        $this->req($a)->get('/api/v1/admin/ekspor/rahasia/pdf')->assertStatus(404);
        $this->req($this->as($this->guru, 'guru'))->get('/api/v1/admin/ekspor/absensi/csv'.$q)->assertStatus(403);
    }

    public function test_kepala_sekolah_mengelola_pengaturan_pengampu_koreksi_dan_wali(): void
    {
        $a = $this->as($this->sinta, 'guru');

        $this->req($a)->putJson('/api/v1/admin/pengaturan-sekolah', ['latitude' => -0.0263, 'longitude' => 109.3425, 'radius_meter' => 150, 'jam_masuk' => '07:15'])->assertOk();
        $this->req($a)->getJson('/api/v1/admin/pengaturan-sekolah')->assertOk()->assertJsonPath('radius_meter', 150)->assertJsonPath('jam_masuk', '07:15');
        $this->req($a)->putJson('/api/v1/admin/pengaturan-sekolah', ['radius_meter' => 5, 'jam_masuk' => '07:15'])->assertStatus(422);

        $this->req($a)->postJson('/api/v1/admin/pengaturan-sekolah/libur', ['tanggal' => '2026-12-24', 'tanggal_akhir' => '2026-12-26', 'keterangan' => 'Libur semester'])->assertCreated();
        $libur = $this->req($a)->getJson('/api/v1/admin/pengaturan-sekolah')->assertJsonCount(3, 'libur')->json('libur.0.id');
        $this->req($a)->deleteJson('/api/v1/admin/pengaturan-sekolah/libur/'.$libur)->assertOk();

        $this->req($a)->putJson('/api/v1/admin/konseling-pengampu', ['pengampu' => [$this->kelas->id => $this->guru->id]])->assertOk();
        $this->assertSame($this->guru->id, $this->kelas->fresh()->pengampu_konseling_id);
        $this->req($a)->getJson('/api/v1/admin/konseling-pengampu')->assertOk()->assertJsonPath('kelas.1.pengampu_id', $this->guru->id);

        $this->req($a)->postJson('/api/v1/admin/presensi-guru/koreksi', ['guru_id' => $this->guru->id, 'tanggal' => '2026-11-09', 'status' => 'Hadir', 'jam_masuk' => '07:00', 'keterangan' => 'Lupa absen'])->assertOk();
        $this->req($a)->getJson('/api/v1/admin/presensi-guru/'.$this->guru->id)->assertOk()->assertJsonPath('data.0.status', 'Hadir')->assertJsonPath('data.0.sumber', 'admin');
        $this->req($a)->postJson('/api/v1/admin/presensi-guru/koreksi', ['guru_id' => $this->guru->id, 'tanggal' => '2026-11-09', 'status' => 'Hadir', 'jam_masuk' => '07:00'])->assertJsonValidationErrors('keterangan');
        $this->req($a)->deleteJson('/api/v1/admin/guru/'.$this->guru->id.'/wajah')->assertOk();

        $this->req($a)->getJson('/api/v1/admin/wali')->assertOk()->assertJsonStructure(['total', 'data' => [['nama', 'login_nis', 'anak']]]);
        $this->req($a)->postJson('/api/v1/admin/wali/sinkron')->assertOk();

        $this->req($this->as($this->guru, 'guru'))->getJson('/api/v1/admin/wali')->assertStatus(403);
        $this->req($this->as($this->guru, 'guru'))->putJson('/api/v1/admin/pengaturan-sekolah', ['radius_meter' => 100, 'jam_masuk' => '07:00'])->assertStatus(403);
    }

    public function test_upload_foto_siswa(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::fake('local');
        $h = $this->wali();
        $file = \Illuminate\Http\UploadedFile::fake()->image('siswa.jpg', 200, 200);

        $res = $this->req($h)->post('/api/v1/wali/profil/foto', ['foto' => $file]);
        $res->assertOk()->assertJsonStructure(['message', 'foto_url']);
        $this->assertNotNull($this->zaki->fresh()->foto);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($this->zaki->fresh()->foto);

        // Wali sendiri dan guru boleh melihat; wali lain tidak.
        $url = parse_url($res->json('foto_url'), PHP_URL_PATH);
        $this->req($this->wali())->get($url)->assertOk();
        $this->req($this->as($this->guru, 'guru'))->get($url)->assertOk();
        $this->req($this->as(Wali::firstWhere('username', '2024009'), 'wali'))->get($url)->assertForbidden();
    }
}
