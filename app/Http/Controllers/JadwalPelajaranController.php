<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        $this->clearJadwalCache($validated['guru_id'], $validated['kelas_id']);

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

        $oldGuruId = $jadwal->guru_id;
        $oldKelasId = $jadwal->kelas_id;
        $jadwal->update($validated);
        $this->clearJadwalCache($validated['guru_id'], $validated['kelas_id']);
        if ($oldGuruId !== $validated['guru_id'] || $oldKelasId !== $validated['kelas_id']) {
            $this->clearJadwalCache($oldGuruId, $oldKelasId);
        }

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil diperbarui!');
    }

    public function destroy(JadwalPelajaran $jadwal)
    {
        $guruId = $jadwal->guru_id;
        $kelasId = $jadwal->kelas_id;
        $jadwal->delete();
        $this->clearJadwalCache($guruId, $kelasId);

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

        $filename = 'jadwal_pelajaran_kelas_'.str_replace(' ', '_', $kelas->nama_kelas).'_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($jadwal, $kelas) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8

            // Header Section
            fputcsv($file, ['YAYASAN PENDIDIKAN DARUL FIKRI']);
            fputcsv($file, ['SD IT DARUL FIKRI']);
            fputcsv($file, ['LAPORAN JADWAL PELAJARAN']);
            fputcsv($file, []);
            fputcsv($file, ['Kelas', ':', $kelas->nama_kelas]);
            fputcsv($file, ['Tahun Ajaran', ':', date('Y').'/'.(date('Y') + 1)]);
            fputcsv($file, ['Tanggal Cetak', ':', now()->translatedFormat('d F Y')]);
            fputcsv($file, []);

            fputcsv($file, ['Hari', 'Waktu', 'Mata Pelajaran', 'Guru Pengampu']);

            foreach ($jadwal as $j) {
                fputcsv($file, [
                    $j->hari,
                    Carbon::parse($j->jam_mulai)->format('H:i').' - '.Carbon::parse($j->jam_selesai)->format('H:i'),
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

    private function clearJadwalCache($guruId, $kelasId)
    {
        Cache::forget("jadwal_sepekan_guru_{$guruId}");
        Cache::forget("jadwal_list_guru_{$guruId}_kelas_{$kelasId}");
        Cache::forget("kelas_ids_guru_{$guruId}");
        Cache::forget("kelas_list_guru_{$guruId}");
        Cache::forget("presensi_kelas_list_{$guruId}");
        Cache::forget("guru_jadwal_ids_{$guruId}");
        Cache::forget("total_siswa_aktif_guru_{$guruId}");
        Cache::forget("rekap_kelas_list_guru_{$guruId}");
    }
}
