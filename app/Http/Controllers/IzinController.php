<?php

namespace App\Http\Controllers;

use App\Models\PengajuanIzin;
use Illuminate\Http\Request;

/**
 * Izin siswa (diajukan wali lewat aplikasi MyDarfik). Wali kelas atau admin
 * menyetujui/menolak di web. Izin yang disetujui otomatis tercatat di presensi
 * kelas (status S/I) untuk setiap jadwal pada hari sekolah dalam rentang izin.
 */
class IzinController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->get('status', PengajuanIzin::MENUNGGU);
        $search = trim((string) $request->get('search'));

        $izinList = PengajuanIzin::terlihatOleh($user)
            ->with(['siswa.kelas', 'wali', 'peninjau'])
            ->when(in_array($status, [PengajuanIzin::MENUNGGU, PengajuanIzin::DISETUJUI, PengajuanIzin::DITOLAK], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->whereHas('siswa', fn ($s) => $s->where('nama_siswa', 'like', "%{$search}%")))
            ->orderByRaw("CASE status WHEN 'Menunggu' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $jumlahMenunggu = PengajuanIzin::terlihatOleh($user)->where('status', PengajuanIzin::MENUNGGU)->count();

        return view('izin.index', compact('izinList', 'status', 'search', 'jumlahMenunggu'));
    }

    public function approve(Request $request, PengajuanIzin $izin)
    {
        abort_unless($izin->bisaDitinjauOleh($request->user()), 403);
        abort_unless($izin->status === PengajuanIzin::MENUNGGU, 409, 'Izin ini sudah ditinjau.');

        $catatan = $request->validate(['catatan' => 'nullable|string|max:255'])['catatan'] ?? null;
        $jumlah = $izin->setujui($request->user(), $catatan);

        return redirect()->route('izin.index')->with('success', "Izin {$izin->siswa->nama_siswa} disetujui dan dicatat di {$jumlah} presensi pelajaran.");
    }

    public function reject(Request $request, PengajuanIzin $izin)
    {
        abort_unless($izin->bisaDitinjauOleh($request->user()), 403);
        abort_unless($izin->status === PengajuanIzin::MENUNGGU, 409, 'Izin ini sudah ditinjau.');

        $data = $request->validate(['catatan' => 'required|string|max:255'], ['catatan.required' => 'Tulis alasan penolakan untuk wali murid.']);
        $izin->tolak($request->user(), $data['catatan']);

        return redirect()->route('izin.index')->with('success', "Izin {$izin->siswa->nama_siswa} ditolak.");
    }
}
