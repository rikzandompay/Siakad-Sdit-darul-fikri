<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiTugas;
use App\Models\PengajuanIzin;
use App\Models\PengaturanSekolah;
use App\Models\Presensi;
use App\Models\PresensiGuru;
use App\Models\PresensiSholat;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use App\Models\WajahGuru;
use App\Models\Wali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiturPendukungAplikasiTest extends TestCase
{
    use RefreshDatabase;

    private User $sinta;

    private User $waliKelas;

    private User $guruMapel;

    private User $guruLain;

    private Kelas $kelas;

    private MataPelajaran $mtk;

    private Siswa $zahra;

    private Siswa $yusuf;

    private JadwalPelajaran $senin;

    private JadwalPelajaran $selasa;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta'], 'presensi_guru.kecualikan_username' => ['admin']]);

        $this->sinta = $this->user('sinta', 'Dewi Quraisyinta');
        $this->waliKelas = $this->user('rina', 'Rina Wali Kelas');
        $this->guruMapel = $this->user('budi', 'Budi Guru Mapel');
        $this->guruLain = $this->user('citra', 'Citra Lain');

        $this->kelas = Kelas::create(['nama_kelas' => 'Kelas 4A', 'wali_kelas_id' => $this->waliKelas->id]);
        $this->mtk = MataPelajaran::create(['kode_pelajaran' => 'MTK', 'nama_pelajaran' => 'Matematika']);
        $this->zahra = $this->siswa('4001', 'Zahra');
        $this->yusuf = $this->siswa('4002', 'Yusuf');

        $this->senin = JadwalPelajaran::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guruMapel->id, 'hari' => 'Senin', 'jam_mulai' => '07:00', 'jam_selesai' => '08:00']);
        $this->selasa = JadwalPelajaran::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guruMapel->id, 'hari' => 'Selasa', 'jam_mulai' => '07:00', 'jam_selesai' => '08:00']);
    }

    private function user(string $username, string $nama): User
    {
        return User::create(['name' => $username, 'nama_lengkap' => $nama, 'username' => $username, 'email' => $username.'@s.id', 'password' => 'Password123', 'status' => 'Aktif', 'jenis_kelamin' => 'P']);
    }

    private function siswa(string $nis, string $nama): Siswa
    {
        return Siswa::create(['nis' => $nis, 'nisn' => '9'.$nis, 'nama_siswa' => $nama, 'tanggal_lahir' => '2016-01-01', 'jenis_kelamin' => 'P', 'kelas_id' => $this->kelas->id, 'status' => 'Aktif']);
    }

    private function izin(string $jenis = 'Sakit', string $mulai = '2026-11-02', string $selesai = '2026-11-04'): PengajuanIzin
    {
        return PengajuanIzin::create(['siswa_id' => $this->zahra->id, 'jenis' => $jenis, 'tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai, 'alasan' => 'Demam']);
    }

    // ── Pengaturan sekolah & hari libur ───────────────────────────────

    public function test_pengaturan_sekolah_hanya_admin_dan_tervalidasi(): void
    {
        $this->actingAs($this->waliKelas)->get('/pengaturan-sekolah')->assertStatus(403);
        $this->actingAs($this->waliKelas)->put('/pengaturan-sekolah', [])->assertStatus(403);

        $this->actingAs($this->sinta)->put('/pengaturan-sekolah', ['latitude' => '120', 'longitude' => '109.3', 'radius_meter' => 5, 'jam_masuk' => '7pagi'])
            ->assertSessionHasErrors(['latitude', 'radius_meter', 'jam_masuk']);
        $this->actingAs($this->sinta)->put('/pengaturan-sekolah', ['latitude' => '-0.0263', 'radius_meter' => 100, 'jam_masuk' => '07:00'])
            ->assertSessionHasErrors('longitude');

        $this->actingAs($this->sinta)->put('/pengaturan-sekolah', ['latitude' => '-0.0263', 'longitude' => '109.3425', 'radius_meter' => 150, 'jam_masuk' => '07:30'])
            ->assertRedirect('/pengaturan-sekolah');

        $this->assertSame(['latitude' => -0.0263, 'longitude' => 109.3425], PengaturanSekolah::lokasi());
        $this->assertSame(150, PengaturanSekolah::radiusMeter());
        $this->assertSame('07:30', PengaturanSekolah::jamMasuk());

        // Jam masuk dari pengaturan dipakai untuk penanda Terlambat.
        $p = PresensiGuru::create(['guru_id' => $this->guruMapel->id, 'tanggal' => '2026-11-02', 'status' => 'Hadir', 'jam_masuk' => '07:20:00']);
        $this->assertFalse($p->terlambat);
        $this->actingAs($this->sinta)->get('/pengaturan-sekolah')->assertOk()->assertSee('Area absen guru');
    }

    public function test_lokasi_boleh_dikosongkan_dan_belum_diatur_menampilkan_peringatan(): void
    {
        $this->assertNull(PengaturanSekolah::lokasi());
        $this->actingAs($this->sinta)->get('/pengaturan-sekolah')->assertSee('Lokasi sekolah belum diatur');
    }

    public function test_hari_libur_rentang_tanggal_dan_dilewati_command(): void
    {
        $this->actingAs($this->sinta)->post('/pengaturan-sekolah/libur', ['tanggal' => '2026-11-02', 'tanggal_akhir' => '2026-11-03', 'keterangan' => 'Libur semester'])
            ->assertRedirect('/pengaturan-sekolah');
        $this->assertSame(2, HariLibur::count());
        $this->assertTrue(HariLibur::isLibur('2026-11-03'));
        $this->assertFalse(HariLibur::isLibur('2026-11-04'));

        $this->actingAs($this->sinta)->post('/pengaturan-sekolah/libur', ['tanggal' => '2026-11-02', 'keterangan' => 'dobel'])->assertSessionHasErrors('tanggal');
        $this->actingAs($this->waliKelas)->post('/pengaturan-sekolah/libur', ['tanggal' => '2026-12-01', 'keterangan' => 'x'])->assertStatus(403);

        $this->artisan('presensi-guru:tandai-tidak-hadir', ['--tanggal' => '2026-11-02'])->assertSuccessful();
        $this->artisan('sholat:tandai-tidak-hadir', ['jenis' => 'Dzuhur', '--tanggal' => '2026-11-02'])->assertSuccessful();
        $this->assertSame(0, PresensiGuru::count());
        $this->assertSame(0, PresensiSholat::count());

        $this->artisan('presensi-guru:tandai-tidak-hadir', ['--tanggal' => '2026-11-04'])->assertSuccessful();
        $this->assertGreaterThan(0, PresensiGuru::count());

        $this->actingAs($this->sinta)->delete('/pengaturan-sekolah/libur/'.HariLibur::first()->id);
        $this->assertSame(1, HariLibur::count());
    }

    // ── Akun wali (otomatis dari data siswa) ──────────────────────────

    private function siswaDenganWali(string $nis, string $nama, ?string $namaWali, string $status = 'Aktif'): Siswa
    {
        return Siswa::create(['nis' => $nis, 'nisn' => '8'.$nis, 'nama_siswa' => $nama, 'tanggal_lahir' => '2016-01-01', 'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas->id, 'status' => $status, 'nama_wali' => $namaWali, 'no_hp_wali' => '0812']);
    }

    public function test_siswa_baru_otomatis_punya_akun_wali_login_nis_dan_nama_wali(): void
    {
        $siswa = $this->siswaDenganWali('5001', 'Aisyah', 'Siti Aminah');

        $wali = Wali::firstWhere('username', '5001');
        $this->assertNotNull($wali);
        $this->assertSame('Siti Aminah', $wali->nama);
        $this->assertSame('Aktif', $wali->status);
        $this->assertSame([$siswa->id], $wali->siswa()->pluck('siswa.id')->all());
        $this->assertNotSame('siti aminah', $wali->password, 'Tersimpan ter-hash');

        // Huruf besar/kecil, spasi ganda, dan tanda baca tidak membuat login gagal.
        $this->assertTrue($wali->cocokNama('siti aminah'));
        $this->assertTrue($wali->cocokNama('  SITI   Aminah. '));
        $this->assertFalse($wali->cocokNama('Siti Aminah Lain'));
        $this->assertFalse($wali->cocokNama(''));
    }

    public function test_ubah_data_siswa_menyinkronkan_akun_wali(): void
    {
        $siswa = $this->siswaDenganWali('5002', 'Bilal', 'Budi Santoso');
        $id = Wali::firstWhere('username', '5002')->id;

        $siswa->update(['nama_wali' => 'Hj. Rahmawati', 'nis' => '5099']);
        $wali = Wali::findOrFail($id);
        $this->assertSame('5099', $wali->username, 'NIS baru menjadi username');
        $this->assertTrue($wali->cocokNama('hj rahmawati'));
        $this->assertFalse($wali->cocokNama('Budi Santoso'), 'Nama lama tidak berlaku');
        $this->assertSame(1, Wali::count(), 'Memperbarui, bukan membuat akun baru');

        $siswa->update(['status' => 'Pindah']);
        $this->assertSame('Nonaktif', $wali->fresh()->status);
        $siswa->update(['status' => 'Aktif']);
        $this->assertSame('Aktif', $wali->fresh()->status);

        $siswa->update(['nama_wali' => null]);
        $this->assertSame(0, Wali::count(), 'Tanpa nama wali tidak ada akun');
        $siswa->update(['nama_wali' => 'Ibu Baru']);
        $this->assertSame(1, Wali::count());
    }

    public function test_hapus_siswa_menghapus_akun_wali_dan_lewat_form_data_siswa(): void
    {
        $this->actingAs($this->sinta)->post('/siswa', [
            'nis' => '5003', 'nisn' => '85003', 'nama_siswa' => 'Citra', 'tanggal_lahir' => '2016-02-02', 'jenis_kelamin' => 'P',
            'kelas_id' => $this->kelas->id, 'nama_wali' => 'Ibu Citra', 'no_hp_wali' => '0813', 'status' => 'Aktif',
        ])->assertRedirect();
        $this->assertTrue(Wali::firstWhere('username', '5003')->cocokNama('ibu citra'));

        $siswa = Siswa::firstWhere('nis', '5003');
        $this->actingAs($this->sinta)->delete('/siswa/'.$siswa->id)->assertRedirect();
        $this->assertNull(Wali::firstWhere('username', '5003'));
    }

    public function test_sinkron_massal_membuat_akun_untuk_siswa_lama_dan_halaman_hanya_admin(): void
    {
        // Siswa "lama": ada sebelum fitur, tanpa akun.
        $siswa = $this->siswaDenganWali('5004', 'Dodi', 'Pak Dodi');
        Wali::query()->delete();
        $tanpa = $this->siswaDenganWali('5005', 'Eka', null);

        $this->actingAs($this->waliKelas)->get('/wali')->assertStatus(403);
        $this->actingAs($this->waliKelas)->post('/wali/sinkron')->assertStatus(403);

        $this->actingAs($this->sinta)->get('/wali')->assertOk()->assertSee('belum punya akun karena nama wali kosong')->assertSee('Eka');
        $this->actingAs($this->sinta)->post('/wali/sinkron')->assertRedirect('/wali');
        $this->assertSame(1, Wali::count());
        $this->assertTrue(Wali::firstWhere('username', '5004')->cocokNama('pak dodi'));
        $this->actingAs($this->sinta)->get('/wali')->assertSee('5004')->assertSee('Pak Dodi');
    }

    // ── Izin siswa ────────────────────────────────────────────────────

    public function test_izin_disetujui_menulis_presensi_hanya_hari_sekolah_dan_bukan_libur(): void
    {
        // 2026-11-02 Senin, 03 Selasa (libur), 04 Rabu (tidak ada jadwal Rabu).
        HariLibur::create(['tanggal' => '2026-11-03', 'keterangan' => 'Libur']);
        \DB::table('presensi')->insert(['jadwal_id' => $this->senin->id, 'siswa_id' => $this->zahra->id, 'tanggal' => '2026-11-02', 'status_kehadiran' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $izin = $this->izin('Sakit', '2026-11-02', '2026-11-04');

        $this->actingAs($this->waliKelas)->post('/izin/'.$izin->id.'/setujui')->assertRedirect('/izin');

        $this->assertSame('Disetujui', $izin->fresh()->status);
        $this->assertSame($this->waliKelas->id, $izin->fresh()->ditinjau_oleh);
        $this->assertSame(1, Presensi::where('siswa_id', $this->zahra->id)->count(), 'Hanya Senin; Selasa libur, Rabu tanpa jadwal');
        $this->assertSame('S', Presensi::where('jadwal_id', $this->senin->id)->value('status_kehadiran'), 'Menimpa Alpa');

        // Siswa lain tidak tersentuh.
        $this->assertSame(0, Presensi::where('siswa_id', $this->yusuf->id)->count());
    }

    public function test_izin_jenis_izin_dicatat_sebagai_i_dan_tidak_bisa_ditinjau_dua_kali(): void
    {
        $izin = $this->izin('Izin', '2026-11-09', '2026-11-10');
        $this->actingAs($this->sinta)->post('/izin/'.$izin->id.'/setujui')->assertRedirect();
        $this->assertSame(['I'], Presensi::pluck('status_kehadiran')->unique()->values()->all());
        $this->assertSame(2, Presensi::count());

        $this->actingAs($this->sinta)->post('/izin/'.$izin->id.'/setujui')->assertStatus(409);
        $this->actingAs($this->sinta)->post('/izin/'.$izin->id.'/tolak', ['catatan' => 'x'])->assertStatus(409);
    }

    public function test_hanya_wali_kelas_atau_admin_yang_bisa_meninjau_dan_penolakan_wajib_alasan(): void
    {
        $izin = $this->izin();

        $this->actingAs($this->guruMapel)->post('/izin/'.$izin->id.'/setujui')->assertStatus(403);
        $this->actingAs($this->guruLain)->post('/izin/'.$izin->id.'/tolak', ['catatan' => 'x'])->assertStatus(403);
        $this->actingAs($this->guruLain)->get('/izin?status=semua')->assertOk()->assertDontSee('Zahra');
        $this->actingAs($this->waliKelas)->get('/izin')->assertOk()->assertSee('Zahra');

        $this->actingAs($this->waliKelas)->post('/izin/'.$izin->id.'/tolak', ['catatan' => ''])->assertSessionHasErrors('catatan');
        $this->actingAs($this->waliKelas)->post('/izin/'.$izin->id.'/tolak', ['catatan' => 'Surat dokter belum ada'])->assertRedirect('/izin');
        $this->assertSame('Ditolak', $izin->fresh()->status);
        $this->assertSame(0, Presensi::count(), 'Penolakan tidak menulis presensi');
    }

    public function test_badge_izin_menunggu_muncul_di_sidebar_wali_kelas(): void
    {
        $this->izin();
        $this->actingAs($this->waliKelas)->get('/dashboard')->assertSee('1 izin menunggu');
        $this->actingAs($this->guruLain)->get('/dashboard')->assertDontSee('izin menunggu');
    }

    // ── Tugas & nilai tugas (ruang per mata pelajaran) ────────────────

    private function ruangUrl(?Kelas $kelas = null, ?MataPelajaran $mapel = null): string
    {
        return '/kelas/'.($kelas ?? $this->kelas)->id.'/mapel/'.($mapel ?? $this->mtk)->id.'/tugas';
    }

    public function test_tiap_mapel_punya_ruang_tugas_yang_hanya_dibuka_pengampunya(): void
    {
        $bahasa = MataPelajaran::create(['kode_pelajaran' => 'BIN', 'nama_pelajaran' => 'Bahasa Indonesia']);
        JadwalPelajaran::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $bahasa->id, 'guru_id' => $this->guruLain->id, 'hari' => 'Rabu', 'jam_mulai' => '07:00', 'jam_selesai' => '08:00']);

        $this->actingAs($this->guruMapel)->post($this->ruangUrl(), ['judul' => 'Latihan Pecahan', 'tenggat' => '2026-11-20'])->assertRedirect();
        $this->actingAs($this->guruLain)->post($this->ruangUrl(null, $bahasa), ['judul' => 'Membaca Cerpen'])->assertRedirect();

        $tugas = Tugas::firstWhere('judul', 'Latihan Pecahan');
        $this->assertSame([$this->kelas->id, $this->mtk->id, $this->guruMapel->id], [$tugas->kelas_id, $tugas->pelajaran_id, $tugas->guru_id]);

        // Ruang terpisah: masing-masing hanya berisi tugas mapelnya.
        $this->actingAs($this->guruMapel)->get($this->ruangUrl())->assertOk()->assertSee('Latihan Pecahan')->assertDontSee('Membaca Cerpen');
        $this->actingAs($this->guruLain)->get($this->ruangUrl(null, $bahasa))->assertOk()->assertSee('Membaca Cerpen')->assertDontSee('Latihan Pecahan');

        // Bukan pengampu: tidak bisa membuka, membuat, atau mengubah ruang itu.
        $this->actingAs($this->guruLain)->get($this->ruangUrl())->assertStatus(403);
        $this->actingAs($this->guruLain)->post($this->ruangUrl(), ['judul' => 'Selundupan'])->assertStatus(403);
        $this->actingAs($this->guruMapel)->get($this->ruangUrl(null, $bahasa))->assertStatus(403);
        $this->actingAs($this->guruLain)->get('/tugas/'.$tugas->id)->assertStatus(403);
        $this->actingAs($this->guruLain)->put('/tugas/'.$tugas->id, ['judul' => 'x'])->assertStatus(403);
        $this->actingAs($this->guruLain)->delete('/tugas/'.$tugas->id)->assertStatus(403);
        $this->assertNull(Tugas::firstWhere('judul', 'Selundupan'));

        // Admin boleh semua ruang. Kelas lain / mapel yang tidak diajar di kelas itu tetap tertutup untuk guru.
        $this->actingAs($this->sinta)->get($this->ruangUrl())->assertOk();
        $this->actingAs($this->sinta)->get($this->ruangUrl(null, $bahasa))->assertOk();
        $kelasLain = Kelas::create(['nama_kelas' => 'Kelas 4B']);
        $this->actingAs($this->guruMapel)->get($this->ruangUrl($kelasLain))->assertStatus(403);

        // Redirect rute lama.
        $this->actingAs($this->guruMapel)->get('/tugas')->assertRedirect('/kelas');
    }

    public function test_halaman_kelas_menampilkan_ruang_tugas_per_mapel(): void
    {
        Tugas::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guruMapel->id, 'judul' => 'A']);
        Tugas::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guruMapel->id, 'judul' => 'B']);

        $this->actingAs($this->guruMapel)->get('/kelas/'.$this->kelas->id)->assertOk()
            ->assertSee('Ruang Tugas per Mata Pelajaran')
            ->assertSee($this->ruangUrl())
            ->assertViewHas('ruangTugas', fn ($r) => $r->count() === 1 && $r[0]['jumlah'] === 2 && $r[0]['boleh'] === true);

        $this->actingAs($this->guruLain)->get('/kelas/'.$this->kelas->id)->assertOk()
            ->assertDontSee($this->ruangUrl())
            ->assertSee('Bukan mata pelajaran Anda')
            ->assertViewHas('ruangTugas', fn ($r) => $r[0]['boleh'] === false);
    }

    public function test_nilai_tugas_disimpan_dikoreksi_dan_dibatasi_siswa_kelas_itu(): void
    {
        $tugas = Tugas::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guruMapel->id, 'judul' => 'Latihan']);
        $kelasLain = Kelas::create(['nama_kelas' => 'Kelas 4B']);
        $orangLain = Siswa::create(['nis' => '9', 'nisn' => '99', 'nama_siswa' => 'Orang Lain', 'tanggal_lahir' => '2016-01-01', 'jenis_kelamin' => 'L', 'kelas_id' => $kelasLain->id, 'status' => 'Aktif']);

        $this->actingAs($this->guruMapel)->post('/tugas/'.$tugas->id.'/nilai', ['nilai' => [
            ['siswa_id' => $this->zahra->id, 'nilai' => '88.5', 'catatan' => 'Bagus'],
            ['siswa_id' => $this->yusuf->id, 'nilai' => ''],
        ]])->assertRedirect('/tugas/'.$tugas->id);
        $this->assertSame(88.5, NilaiTugas::where('siswa_id', $this->zahra->id)->value('nilai'));
        $this->assertSame(1, NilaiTugas::count(), 'Baris kosong dilewati');

        $this->actingAs($this->guruMapel)->post('/tugas/'.$tugas->id.'/nilai', ['nilai' => [['siswa_id' => $this->zahra->id, 'nilai' => '95']]]);
        $this->assertSame(95.0, NilaiTugas::where('siswa_id', $this->zahra->id)->value('nilai'));
        $this->assertSame(1, NilaiTugas::count(), 'Koreksi memperbarui, bukan menggandakan');

        $this->actingAs($this->guruMapel)->post('/tugas/'.$tugas->id.'/nilai', ['nilai' => [['siswa_id' => $this->zahra->id, 'nilai' => '120']]])->assertSessionHasErrors('nilai.0.nilai');
        $this->actingAs($this->guruMapel)->post('/tugas/'.$tugas->id.'/nilai', ['nilai' => [['siswa_id' => $orangLain->id, 'nilai' => '80']]])->assertSessionHasErrors('nilai.0.siswa_id');
        $this->actingAs($this->guruLain)->post('/tugas/'.$tugas->id.'/nilai', ['nilai' => [['siswa_id' => $this->zahra->id, 'nilai' => '1']]])->assertStatus(403);

        $this->actingAs($this->guruMapel)->get('/tugas/'.$tugas->id)->assertOk()->assertSee('Zahra')->assertSee('95');
        $this->actingAs($this->guruMapel)->delete('/tugas/'.$tugas->id)->assertRedirect($this->ruangUrl());
        $this->assertSame(0, NilaiTugas::count(), 'Nilai ikut terhapus');
    }

    // ── Koreksi manual absen guru & reset wajah ───────────────────────

    public function test_admin_mengoreksi_absen_guru_dan_menandai_sumber_admin(): void
    {
        PresensiGuru::create(['guru_id' => $this->guruMapel->id, 'tanggal' => '2026-11-02', 'status' => 'Tidak Hadir', 'sumber' => 'otomatis']);
        $payload = ['guru_id' => $this->guruMapel->id, 'tanggal' => '2026-11-02', 'status' => 'Hadir', 'jam_masuk' => '07:05', 'jam_pulang' => '14:00', 'keterangan' => 'HP rusak'];

        $this->actingAs($this->waliKelas)->post('/presensi-guru/manual', $payload)->assertStatus(403);
        $this->actingAs($this->sinta)->post('/presensi-guru/manual', ['keterangan' => ''] + $payload)->assertSessionHasErrors('keterangan');
        $this->actingAs($this->sinta)->post('/presensi-guru/manual', ['jam_masuk' => null] + $payload)->assertSessionHasErrors('jam_masuk');
        $this->actingAs($this->sinta)->post('/presensi-guru/manual', ['jam_pulang' => '06:00'] + $payload)->assertSessionHasErrors('jam_pulang');
        $this->actingAs($this->sinta)->post('/presensi-guru/manual', ['tanggal' => now()->addDays(2)->toDateString()] + $payload)->assertSessionHasErrors('tanggal');

        $this->actingAs($this->sinta)->post('/presensi-guru/manual', $payload)->assertRedirect();
        $row = PresensiGuru::where('guru_id', $this->guruMapel->id)->firstOrFail();
        $this->assertSame(1, PresensiGuru::where('guru_id', $this->guruMapel->id)->count());
        $this->assertSame('Hadir', $row->status);
        $this->assertSame('admin', $row->sumber);
        $this->assertStringContainsString('HP rusak', $row->keterangan);
        $this->assertStringContainsString('Dewi Quraisyinta', $row->keterangan);

        // Tidak Hadir menghapus jam.
        $this->actingAs($this->sinta)->post('/presensi-guru/manual', ['status' => 'Tidak Hadir'] + $payload);
        $this->assertNull($row->fresh()->jam_masuk);
    }

    public function test_admin_mereset_data_wajah_dan_template_tidak_tampil(): void
    {
        WajahGuru::create(['guru_id' => $this->guruMapel->id, 'template' => 'RAHASIA-EMBEDDING-123', 'didaftarkan_pada' => now()]);
        $this->assertNotSame('RAHASIA-EMBEDDING-123', \DB::table('wajah_guru')->value('template'), 'Tersimpan terenkripsi');
        $this->assertSame('RAHASIA-EMBEDDING-123', WajahGuru::first()->template);

        $this->actingAs($this->sinta)->get('/presensi-guru/rekap?guru_id='.$this->guruMapel->id)->assertOk()->assertSee('Terdaftar')->assertDontSee('RAHASIA-EMBEDDING-123');
        $this->actingAs($this->waliKelas)->delete('/presensi-guru/wajah/'.$this->guruMapel->id)->assertStatus(403);
        $this->assertSame(1, WajahGuru::count());

        $this->actingAs($this->sinta)->delete('/presensi-guru/wajah/'.$this->guruMapel->id)->assertRedirect();
        $this->assertSame(0, WajahGuru::count());
        $this->actingAs($this->sinta)->get('/presensi-guru/rekap?guru_id='.$this->guruMapel->id)->assertSee('Belum terdaftar');
    }
}
