<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KelasController extends Controller
{
    public function index()
    {
        $kelasList = Cache::remember('kelas_list_admin', 300, function () {
            return Kelas::withCount('siswaAktif')->with('waliKelas')->orderBy('nama_kelas')->get();
        });

        $guruList = Cache::remember('guru_list_all', 300, function () {
            return User::orderBy('nama_lengkap')->get();
        });

        return view('kelas.index', compact('kelasList', 'guruList'));
    }

    public function show(Kelas $kelas)
    {
        $kelas->load([
            'waliKelas',
            'siswa' => fn ($q) => $q->where('status', 'Aktif')->orderBy('nama_siswa'),
            'jadwalPelajaran' => fn ($q) => $q->urutHari()->orderBy('jam_mulai'),
            'jadwalPelajaran.mataPelajaran',
            'jadwalPelajaran.guru',
        ]);

        // Ruang tugas per mata pelajaran: jumlah tugas dan apakah user boleh membukanya.
        $user = auth()->user();
        $jumlahTugas = Tugas::where('kelas_id', $kelas->id)
            ->selectRaw('pelajaran_id, COUNT(*) as jumlah')
            ->groupBy('pelajaran_id')
            ->pluck('jumlah', 'pelajaran_id');
        $ruangTugas = $kelas->jadwalPelajaran->groupBy('pelajaran_id')->map(fn ($jadwal) => [
            'mapel' => $jadwal->first()->mataPelajaran,
            'guru' => $jadwal->pluck('guru.nama_lengkap')->filter()->unique()->values(),
            'jumlah' => (int) ($jumlahTugas[$jadwal->first()->pelajaran_id] ?? 0),
            'boleh' => $jadwal->contains('guru_id', $user->id),
        ])->values();

        return view('kelasmapel', compact('kelas', 'ruangTugas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'wali_kelas_id' => 'nullable|exists:users,id',
        ]);

        $kelas = Kelas::create($validated);
        $this->clearKelasCache($kelas->wali_kelas_id);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan!');
    }

    public function update(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'wali_kelas_id' => 'nullable|exists:users,id',
        ]);

        $oldWaliId = $kelas->wali_kelas_id;
        $kelas->update($validated);
        $this->clearKelasCache($oldWaliId, $kelas->wali_kelas_id);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil diperbarui!');
    }

    public function destroy(Kelas $kelas)
    {
        $waliId = $kelas->wali_kelas_id;
        $kelas->delete();
        $this->clearKelasCache($waliId);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus!');
    }

    private function clearKelasCache(?int ...$waliIds): void
    {
        Cache::forget('kelas_list_admin');
        Cache::forget('kelas_list_dashboard');
        Cache::forget('kelas_list_filter');
        Cache::forget('total_kelas');

        foreach (array_unique(array_filter($waliIds)) as $waliId) {
            Cache::forget("rekap_kelas_wali_ids_guru_{$waliId}");
            Cache::forget("rekap_kelas_wali_list_guru_{$waliId}");
            Cache::forget("sholat:kelas:guru:{$waliId}");
            Cache::forget("total_siswa_aktif_guru_{$waliId}");
        }
    }
}
