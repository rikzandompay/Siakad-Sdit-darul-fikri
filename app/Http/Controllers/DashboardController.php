<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\JadwalPelajaran;
use App\Models\Presensi;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalSiswa = Siswa::where('status', 'Aktif')->count();
        $totalKelas = Kelas::count();
        $tahunAktif = TahunAjaran::getAktif();

        // Stats presensi hari ini
        $today = Carbon::today();
        $presensiToday = Presensi::where('tanggal', $today)->get();
        $hadirCount = $presensiToday->where('status_kehadiran', 'H')->count();
        $sakitCount = $presensiToday->where('status_kehadiran', 'S')->count();
        $izinCount = $presensiToday->where('status_kehadiran', 'I')->count();
        $alpaCount = $presensiToday->where('status_kehadiran', 'A')->count();
        $totalPresensi = $presensiToday->count();
        $persenHadir = $totalPresensi > 0 ? round(($hadirCount / $totalPresensi) * 100, 1) : 0;

        // Jadwal mengajar guru sepekan
        $jadwalSepekan = JadwalPelajaran::where('guru_id', Auth::id())
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
            ")->orderBy('jam_mulai')->get();

        // Daftar kelas
        $kelasList = Kelas::withCount(['siswa' => function ($q) {
            $q->where('status', 'Aktif');
        }])->with('waliKelas')->get();

        return view('dashboard', compact(
            'totalSiswa', 'totalKelas', 'tahunAktif',
            'hadirCount', 'sakitCount', 'izinCount', 'alpaCount', 'persenHadir',
            'jadwalSepekan', 'kelasList'
        ));
    }
}
