<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PerangkatNotifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Token push perangkat dan riwayat notifikasi untuk guru maupun wali murid. */
class NotifikasiController extends Controller
{
    public function simpanPerangkat(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => 'required|string|max:512', 'platform' => 'nullable|string|in:android,ios']);
        $akun = $request->user();

        // Satu token = satu perangkat. Bila HP sama dipakai login akun lain, pemiliknya pindah.
        PerangkatNotifikasi::updateOrCreate(['token' => $data['token']], [
            'pemilik_type' => $akun->getMorphClass(),
            'pemilik_id' => $akun->getKey(),
            'akses_id' => $akun->currentAccessToken()?->getKey(),
            'platform' => $data['platform'] ?? 'android',
        ]);

        return response()->json(['message' => 'Perangkat terdaftar.']);
    }

    public function hapusPerangkat(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => 'required|string|max:512']);
        $request->user()->perangkatNotifikasi()->where('token', $data['token'])->delete();

        return response()->json(['message' => 'Perangkat dihapus.']);
    }

    public function index(Request $request): JsonResponse
    {
        $akun = $request->user();
        $halaman = $akun->notifications()->latest()->paginate(20);

        return response()->json([
            'belum_dibaca' => $akun->unreadNotifications()->count(),
            'data' => collect($halaman->items())->map(fn ($n) => [
                'id' => $n->id,
                'jenis' => $n->data['jenis'] ?? null,
                'judul' => $n->data['judul'] ?? '',
                'isi' => $n->data['isi'] ?? '',
                'rute' => $n->data['rute'] ?? null,
                'dibaca' => $n->read_at !== null,
                'waktu' => $n->created_at?->toIso8601String(),
            ]),
            'halaman' => $halaman->currentPage(),
            'halaman_terakhir' => $halaman->lastPage(),
        ]);
    }

    public function baca(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->json(['belum_dibaca' => $request->user()->unreadNotifications()->count()]);
    }

    public function bacaSemua(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['belum_dibaca' => 0]);
    }
}
