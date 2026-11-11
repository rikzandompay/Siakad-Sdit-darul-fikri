<?php

namespace Tests\Feature;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Perbaikan temuan audit keamanan (safety.md #2-#5). */
class AuditKeamananTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $wali;

    private User $guruMtk;

    private User $guruIpa;

    private Kelas $kelas;

    private MataPelajaran $mtk;

    private MataPelajaran $ipa;

    private TahunAjaran $ta;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta']]);

        $buat = fn (string $u, ?string $nip = null) => User::create(['name' => $u, 'nama_lengkap' => ucfirst($u), 'username' => $u, 'nip' => $nip, 'email' => "{$u}@s.id", 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->admin = $buat('sinta');
        $this->wali = $buat('wali', '1001');
        $this->guruMtk = $buat('budi', '1002');
        $this->guruIpa = $buat('citra', '1003');

        $this->kelas = Kelas::create(['nama_kelas' => 'Kelas 5A', 'wali_kelas_id' => $this->wali->id]);
        $this->mtk = MataPelajaran::create(['kode_pelajaran' => 'MTK', 'nama_pelajaran' => 'Matematika']);
        $this->ipa = MataPelajaran::create(['kode_pelajaran' => 'IPA', 'nama_pelajaran' => 'IPA']);
        JadwalPelajaran::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guruMtk->id, 'hari' => 'Senin', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00']);
        JadwalPelajaran::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->ipa->id, 'guru_id' => $this->guruIpa->id, 'hari' => 'Selasa', 'jam_mulai' => '07:30', 'jam_selesai' => '09:00']);
        $this->ta = TahunAjaran::create(['nama_semester' => '2026/2027 Ganjil', 'status_aktif' => 'Y']);

        Siswa::create(['nis' => '2024002', 'nisn' => '01', 'nama_siswa' => 'Muhammad Zaki', 'tanggal_lahir' => '2018-07-05', 'jenis_kelamin' => 'L', 'kelas_id' => $this->kelas->id, 'status' => 'Aktif', 'nama_wali' => 'Agus']);
    }

    // ── #2 Tanggal lahir (= sandi wali) tidak ikut ekspor guru ───────

    public function test_ekspor_siswa_oleh_guru_tanpa_tanggal_lahir_admin_dengan(): void
    {
        $csvGuru = $this->actingAs($this->wali)->get(route('siswa.export.csv'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Muhammad Zaki', $csvGuru);
        $this->assertStringNotContainsString('Tanggal Lahir', $csvGuru);
        $this->assertStringNotContainsString('2018-07-05', $csvGuru);

        $this->actingAs($this->wali)->get(route('siswa.export.pdf'))->assertOk()
            ->assertSee('Muhammad Zaki')->assertDontSee('Tgl Lahir')->assertDontSee('2018-07-05');

        $csvAdmin = $this->actingAs($this->admin)->get(route('siswa.export.csv'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Tanggal Lahir', $csvAdmin);
        $this->assertStringContainsString('2018-07-05', $csvAdmin);
        $this->actingAs($this->admin)->get(route('siswa.export.pdf'))->assertOk()->assertSee('Tgl Lahir')->assertSee('2018-07-05');
    }

    // ── #3 Kunci login guru per username + IP ─────────────────────────

    private function loginApi(string $sandi, string $ip, string $username = 'budi')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/auth/login', ['peran' => 'guru', 'username' => $username, 'password' => $sandi]);
    }

    public function test_salah_login_dari_satu_ip_tidak_mengunci_guru_di_perangkat_lain(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->loginApi('salah', '10.0.0.66')->assertStatus(422);
        }
        $this->loginApi('Rahasia123', '10.0.0.66')->assertStatus(429);

        // Guru asli dari HP/jaringannya sendiri tetap bisa masuk.
        $this->loginApi('Rahasia123', '10.0.0.7')->assertOk();
    }

    public function test_penebakan_dari_banyak_ip_tetap_dibatasi(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->loginApi('salah', '10.0.1.'.intdiv($i, 4))->assertStatus(422);
        }
        $this->loginApi('Rahasia123', '10.0.9.9')->assertStatus(429);
    }

    public function test_kunci_login_web_juga_per_username_dan_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.66'])->post(route('login.post'), ['username' => 'budi', 'password' => 'salah']);
        }
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.7'])->post(route('login.post'), ['username' => 'budi', 'password' => 'Rahasia123'])
            ->assertRedirect(route('dashboard'));
    }

    // ── #4 Nilai mapel lain di kelas yang sama ───────────────────────

    public function test_guru_mapel_lain_tidak_bisa_melihat_atau_mengekspor_nilai(): void
    {
        $q = ['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'tahun_ajaran_id' => $this->ta->id];

        $this->actingAs($this->guruIpa)->get(route('nilai.index', $q))->assertForbidden();
        $this->actingAs($this->guruIpa)->get(route('nilai.export.csv', $q))->assertForbidden();
        $this->actingAs($this->guruIpa)->get(route('nilai.export.pdf', $q))->assertForbidden();

        // Pengampu mapel, wali kelas, dan admin tetap bisa.
        foreach ([$this->guruMtk, $this->wali, $this->admin] as $boleh) {
            $this->actingAs($boleh)->get(route('nilai.index', $q))->assertOk();
            $this->actingAs($boleh)->get(route('nilai.export.csv', $q))->assertOk();
            $this->actingAs($boleh)->get(route('nilai.export.pdf', $q))->assertOk();
        }
    }

    // ── #5 NIP tidak boleh bentrok dengan NIP/username lain ──────────

    public function test_nip_profil_tidak_boleh_sama_dengan_nip_atau_username_lain(): void
    {
        $data = ['nama_lengkap' => 'Budi', 'email' => 'budi@s.id', 'no_hp' => null];

        $this->actingAs($this->guruMtk)->put(route('pengaturan.profil.update'), $data + ['nip' => '1003'])->assertSessionHasErrors('nip');
        $this->actingAs($this->guruMtk)->put(route('pengaturan.profil.update'), $data + ['nip' => 'citra'])->assertSessionHasErrors('nip');
        $this->assertSame('1002', $this->guruMtk->fresh()->nip);

        $this->actingAs($this->guruMtk)->put(route('pengaturan.profil.update'), $data + ['nip' => '1002'])->assertSessionHasNoErrors();
        $this->actingAs($this->guruMtk)->put(route('pengaturan.profil.update'), $data + ['nip' => '2002'])->assertSessionHasNoErrors();
        $this->assertSame('2002', $this->guruMtk->fresh()->nip);
    }

    public function test_login_mengutamakan_username_persis_daripada_nip_akun_lain(): void
    {
        // Data lama yang sudah terlanjur bentrok: NIP citra = username budi.
        $this->guruIpa->forceFill(['nip' => 'budi'])->save();

        $this->loginApi('Rahasia123', '10.0.0.7')->assertOk()->assertJsonPath('akun.nama', 'Budi');
    }
}
