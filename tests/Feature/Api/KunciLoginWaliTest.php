<?php

namespace Tests\Feature\Api;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Wali;
use App\Support\KunciLogin;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Login wali (NIS + tanggal lahir) dikunci bertingkat agar tanggal lahir tidak bisa ditebak. */
class KunciLoginWaliTest extends TestCase
{
    use RefreshDatabase;

    private const NIS = '2024002';

    private const BENAR = '18-07-05';

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta']]);
        Carbon::setTestNow(Carbon::parse('2026-11-11 08:00:00', 'Asia/Pontianak'));

        $kelas = Kelas::create(['nama_kelas' => 'Kelas 5A']);
        Siswa::create(['nis' => self::NIS, 'nisn' => '01', 'nama_siswa' => 'Muhammad Zaki', 'tanggal_lahir' => '2018-07-05', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas->id, 'status' => 'Aktif', 'nama_wali' => 'Agus Ahmad']);
    }

    private function login(string $sandi, string $nis = self::NIS, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/auth/login', ['peran' => 'wali', 'username' => $nis, 'password' => $sandi]);
    }

    private function salah(int $kali, string $ip = '10.0.0.1'): void
    {
        for ($i = 0; $i < $kali; $i++) {
            Carbon::setTestNow(now()->addSeconds(KunciLogin::sisa(KunciLogin::wali(self::NIS)) + 1));
            $this->login('18-01-'.str_pad((string) ($i % 28 + 1), 2, '0', STR_PAD_LEFT), ip: $ip);
        }
    }

    public function test_salah_lima_kali_mengunci_lima_menit_walau_tanggal_benar(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->login('18-01-0'.$i)->assertStatus(422);
        }
        $this->login('18-01-05')->assertStatus(429);

        $this->login(self::BENAR)->assertStatus(429)->assertJsonPath('errors.username.0', 'Terlalu banyak percobaan. Coba lagi dalam 5 menit.');

        Carbon::setTestNow(now()->addMinutes(5)->addSecond());
        $this->login(self::BENAR)->assertOk();
    }

    public function test_kunci_makin_lama_sampai_24_jam(): void
    {
        $this->salah(5);
        $this->assertSame(300, KunciLogin::sisa(KunciLogin::wali(self::NIS)));

        $this->salah(1);
        $this->assertSame(600, KunciLogin::sisa(KunciLogin::wali(self::NIS)));

        $this->salah(10);
        $this->assertSame(86400, KunciLogin::sisa(KunciLogin::wali(self::NIS)));
    }

    public function test_login_berhasil_menghapus_hitungan_salah(): void
    {
        $this->salah(4);
        $this->login(self::BENAR)->assertOk();

        $this->assertSame(0, KunciLogin::jumlahGagal(KunciLogin::wali(self::NIS)));
        $this->login('18-01-01')->assertStatus(422);
    }

    public function test_satu_ip_tidak_bisa_menyisir_banyak_nis(): void
    {
        for ($i = 0; $i < 30; $i++) {
            Carbon::setTestNow(now()->addSeconds(4)); // di bawah batas 20 login/menit, total 2 menit
            $this->login('18-01-01', nis: '99'.$i, ip: '10.0.0.9')->assertStatus(422);
        }
        $this->login(self::BENAR, ip: '10.0.0.9')->assertStatus(429);
        $this->login(self::BENAR, ip: '10.0.0.2')->assertOk();
    }

    public function test_tata_usaha_bisa_membuka_kunci(): void
    {
        $admin = User::create(['name' => 'sinta', 'nama_lengkap' => 'Dewi Quraisyinta', 'username' => 'sinta', 'email' => 's@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $guru = User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ust. Ahmad', 'username' => 'ahmad', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $wali = Wali::firstWhere('username', self::NIS);
        $this->salah(5);

        $this->actingAs($admin)->get(route('wali.index'))->assertOk()->assertSee('Buka kunci');
        $this->actingAs($guru)->post(route('wali.buka-kunci', $wali))->assertForbidden();
        $this->assertGreaterThan(0, KunciLogin::sisa(KunciLogin::wali(self::NIS)));

        $this->actingAs($admin)->post(route('wali.buka-kunci', $wali))->assertRedirect();
        $this->login(self::BENAR)->assertOk();
    }

    public function test_login_guru_tetap_memakai_kunci_lima_menit(): void
    {
        User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ust. Ahmad', 'username' => 'ahmad', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', ['peran' => 'guru', 'username' => 'ahmad', 'password' => 'salah'])->assertStatus(422);
        }
        $this->postJson('/api/v1/auth/login', ['peran' => 'guru', 'username' => 'ahmad', 'password' => 'Rahasia123'])->assertStatus(429);
    }
}
