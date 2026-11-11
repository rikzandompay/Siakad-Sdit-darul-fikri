<?php

namespace Tests\Feature\Api;

use App\Models\PengaturanSekolah;
use App\Models\PresensiGuru;
use App\Models\User;
use App\Notifications\LokasiPalsuTerdeteksi;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Absen dengan lokasi palsu (fake GPS) ditolak dan kepala sekolah diberi peringatan. */
class LokasiPalsuTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = -0.0263;

    private const LNG = 109.3425;

    private User $kepsek;

    private User $guru;

    private User $guruLain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['kepsek']]);
        Carbon::setTestNow(Carbon::parse('2026-11-09 07:05:00', 'Asia/Pontianak')); // Senin
        Notification::fake();

        $this->kepsek = User::create(['name' => 'kepsek', 'nama_lengkap' => 'Kepala Sekolah', 'username' => 'kepsek', 'email' => 'k@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->guru = User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ust. Ahmad Fauzi', 'username' => 'ahmad', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->guruLain = User::create(['name' => 'lain', 'nama_lengkap' => 'Guru Lain', 'username' => 'lain', 'email' => 'l@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        PengaturanSekolah::simpan([PengaturanSekolah::LATITUDE => self::LAT, PengaturanSekolah::LONGITUDE => self::LNG, PengaturanSekolah::RADIUS => 100, PengaturanSekolah::JAM_MASUK => '07:00']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function headers(?User $user = null): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.($user ?? $this->guru)->createToken('test', ['guru'])->plainTextToken, 'Accept' => 'application/json'];
    }

    /** Titik palsu kira-kira 1,1 km dari sekolah. */
    private function palsu(): array
    {
        return ['latitude' => self::LAT + 0.01, 'longitude' => self::LNG, 'akurasi' => 5, 'mock' => true];
    }

    public function test_cek_lokasi_palsu_ditolak_dan_kepala_sekolah_diberi_tahu(): void
    {
        $this->withHeaders($this->headers())->postJson('/api/v1/guru/absen/cek-lokasi', $this->palsu())
            ->assertStatus(423)->assertJsonPath('kode', 'lokasi_palsu');

        Notification::assertSentTo($this->kepsek, LokasiPalsuTerdeteksi::class, function (LokasiPalsuTerdeteksi $n) {
            $data = $n->toArray($this->kepsek);

            return $data['jenis'] === 'lokasi'
                && str_contains($data['isi'], 'Ust. Ahmad Fauzi')
                && str_contains($data['isi'], '07:05')
                && str_contains($data['isi'], '1.112 m')
                && $data['rute'] === '/admin/guru/'.$this->guru->id.'/absen';
        });
        Notification::assertNotSentTo([$this->guru, $this->guruLain], LokasiPalsuTerdeteksi::class);
    }

    public function test_absen_masuk_dan_pulang_dengan_lokasi_palsu_ditolak_tanpa_mencatat_absen(): void
    {
        $h = $this->headers();
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $this->palsu() + ['embedding' => [0.1], 'liveness' => true])
            ->assertStatus(423)->assertJsonPath('kode', 'lokasi_palsu');
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/pulang', $this->palsu())
            ->assertStatus(423)->assertJsonPath('kode', 'lokasi_palsu');

        $this->assertSame(0, PresensiGuru::count());
        Notification::assertSentTo($this->kepsek, LokasiPalsuTerdeteksi::class);
    }

    public function test_percobaan_berulang_hanya_memberi_satu_peringatan_per_30_menit(): void
    {
        $h = $this->headers();
        for ($i = 0; $i < 3; $i++) {
            $this->withHeaders($h)->postJson('/api/v1/guru/absen/cek-lokasi', $this->palsu())->assertStatus(423);
        }
        Notification::assertSentToTimes($this->kepsek, LokasiPalsuTerdeteksi::class, 1);

        // Guru lain tetap memicu peringatannya sendiri.
        $this->withHeaders($this->headers($this->guruLain))->postJson('/api/v1/guru/absen/cek-lokasi', $this->palsu())->assertStatus(423);
        Notification::assertSentToTimes($this->kepsek, LokasiPalsuTerdeteksi::class, 2);

        Carbon::setTestNow(now()->addMinutes(31));
        $this->withHeaders($this->headers())->postJson('/api/v1/guru/absen/cek-lokasi', $this->palsu())->assertStatus(423);
        Notification::assertSentToTimes($this->kepsek, LokasiPalsuTerdeteksi::class, 3);
    }

    /** Tanda lokasi palsu Android tidak muncul di setiap pembacaan; "Coba Lagi" tanpa tanda tetap ditolak. */
    public function test_setelah_ketahuan_absen_aplikasi_diblokir_sampai_akhir_hari(): void
    {
        $h = $this->headers();
        $asli = ['latitude' => self::LAT + 0.0003, 'longitude' => self::LNG, 'akurasi' => 8, 'mock' => false];
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/cek-lokasi', $this->palsu())->assertStatus(423);

        Carbon::setTestNow(Carbon::parse('2026-11-09 07:06:00', 'Asia/Pontianak'));
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/cek-lokasi', $asli)
            ->assertStatus(423)->assertJsonPath('kode', 'lokasi_palsu_diblokir')
            ->assertJsonPath('message', 'Absen lewat aplikasi diblokir hari ini karena terdeteksi lokasi palsu pukul 07:05. Hubungi kepala sekolah untuk koreksi.');
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/masuk', $asli + ['embedding' => [0.1], 'liveness' => true])
            ->assertStatus(423)->assertJsonPath('kode', 'lokasi_palsu_diblokir');
        Carbon::setTestNow(Carbon::parse('2026-11-09 23:30:00', 'Asia/Pontianak'));
        $this->withHeaders($h)->postJson('/api/v1/guru/absen/cek-lokasi', $asli)->assertStatus(423);
        $this->assertSame(0, PresensiGuru::count());

        // Guru lain tidak ikut terblokir.
        $this->withHeaders($this->headers($this->guruLain))->postJson('/api/v1/guru/absen/cek-lokasi', $asli)->assertOk();

        // Besoknya kembali normal.
        Carbon::setTestNow(Carbon::parse('2026-11-10 07:00:00', 'Asia/Pontianak'));
        $this->withHeaders($this->headers())->postJson('/api/v1/guru/absen/cek-lokasi', $asli)->assertOk()->assertJsonPath('di_dalam_area', true);
    }

    public function test_lokasi_asli_tidak_memicu_peringatan(): void
    {
        $this->withHeaders($this->headers())->postJson('/api/v1/guru/absen/cek-lokasi', ['latitude' => self::LAT + 0.0003, 'longitude' => self::LNG, 'akurasi' => 8, 'mock' => false])
            ->assertOk()->assertJsonPath('di_dalam_area', true);

        Notification::assertNothingSent();
    }
}
