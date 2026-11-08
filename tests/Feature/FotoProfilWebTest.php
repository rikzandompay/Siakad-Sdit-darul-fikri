<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoProfilWebTest extends TestCase
{
    use RefreshDatabase;

    private function guru(): User
    {
        return User::create(['name' => 'ahmad', 'nama_lengkap' => 'Ahmad Fauzi', 'username' => 'ahmad', 'email' => 'a@s.id', 'password' => 'Password123', 'status' => 'Aktif', 'jenis_kelamin' => 'L']);
    }

    public function test_guru_mengunggah_dan_menghapus_foto_dari_pengaturan_web(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $guru = $this->guru();

        $this->actingAs($guru)->post('/pengaturan/foto', ['foto' => UploadedFile::fake()->image('a.jpg', 200, 200)])
            ->assertRedirect(route('pengaturan.index'));

        $guru->refresh();
        Storage::disk('local')->assertExists($guru->foto_profil);
        $this->assertStringContainsString('/foto/guru/'.$guru->id, $guru->fotoUrl());
        $this->actingAs($guru)->get($guru->fotoUrl())->assertOk();

        // Foto yang sama tampil di header web.
        $this->actingAs($guru)->get('/pengaturan')->assertOk()->assertSee($guru->fotoUrl(), false);

        $lama = $guru->foto_profil;
        $this->actingAs($guru)->delete('/pengaturan/foto')->assertRedirect();
        Storage::disk('local')->assertMissing($lama);
        $this->assertNull($guru->fresh()->foto_profil);
    }

    public function test_berkas_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');
        $this->actingAs($this->guru())->post('/pengaturan/foto', ['foto' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('foto');
    }
}
