<?php

namespace App\Http\Controllers;

use App\Models\MataPelajaran;
use Illuminate\Http\Request;

class MataPelajaranController extends Controller
{
    public function index()
    {
        $mapelList = MataPelajaran::orderBy('nama_pelajaran')->get();
        return view('mapel.index', compact('mapelList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_pelajaran' => 'required|string|max:20|unique:mata_pelajaran,kode_pelajaran',
            'nama_pelajaran' => 'required|string|max:100',
        ]);

        MataPelajaran::create($validated);

        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil ditambahkan!');
    }

    public function update(Request $request, MataPelajaran $mapel)
    {
        $validated = $request->validate([
            'kode_pelajaran' => 'required|string|max:20|unique:mata_pelajaran,kode_pelajaran,' . $mapel->id,
            'nama_pelajaran' => 'required|string|max:100',
        ]);

        $mapel->update($validated);

        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil diperbarui!');
    }

    public function destroy(MataPelajaran $mapel)
    {
        $mapel->delete();
        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil dihapus!');
    }
}
