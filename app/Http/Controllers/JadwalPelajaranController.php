<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Http\Request;

class JadwalPelajaranController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'guru_id' => 'required|exists:users,id',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
        ]);

        JadwalPelajaran::create($validated);

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil ditambahkan!');
    }

    public function update(Request $request, JadwalPelajaran $jadwal)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'guru_id' => 'required|exists:users,id',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
        ]);

        $jadwal->update($validated);

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil diperbarui!');
    }

    public function destroy(JadwalPelajaran $jadwal)
    {
        $kelasId = $jadwal->kelas_id;
        $jadwal->delete();
        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil dihapus!');
    }

    public function exportCsv(Kelas $kelas)
    {
        $jadwal = JadwalPelajaran::with(['mataPelajaran', 'guru'])
            ->where('kelas_id', $kelas->id)
            ->orderByRaw("
                CASE hari
                    WHEN 'Senin' THEN 1
                    WHEN 'Selasa' THEN 2
                    WHEN 'Rabu' THEN 3
                    WHEN 'Kamis' THEN 4
                    WHEN 'Jumat' THEN 5
                    WHEN 'Sabtu' THEN 6
                END
            ")->orderBy('jam_mulai')->get();

        $filename = 'jadwal_pelajaran_kelas_' . str_replace(' ', '_', $kelas->nama_kelas) . '_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($jadwal, $kelas) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for UTF-8
            fputcsv($file, ['Jadwal Pelajaran Kelas ' . $kelas->nama_kelas]);
            fputcsv($file, ['Hari', 'Waktu', 'Mata Pelajaran', 'Guru Pengampu']);
            
            foreach ($jadwal as $j) {
                fputcsv($file, [
                    $j->hari,
                    \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') . ' - ' . \Carbon\Carbon::parse($j->jam_selesai)->format('H:i'),
                    $j->mataPelajaran->nama_pelajaran ?? '-',
                    $j->guru->nama_lengkap ?? $j->guru->name ?? '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Kelas $kelas)
    {
        $jadwal = JadwalPelajaran::with(['mataPelajaran', 'guru'])
            ->where('kelas_id', $kelas->id)
            ->orderByRaw("
                CASE hari
                    WHEN 'Senin' THEN 1
                    WHEN 'Selasa' THEN 2
                    WHEN 'Rabu' THEN 3
                    WHEN 'Kamis' THEN 4
                    WHEN 'Jumat' THEN 5
                    WHEN 'Sabtu' THEN 6
                END
            ")->orderBy('jam_mulai')->get();

        return view('exports.jadwal-pdf', compact('kelas', 'jadwal'));
    }
}
