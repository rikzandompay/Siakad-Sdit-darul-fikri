<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Support\Sekolah;
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
            ->urutHari()->orderBy('jam_mulai')->get();

        $filename = 'jadwal_pelajaran_kelas_'.str_replace(' ', '_', $kelas->nama_kelas).'_'.Sekolah::now()->format('Ymd_His').'.csv';

        return $this->streamCsv($filename, function ($file) use ($jadwal, $kelas) {
            $this->putKopCsv($file, 'LAPORAN JADWAL PELAJARAN', [
                ['Kelas', ':', $kelas->nama_kelas],
                ['Tahun Ajaran', ':', TahunAjaran::getAktif()?->nama_semester ?? '-'],
                ['Tanggal Cetak', ':', Sekolah::now()->translatedFormat('d F Y')],
            ]);

            fputcsv($file, ['Hari', 'Waktu', 'Mata Pelajaran', 'Guru Pengampu']);

            foreach ($jadwal as $j) {
                $this->putSafeCsv($file, [
                    $j->hari,
                    Carbon::parse($j->jam_mulai)->format('H:i').' - '.Carbon::parse($j->jam_selesai)->format('H:i'),
                    $j->mataPelajaran->nama_pelajaran ?? '-',
                    $j->guru->nama_lengkap ?? $j->guru->name ?? '-',
                ]);
            }
        });
    }

    public function exportPdf(Kelas $kelas)
    {
        $jadwal = JadwalPelajaran::with(['mataPelajaran', 'guru'])
            ->where('kelas_id', $kelas->id)
            ->urutHari()->orderBy('jam_mulai')->get();

        return view('exports.jadwal-pdf', compact('kelas', 'jadwal'));
    }

    private function clearJadwalCache($guruId, $kelasId)
    {
        Cache::forget("jadwal_sepekan_guru_{$guruId}");
        Cache::forget("kelas_ids_guru_{$guruId}");
        Cache::forget("kelas_list_guru_{$guruId}");
        Cache::forget("mapel_list_guru_{$guruId}_kelas_all");
        Cache::forget("mapel_list_guru_{$guruId}_kelas_{$kelasId}");
        Cache::forget("total_siswa_aktif_guru_{$guruId}");
        Cache::forget("jadwal_list_wali_kelas_{$kelasId}");
    }
}
