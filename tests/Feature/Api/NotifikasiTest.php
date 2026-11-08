<?php

namespace Tests\Feature\Api;

use App\Models\Kelas;
use App\Models\KonselingPesan;
use App\Models\MataPelajaran;
use App\Models\PengajuanIzin;
use App\Models\Pengumuman;
use App\Models\PerangkatNotifikasi;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use App\Models\Wali;
use App\Notifications\IzinDiajukan;
use App\Notifications\IzinDitinjau;
use App\Notifications\PengumumanBaru;
use App\Notifications\PesanKonselingBaru;
use App\Notifications\TugasBaru;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Fcm\FcmChannel;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private User $sinta;

    private User $guru;

    private User $guruLain;

    private Kelas $kelas;

    private Siswa $zaki;

    private Wali $waliZaki;

    private Wali $waliLain;

    private MataPelajaran $mtk;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta'], 'firebase.projects.app.credentials' => null]);
        Carbon::setTestNow(Carbon::parse('2026-11-09 13:30:00', 'Asia/Pontianak'));

        $this->sinta = User::create(['name' => 'sinta', 'nama_lengkap' => 'Dewi Quraisyinta', 'username' => 'sinta', 'email' => 's@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->guru = User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ust. Ahmad', 'username' => 'ahmad', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->guruLain = User::create(['name' => 'budi', 'nama_lengkap' => 'Budi', 'username' => 'budi', 'email' => 'b@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->kelas = Kelas::create(['nama_kelas' => 'Kelas 5A', 'wali_kelas_id' => $this->guru->id]);
        $this->kelas->forceFill(['pengampu_konseling_id' => $this->guru->id])->save();
        $this->mtk = MataPelajaran::create(['kode_pelajaran' => 'MTK', 'nama_pelajaran' => 'Matematika']);

        $this->zaki = Siswa::create(['nis' => '2024002', 'nisn' => '01', 'nama_siswa' => 'Muhammad Zaki', 'tanggal_lahir' => '2018-07-05', 'jenis_kelamin' => 'L', 'kelas_id' => $this->kelas->id, 'status' => 'Aktif', 'nama_wali' => 'Agus']);
        $kelasLain = Kelas::create(['nama_kelas' => 'Kelas 4B']);
        Siswa::create(['nis' => '2024009', 'nisn' => '09', 'nama_siswa' => 'Anak Lain', 'tanggal_lahir' => '2018-01-01', 'jenis_kelamin' => 'P', 'kelas_id' => $kelasLain->id, 'status' => 'Aktif', 'nama_wali' => 'Lain']);
        $this->waliZaki = Wali::firstWhere('username', '2024002');
        $this->waliLain = Wali::firstWhere('username', '2024009');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function headers(User|Wali $akun, string $peran): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$akun->createToken('t', [$peran])->plainTextToken, 'Accept' => 'application/json'];
    }

    public function test_konseling_dua_arah_hanya_ke_pihak_percakapan(): void
    {
        Notification::fake();

        $id = $this->withHeaders($this->headers($this->waliZaki, 'wali'))
            ->postJson('/api/v1/wali/konseling', ['siswa_id' => $this->zaki->id, 'topik' => 'Belajar', 'isi' => 'Assalamualaikum'])
            ->assertCreated()->json('id');

        Notification::assertSentTo($this->guru, PesanKonselingBaru::class, function ($n, $channels, $notifiable) use ($id) {
            $data = $n->toArray($notifiable);

            return $data['rute'] === "/guru/konseling/{$id}" && str_contains($data['isi'], 'Assalamualaikum');
        });
        Notification::assertNotSentTo([$this->sinta, $this->waliZaki], PesanKonselingBaru::class);

        $this->withHeaders($this->headers($this->guru, 'guru'))
            ->postJson("/api/v1/guru/konseling/{$id}", ['isi' => 'Waalaikumsalam'])->assertSuccessful();

        Notification::assertSentTo($this->waliZaki, PesanKonselingBaru::class,
            fn ($n, $c, $notifiable) => $n->pesan->pengirim === KonselingPesan::GURU && $n->toArray($notifiable)['rute'] === "/wali/konseling/{$id}");
        Notification::assertNotSentTo($this->waliLain, PesanKonselingBaru::class);
    }

    public function test_pengumuman_sesuai_sasaran_dan_tidak_ke_pembuat(): void
    {
        Notification::fake();

        Pengumuman::create(['judul' => 'Manasik', 'isi' => 'Besok', 'sasaran' => Pengumuman::WALI, 'dibuat_oleh' => $this->sinta->id]);
        Notification::assertSentTo([$this->waliZaki, $this->waliLain], PengumumanBaru::class);
        Notification::assertNotSentTo([$this->guru, $this->sinta], PengumumanBaru::class);

        Notification::fake();
        Pengumuman::create(['judul' => 'Rapat', 'isi' => 'Guru', 'sasaran' => Pengumuman::GURU, 'dibuat_oleh' => $this->sinta->id]);
        Notification::assertSentTo([$this->guru, $this->guruLain], PengumumanBaru::class,
            fn ($n, $c, $notifiable) => $n->toArray($notifiable)['rute'] === '/guru/pengumuman');
        Notification::assertNotSentTo([$this->sinta, $this->waliZaki], PengumumanBaru::class);

        Notification::fake();
        $p = Pengumuman::create(['judul' => 'Libur', 'isi' => 'Semua', 'sasaran' => Pengumuman::SEMUA, 'dibuat_oleh' => $this->sinta->id]);
        Notification::assertSentTo([$this->guru, $this->waliZaki], PengumumanBaru::class);

        // Edit pengumuman tidak memicu notifikasi ulang.
        Notification::fake();
        $p->update(['judul' => 'Libur (revisi)']);
        Notification::assertNothingSent();
    }

    public function test_izin_ke_wali_kelas_lalu_hasil_tinjauan_ke_wali(): void
    {
        Notification::fake();

        $izin = PengajuanIzin::create(['siswa_id' => $this->zaki->id, 'wali_id' => $this->waliZaki->id, 'jenis' => 'Sakit',
            'tanggal_mulai' => '2026-11-10', 'tanggal_selesai' => '2026-11-10', 'alasan' => 'Demam', 'status' => PengajuanIzin::MENUNGGU]);

        Notification::assertSentTo($this->guru, IzinDiajukan::class, fn ($n, $c, $notifiable) => $n->toArray($notifiable)['rute'] === '/guru/izin');
        Notification::assertNotSentTo([$this->sinta, $this->guruLain], IzinDiajukan::class);

        $izin->update(['alasan' => 'Demam tinggi']);
        Notification::assertNotSentTo($this->waliZaki, IzinDitinjau::class);

        $izin->tolak($this->guru, 'Lampirkan surat dokter');
        Notification::assertSentTo($this->waliZaki, IzinDitinjau::class, function ($n, $c, $notifiable) {
            $data = $n->toArray($notifiable);

            return $data['judul'] === 'Izin Ditolak' && str_contains($data['isi'], 'surat dokter') && $data['rute'] === '/wali/izin';
        });
        Notification::assertNotSentTo($this->waliLain, IzinDitinjau::class);
    }

    public function test_izin_kelas_tanpa_wali_kelas_ke_kepala_sekolah(): void
    {
        Notification::fake();
        $this->kelas->update(['wali_kelas_id' => null]);

        PengajuanIzin::create(['siswa_id' => $this->zaki->id, 'wali_id' => $this->waliZaki->id, 'jenis' => 'Izin',
            'tanggal_mulai' => '2026-11-10', 'tanggal_selesai' => '2026-11-11', 'alasan' => 'Acara keluarga', 'status' => PengajuanIzin::MENUNGGU]);

        Notification::assertSentTo($this->sinta, IzinDiajukan::class);
        Notification::assertNotSentTo($this->guru, IzinDiajukan::class);
    }

    public function test_tugas_baru_ke_wali_kelas_itu_dan_pengingat_h_min_1(): void
    {
        Notification::fake();

        $besok = Tugas::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guru->id, 'judul' => 'Latihan 3', 'tenggat' => '2026-11-10']);
        Tugas::create(['kelas_id' => $this->kelas->id, 'pelajaran_id' => $this->mtk->id, 'guru_id' => $this->guru->id, 'judul' => 'Proyek', 'tenggat' => '2026-11-20']);

        Notification::assertSentTo($this->waliZaki, TugasBaru::class, fn ($n) => ! $n->pengingat);
        Notification::assertNotSentTo($this->waliLain, TugasBaru::class);

        Notification::fake();
        $this->artisan('tugas:ingatkan-tenggat')->assertSuccessful();

        Notification::assertSentToTimes($this->waliZaki, TugasBaru::class, 1);
        Notification::assertSentTo($this->waliZaki, TugasBaru::class, function ($n, $c, $notifiable) use ($besok) {
            return $n->pengingat && $n->tugas->is($besok) && str_starts_with($n->toArray($notifiable)['judul'], 'Tenggat besok');
        });
        Notification::assertNotSentTo($this->waliLain, TugasBaru::class);
    }

    public function test_push_hanya_aktif_bila_firebase_dikonfigurasi(): void
    {
        $pesan = new TugasBaru(Tugas::make(['judul' => 'x']));
        $this->assertSame(['database'], $pesan->via($this->waliZaki));

        config(['firebase.projects.app.credentials' => '{"type":"service_account"}']);
        $this->assertSame(['database', FcmChannel::class], $pesan->via($this->waliZaki));
    }

    public function test_perangkat_dan_riwayat_notifikasi_milik_sendiri(): void
    {
        $h = $this->headers($this->waliZaki, 'wali');
        $this->withHeaders($h)->postJson('/api/v1/perangkat', ['token' => 'tok-hp-1'])->assertOk();
        $this->assertSame(['tok-hp-1'], $this->waliZaki->routeNotificationForFcm());

        // HP yang sama dipakai login guru: token pindah pemilik.
        $this->withHeaders($this->headers($this->guru, 'guru'))->postJson('/api/v1/perangkat', ['token' => 'tok-hp-1'])->assertOk();
        $this->assertSame([], $this->waliZaki->fresh()->routeNotificationForFcm());
        $this->assertSame(['tok-hp-1'], $this->guru->routeNotificationForFcm());
        $this->assertSame(1, PerangkatNotifikasi::count());

        Pengumuman::create(['judul' => 'Manasik', 'isi' => 'Besok pagi', 'sasaran' => Pengumuman::WALI, 'dibuat_oleh' => $this->sinta->id]);
        $res = $this->withHeaders($this->headers($this->waliZaki, 'wali'))->getJson('/api/v1/notifikasi')->assertOk()
            ->assertJsonPath('belum_dibaca', 1)
            ->assertJsonPath('data.0.jenis', 'pengumuman')
            ->assertJsonPath('data.0.rute', '/wali/pengumuman')
            ->assertJsonPath('data.0.dibaca', false);
        $id = $res->json('data.0.id');

        // Notifikasi wali tidak bisa ditandai oleh akun lain.
        $this->withHeaders($this->headers($this->waliLain, 'wali'))->postJson("/api/v1/notifikasi/{$id}/baca")->assertNotFound();

        $this->withHeaders($this->headers($this->waliZaki, 'wali'))->postJson("/api/v1/notifikasi/{$id}/baca")->assertOk()->assertJsonPath('belum_dibaca', 0);
        $this->withHeaders($this->headers($this->waliLain, 'wali'))->postJson('/api/v1/notifikasi/baca-semua')->assertOk();
        $this->assertSame(0, $this->waliLain->unreadNotifications()->count());

        $this->withHeaders($this->headers($this->guru, 'guru'))->deleteJson('/api/v1/perangkat', ['token' => 'tok-hp-1'])->assertOk();
        $this->assertSame(0, PerangkatNotifikasi::count());
    }

    public function test_perangkat_berhenti_menerima_push_setelah_logout_atau_sesi_kedaluwarsa(): void
    {
        $h = $this->headers($this->waliZaki, 'wali');
        $this->withHeaders($h)->postJson('/api/v1/perangkat', ['token' => 'tok-a'])->assertOk();
        $this->assertSame(['tok-a'], $this->waliZaki->routeNotificationForFcm());

        // Sesi lebih tua dari masa berlaku token Sanctum: tidak dikirimi push.
        Carbon::setTestNow(now()->addMinutes(config('sanctum.expiration') + 1));
        $this->assertSame([], $this->waliZaki->routeNotificationForFcm());
        Carbon::setTestNow(Carbon::parse('2026-11-09 13:30:00', 'Asia/Pontianak'));

        // Logout mencabut token login dan ikut menghapus perangkatnya.
        $this->app['auth']->forgetGuards();
        $this->withHeaders($h)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertSame(0, PerangkatNotifikasi::count());
    }
}
