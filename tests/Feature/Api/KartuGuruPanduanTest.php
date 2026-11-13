<?php

namespace Tests\Feature\Api;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Wali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Kartu guru PDF dan panduan pengguna PDF sesuai peran. */
class KartuGuruPanduanTest extends TestCase
{
    use RefreshDatabase;

    private User $kepsek;

    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.usernames' => ['sinta']]);
        $this->kepsek = User::create(['name' => 'sinta', 'nama_lengkap' => 'Dewi Quraisyinta, S.Pd.', 'username' => 'sinta', 'email' => 's@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $this->guru = User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ust. Ahmad', 'username' => 'ahmad', 'nip' => '198705', 'email' => 'a@s.id', 'password' => 'Rahasia123', 'status' => 'Aktif']);
        $kelas = Kelas::create(['nama_kelas' => 'Kelas 1']);
        Siswa::create(['nis' => '2024002', 'nisn' => '01', 'nama_siswa' => 'Muhammad Zaki', 'tanggal_lahir' => '2018-07-05', 'jenis_kelamin' => 'L', 'kelas_id' => $kelas->id, 'status' => 'Aktif', 'nama_wali' => 'Agus']);
    }

    private function h(User|Wali $akun, string $peran): array
    {
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$akun->createToken('t', [$peran])->plainTextToken];
    }

    public function test_guru_mengunduh_kartu_guru_pdf(): void
    {
        $res = $this->withHeaders($this->h($this->guru, 'guru'))->get('/api/v1/guru/kartu');
        $res->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('kartu-guru-198705.pdf', $res->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $res->getContent());

        // Wali tidak bisa memakai rute guru.
        $this->flushHeaders();
        $this->withHeaders($this->h(Wali::firstWhere('username', '2024002'), 'wali'))->getJson('/api/v1/guru/kartu')->assertStatus(403);
    }

    public function test_panduan_sesuai_peran(): void
    {
        $kasus = [
            [$this->guru, 'guru', 'Panduan-MyDarfik-Guru.pdf'],
            [$this->kepsek, 'guru', 'Panduan-MyDarfik-Kepala-Sekolah.pdf'],
            [Wali::firstWhere('username', '2024002'), 'wali', 'Panduan-MyDarfik-Wali-Murid.pdf'],
        ];
        foreach ($kasus as [$akun, $peran, $berkas]) {
            $this->flushHeaders();
            $res = $this->withHeaders($this->h($akun, $peran))->get('/api/v1/panduan');
            $res->assertOk();
            $this->assertStringContainsString($berkas, $res->headers->get('Content-Disposition'));
            $this->assertSame(filesize(resource_path('panduan/'.$berkas)), (int) $res->headers->get('Content-Length'));
        }
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/panduan')->assertStatus(401);
    }
}
