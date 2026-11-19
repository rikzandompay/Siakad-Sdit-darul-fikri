<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiRapot;
use App\Models\PresensiGuru;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Uji regresi perbaikan audit (#1-#4). */
class AuditPerbaikanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guru;

    private Kelas $kelasWali;

    private Kelas $kelasAjar;

    private MataPelajaran $mtk;

    private TahunAjaran $taAktif;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta']]);

        $buat = fn (string $u, ?string $nip = null) => User::create([
            'name' => $u,
            'nama_lengkap' => ucfirst($u),
            'username' => $u,
            'nip' => $nip,
            'email' => "{$u}@s.id",
            'password' => 'Rahasia123',
            'status' => 'Aktif',
        ]);

        $this->admin = $buat('sinta');
        $this->guru = $buat('budi', '1002');
        $waliLain = $buat('citra', '1003');

        $this->kelasWali = Kelas::create(['nama_kelas' => 'Kelas 5A', 'wali_kelas_id' => $this->guru->id]);
        $this->kelasAjar = Kelas::create(['nama_kelas' => 'Kelas 6A', 'wali_kelas_id' => $waliLain->id]);
        $this->mtk = MataPelajaran::create(['kode_pelajaran' => 'MTK', 'nama_pelajaran' => 'Matematika']);
        JadwalPelajaran::create([
            'kelas_id' => $this->kelasAjar->id,
            'pelajaran_id' => $this->mtk->id,
            'guru_id' => $this->guru->id,
            'hari' => 'Senin',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:00',
        ]);
        $this->taAktif = TahunAjaran::create(['nama_semester' => '2026/2027 Ganjil', 'status_aktif' => 'Y']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── #1 CSV formula injection di kop ekspor ───────────────────────

    public function test_kop_csv_menetralkan_karakter_formula(): void
    {
        $kelasBerbahaya = Kelas::create(['nama_kelas' => '=CMD()', 'wali_kelas_id' => $this->guru->id]);

        $csv = $this->actingAs($this->guru)
            ->get(route('presensi.export.csv', $kelasBerbahaya))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString("'=CMD()", $csv);
        $this->assertStringNotContainsString(',=CMD()', $csv);
    }

    // ── #2 Tanggal acuan memakai WIB (Asia/Pontianak), bukan UTC ─────

    public function test_koreksi_absen_guru_dan_presensi_memakai_tanggal_wib_saat_pagi_dini(): void
    {
        // 22:00 UTC tanggal 18 = 05:00 WIB tanggal 19
        Carbon::setTestNow(Carbon::create(2026, 11, 18, 22, 0, 0, 'UTC'));

        $this->actingAs($this->admin)
            ->post(route('presensi-guru.manual'), [
                'guru_id' => $this->guru->id,
                'tanggal' => '2026-11-19',
                'status' => PresensiGuru::HADIR,
                'jam_masuk' => '07:00',
                'keterangan' => 'Koreksi pagi WIB',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('presensi_guru', [
            'guru_id' => $this->guru->id,
            'tanggal' => '2026-11-19',
        ]);

        Sanctum::actingAs($this->admin, ['guru']);
        $this->postJson('/api/v1/admin/presensi-guru/koreksi', [
            'guru_id' => $this->guru->id,
            'tanggal' => '2026-11-19',
            'status' => PresensiGuru::HADIR,
            'jam_masuk' => '07:05',
            'keterangan' => 'Koreksi API pagi WIB',
        ])->assertOk();

        $this->actingAs($this->guru)
            ->get(route('presensi.show', $this->kelasWali))
            ->assertOk()
            ->assertViewHas('tanggal', '2026-11-19');
    }

    // ── #3 Guard hapus tahun ajaran & mapel di web ───────────────────

    public function test_pengaturan_web_melarang_hapus_semester_aktif_atau_bernilai_dan_mapel_terpakai(): void
    {
        $taBerisi = TahunAjaran::create(['nama_semester' => '2025/2026 Genap', 'status_aktif' => 'N']);
        $taKosong = TahunAjaran::create(['nama_semester' => '2024/2025 Ganjil', 'status_aktif' => 'N']);
        $siswa = Siswa::create([
            'nis' => '2024001',
            'nisn' => '01',
            'nama_siswa' => 'Zaki',
            'tanggal_lahir' => '2018-07-05',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelasWali->id,
            'status' => 'Aktif',
            'nama_wali' => 'Agus',
        ]);
        NilaiRapot::create([
            'tahun_ajaran_id' => $taBerisi->id,
            'siswa_id' => $siswa->id,
            'pelajaran_id' => $this->mtk->id,
            'formatif_data' => NilaiRapot::getDefaultFormatifData(),
            'sas' => 80,
            'kehadiran' => 100,
            'nilai_rapor' => 85,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('pengaturan.tahun-ajaran.destroy', $this->taAktif))
            ->assertSessionHasErrors('tahun_ajaran');
        $this->assertDatabaseHas('tahun_ajaran', ['id' => $this->taAktif->id]);

        $this->actingAs($this->admin)
            ->delete(route('pengaturan.tahun-ajaran.destroy', $taBerisi))
            ->assertSessionHasErrors('tahun_ajaran');
        $this->assertDatabaseHas('tahun_ajaran', ['id' => $taBerisi->id]);

        $this->actingAs($this->admin)
            ->delete(route('pengaturan.tahun-ajaran.destroy', $taKosong))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('tahun_ajaran', ['id' => $taKosong->id]);

        $this->actingAs($this->admin)
            ->delete(route('pengaturan.mapel.destroy', $this->mtk))
            ->assertSessionHasErrors('mapel');
        $this->assertDatabaseHas('mata_pelajaran', ['id' => $this->mtk->id]);

        $mapelKosong = MataPelajaran::create(['kode_pelajaran' => 'SBK', 'nama_pelajaran' => 'Seni Budaya']);
        $this->actingAs($this->admin)
            ->delete(route('pengaturan.mapel.destroy', $mapelKosong))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('mata_pelajaran', ['id' => $mapelKosong->id]);
    }

    // ── #4 Dropdown rekap presensi hanya berisi kelas wali ───────────

    public function test_dropdown_presensi_show_hanya_menampilkan_kelas_wali_guru(): void
    {
        $response = $this->actingAs($this->guru)
            ->get(route('presensi.show', $this->kelasWali))
            ->assertOk();

        $kelasIds = $response->viewData('kelasList')->pluck('id')->all();
        $this->assertSame([$this->kelasWali->id], $kelasIds);
    }
}
