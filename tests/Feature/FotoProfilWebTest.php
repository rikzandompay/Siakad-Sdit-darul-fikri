<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoProfilWebTest extends TestCase
{
    use RefreshDatabase;

    private function guru(): User
    {
        return User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ahmad Fauzi', 'username' => 'ahmad', 'email' => 'a@s.id', 'password' => 'Password123', 'status' => 'Aktif', 'jenis_kelamin' => 'L']);
    }

    private function admin(): User
    {
        config(['admin.usernames' => ['kepsek']]);

        return User::create(['name' => 'kepsek', 'nama_lengkap' => 'Kepala Sekolah', 'username' => 'kepsek', 'email' => 'k@s.id', 'password' => 'Password123', 'status' => 'Aktif']);
    }

    public function test_foto_guru_dari_web_menunggu_persetujuan_lalu_aktif(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Notification::fake();
        $admin = $this->admin();
        $guru = $this->guru();

        $this->actingAs($guru)->post('/pengaturan/foto', ['foto' => UploadedFile::fake()->image('a.jpg', 200, 200)])
            ->assertRedirect(route('pengaturan.index'));

        $guru->refresh();
        $this->assertNull($guru->foto_profil);
        Storage::disk('local')->assertExists($guru->foto_pengajuan);
        $this->actingAs($guru)->get('/pengaturan')->assertOk()->assertSee('menunggu persetujuan');

        // Bukan admin tidak boleh menyetujui.
        $this->actingAs($guru)->post(route('presensi-guru.foto.setujui', $guru))->assertForbidden();

        // Admin melihat pengajuan di halaman rekap lalu menyetujui.
        $this->actingAs($admin)->get(route('presensi-guru.rekap'))->assertOk()->assertSee($guru->fotoPengajuanUrl(), false);
        $this->actingAs($admin)->post(route('presensi-guru.foto.setujui', $guru))->assertRedirect();

        $guru->refresh();
        Storage::disk('local')->assertExists($guru->foto_profil);
        $this->assertNull($guru->foto_pengajuan);
        $this->assertStringContainsString('/foto/guru/'.$guru->id, $guru->fotoUrl());
        $this->actingAs($guru)->get($guru->fotoUrl())->assertOk();

        // Foto yang sama tampil di header web.
        $this->actingAs($guru)->get('/pengaturan')->assertOk()->assertSee($guru->fotoUrl(), false);
    }

    public function test_guru_tidak_bisa_menghapus_foto_aktif_tetapi_bisa_membatalkan_pengajuan(): void
    {
        Storage::fake('local');
        Notification::fake();
        $guru = $this->guru();
        $guru->update(['foto_profil' => 'foto_guru/aktif.jpg']);

        $this->actingAs($guru)->delete('/pengaturan/foto')->assertSessionHasErrors('foto');
        $this->assertSame('foto_guru/aktif.jpg', $guru->fresh()->foto_profil);

        $this->actingAs($guru)->post('/pengaturan/foto', ['foto' => UploadedFile::fake()->image('a.jpg', 200, 200)]);
        $pengajuan = $guru->fresh()->foto_pengajuan;
        $this->actingAs($guru)->delete('/pengaturan/foto')->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($pengajuan);
        $this->assertNull($guru->fresh()->foto_pengajuan);
        $this->assertSame('foto_guru/aktif.jpg', $guru->fresh()->foto_profil);
    }

    public function test_admin_mengunggah_dan_menghapus_foto_sendiri(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/pengaturan/foto', ['foto' => UploadedFile::fake()->image('a.jpg', 200, 200)])->assertRedirect();
        $lama = $admin->fresh()->foto_profil;
        Storage::disk('local')->assertExists($lama);

        $this->actingAs($admin)->delete('/pengaturan/foto')->assertRedirect();
        Storage::disk('local')->assertMissing($lama);
        $this->assertNull($admin->fresh()->foto_profil);
    }

    public function test_berkas_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');
        $this->actingAs($this->guru())->post('/pengaturan/foto', ['foto' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('foto');
    }
}
