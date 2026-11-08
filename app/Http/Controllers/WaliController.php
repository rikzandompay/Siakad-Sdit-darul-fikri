<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Wali;
use Illuminate\Http\Request;

/**
 * Daftar akun wali murid (admin). Akun dibuat otomatis dari data siswa: login di aplikasi
 * MyDarfik memakai NIS siswa dan nama lengkap wali. Halaman ini hanya menampilkan dan
 * menyinkronkan; perubahan dilakukan lewat Data Siswa.
 */
class WaliController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));

        $waliList = Wali::with('siswa.kelas')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhereHas('siswa', fn ($s) => $s->where('nama_siswa', 'like', "%{$search}%"))))
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        $siswaTanpaNamaWali = Siswa::where('status', 'Aktif')
            ->whereNull('tanggal_lahir')
            ->orderBy('nama_siswa')
            ->with('kelas')
            ->get();

        return view('wali.index', compact('waliList', 'search', 'siswaTanpaNamaWali'));
    }

    /** Untuk data siswa lama yang dibuat sebelum akun otomatis ada. */
    public function sinkron()
    {
        $jumlah = Wali::sinkronSemua();

        return redirect()->route('wali.index')->with('success', "Akun wali disinkronkan dari data siswa ({$jumlah} akun).");
    }
}
