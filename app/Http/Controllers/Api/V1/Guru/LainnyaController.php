<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\NilaiTugas;
use App\Models\PengajuanIzin;
use App\Models\Pengumuman;
use App\Models\Tugas;
use App\Support\Sekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Fitur guru lainnya: tugas & nilai tugas, verifikasi izin, pengumuman, jadwal mengajar. */
class LainnyaController extends Controller
{
    // ── Tugas ────────────────────────────────────────────────────

    public function tugas(Request $request): JsonResponse
    {
        $user = $request->user();
        // Hanya kelas + mata pelajaran yang ia ajar sendiri; kepala sekolah tidak melihat semua kelas.
        $pilihan = JadwalPelajaran::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', $user->id)->get()
            ->unique(fn ($j) => $j->kelas_id.'-'.$j->pelajaran_id)
            ->map(fn ($j) => ['kelas_id' => $j->kelas_id, 'pelajaran_id' => $j->pelajaran_id, 'label' => ($j->mataPelajaran?->nama_pelajaran).' - '.($j->kelas?->nama_kelas)])
            ->sortBy('label')->values();

        // Hanya tugas di pasangan kelas + mapel yang ia ajar (ruang tugas mapel).
        $pasangan = $pilihan->map(fn ($p) => $p['kelas_id'].'-'.$p['pelajaran_id'])->all();
        $tugas = Tugas::with(['kelas', 'mataPelajaran'])->withCount(['nilai as dinilai' => fn ($q) => $q->whereNotNull('nilai')])
            ->whereIn('kelas_id', $pilihan->pluck('kelas_id'))
            ->latest()->limit(100)->get()
            ->filter(fn ($t) => in_array($t->kelas_id.'-'.$t->pelajaran_id, $pasangan, true))
            ->take(50);

        return response()->json([
            'pilihan' => $pilihan,
            'data' => $tugas->map(fn ($t) => [
                'id' => $t->id, 'judul' => $t->judul, 'deskripsi' => $t->deskripsi,
                'kelas' => $t->kelas?->nama_kelas, 'mata_pelajaran' => $t->mataPelajaran?->nama_pelajaran,
                'tenggat' => $t->tenggat?->toDateString(), 'dinilai' => $t->dinilai,
                'jumlah_siswa' => $t->kelas?->siswaAktif()->count() ?? 0,
            ]),
        ]);
    }

    public function buatTugas(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kelas_id' => 'required|exists:kelas,id', 'pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'judul' => 'required|string|max:150', 'deskripsi' => 'nullable|string|max:3000', 'tenggat' => 'nullable|date',
        ]);
        abort_unless(Tugas::bisaMengelolaRuang($request->user(), (int) $data['kelas_id'], (int) $data['pelajaran_id']), 403, 'Anda tidak mengajar mata pelajaran ini di kelas tersebut.');
        $tugas = Tugas::create($data + ['guru_id' => $request->user()->id]);

        return response()->json(['id' => $tugas->id, 'message' => 'Tugas terkirim ke wali murid.'], 201);
    }

    public function showTugas(Request $request, Tugas $tugas): JsonResponse
    {
        abort_unless($tugas->bisaDikelolaOleh($request->user()), 403);
        $nilai = $tugas->nilai()->get()->keyBy('siswa_id');

        return response()->json([
            'tugas' => ['id' => $tugas->id, 'judul' => $tugas->judul, 'kelas' => $tugas->kelas?->nama_kelas, 'mata_pelajaran' => $tugas->mataPelajaran?->nama_pelajaran, 'tenggat' => $tugas->tenggat?->toDateString()],
            'data' => $tugas->kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get()->map(fn ($s) => [
                'siswa_id' => $s->id, 'nama' => $s->nama_siswa, 'nilai' => $nilai[$s->id]->nilai ?? null, 'catatan' => $nilai[$s->id]->catatan ?? null,
            ]),
        ]);
    }

    public function nilaiTugas(Request $request, Tugas $tugas): JsonResponse
    {
        abort_unless($tugas->bisaDikelolaOleh($request->user()), 403);
        $data = $request->validate([
            'nilai' => 'required|array',
            'nilai.*.siswa_id' => ['required', Rule::exists('siswa', 'id')->where('kelas_id', $tugas->kelas_id)],
            'nilai.*.nilai' => 'nullable|numeric|between:0,100',
        ], ['nilai.*.nilai.between' => 'Nilai harus antara 0 dan 100.']);

        $now = now();
        $rows = collect($data['nilai'])->filter(fn ($r) => isset($r['nilai']) && $r['nilai'] !== '')->map(fn ($r) => [
            'tugas_id' => $tugas->id, 'siswa_id' => $r['siswa_id'], 'nilai' => $r['nilai'], 'catatan' => null, 'created_at' => $now, 'updated_at' => $now,
        ])->values()->all();
        if ($rows) {
            NilaiTugas::upsert($rows, ['tugas_id', 'siswa_id'], ['nilai', 'updated_at']);
        }

        return response()->json(['message' => 'Nilai tersimpan.', 'tersimpan' => count($rows)]);
    }

    // ── Izin ─────────────────────────────────────────────────────

    public function izin(Request $request): JsonResponse
    {
        $status = $request->input('status', PengajuanIzin::MENUNGGU);

        return response()->json(['data' => PengajuanIzin::terlihatOleh($request->user())->with(['siswa.kelas', 'wali'])
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            ->latest()->limit(50)->get()->map(fn ($i) => [
                'id' => $i->id, 'jenis' => $i->jenis, 'status' => $i->status, 'alasan' => $i->alasan,
                'siswa' => $i->siswa?->nama_siswa, 'kelas' => $i->siswa?->kelas?->nama_kelas, 'wali' => $i->wali?->nama ?? $i->siswa?->nama_wali,
                'tanggal_mulai' => $i->tanggal_mulai->toDateString(), 'tanggal_selesai' => $i->tanggal_selesai->toDateString(),
                'diajukan' => $i->created_at->toIso8601String(), 'ada_lampiran' => (bool) $i->lampiran, 'catatan_peninjau' => $i->catatan_peninjau,
            ])]);
    }

    public function setujuiIzin(Request $request, PengajuanIzin $izin): JsonResponse
    {
        $this->bisaTinjau($request, $izin);
        $jumlah = $izin->setujui($request->user(), $request->input('catatan'));

        return response()->json(['message' => "Izin disetujui dan dicatat di {$jumlah} presensi pelajaran.", 'siswa' => $izin->siswa->nama_siswa, 'jenis' => $izin->jenis, 'jumlah_presensi' => $jumlah]);
    }

    public function tolakIzin(Request $request, PengajuanIzin $izin): JsonResponse
    {
        $this->bisaTinjau($request, $izin);
        $data = $request->validate(['catatan' => 'required|string|max:255'], ['catatan.required' => 'Tulis alasan penolakan untuk wali murid.']);
        $izin->tolak($request->user(), $data['catatan']);

        return response()->json(['message' => 'Izin ditolak.']);
    }

    public function lampiranIzin(Request $request, PengajuanIzin $izin)
    {
        abort_unless($izin->bisaDitinjauOleh($request->user()) && $izin->lampiran && Storage::disk('local')->exists($izin->lampiran), 404);

        return Storage::disk('local')->response($izin->lampiran);
    }

    private function bisaTinjau(Request $request, PengajuanIzin $izin): void
    {
        abort_unless($izin->bisaDitinjauOleh($request->user()), 403);
        abort_unless($izin->status === PengajuanIzin::MENUNGGU, 409, 'Izin ini sudah ditinjau.');
    }

    // ── Pengumuman & jadwal ──────────────────────────────────────

    public function pengumuman(Request $request): JsonResponse
    {
        return response()->json(['data' => Pengumuman::when(! $request->user()->isAdmin(), fn ($q) => $q->untukGuru())
            ->orderByDesc('penting')->latest()->limit(50)->get()->map(fn ($p) => [
                'id' => $p->id, 'judul' => $p->judul, 'isi' => $p->isi, 'sasaran' => $p->sasaran, 'penting' => $p->penting, 'tanggal' => $p->created_at->toDateString(),
            ])]);
    }

    public function jadwal(Request $request): JsonResponse
    {
        $jadwal = JadwalPelajaran::with(['mataPelajaran', 'kelas'])->where('guru_id', $request->user()->id)->orderBy('jam_mulai')->get();

        return response()->json([
            'hari_ini' => Sekolah::hari(),
            'minggu' => collect(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])->mapWithKeys(fn ($h) => [$h => $jadwal->where('hari', $h)->map(fn ($j) => [
                'mata_pelajaran' => $j->mataPelajaran?->nama_pelajaran, 'kelas' => $j->kelas?->nama_kelas,
                'jam_mulai' => substr($j->jam_mulai, 0, 5), 'jam_selesai' => substr($j->jam_selesai, 0, 5),
            ])->values()]),
        ]);
    }
}
