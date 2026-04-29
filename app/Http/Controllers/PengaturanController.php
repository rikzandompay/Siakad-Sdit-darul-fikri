<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use App\Models\MataPelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PengaturanController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tahunAjaranList = TahunAjaran::orderByDesc('id')->get();
        $mapelList = MataPelajaran::orderBy('nama_pelajaran')->get();

        return view('pengaturan', compact('user', 'tahunAjaranList', 'mapelList'));
    }

    // ── Profil ──
    public function updateProfil(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'nip' => 'nullable|string|max:30',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $user->update($validated);
        $user->update(['name' => explode(' ', $validated['nama_lengkap'])[0]]);

        return redirect()->route('pengaturan.index')->with('success', 'Profil berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama tidak sesuai.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('pengaturan.index')->with('success', 'Password berhasil diperbarui!');
    }

    // ── Tahun Ajaran ──
    public function storeTahunAjaran(Request $request)
    {
        $request->validate([
            'tahun' => 'required|string|max:20',
            'semester' => 'required|in:Ganjil,Genap',
            'status_aktif' => 'required|in:Y,N',
        ]);

        $namaSemester = $request->tahun . ' - ' . $request->semester;

        $ta = TahunAjaran::create([
            'nama_semester' => $namaSemester,
            'status_aktif' => $request->status_aktif
        ]);

        if ($request->status_aktif == 'Y') {
            $ta->setSemesterAktif();
        }

        return redirect()->route('pengaturan.index')->with('success', 'Tahun ajaran berhasil ditambahkan!');
    }

    public function updateTahunAjaran(Request $request, TahunAjaran $tahunAjaran)
    {
        $request->validate([
            'tahun' => 'required|string|max:20',
            'semester' => 'required|in:Ganjil,Genap',
            'status_aktif' => 'required|in:Y,N',
        ]);

        $namaSemester = $request->tahun . ' - ' . $request->semester;

        $tahunAjaran->update([
            'nama_semester' => $namaSemester,
            'status_aktif' => $request->status_aktif
        ]);

        if ($request->status_aktif == 'Y') {
            $tahunAjaran->setSemesterAktif();
        }

        return redirect()->route('pengaturan.index')->with('success', 'Tahun ajaran berhasil diperbarui!');
    }

    public function setAktifTahunAjaran(TahunAjaran $tahunAjaran)
    {
        $tahunAjaran->setSemesterAktif();
        return redirect()->route('pengaturan.index')->with('success', 'Tahun ajaran aktif berhasil diubah!');
    }

    public function destroyTahunAjaran(TahunAjaran $tahunAjaran)
    {
        $tahunAjaran->delete();
        return redirect()->route('pengaturan.index')->with('success', 'Tahun ajaran berhasil dihapus!');
    }

    // ── Mata Pelajaran ──
    public function storeMapel(Request $request)
    {
        $validated = $request->validate([
            'kode_pelajaran' => 'required|string|max:20|unique:mata_pelajaran,kode_pelajaran',
            'nama_pelajaran' => 'required|string|max:100',
        ]);

        MataPelajaran::create($validated);

        return redirect()->route('pengaturan.index')->with('success', 'Mata pelajaran berhasil ditambahkan!');
    }

    public function updateMapel(Request $request, MataPelajaran $mapel)
    {
        $validated = $request->validate([
            'kode_pelajaran' => 'required|string|max:20|unique:mata_pelajaran,kode_pelajaran,' . $mapel->id,
            'nama_pelajaran' => 'required|string|max:100',
        ]);

        $mapel->update($validated);

        return redirect()->route('pengaturan.index')->with('success', 'Mata pelajaran berhasil diperbarui!');
    }

    public function destroyMapel(MataPelajaran $mapel)
    {
        $mapel->delete();
        return redirect()->route('pengaturan.index')->with('success', 'Mata pelajaran berhasil dihapus!');
    }
}
