<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KonselingPercakapan;
use App\Models\KonselingPesan;
use App\Models\Pengumuman;
use App\Models\PresensiGuru;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KonselingPengumumanPresensiGuruTest extends TestCase
{
    use RefreshDatabase;

    private User $sinta;

    private User $pengampu;

    private User $guruLain;

    private Kelas $kelas;

    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta'], 'presensi_guru.kecualikan_username' => ['admin']]);

        $this->sinta = $this->makeUser('sinta', 'Dewi Quraisyinta');
        $this->pengampu = $this->makeUser('rina', 'Rina Pengampu');
        $this->guruLain = $this->makeUser('budi', 'Budi Lain');
        $this->makeUser('admin', 'Akun Sistem');

        $this->kelas = Kelas::create(['nama_kelas' => 'Kelas 5A', 'wali_kelas_id' => $this->guruLain->id]);
        $this->siswa = Siswa::create([
            'nis' => '5001', 'nisn' => '95001', 'nama_siswa' => 'Zahra', 'tanggal_lahir' => '2015-01-01',
            'jenis_kelamin' => 'P', 'kelas_id' => $this->kelas->id, 'status' => 'Aktif', 'nama_wali' => 'Ibu Zahra',
        ]);
    }

    private function makeUser(string $username, string $nama): User
    {
        return User::create([
            'name' => $username, 'nama_lengkap' => $nama, 'username' => $username,
            'email' => $username.'@s.id', 'password' => 'Password123', 'status' => 'Aktif', 'jenis_kelamin' => 'P',
        ]);
    }

    private function setPengampu(): void
    {
        $this->actingAs($this->sinta)->put('/konseling/pengampu', [
            'pengampu' => [$this->kelas->id => $this->pengampu->id],
        ])->assertRedirect('/konseling/pengampu');
    }

    private function percakapanDariWali(): KonselingPercakapan
    {
        // Mensimulasikan pesan dari aplikasi wali.
        $p = KonselingPercakapan::create(['siswa_id' => $this->siswa->id, 'topik' => 'Anak susah fokus', 'pesan_terakhir_pada' => now()]);
        $p->pesan()->create(['pengirim' => KonselingPesan::WALI, 'isi' => 'Assalamualaikum Bu, anak saya susah fokus.']);

        return $p;
    }

    // ── Konseling ─────────────────────────────────────────────────────

    public function test_hanya_admin_yang_bisa_mengatur_pengampu(): void
    {
        $this->actingAs($this->guruLain)->get('/konseling/pengampu')->assertStatus(403);
        $this->actingAs($this->guruLain)->put('/konseling/pengampu', ['pengampu' => [$this->kelas->id => $this->guruLain->id]])->assertStatus(403);

        $this->setPengampu();
        $this->assertSame($this->pengampu->id, $this->kelas->fresh()->pengampu_konseling_id);

        // Mengosongkan pengampu.
        $this->actingAs($this->sinta)->put('/konseling/pengampu', ['pengampu' => [$this->kelas->id => '']]);
        $this->assertNull($this->kelas->fresh()->pengampu_konseling_id);
    }

    public function test_pengampu_melihat_membalas_dan_badge_hilang_setelah_dibaca(): void
    {
        $this->setPengampu();
        $p = $this->percakapanDariWali();

        $this->actingAs($this->pengampu)->get('/dashboard')->assertSee('1 pesan wali belum dibaca');
        $this->actingAs($this->pengampu)->get('/konseling')->assertOk()->assertSee('Anak susah fokus');

        $this->actingAs($this->pengampu)->get('/konseling/'.$p->id)->assertOk()->assertSee('susah fokus');
        $this->assertNotNull($p->pesan()->first()->dibaca_pada);
        $this->actingAs($this->pengampu)->get('/dashboard')->assertDontSee('pesan wali belum dibaca');

        $this->actingAs($this->pengampu)->post('/konseling/'.$p->id.'/balas', ['isi' => 'Waalaikumsalam, kita atur jadwal bertemu ya Bu.'])
            ->assertRedirect('/konseling/'.$p->id);
        $balasan = $p->pesan()->latest('id')->first();
        $this->assertSame(KonselingPesan::GURU, $balasan->pengirim);
        $this->assertSame($this->pengampu->id, $balasan->guru_id);
    }

    public function test_guru_bukan_pengampu_tidak_bisa_membuka_atau_membalas(): void
    {
        $this->setPengampu();
        $p = $this->percakapanDariWali();

        $this->actingAs($this->guruLain)->get('/konseling')->assertOk()->assertDontSee('Anak susah fokus');
        $this->actingAs($this->guruLain)->get('/konseling/'.$p->id)->assertStatus(403);
        $this->actingAs($this->guruLain)->post('/konseling/'.$p->id.'/balas', ['isi' => 'x'])->assertStatus(403);
        $this->actingAs($this->guruLain)->post('/konseling', [
            'siswa_id' => $this->siswa->id, 'topik' => 'Coba', 'isi' => 'Halo',
        ])->assertSessionHasErrors('siswa_id');
    }

    public function test_admin_tidak_bisa_membaca_konseling_dan_ganti_pengampu_memindahkan_percakapan(): void
    {
        $this->setPengampu();
        $p = $this->percakapanDariWali();

        // Privasi: kepala sekolah tidak boleh membaca, membalas, atau melihat daftarnya.
        $this->actingAs($this->sinta)->get('/konseling/'.$p->id)->assertForbidden();
        $this->actingAs($this->sinta)->post('/konseling/'.$p->id.'/balas', ['isi' => 'intip'])->assertForbidden();
        $this->actingAs($this->sinta)->get('/konseling')->assertOk()->assertDontSee($p->topik);
        $this->assertNull($p->pesan()->first()->dibaca_pada);

        $this->actingAs($this->sinta)->put('/konseling/pengampu', ['pengampu' => [$this->kelas->id => $this->guruLain->id]]);
        $this->actingAs($this->guruLain)->get('/konseling/'.$p->id)->assertOk();
        $this->actingAs($this->pengampu)->get('/konseling/'.$p->id)->assertStatus(403);
    }

    public function test_pengampu_memulai_percakapan_dan_menandai_selesai(): void
    {
        $this->setPengampu();

        $this->actingAs($this->pengampu)->post('/konseling', [
            'siswa_id' => $this->siswa->id, 'topik' => 'Nilai Matematika', 'isi' => 'Bu, nilai Zahra turun.',
        ])->assertRedirect();

        $p = KonselingPercakapan::firstOrFail();
        $this->assertSame(1, $p->pesan()->count());

        $this->actingAs($this->pengampu)->put('/konseling/'.$p->id.'/status', ['status' => 'Selesai']);
        $this->assertSame('Selesai', $p->fresh()->status);
        $this->actingAs($this->pengampu)->get('/konseling')->assertDontSee('Nilai Matematika');
        $this->actingAs($this->pengampu)->get('/konseling?status=Selesai')->assertSee('Nilai Matematika');
    }

    // ── Pengumuman ────────────────────────────────────────────────────

    public function test_admin_mengelola_pengumuman_guru_hanya_membaca_yang_untuk_guru(): void
    {
        $this->actingAs($this->guruLain)->get('/pengumuman/buat')->assertStatus(403);
        $this->actingAs($this->guruLain)->post('/pengumuman', ['judul' => 'x', 'isi' => 'y', 'sasaran' => 'Semua'])->assertStatus(403);

        $this->actingAs($this->sinta)->post('/pengumuman', [
            'judul' => 'Rapat Guru Sabtu', 'isi' => 'Rapat pukul 09.00', 'sasaran' => 'Guru', 'penting' => '1',
        ])->assertRedirect('/pengumuman');
        $this->actingAs($this->sinta)->post('/pengumuman', [
            'judul' => 'Pengambilan Rapor', 'isi' => 'Untuk orang tua', 'sasaran' => 'Wali Murid',
        ]);
        $this->actingAs($this->sinta)->post('/pengumuman', ['judul' => 'x', 'isi' => 'y', 'sasaran' => 'Siswa'])
            ->assertSessionHasErrors('sasaran');

        $rapat = Pengumuman::where('judul', 'Rapat Guru Sabtu')->firstOrFail();
        $this->assertTrue($rapat->penting);
        $this->assertSame($this->sinta->id, $rapat->dibuat_oleh);

        $this->actingAs($this->guruLain)->get('/pengumuman')->assertOk()
            ->assertSee('Rapat Guru Sabtu')
            ->assertDontSee('Pengambilan Rapor');
        $this->actingAs($this->sinta)->get('/pengumuman')->assertSee('Pengambilan Rapor');

        $this->actingAs($this->sinta)->put('/pengumuman/'.$rapat->id, ['judul' => 'Rapat Guru Ditunda', 'isi' => 'Ditunda', 'sasaran' => 'Semua']);
        $this->assertFalse($rapat->fresh()->penting);
        $this->actingAs($this->sinta)->delete('/pengumuman/'.$rapat->id)->assertRedirect('/pengumuman');
        $this->assertNull($rapat->fresh());
    }

    // ── Rekap presensi guru ───────────────────────────────────────────

    public function test_rekap_presensi_guru_menghitung_hadir_terlambat_dan_tidak_hadir(): void
    {
        $data = [
            ['2026-11-02', 'Hadir', '06:55:00'],
            ['2026-11-03', 'Hadir', '07:20:00'],
            ['2026-11-04', 'Tidak Hadir', null],
        ];
        foreach ($data as [$tgl, $status, $jam]) {
            PresensiGuru::create([
                'guru_id' => $this->pengampu->id, 'tanggal' => $tgl, 'status' => $status, 'jam_masuk' => $jam,
                'masuk_jarak_meter' => $jam ? 42 : null, 'masuk_skor_wajah' => $jam ? 97.5 : null,
            ]);
        }

        $this->actingAs($this->guruLain)->get('/presensi-guru/rekap')->assertStatus(403);

        $this->actingAs($this->sinta)->get('/presensi-guru/rekap?rentang=bulan_ini&tanggal=2026-11-10')
            ->assertOk()
            ->assertDontSee('Akun Sistem')
            ->assertViewHas('ringkasan', function ($rows) {
                $r = $rows->firstWhere('guru.id', $this->pengampu->id);

                return $r['hadir'] === 2 && $r['terlambat'] === 1 && $r['tidak_hadir'] === 1 && $r['persen'] === 66.7;
            });

        $this->actingAs($this->sinta)->get('/presensi-guru/rekap?rentang=bulan_ini&tanggal=2026-11-10&guru_id='.$this->pengampu->id)
            ->assertOk()->assertSee('Terlambat')->assertSee('42 m')->assertSee('97.5%');

        $csv = $this->actingAs($this->sinta)->get('/presensi-guru/rekap/export/csv?rentang=bulan_ini&tanggal=2026-11-10');
        $this->assertStringContainsString('"Rina Pengampu",2,1,1,66.7', $csv->streamedContent());
        $this->actingAs($this->sinta)->get('/presensi-guru/rekap/export/pdf?rentang=bulan_ini&tanggal=2026-11-10')
            ->assertOk()->assertSee('REKAPITULASI PRESENSI GURU');
    }

    public function test_command_tandai_guru_tidak_hadir(): void
    {
        $senin = '2026-11-02';
        PresensiGuru::create(['guru_id' => $this->pengampu->id, 'tanggal' => $senin, 'status' => 'Hadir', 'jam_masuk' => '07:00:00']);

        $this->artisan('presensi-guru:tandai-tidak-hadir', ['--tanggal' => $senin])->assertSuccessful();
        $this->artisan('presensi-guru:tandai-tidak-hadir', ['--tanggal' => $senin])->assertSuccessful();

        $this->assertSame('Hadir', PresensiGuru::where('guru_id', $this->pengampu->id)->value('status'));
        $this->assertSame('Tidak Hadir', PresensiGuru::where('guru_id', $this->sinta->id)->value('status'));
        $this->assertSame(3, PresensiGuru::count(), 'sinta, rina, budi; akun "admin" dikecualikan, tidak dobel');

        $this->artisan('presensi-guru:tandai-tidak-hadir', ['--tanggal' => '2026-11-08'])->assertSuccessful();
        $this->assertSame(3, PresensiGuru::count(), 'Minggu dilewati');
    }
}
