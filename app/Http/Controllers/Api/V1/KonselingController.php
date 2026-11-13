<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KonselingPercakapan;
use App\Models\KonselingPesan;
use App\Models\Wali;
use App\Support\Sekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Konseling wali murid <-> guru pengampu konseling kelas (aturan sama dengan web).
 * Pesan wali "dibaca" saat dibuka pengampu; pesan guru "dibaca" saat dibuka wali.
 */
class KonselingController extends Controller
{
    // ── Wali ─────────────────────────────────────────────────────

    public function waliIndex(Request $request): JsonResponse
    {
        $siswa = $this->anakWali($request, $request->input('siswa_id'));
        $pengampu = $siswa->kelas?->pengampuKonseling;

        return response()->json([
            'anak' => ['id' => $siswa->id, 'nama' => $siswa->nama_siswa, 'kelas' => $siswa->kelas?->nama_kelas],
            'pengampu' => $pengampu ? [
                'nama' => $pengampu->nama_lengkap ?? $pengampu->name, 'inisial' => Sekolah::inisial($pengampu->nama_lengkap ?? $pengampu->name),
                'jabatan' => $pengampu->isAdmin() ? 'Kepala Sekolah' : 'Guru',
            ] : null,
            'data' => $this->daftar(KonselingPercakapan::where('siswa_id', $siswa->id), KonselingPesan::GURU),
        ]);
    }

    public function waliMulai(Request $request): JsonResponse
    {
        $data = $request->validate(['siswa_id' => 'required|integer', 'topik' => 'required|string|max:150', 'isi' => 'required|string|max:2000']);
        $siswa = $this->anakWali($request, $data['siswa_id']);
        abort_unless($siswa->kelas?->pengampu_konseling_id, 422, 'Kelas ananda belum memiliki guru pengampu konseling. Hubungi sekolah.');

        $p = DB::transaction(function () use ($siswa, $data) {
            $p = KonselingPercakapan::create(['siswa_id' => $siswa->id, 'topik' => $data['topik'], 'status' => KonselingPercakapan::AKTIF, 'pesan_terakhir_pada' => now()]);
            $p->pesan()->create(['pengirim' => KonselingPesan::WALI, 'isi' => $data['isi']]);

            return $p;
        });

        return response()->json(['id' => $p->id], 201);
    }

    public function waliShow(Request $request, KonselingPercakapan $percakapan): JsonResponse
    {
        $this->anakWali($request, $percakapan->siswa_id);
        $percakapan->pesan()->where('pengirim', KonselingPesan::GURU)->whereNull('dibaca_pada')->update(['dibaca_pada' => now()]);

        return response()->json($this->detail($percakapan));
    }

    public function waliBalas(Request $request, KonselingPercakapan $percakapan): JsonResponse
    {
        $this->anakWali($request, $percakapan->siswa_id);

        return $this->kirim($request, $percakapan, KonselingPesan::WALI, null);
    }

    // ── Guru ─────────────────────────────────────────────────────

    public function guruIndex(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->daftar(KonselingPercakapan::terlihatOleh($request->user()), KonselingPesan::WALI)]);
    }

    public function guruShow(Request $request, KonselingPercakapan $percakapan): JsonResponse
    {
        abort_unless($percakapan->bisaDibukaOleh($request->user()), 403);
        if ($percakapan->siswa?->kelas?->pengampu_konseling_id === $request->user()->id) {
            $percakapan->pesanWaliBelumDibaca()->update(['dibaca_pada' => now()]);
        }

        return response()->json($this->detail($percakapan));
    }

    public function guruBalas(Request $request, KonselingPercakapan $percakapan): JsonResponse
    {
        abort_unless($percakapan->bisaDibukaOleh($request->user()), 403);

        return $this->kirim($request, $percakapan, KonselingPesan::GURU, $request->user()->id);
    }

    // ── Ubah, hapus, foto pesan ───────────────────────────────────

    public function waliUbahPesan(Request $request, KonselingPercakapan $percakapan, KonselingPesan $pesan): JsonResponse
    {
        $this->anakWali($request, $percakapan->siswa_id);
        abort_unless($pesan->percakapan_id === $percakapan->id && $pesan->pengirim === KonselingPesan::WALI, 403);

        return $this->ubah($request, $pesan);
    }

    public function waliHapusPesan(Request $request, KonselingPercakapan $percakapan, KonselingPesan $pesan): JsonResponse
    {
        $this->anakWali($request, $percakapan->siswa_id);
        abort_unless($pesan->percakapan_id === $percakapan->id && $pesan->pengirim === KonselingPesan::WALI, 403);

        return $this->hapus($pesan);
    }

    public function waliFoto(Request $request, KonselingPercakapan $percakapan, KonselingPesan $pesan)
    {
        $this->anakWali($request, $percakapan->siswa_id);

        return $this->sajikanFoto($percakapan, $pesan);
    }

    public function guruUbahPesan(Request $request, KonselingPercakapan $percakapan, KonselingPesan $pesan): JsonResponse
    {
        abort_unless($percakapan->bisaDibukaOleh($request->user()) && $pesan->percakapan_id === $percakapan->id && $pesan->milikGuru($request->user()), 403, 'Hanya penulis pesan yang dapat mengubahnya.');

        return $this->ubah($request, $pesan);
    }

    public function guruHapusPesan(Request $request, KonselingPercakapan $percakapan, KonselingPesan $pesan): JsonResponse
    {
        abort_unless($percakapan->bisaDibukaOleh($request->user()) && $pesan->percakapan_id === $percakapan->id && $pesan->milikGuru($request->user()), 403, 'Hanya penulis pesan yang dapat menghapusnya.');

        return $this->hapus($pesan);
    }

    public function guruFoto(Request $request, KonselingPercakapan $percakapan, KonselingPesan $pesan)
    {
        abort_unless($percakapan->bisaDibukaOleh($request->user()), 403);

        return $this->sajikanFoto($percakapan, $pesan);
    }

    // ── Bantuan ──────────────────────────────────────────────────

    private function anakWali(Request $request, $siswaId)
    {
        /** @var Wali $wali */
        $wali = $request->user();
        $siswa = $siswaId ? $wali->siswa()->whereKey($siswaId)->first() : $wali->siswa()->first();
        abort_unless($siswa, 403, 'Percakapan ini bukan milik Anda.');

        return $siswa->load('kelas.pengampuKonseling');
    }

    private function daftar($query, string $belumDibacaDari): array
    {
        return $query->with(['siswa.kelas', 'pesanTerakhir'])
            ->withCount(['pesan as belum_dibaca' => fn ($q) => $q->where('pengirim', $belumDibacaDari)->whereNull('dibaca_pada')])
            ->orderByDesc('pesan_terakhir_pada')->limit(50)->get()
            ->map(fn ($p) => [
                'id' => $p->id, 'topik' => $p->topik, 'status' => $p->status,
                'siswa' => $p->siswa?->nama_siswa, 'kelas' => $p->siswa?->kelas?->nama_kelas, 'nama_wali' => $p->siswa?->nama_wali,
                'inisial' => Sekolah::inisial($p->siswa?->nama_wali ?: $p->siswa?->nama_siswa),
                'pesan_terakhir' => $p->pesanTerakhir?->ringkas(), 'pesan_terakhir_pada' => $p->pesan_terakhir_pada?->toIso8601String(),
                'belum_dibaca' => $p->belum_dibaca,
            ])->all();
    }

    private function detail(KonselingPercakapan $p): array
    {
        $p->load(['siswa.kelas.pengampuKonseling', 'pesan.guru']);
        $pengampu = $p->siswa?->kelas?->pengampuKonseling;

        return [
            'id' => $p->id, 'topik' => $p->topik, 'status' => $p->status,
            'siswa' => $p->siswa?->nama_siswa, 'kelas' => $p->siswa?->kelas?->nama_kelas, 'nama_wali' => $p->siswa?->nama_wali,
            'pengampu' => $pengampu?->nama_lengkap,
            'pesan' => $p->pesan->map(fn ($m) => [
                'id' => $m->id, 'pengirim' => $m->pengirim, 'isi' => $m->isi, 'ada_foto' => $m->foto !== null, 'diedit' => $m->diedit_pada !== null,
                'guru_id' => $m->guru_id,
                'nama' => $m->pengirim === KonselingPesan::GURU ? ($m->guru?->nama_lengkap ?? 'Guru') : ($p->siswa?->nama_wali ?: 'Wali'),
                'waktu' => $m->created_at->toIso8601String(),
            ])->values(),
        ];
    }

    private function kirim(Request $request, KonselingPercakapan $p, string $pengirim, ?int $guruId): JsonResponse
    {
        $data = $request->validate([
            'isi' => 'nullable|required_without:foto|string|max:2000',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], ['isi.required_without' => 'Tulis pesan atau lampirkan foto.']);
        $foto = $request->hasFile('foto') ? KonselingPesan::simpanFoto($request->file('foto')) : null;
        $pesan = DB::transaction(function () use ($p, $pengirim, $guruId, $data, $foto) {
            $m = $p->pesan()->create(['pengirim' => $pengirim, 'guru_id' => $guruId, 'isi' => $data['isi'] ?? null, 'foto' => $foto]);
            $p->update(['pesan_terakhir_pada' => now(), 'status' => KonselingPercakapan::AKTIF]);

            return $m;
        });

        return response()->json(['id' => $pesan->id, 'waktu' => $pesan->created_at->toIso8601String()], 201);
    }

    private function ubah(Request $request, KonselingPesan $pesan): JsonResponse
    {
        $data = $request->validate(['isi' => [$pesan->foto ? 'nullable' : 'required', 'string', 'max:2000']]);
        $pesan->update(['isi' => $data['isi'] ?? null, 'diedit_pada' => now()]);

        return response()->json(['message' => 'Pesan diperbarui.']);
    }

    private function hapus(KonselingPesan $pesan): JsonResponse
    {
        $pesan->hapusBerkasFoto();
        $pesan->delete();

        return response()->json(['message' => 'Pesan dihapus.']);
    }

    private function sajikanFoto(KonselingPercakapan $percakapan, KonselingPesan $pesan)
    {
        abort_unless($pesan->percakapan_id === $percakapan->id && $pesan->foto, 404);

        return Storage::disk('local')->response($pesan->foto);
    }
}
