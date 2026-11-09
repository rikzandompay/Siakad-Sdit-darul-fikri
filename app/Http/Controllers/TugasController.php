<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiTugas;
use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Tugas dan nilai tugas. Setiap mata pelajaran di sebuah kelas punya "ruang tugas" sendiri
 * (dibuka dari Kelas & Mapel). Hanya guru yang mengajar mata pelajaran itu di kelas tersebut
 * (guru pengampu) yang boleh mengelola isinya; kepala sekolah/admin tidak ikut memberi tugas.
 */
class TugasController extends Controller
{
    public function ruang(Request $request, Kelas $kelas, MataPelajaran $mapel)
    {
        $this->izinkanRuang($request, $kelas, $mapel);

        $tugasList = Tugas::where('kelas_id', $kelas->id)
            ->where('pelajaran_id', $mapel->id)
            ->with('guru')
            ->withCount(['nilai as dinilai_count' => fn ($q) => $q->whereNotNull('nilai')])
            ->orderByRaw('tenggat IS NULL')
            ->orderByDesc('tenggat')
            ->latest()
            ->paginate(15);

        return view('tugas.ruang', [
            'kelas' => $kelas,
            'mapel' => $mapel,
            'tugasList' => $tugasList,
            'jumlahSiswa' => $kelas->siswa()->where('status', 'Aktif')->count(),
        ]);
    }

    public function create(Request $request, Kelas $kelas, MataPelajaran $mapel)
    {
        $this->izinkanRuang($request, $kelas, $mapel);

        return view('tugas.form', ['tugas' => new Tugas, 'kelas' => $kelas, 'mapel' => $mapel]);
    }

    public function store(Request $request, Kelas $kelas, MataPelajaran $mapel)
    {
        $this->izinkanRuang($request, $kelas, $mapel);

        $tugas = Tugas::create($this->validated($request) + [
            'kelas_id' => $kelas->id,
            'pelajaran_id' => $mapel->id,
            'guru_id' => $request->user()->id,
        ]);

        return redirect()->route('tugas.show', $tugas)->with('success', 'Tugas dibuat. Wali murid melihatnya di aplikasi MyDarfik.');
    }

    public function edit(Request $request, Tugas $tugas)
    {
        abort_unless($tugas->bisaDikelolaOleh($request->user()), 403);

        return view('tugas.form', ['tugas' => $tugas, 'kelas' => $tugas->kelas, 'mapel' => $tugas->mataPelajaran]);
    }

    public function update(Request $request, Tugas $tugas)
    {
        abort_unless($tugas->bisaDikelolaOleh($request->user()), 403);
        $tugas->update($this->validated($request));

        return redirect()->route('tugas.show', $tugas)->with('success', 'Tugas diperbarui.');
    }

    public function destroy(Request $request, Tugas $tugas)
    {
        abort_unless($tugas->bisaDikelolaOleh($request->user()), 403);
        $tugas->delete();

        return redirect()->route('tugas.ruang', [$tugas->kelas_id, $tugas->pelajaran_id])->with('success', 'Tugas dihapus beserta nilainya.');
    }

    public function show(Request $request, Tugas $tugas)
    {
        abort_unless($tugas->bisaDikelolaOleh($request->user()), 403);

        $tugas->load(['kelas', 'mataPelajaran', 'guru']);
        $siswaList = $tugas->kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $nilai = $tugas->nilai()->get()->keyBy('siswa_id');

        return view('tugas.show', compact('tugas', 'siswaList', 'nilai'));
    }

    public function simpanNilai(Request $request, Tugas $tugas)
    {
        abort_unless($tugas->bisaDikelolaOleh($request->user()), 403);

        $data = $request->validate([
            'nilai' => 'required|array',
            'nilai.*.siswa_id' => ['required', Rule::exists('siswa', 'id')->where('kelas_id', $tugas->kelas_id)],
            'nilai.*.nilai' => 'nullable|numeric|between:0,100',
            'nilai.*.catatan' => 'nullable|string|max:255',
        ], ['nilai.*.nilai.between' => 'Nilai harus antara 0 dan 100.']);

        $now = now();
        $rows = collect($data['nilai'])
            ->filter(fn ($r) => ($r['nilai'] ?? '') !== '' || ($r['catatan'] ?? '') !== '')
            ->map(fn ($r) => [
                'tugas_id' => $tugas->id, 'siswa_id' => $r['siswa_id'],
                'nilai' => ($r['nilai'] ?? '') === '' ? null : $r['nilai'],
                'catatan' => ($r['catatan'] ?? '') === '' ? null : $r['catatan'],
                'created_at' => $now, 'updated_at' => $now,
            ])->values()->all();

        DB::transaction(fn () => NilaiTugas::upsert($rows, ['tugas_id', 'siswa_id'], ['nilai', 'catatan', 'updated_at']));

        return redirect()->route('tugas.show', $tugas)->with('success', 'Nilai tugas disimpan.');
    }

    private function izinkanRuang(Request $request, Kelas $kelas, MataPelajaran $mapel): void
    {
        abort_unless(Tugas::bisaMengelolaRuang($request->user(), $kelas->id, $mapel->id), 403, 'Anda tidak mengajar mata pelajaran ini di kelas tersebut.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'judul' => 'required|string|max:150',
            'deskripsi' => 'nullable|string|max:3000',
            'tenggat' => 'nullable|date',
        ]);
    }
}
