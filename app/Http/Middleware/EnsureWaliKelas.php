<?php

namespace App\Http\Middleware;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Presensi kelas hanya boleh diisi oleh wali kelas dari kelas tersebut.
 * Admin/kepala sekolah tetap boleh. Kelas dibaca dari parameter rute
 * (kelas / jadwal) atau input jadwal_id.
 */
class EnsureWaliKelas
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $kelasId = $this->kelasId($request);

        $boleh = $user && ($user->isAdmin() || ($kelasId && Kelas::whereKey($kelasId)->where('wali_kelas_id', $user->id)->exists()));

        if (! $boleh) {
            $pesan = 'Presensi kelas hanya dapat diisi oleh wali kelas.';

            return $request->expectsJson() || $request->is('api/*')
                ? response()->json(['message' => $pesan], 403)
                : abort(403, $pesan);
        }

        return $next($request);
    }

    private function kelasId(Request $request): ?int
    {
        $kelas = $request->route('kelas');
        if ($kelas) {
            return (int) ($kelas instanceof Kelas ? $kelas->id : $kelas);
        }

        $jadwal = $request->route('jadwal');
        if ($jadwal) {
            return (int) ($jadwal instanceof JadwalPelajaran ? $jadwal->kelas_id : JadwalPelajaran::whereKey($jadwal)->value('kelas_id'));
        }

        $jadwalId = $request->input('jadwal_id');

        return $jadwalId ? (int) JadwalPelajaran::whereKey($jadwalId)->value('kelas_id') : null;
    }
}
