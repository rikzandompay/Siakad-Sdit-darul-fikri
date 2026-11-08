<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengumuman sekolah. Admin (kepala sekolah/TU) membuat dan mengelola;
 * guru membaca pengumuman untuk "Semua" dan "Guru". Pengumuman untuk
 * "Semua" dan "Wali Murid" tampil di aplikasi MyDarfik wali.
 */
class PengumumanController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = $request->user()->isAdmin();
        $sasaran = $request->get('sasaran');
        $search = trim((string) $request->get('search'));

        $pengumuman = Pengumuman::with('pembuat')
            ->when(! $isAdmin, fn ($q) => $q->untukGuru())
            ->when($isAdmin && in_array($sasaran, Pengumuman::SASARAN, true), fn ($q) => $q->where('sasaran', $sasaran))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('judul', 'like', "%{$search}%")
                ->orWhere('isi', 'like', "%{$search}%")))
            ->orderByDesc('penting')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pengumuman.index', compact('pengumuman', 'isAdmin', 'sasaran', 'search'));
    }

    public function create()
    {
        return view('pengumuman.form', ['pengumuman' => new Pengumuman(['sasaran' => Pengumuman::SEMUA])]);
    }

    public function store(Request $request)
    {
        Pengumuman::create($this->validated($request) + ['dibuat_oleh' => $request->user()->id]);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil diterbitkan.');
    }

    public function edit(Pengumuman $pengumuman)
    {
        return view('pengumuman.form', compact('pengumuman'));
    }

    public function update(Request $request, Pengumuman $pengumuman)
    {
        $pengumuman->update($this->validated($request));

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman)
    {
        $pengumuman->delete();

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'judul' => 'required|string|max:150',
            'isi' => 'required|string|max:5000',
            'sasaran' => ['required', Rule::in(Pengumuman::SASARAN)],
            'penting' => 'nullable|boolean',
        ]);

        $data['penting'] = $request->boolean('penting');

        return $data;
    }
}
