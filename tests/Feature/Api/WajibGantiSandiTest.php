<?php

namespace Tests\Feature\Api;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Wali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Akun yang masih memakai sandi bawaan ditandai wajib ganti; setelah diganti tanda hilang. */
class WajibGantiSandiTest extends TestCase
{
    use RefreshDatabase;

    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta']]);
        $kelas = Kelas::create(['nama_kelas' => 'Kelas 5A']);
        $this->siswa = Siswa::create(['nis' => '2024002', 'nisn' => '01', 'nama_siswa' => 'Muhammad Zaki', 'tanggal_lahir' => '2018-07-05', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas->id, 'status' => 'Aktif', 'nama_wali' => 'Agus Ahmad']);
    }

    private function login(string $peran, string $user, string $sandi)
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/v1/auth/login', ['peran' => $peran, 'username' => $user, 'password' => $sandi]);
    }

    public function test_wali_wajib_ganti_lalu_masuk_dengan_sandi_baru(): void
    {
        $res = $this->login('wali', '2024002', '18-07-05')->assertOk()->assertJsonPath('akun.wajib_ganti_sandi', true);
        $h = ['Authorization' => 'Bearer '.$res->json('token')];

        // Sandi lama boleh format tanggal lain; sandi baru wajib kuat.
        $this->withHeaders($h)->postJson('/api/v1/wali/ubah-sandi', ['sandi_lama' => '2018/07/05', 'sandi_baru' => 'lemah', 'sandi_baru_confirmation' => 'lemah'])->assertStatus(422);
        $this->withHeaders($h)->postJson('/api/v1/wali/ubah-sandi', ['sandi_lama' => '18-01-01', 'sandi_baru' => 'Rahasia123', 'sandi_baru_confirmation' => 'Rahasia123'])->assertStatus(422);
        $this->withHeaders($h)->postJson('/api/v1/wali/ubah-sandi', ['sandi_lama' => '2018/07/05', 'sandi_baru' => 'Rahasia123', 'sandi_baru_confirmation' => 'Rahasia123'])->assertOk();
        $this->withHeaders($h)->getJson('/api/v1/auth/me')->assertJsonPath('akun.wajib_ganti_sandi', false);

        $this->login('wali', '2024002', '18-07-05')->assertStatus(422);
        $this->login('wali', '2024002', 'Rahasia123')->assertOk()->assertJsonPath('akun.wajib_ganti_sandi', false);

        // Siswa disimpan ulang (sinkron) tidak menimpa sandi yang sudah diganti wali.
        $this->siswa->update(['alamat' => 'Jl. Baru']);
        $this->login('wali', '2024002', 'Rahasia123')->assertOk();
    }

    public function test_guru_wajib_ganti_lalu_tanda_hilang(): void
    {
        User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ust. Ahmad', 'username' => 'ahmad', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $res = $this->login('guru', 'ahmad', 'Rahasia123')->assertOk()->assertJsonPath('akun.wajib_ganti_sandi', true);
        $h = ['Authorization' => 'Bearer '.$res->json('token')];

        $this->withHeaders($h)->postJson('/api/v1/guru/ubah-sandi', ['sandi_lama' => 'Rahasia123', 'sandi_baru' => 'Rahasia123', 'sandi_baru_confirmation' => 'Rahasia123'])->assertStatus(422);
        $this->withHeaders($h)->postJson('/api/v1/guru/ubah-sandi', ['sandi_lama' => 'Rahasia123', 'sandi_baru' => 'BaruSekali9', 'sandi_baru_confirmation' => 'BaruSekali9'])->assertOk();
        $this->assertFalse(User::firstWhere('username', 'ahmad')->wajib_ganti_sandi);
    }
}
