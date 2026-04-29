<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index()
    {
        $kelasList = Kelas::withCount(['siswa' => function ($q) {
            $q->where('status', 'Aktif');
        }])->with('waliKelas')->orderBy('nama_kelas')->get();

        $guruList = User::orderBy('nama_lengkap')->get();

        return view('kelas.index', compact('kelasList', 'guruList'));
    }

    public function show(Kelas $kelas)
    {
        $kelas->load([
            'waliKelas',
            'siswa' => fn($q) => $q->where('status', 'Aktif')->orderBy('nama_siswa'),
            'jadwalPelajaran' => fn($q) => $q->orderByRaw("
                CASE hari
                    WHEN 'Senin' THEN 1
                    WHEN 'Selasa' THEN 2
                    WHEN 'Rabu' THEN 3
                    WHEN 'Kamis' THEN 4
                    WHEN 'Jumat' THEN 5
                    WHEN 'Sabtu' THEN 6
                END
            ")->orderBy('jam_mulai'),
            'jadwalPelajaran.mataPelajaran',
            'jadwalPelajaran.guru',
        ]);

        return view('kelasmapel', compact('kelas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'wali_kelas_id' => 'nullable|exists:users,id',
        ]);

        Kelas::create($validated);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan!');
    }

    public function update(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'wali_kelas_id' => 'nullable|exists:users,id',
        ]);

        $kelas->update($validated);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil diperbarui!');
    }

    public function destroy(Kelas $kelas)
    {
        $kelas->delete();
        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus!');
    }
}
