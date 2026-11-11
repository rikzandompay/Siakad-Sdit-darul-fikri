<?php

namespace Tests\Feature\Api;

use App\Models\PengaturanSekolah;
use App\Models\PresensiGuru;
use App\Models\User;
use App\Models\WajahGuru;
use App\Notifications\AcuanWajahGagal;
use App\Support\PengenalWajah;
use App\Support\PengenalWajahGagal;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Acuan wajah presensi dibuat server dari foto profil yang disetujui (safety.md #1):
 * HP tidak bisa lagi mendaftarkan atau mengirim embedding foto profil.
 */
class AcuanWajahServerTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = -0.0263;

    private const LNG = 109.3425;

    private User $guru;

    private User $guruLain;

    /** Embedding yang dikembalikan pengenal palsu, atau pengecualian yang dilempar. */
    private array|\Throwable $hasilPengenal;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['kepsek'], 'presensi_guru.acuan_otomatis' => true]);
        Carbon::setTestNow(Carbon::parse('2026-11-09 07:05:00', 'Asia/Pontianak')); // Senin
        Storage::fake('local');
        Storage::disk('local')->put('foto_guru/ahmad.jpg', 'foto');
        Storage::disk('local')->put('foto_guru_pengajuan/ahmad-baru.jpg', 'foto');

        $this->hasilPengenal = $this->wajahDari(0);
        $uji = $this;
        $this->app->instance(PengenalWajah::class, new class($uji) implements PengenalWajah
        {
            public function __construct(private AcuanWajahServerTest $uji) {}

            public function dariFoto(string $pathFoto): array
            {
                return $this->uji->jalankanPengenal();
            }
        });

        $this->guru = User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ust. Ahmad', 'username' => 'ahmad', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->guruLain = User::create(['name' => 'lain', 'nama_lengkap' => 'Guru Lain', 'username' => 'lain', 'email' => 'l@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        PengaturanSekolah::simpan([PengaturanSekolah::LATITUDE => self::LAT, PengaturanSekolah::LONGITUDE => self::LNG, PengaturanSekolah::RADIUS => 100, PengaturanSekolah::JAM_MASUK => '07:00']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function jalankanPengenal(): array
    {
        if ($this->hasilPengenal instanceof \Throwable) {
            throw $this->hasilPengenal;
        }

        return $this->hasilPengenal;
    }

    private function wajahDari(float $derau): array
    {
        return array_map(fn ($i) => sin($i) + $derau * cos($i * 7), range(1, 192));
    }

    private function token(?User $u = null): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.($u ?? $this->guru)->createToken('t', ['guru'])->plainTextToken, 'Accept' => 'application/json'];
    }

    private function absen(array $tambahan = []): array
    {
        return $tambahan + ['latitude' => self::LAT + 0.0003, 'longitude' => self::LNG, 'akurasi' => 8, 'mock' => false, 'embedding' => $this->wajahDari(0.05), 'liveness' => true, 'pipeline_wajah' => 2, 'perangkat_id' => 'perangkat-uji-001'];
    }

    public function test_foto_disetujui_membuat_acuan_di_server_terenkripsi_dan_tidak_dikirim_ke_hp(): void
    {
        $this->guru->update(['foto_pengajuan' => 'foto_guru_pengajuan/ahmad-baru.jpg']);
        $this->withHeaders($this->token())->getJson('/api/v1/guru/absen')->assertJsonPath('acuan_wajah', 'foto_menunggu');

        $this->guru->setujuiFotoPengajuan();

        $wajah = WajahGuru::where('guru_id', $this->guru->id)->first();
        $this->assertNotNull($wajah);
        $this->assertEqualsWithDelta($this->wajahDari(0)[0], $wajah->vektor()[0], 1e-9);
        $this->assertStringNotContainsString((string) round($this->wajahDari(0)[0], 4), DB::table('wajah_guru')->value('template'));

        $h = $this->token();
        $this->withHeaders($h)->getJson('/api/v1/guru/absen')->assertJsonPath('acuan_wajah', 'siap');
        $res = $this->withHeaders($h)->getJson('/api/v1/guru/wajah')->assertOk()->assertJsonPath('terdaftar', true);
        $this->assertArrayNotHasKey('template', $res->json());

        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $this->absen())->assertOk();
        $this->assertSame(PresensiGuru::HADIR, PresensiGuru::first()->status);
    }

    public function test_hp_tidak_bisa_mendaftarkan_atau_mengirim_acuan_wajah(): void
    {
        config(['presensi_guru.acuan_otomatis' => false]); // acuan server belum sempat dibuat
        $this->guru->update(['foto_profil' => 'foto_guru/ahmad.jpg']);
        $h = $this->token();

        $this->withHeaders($h)->postJson('/api/v1/guru/wajah', ['foto_embedding' => $this->wajahDari(0), 'dari_foto_profil' => true])->assertStatus(405);
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $this->absen(['foto_embedding' => $this->wajahDari(0.05)]))
            ->assertStatus(422)->assertJsonPath('kode', 'wajah_belum_terdaftar');

        $this->assertSame(0, WajahGuru::count());
        $this->assertSame(0, PresensiGuru::count());
    }

    public function test_aplikasi_lama_tanpa_pipeline_baru_diminta_memperbarui(): void
    {
        WajahGuru::create(['guru_id' => $this->guru->id, 'template' => json_encode($this->wajahDari(0)), 'didaftarkan_pada' => now()]);
        $payload = $this->absen();
        unset($payload['pipeline_wajah']);

        $this->withHeaders($this->token())->postJson('/api/v1/guru/absen/masuk', $payload)->assertStatus(426)->assertJsonPath('kode', 'perbarui_aplikasi');
        $this->assertSame(0, PresensiGuru::count());
    }

    public function test_foto_tanpa_wajah_memberi_tahu_guru_lalu_foto_baru_memperbaikinya(): void
    {
        Notification::fake();
        $this->hasilPengenal = new PengenalWajahGagal('tidak_ada_wajah', 'Wajah tidak terlihat pada foto profil.');
        $this->guru->update(['foto_pengajuan' => 'foto_guru_pengajuan/ahmad-baru.jpg']);
        $this->guru->setujuiFotoPengajuan();

        $this->assertSame(0, WajahGuru::count());
        Notification::assertSentTo($this->guru, AcuanWajahGagal::class);
        $this->withHeaders($this->token())->getJson('/api/v1/guru/absen')
            ->assertJsonPath('acuan_wajah', 'gagal')
            ->assertJsonPath('acuan_pesan', 'Wajah tidak terlihat pada foto profil. Ganti foto profil lalu tunggu persetujuan kepala sekolah.');

        $this->hasilPengenal = $this->wajahDari(0);
        Storage::disk('local')->put('foto_guru_pengajuan/ahmad-lagi.jpg', 'foto');
        $this->guru->update(['foto_pengajuan' => 'foto_guru_pengajuan/ahmad-lagi.jpg']);
        $this->guru->setujuiFotoPengajuan();

        $this->withHeaders($this->token())->getJson('/api/v1/guru/absen')->assertJsonPath('acuan_wajah', 'siap');
    }

    public function test_foto_lama_tanpa_acuan_otomatis_dijadwalkan_saat_membuka_presensi(): void
    {
        config(['presensi_guru.acuan_otomatis' => false]);
        $this->guru->update(['foto_profil' => 'foto_guru/ahmad.jpg']);
        config(['presensi_guru.acuan_otomatis' => true]);

        $this->withHeaders($this->token())->getJson('/api/v1/guru/absen')->assertJsonPath('acuan_wajah', 'diproses');
        $this->assertSame(1, WajahGuru::count()); // antrean sync di tes langsung menjalankan job
        $this->withHeaders($this->token())->getJson('/api/v1/guru/absen')->assertJsonPath('acuan_wajah', 'siap');
    }

    public function test_wajah_mirip_guru_lain_ditandai_saat_acuan_dibuat_dan_reset_membersihkan_tanda(): void
    {
        WajahGuru::create(['guru_id' => $this->guruLain->id, 'template' => json_encode($this->wajahDari(0)), 'didaftarkan_pada' => now()]);
        $this->hasilPengenal = $this->wajahDari(0.1);
        Storage::disk('local')->put('foto_guru/a.jpg', 'foto');
        $this->guru->update(['foto_pengajuan' => 'foto_guru/a.jpg']);
        $this->guru->setujuiFotoPengajuan();

        $this->assertSame($this->guruLain->id, WajahGuru::where('guru_id', $this->guru->id)->first()->mirip_guru_id);
        $this->assertSame($this->guru->id, WajahGuru::where('guru_id', $this->guruLain->id)->first()->mirip_guru_id);

        config(['presensi_guru.acuan_otomatis' => false]);
        $this->guru->hapusWajah();
        $this->assertNull(WajahGuru::where('guru_id', $this->guruLain->id)->first()->mirip_guru_id);
    }

    public function test_wajah_berbeda_tidak_ditandai_mirip(): void
    {
        WajahGuru::create(['guru_id' => $this->guruLain->id, 'template' => json_encode(array_map(fn ($i) => cos($i * 3), range(1, 192))), 'didaftarkan_pada' => now()]);
        $this->guru->update(['foto_pengajuan' => 'foto_guru_pengajuan/ahmad-baru.jpg']);
        $this->guru->setujuiFotoPengajuan();

        $this->assertSame(2, WajahGuru::count());
        $this->assertSame(0, WajahGuru::whereNotNull('mirip_guru_id')->count());
    }

    public function test_perintah_membuat_ulang_acuan_semua_guru(): void
    {
        config(['presensi_guru.acuan_otomatis' => false]);
        $this->guru->update(['foto_profil' => 'foto_guru/ahmad.jpg']);
        // Acuan lama buatan HP diganti acuan dari server.
        WajahGuru::create(['guru_id' => $this->guru->id, 'template' => json_encode($this->wajahDari(2)), 'didaftarkan_pada' => now()->subDay()]);

        $this->artisan('wajah:buat-acuan', ['--semua' => true])->expectsOutputToContain('1 guru diproses.')->assertSuccessful();

        $this->assertEqualsWithDelta($this->wajahDari(0)[5], WajahGuru::where('guru_id', $this->guru->id)->first()->vektor()[5], 1e-9);
    }
}
