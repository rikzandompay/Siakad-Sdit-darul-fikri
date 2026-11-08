<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\User;
use App\Models\Wali;
use App\Support\FotoPrivat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Menyajikan foto guru dan siswa hanya untuk akun yang login (sesi web atau token aplikasi).
 * Guru/staf boleh melihat semua; wali hanya foto anaknya sendiri dan foto guru.
 */
class FotoController extends Controller
{
    public function guru(User $guru)
    {
        return $this->kirim($guru->foto_profil);
    }

    public function siswa(Request $request, Siswa $siswa)
    {
        $akun = $request->user();
        if ($akun instanceof Wali) {
            abort_unless($akun->siswa()->whereKey($siswa->id)->exists(), 403);
        }

        return $this->kirim($siswa->foto);
    }

    private function kirim(?string $path)
    {
        $disk = FotoPrivat::disk($path);
        abort_unless($disk, 404);

        return Storage::disk($disk)->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}
