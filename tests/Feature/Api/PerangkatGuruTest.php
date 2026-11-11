<?php

namespace Tests\Feature\Api;

use App\Models\PengaturanSekolah;
use App\Models\PerangkatGuru;
use App\Models\PresensiGuru;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Device binding absen guru: satu guru satu perangkat.
 * Perangkat pertama otomatis aktif; ganti HP harus disetujui admin/kepala sekolah.
 */
class PerangkatGuruTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = -0.0263;

    private const LNG = 109.3425;

    private User $guru;

    private User $kepsek;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['kepsek'], 'presensi_guru.kecualikan_username' => ['admin']]);
        Carbon::setTestNow(Carbon::parse('2026-11-09 07:05:00', 'Asia/Pontianak'));

        $this->guru = User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ust. Ahmad Fauzi, S.Pd.', 'username' => 'ahmad', 'nip' => '19870512', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif', 'jenis_kelamin' => 'L']);
        $this->kepsek = User::create(['name' => 'kepsek', 'nama_lengkap' => 'Kepala Sekolah', 'username' => 'kepsek', 'email' => 'k@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif', 'jenis_kelamin' => 'L']);

        PengaturanSekolah::simpan([
            PengaturanSekolah::LATITUDE => self::LAT,
            PengaturanSekolah::LONGITUDE => self::LNG,
            PengaturanSekolah::RADIUS => 100,
            PengaturanSekolah::JAM_MASUK => '07:00',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function token(User $user, string $ability = 'guru'): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken('test', [$ability])->plainTextToken, 'Accept' => 'application/json'];
    }

    private function wajahDari(float $derau): array
    {
        return array_map(fn ($i) => sin($i) + $derau * cos($i * 7), range(1, 192));
    }

    private function payload(string $perangkatId, string $nama = 'Samsung SM-A125F'): array
    {
        return [
            'latitude' => self::LAT + 0.00038, 'longitude' => self::LNG, 'akurasi' => 8,
            'mock' => false, 'embedding' => $this->wajahDari(0.05), 'liveness' => true,
            'perangkat_id' => $perangkatId, 'perangkat_nama' => $nama,
            'perangkat_platform' => 'android', 'perangkat_versi_os' => '13',
        ];
    }

    private function siapkanWajah(): void
    {
        // Foto profil harus sudah disetujui sebelum wajah boleh didaftarkan.
        $this->guru->update(['foto_profil' => 'foto_guru/a.jpg', 'foto_pengajuan' => null]);
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/wajah', ['foto_embedding' => $this->wajahDari(0), 'dari_foto_profil' => true])
            ->assertCreated();
    }

    public function test_absen_tanpa_perangkat_id_ditolak(): void
    {
        $payload = $this->payload('perangkat-aaa');
        unset($payload['perangkat_id']);

        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/masuk', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('perangkat_id');
    }

    public function test_perangkat_pertama_langsung_aktif_dan_bisa_absen(): void
    {
        $this->siapkanWajah();

        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/masuk', $this->payload('hp-pertama-000'))
            ->assertOk();

        $this->assertSame(1, PerangkatGuru::where('guru_id', $this->guru->id)->count());
        $this->assertTrue(PerangkatGuru::aktifMilik($this->guru->id)->aktif());
        $this->assertSame('Samsung SM-A125F', PerangkatGuru::aktifMilik($this->guru->id)->nama);
        $this->assertNotNull(PresensiGuru::where('guru_id', $this->guru->id)->first()?->jam_masuk);
    }

    public function test_ganti_hp_masuk_antrian_menunggu_dan_absen_ditolak(): void
    {
        $this->siapkanWajah();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/masuk', $this->payload('hp-lama-001'))->assertOk();

        // HP baru: ditolak, status menunggu.
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/pulang', $this->payload('hp-baru-002', 'Xiaomi Redmi 12'))
            ->assertStatus(423)
            ->assertJsonPath('kode', 'perangkat_menunggu');

        $baru = PerangkatGuru::where('perangkat_id', 'hp-baru-002')->first();
        $this->assertSame(PerangkatGuru::MENUNGGU, $baru->status);
        // Perangkat lama tetap satu-satunya yang aktif.
        $this->assertSame(1, PerangkatGuru::where('guru_id', $this->guru->id)->where('status', PerangkatGuru::AKTIF)->count());
        $this->assertSame('hp-lama-001', PerangkatGuru::aktifMilik($this->guru->id)->perangkat_id);
        // Absen pulang tidak tercatat karena ditolak sebelum verifikasi.
        $this->assertNull(PresensiGuru::where('guru_id', $this->guru->id)->first()->jam_pulang);
    }

    public function test_setelah_disetujui_hp_baru_aktif_dan_hp_lama_dicabut(): void
    {
        $this->siapkanWajah();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/masuk', $this->payload('hp-lama-001'))->assertOk();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/pulang', $this->payload('hp-baru-002'))->assertStatus(423);

        $baru = PerangkatGuru::where('perangkat_id', 'hp-baru-002')->first();

        // Kepala sekolah menyetujui.
        $this->withHeaders($this->token($this->kepsek))
            ->postJson("/api/v1/admin/perangkat-guru/{$baru->id}/setujui")
            ->assertOk();

        $this->assertSame(PerangkatGuru::AKTIF, $baru->fresh()->status);
        $this->assertSame($this->kepsek->id, $baru->fresh()->disetujui_oleh);
        // Hanya satu yang aktif: HP lama otomatis dicabut.
        $this->assertSame(1, PerangkatGuru::where('guru_id', $this->guru->id)->where('status', PerangkatGuru::AKTIF)->count());
        $this->assertSame('hp-baru-002', PerangkatGuru::aktifMilik($this->guru->id)->perangkat_id);

        // HP baru sekarang bisa absen pulang.
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/pulang', $this->payload('hp-baru-002'))->assertOk();
        $this->assertNotNull(PresensiGuru::where('guru_id', $this->guru->id)->first()->jam_pulang);
    }

    public function test_hp_baru_ditolak_tidak_bisa_absen(): void
    {
        $this->siapkanWajah();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/masuk', $this->payload('hp-lama-001'))->assertOk();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/pulang', $this->payload('hp-baru-002'))->assertStatus(423);

        $baru = PerangkatGuru::where('perangkat_id', 'hp-baru-002')->first();
        $this->withHeaders($this->token($this->kepsek))
            ->postJson("/api/v1/admin/perangkat-guru/{$baru->id}/tolak", ['catatan' => 'Bukan HP dinas'])
            ->assertOk();

        $this->assertSame(PerangkatGuru::DITOLAK, $baru->fresh()->status);
        $this->assertSame('Bukan HP dinas', $baru->fresh()->catatan);

        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/pulang', $this->payload('hp-baru-002'))
            ->assertStatus(423)
            ->assertJsonPath('kode', 'perangkat_ditolak');
    }

    public function test_perangkat_tidak_bisa_dipakai_dua_guru(): void
    {
        $guruLain = User::create(['name' => 'lain', 'nama_lengkap' => 'Guru Lain', 'username' => 'lain', 'email' => 'l@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif', 'jenis_kelamin' => 'P']);

        $this->siapkanWajah();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/masuk', $this->payload('hp-bersama-003'))->assertOk();

        // Guru lain memakai perangkat yang sama: ditolak.
        $this->withHeaders($this->token($guruLain))
            ->postJson('/api/v1/guru/absen/masuk', $this->payload('hp-bersama-003'))
            ->assertStatus(423)
            ->assertJsonPath('kode', 'perangkat_lain');

        $this->assertSame($this->guru->id, PerangkatGuru::where('perangkat_id', 'hp-bersama-003')->first()->guru_id);
    }

    public function test_endpoint_status_perangkat_melaporkan_keadaan(): void
    {
        $this->siapkanWajah();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/masuk', $this->payload('hp-lama-001'))->assertOk();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/pulang', $this->payload('hp-baru-002'))->assertStatus(423);

        $this->withHeaders($this->token($this->guru))
            ->getJson('/api/v1/guru/absen/perangkat?perangkat_id=hp-lama-001')
            ->assertOk()
            ->assertJsonPath('terdaftar', true)
            ->assertJsonPath('boleh_absen', true)
            ->assertJsonPath('perangkat_aktif.nama', 'Samsung SM-A125F');

        $this->withHeaders($this->token($this->guru))
            ->getJson('/api/v1/guru/absen/perangkat?perangkat_id=hp-baru-002')
            ->assertOk()
            ->assertJsonPath('boleh_absen', false)
            ->assertJsonPath('perangkat_ini.status', PerangkatGuru::MENUNGGU);
    }

    public function test_admin_melihat_daftar_perangkat_menunggu(): void
    {
        $this->siapkanWajah();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/masuk', $this->payload('hp-lama-001'))->assertOk();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/pulang', $this->payload('hp-baru-002'))->assertStatus(423);

        $this->withHeaders($this->token($this->kepsek))
            ->getJson('/api/v1/admin/perangkat-guru')
            ->assertOk()
            ->assertJsonPath('jumlah_menunggu', 1)
            ->assertJsonPath('menunggu.0.nama_guru', 'Ust. Ahmad Fauzi, S.Pd.')
            ->assertJsonPath('menunggu.0.perangkat', 'Samsung SM-A125F')
            ->assertJsonCount(1, 'aktif');
    }

    public function test_admin_bisa_mencabut_perangkat_aktif(): void
    {
        $this->siapkanWajah();
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/masuk', $this->payload('hp-lama-001'))->assertOk();

        $aktif = PerangkatGuru::aktifMilik($this->guru->id);

        $this->withHeaders($this->token($this->kepsek))
            ->deleteJson("/api/v1/admin/perangkat-guru/{$aktif->id}")
            ->assertOk();

        $this->assertNull(PerangkatGuru::aktifMilik($this->guru->id));
        // Setelah dicabut, HP lama tidak bisa dipakai sampai diajukan lagi.
        $this->withHeaders($this->token($this->guru))
            ->postJson('/api/v1/guru/absen/pulang', $this->payload('hp-lama-001'))
            ->assertStatus(423);
    }
}
