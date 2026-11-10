<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\PengajuanIzin;
use App\Models\PengaturanSekolah;
use App\Models\Presensi;
use App\Models\PresensiGuru;
use App\Support\Sekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Data beranda guru di aplikasi MyDarfik. */
class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $guru = $request->user();
        $now = Sekolah::now();
        $tanggal = $now->toDateString();
        $hari = Sekolah::hari($now);

        // Presensi kelas hanya diisi wali kelas: ringkasan mengikuti kelas yang diwalikan
        // (admin/kepala sekolah melihat seluruh sekolah).
        $kelasWali = Kelas::where('wali_kelas_id', $guru->id)->pluck('id');
        $jadwalIds = JadwalPelajaran::whereIn('kelas_id', $kelasWali)->pluck('id');
        $rekap = Presensi::whereIn('jadwal_id', $jadwalIds)->where('tanggal', $tanggal)
            ->selectRaw('status_kehadiran, COUNT(*) as jumlah')->groupBy('status_kehadiran')
            ->pluck('jumlah', 'status_kehadiran');
        $total = (int) $rekap->sum();

        $jadwalHariIni = JadwalPelajaran::with(['kelas', 'mataPelajaran'])
            ->where('guru_id', $guru->id)->where('hari', $hari)->orderBy('jam_mulai')->get()
            ->map(fn ($j) => [
                'jadwal_id' => $j->id,
                'kelas_id' => $j->kelas_id,
                'kelas' => $j->kelas?->nama_kelas,
                'mata_pelajaran' => $j->mataPelajaran?->nama_pelajaran,
                'jam_mulai' => substr($j->jam_mulai, 0, 5),
                'jam_selesai' => substr($j->jam_selesai, 0, 5),
                'bisa_absen' => $kelasWali->contains($j->kelas_id),
                'sedang_berlangsung' => $now->format('H:i') >= substr($j->jam_mulai, 0, 5) && $now->format('H:i') < substr($j->jam_selesai, 0, 5),
            ]);

        $absen = PresensiGuru::where('guru_id', $guru->id)->where('tanggal', $tanggal)->first();

        return response()->json([
            'tanggal' => $tanggal,
            'hari' => $hari,
            'bisa_isi_presensi' => $kelasWali->isNotEmpty(),
            'kehadiran_siswa' => [
                'hadir' => (int) ($rekap['H'] ?? 0),
                'izin' => (int) ($rekap['I'] ?? 0),
                'sakit' => (int) ($rekap['S'] ?? 0),
                'alpa' => (int) ($rekap['A'] ?? 0),
                'total' => $total,
                'persen_hadir' => $total > 0 ? round(($rekap['H'] ?? 0) / $total * 100, 1) : null,
            ],
            'izin_menunggu' => PengajuanIzin::terlihatOleh($guru)->where('status', PengajuanIzin::MENUNGGU)->count(),
            'presensi_saya' => AbsenController::ringkasHariIni($absen) + ['jam_masuk_acuan' => PengaturanSekolah::jamMasuk()],
            'jadwal_hari_ini' => $jadwalHariIni,
        ]);
    }
}
