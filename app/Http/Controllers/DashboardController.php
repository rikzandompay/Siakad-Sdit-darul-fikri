<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\JadwalPelajaran;
use App\Models\Presensi;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        
        // Cache stats for 60 seconds to drastically reduce database roundtrips
        $totalSiswa = Cache::remember('total_siswa_aktif', 60, fn() => Siswa::where('status', 'Aktif')->count());
        $totalKelas = Cache::remember('total_kelas', 60, fn() => Kelas::count());
        $tahunAktif = Cache::remember('tahun_aktif', 60, fn() => TahunAjaran::getAktif());

        // Stats presensi hari ini
        $today = Carbon::today()->toDateString();
        $statsPresensi = Cache::remember("stats_presensi_today_{$today}", 60, function() {
            $data = Presensi::where('tanggal', Carbon::today())
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status_kehadiran = 'H' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status_kehadiran = 'S' THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN status_kehadiran = 'I' THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status_kehadiran = 'A' THEN 1 ELSE 0 END) as alpa
                ")->first();
            return $data ? $data->toArray() : [];
        });
        
        $totalPresensi = $statsPresensi['total'] ?? 0;
        $hadirCount = $statsPresensi['hadir'] ?? 0;
        $sakitCount = $statsPresensi['sakit'] ?? 0;
        $izinCount = $statsPresensi['izin'] ?? 0;
        $alpaCount = $statsPresensi['alpa'] ?? 0;
        $persenHadir = $totalPresensi > 0 ? round(($hadirCount / $totalPresensi) * 100, 1) : 0;

        // Jadwal mengajar guru sepekan (di-cache per user)
        $jadwalSepekan = Cache::remember("jadwal_sepekan_guru_{$userId}", 60, fn() => 
            JadwalPelajaran::where('guru_id', $userId)
                ->with(['kelas', 'mataPelajaran'])
                ->orderByRaw("
                    CASE hari
                        WHEN 'Senin' THEN 1
                        WHEN 'Selasa' THEN 2
                        WHEN 'Rabu' THEN 3
                        WHEN 'Kamis' THEN 4
                        WHEN 'Jumat' THEN 5
                        WHEN 'Sabtu' THEN 6
                    END
                ")->orderBy('jam_mulai')->get()
        );

        // Daftar kelas
        $kelasList = Cache::remember('kelas_list_dashboard', 60, fn() => 
            Kelas::withCount('siswaAktif')->with('waliKelas')->get()
        );

        return view('dashboard', compact(
            'totalSiswa', 'totalKelas', 'tahunAktif',
            'hadirCount', 'sakitCount', 'izinCount', 'alpaCount', 'persenHadir',
            'jadwalSepekan', 'kelasList'
        ));
    }
}
