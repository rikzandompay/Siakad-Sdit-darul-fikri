<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Kelas;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $query = Siswa::with('kelas');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_siswa', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        // Filter by kelas
        if ($request->filled('kelas_id') && $request->kelas_id !== '') {
            $query->where('kelas_id', $request->kelas_id);
        }

        // Filter by gender
        if ($request->filled('jenis_kelamin') && $request->jenis_kelamin !== '') {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $siswa = $query->orderBy('nama_siswa')->paginate(15)->withQueryString();
        $kelasList = Kelas::orderBy('nama_kelas')->get();

        return view('datasiswa', compact('siswa', 'kelasList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:20|unique:siswa,nis',
            'nama_siswa' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'kelas_id' => 'required|exists:kelas,id',
            'nama_wali' => 'nullable|string|max:100',
            'no_hp_wali' => 'nullable|string|max:20',
            'status' => 'required|in:Aktif,Lulus,Pindah',
        ]);

        Siswa::create($validated);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil ditambahkan!');
    }

    public function update(Request $request, Siswa $siswa)
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:20|unique:siswa,nis,' . $siswa->id,
            'nama_siswa' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'kelas_id' => 'required|exists:kelas,id',
            'nama_wali' => 'nullable|string|max:100',
            'no_hp_wali' => 'nullable|string|max:20',
            'status' => 'required|in:Aktif,Lulus,Pindah',
        ]);

        $siswa->update($validated);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diperbarui!');
    }

    public function destroy(Siswa $siswa)
    {
        $siswa->delete();
        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil dihapus!');
    }

    /**
     * Export siswa as CSV
     */
    public function exportCsv(Request $request)
    {
        $query = Siswa::with('kelas');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_siswa', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        if ($request->filled('kelas_id') && $request->kelas_id !== '') {
            $query->where('kelas_id', $request->kelas_id);
        }
        if ($request->filled('jenis_kelamin') && $request->jenis_kelamin !== '') {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $data = $query->orderBy('nama_siswa')->get();
        $filename = 'data_siswa_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['No', 'NIS', 'Nama Siswa', 'Jenis Kelamin', 'Kelas', 'Nama Wali', 'No HP Wali', 'Status']);
            $no = 1;
            foreach ($data as $s) {
                fputcsv($file, [
                    $no++,
                    $s->nis,
                    $s->nama_siswa,
                    $s->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan',
                    $s->kelas->nama_kelas ?? '-',
                    $s->nama_wali ?? '-',
                    $s->no_hp_wali ?? '-',
                    $s->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export siswa as PDF (HTML-based printable)
     */
    public function exportPdf(Request $request)
    {
        $query = Siswa::with('kelas');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_siswa', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        if ($request->filled('kelas_id') && $request->kelas_id !== '') {
            $query->where('kelas_id', $request->kelas_id);
        }
        if ($request->filled('jenis_kelamin') && $request->jenis_kelamin !== '') {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $data = $query->orderBy('nama_siswa')->get();

        return view('exports.siswa-pdf', compact('data'));
    }
}
