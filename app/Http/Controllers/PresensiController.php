<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\MataPelajaran;
use App\Models\JadwalPelajaran;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PresensiController extends Controller
{
    protected function ensureTeacherCanAccessKelas(int $kelasId): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        $guruId = Auth::id();
        $hasAccess = JadwalPelajaran::where('guru_id', $guruId)
            ->where('kelas_id', $kelasId)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini.');
        }
    }

    protected function ensureJadwalOwnership(int $jadwalId): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        $guruId = Auth::id();
        $isOwner = JadwalPelajaran::where('id', $jadwalId)
            ->where('guru_id', $guruId)
            ->exists();

        if (!$isOwner) {
            abort(403, 'Anda tidak memiliki akses ke jadwal ini.');
        }
    }

    /**
     * Daftar kelas untuk presensi
     */
    public function index()
    {
        $guruId = Auth::id();

        // Hanya tampilkan kelas dimana guru ini punya jadwal mengajar
        $kelasIdsGuru = Cache::remember("kelas_ids_guru_{$guruId}", 300, function() use ($guruId) {
            return JadwalPelajaran::where('guru_id', $guruId)->pluck('kelas_id')->unique()->toArray();
        });

        $kelasList = Cache::remember("presensi_kelas_list_{$guruId}", 300, function() use ($kelasIdsGuru) {
            return Kelas::withCount('siswaAktif')
                ->with('waliKelas')
                ->whereIn('id', $kelasIdsGuru)
                ->orderBy('nama_kelas')
                ->get();
        });

        $today = Carbon::today();
        $todayStr = $today->toDateString();
        
        // Hanya hitung presensi dari jadwal guru ini
        $guruJadwalIds = Cache::remember("guru_jadwal_ids_{$guruId}", 300, function() use ($guruId) {
            return JadwalPelajaran::where('guru_id', $guruId)->pluck('id')->toArray();
        });

        $totalSiswaAktif = Cache::remember("total_siswa_aktif_guru_{$guruId}", 300, function() use ($kelasIdsGuru) {
            return Siswa::where('status', 'Aktif')->whereIn('kelas_id', $kelasIdsGuru)->count();
        });

        $presensiStats = Cache::remember("presensi_stats_guru_{$guruId}_{$todayStr}", 60, function() use ($today, $guruJadwalIds) {
            $presensiToday = Presensi::where('tanggal', $today)->whereIn('jadwal_id', $guruJadwalIds)->get();
            $hadirCount = $presensiToday->where('status_kehadiran', 'H')->count();
            $sakitCount = $presensiToday->where('status_kehadiran', 'S')->count();
            $izinCount = $presensiToday->where('status_kehadiran', 'I')->count();
            $alpaCount = $presensiToday->where('status_kehadiran', 'A')->count();
            $totalPresensi = $presensiToday->count();
            
            return compact('hadirCount', 'sakitCount', 'izinCount', 'alpaCount', 'totalPresensi');
        });

        $hadirCount = $presensiStats['hadirCount'];
        $sakitCount = $presensiStats['sakitCount'];
        $izinCount = $presensiStats['izinCount'];
        $alpaCount = $presensiStats['alpaCount'];
        $totalPresensi = $presensiStats['totalPresensi'];
        $persenHadir = $totalPresensi > 0 ? round(($hadirCount / $totalPresensi) * 100, 1) : 0;

        return view('presensikelas', compact(
            'kelasList', 'totalSiswaAktif',
            'hadirCount', 'sakitCount', 'izinCount', 'alpaCount', 'persenHadir'
        ));
    }

    /**
     * Form input presensi per kelas (matching screenshot UI)
     */
    public function show(Kelas $kelas, Request $request)
    {
        $this->ensureTeacherCanAccessKelas($kelas->id);

        $tanggal = $request->get('tanggal', Carbon::today()->format('Y-m-d'));
        $jadwalId = $request->get('jadwal_id');
        $guruId = Auth::id();

        // Cache daftar siswa aktif kelas ini
        $siswa = Cache::remember("siswa_aktif_kelas_{$kelas->id}", 300, function() use ($kelas) {
            return $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        });

        // Cache jadwal kelas ini - hanya yang diampu guru login (5 menit)
        $jadwalList = Cache::remember("jadwal_list_guru_{$guruId}_kelas_{$kelas->id}", 300, function() use ($kelas, $guruId) {
            return $kelas->jadwalPelajaran()
                ->where('guru_id', $guruId)
                ->with('mataPelajaran', 'guru')
                ->orderByRaw("
                    CASE hari
                        WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3
                        WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6
                    END
                ")->orderBy('jam_mulai')->get();
        });

        // Auto-select jadwal berdasarkan hari dari tanggal yang dipilih
        $hariTanggal = Carbon::parse($tanggal)->translatedFormat('l'); // Nama hari dalam Bahasa Indonesia
        // Map hari translasi Carbon ke format jadwal
        $hariMap = [
            'Senin' => 'Senin', 'Selasa' => 'Selasa', 'Rabu' => 'Rabu',
            'Kamis' => 'Kamis', 'Jumat' => 'Jumat', 'Sabtu' => 'Sabtu',
        ];
        $hariTanggalEn = Carbon::parse($tanggal)->format('l'); // English day name
        $hariIndonesia = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
                          'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $hariNow = $hariIndonesia[$hariTanggalEn] ?? null;

        if (!$jadwalId && $jadwalList->count() > 0) {
            // Coba auto-select jadwal yang sesuai hari tanggal
            $jadwalHariIni = $jadwalList->firstWhere('hari', $hariNow);
            $jadwalId = $jadwalHariIni ? $jadwalHariIni->id : $jadwalList->first()->id;
        }

        // Existing presensi data
        $existingPresensi = [];
        if ($jadwalId) {
            $existingPresensi = Presensi::where('jadwal_id', $jadwalId)
                ->where('tanggal', $tanggal)
                ->pluck('status_kehadiran', 'siswa_id')
                ->toArray();
        }

        // Summary counts
        $hadirCount = collect($existingPresensi)->filter(fn($v) => $v === 'H')->count();
        $sakitCount = collect($existingPresensi)->filter(fn($v) => $v === 'S')->count();
        $izinCount = collect($existingPresensi)->filter(fn($v) => $v === 'I')->count();
        $alpaCount = collect($existingPresensi)->filter(fn($v) => $v === 'A')->count();

        // Mapel list for filter - derived from $jadwalList to save query
        $mapelList = $jadwalList->pluck('mataPelajaran')->unique('id')->values();

        // Kelas list for dropdown - hanya kelas yang diampu guru login (di-cache)
        $kelasIdsGuru = Cache::remember("kelas_ids_guru_{$guruId}", 300, function() use ($guruId) {
            return JadwalPelajaran::where('guru_id', $guruId)->pluck('kelas_id')->unique()->toArray();
        });
        
        $kelasList = Cache::remember("kelas_list_guru_{$guruId}", 300, function() use ($kelasIdsGuru) {
            return Kelas::whereIn('id', $kelasIdsGuru)->orderBy('nama_kelas')->get();
        });

        return view('presensiswa', compact(
            'kelas', 'siswa', 'jadwalList', 'mapelList', 'kelasList', 'tanggal', 'jadwalId',
            'existingPresensi', 'hadirCount', 'sakitCount', 'izinCount', 'alpaCount'
        ));
    }

    /**
     * Simpan/update presensi
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'jadwal_id' => 'required|exists:jadwal_pelajaran,id',
            'tanggal' => 'required|date',
            'presensi' => 'required|array',
            'presensi.*.siswa_id' => 'required|exists:siswa,id',
            'presensi.*.status' => 'required|in:H,I,S,A',
            'presensi.*.keterangan' => 'nullable|string|max:255',
        ]);

        $this->ensureJadwalOwnership($validated['jadwal_id']);

        $tanggalObj = \Carbon\Carbon::parse($validated['tanggal']);

        foreach ($validated['presensi'] as $data) {
            Presensi::updateOrCreate(
                [
                    'jadwal_id' => $validated['jadwal_id'],
                    'siswa_id' => $data['siswa_id'],
                    'tanggal' => $tanggalObj,
                ],
                [
                    'status_kehadiran' => $data['status'],
                    'keterangan' => $data['keterangan'] ?? null,
                ]
            );
        }

        $jadwal = JadwalPelajaran::find($validated['jadwal_id']);
        
        // Invalidate cache
        $guruId = Auth::id();
        $todayStr = $tanggalObj->toDateString();
        Cache::forget("presensi_stats_guru_{$guruId}_{$todayStr}");
        Cache::forget("stats_presensi_today_{$todayStr}");

        return redirect()->route('presensi.show', [
            'kelas' => $jadwal->kelas_id,
            'tanggal' => $validated['tanggal'],
            'jadwal_id' => $validated['jadwal_id'],
        ])->with('success', 'Presensi berhasil disimpan!');
    }

    /**
     * Export presensi as CSV
     */
    public function exportCsv(Kelas $kelas, Request $request)
    {
        $this->ensureTeacherCanAccessKelas($kelas->id);

        $dateRange = $this->getDateRange($request->get('rentang', 'hari_ini'), $request->get('tanggal'));
        $jadwalId = $request->get('jadwal_id');

        $query = Presensi::whereHas('jadwal', fn($q) => $q->where('kelas_id', $kelas->id))
            ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
            ->with(['siswa', 'jadwal.mataPelajaran']);

        if ($jadwalId) {
            $query->where('jadwal_id', $jadwalId);
        }

        $data = $query->orderBy('tanggal')->get();

        $filename = 'presensi_' . str_replace(' ', '_', $kelas->nama_kelas) . '_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data, $kelas, $dateRange, $rentangLabel) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for UTF-8
            
            // Header Section
            fputcsv($file, ['YAYASAN PENDIDIKAN DARUL FIKRI']);
            fputcsv($file, ['SD IT DARUL FIKRI']);
            fputcsv($file, ['LAPORAN KEHADIRAN SISWA']);
            fputcsv($file, []);
            fputcsv($file, ['Kelas', ':', $kelas->nama_kelas]);
            fputcsv($file, ['Periode', ':', $rentangLabel . ' (' . \Carbon\Carbon::parse($dateRange['start'])->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($dateRange['end'])->format('d/m/Y') . ')']);
            fputcsv($file, ['Tanggal Cetak', ':', now()->translatedFormat('d F Y')]);
            fputcsv($file, []);

            // Data Header
            fputcsv($file, ['No', 'Tanggal', 'NIS', 'Nama Siswa', 'Mata Pelajaran', 'Status', 'Keterangan']);
            $no = 1;
            foreach ($data as $p) {
                fputcsv($file, [
                    $no++,
                    $p->tanggal->format('d/m/Y'),
                    $p->siswa->nis ?? '-',
                    $p->siswa->nama_siswa ?? '-',
                    $p->jadwal->mataPelajaran->nama_pelajaran ?? '-',
                    $p->status_label,
                    $p->keterangan ?? '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export presensi as PDF
     */
    public function exportPdf(Kelas $kelas, Request $request)
    {
        $this->ensureTeacherCanAccessKelas($kelas->id);

        $dateRange = $this->getDateRange($request->get('rentang', 'hari_ini'), $request->get('tanggal'));
        $jadwalId = $request->get('jadwal_id');

        $query = Presensi::whereHas('jadwal', fn($q) => $q->where('kelas_id', $kelas->id))
            ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
            ->with(['siswa', 'jadwal.mataPelajaran']);

        if ($jadwalId) {
            $query->where('jadwal_id', $jadwalId);
        }

        $data = $query->orderBy('tanggal')->get();

        // Group by siswa for summary
        $siswaList = $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $summary = [];
        foreach ($siswaList as $s) {
            $presensiSiswa = $data->where('siswa_id', $s->id);
            $summary[$s->id] = [
                'siswa' => $s,
                'hadir' => $presensiSiswa->where('status_kehadiran', 'H')->count(),
                'sakit' => $presensiSiswa->where('status_kehadiran', 'S')->count(),
                'izin' => $presensiSiswa->where('status_kehadiran', 'I')->count(),
                'alpa' => $presensiSiswa->where('status_kehadiran', 'A')->count(),
            ];
        }

        $rentangLabel = $this->getRentangLabel($request->get('rentang', 'hari_ini'));

        return view('exports.presensi-pdf', compact('kelas', 'data', 'summary', 'dateRange', 'rentangLabel'));
    }

    public function rekap(Request $request)
    {
        $guruId = Auth::id();
        $selectedKelasId = $request->get('kelas_id');
        $selectedPelajaranId = $request->get('pelajaran_id');
        $tanggal = $request->get('tanggal', Carbon::today()->format('Y-m-d'));
        $rentang = $request->get('rentang', 'bulan_ini');

        // Hanya kelas dimana guru ini punya jadwal (di-cache)
        $kelasIdsGuru = Cache::remember("kelas_ids_guru_{$guruId}", 300, function() use ($guruId) {
            return JadwalPelajaran::where('guru_id', $guruId)->pluck('kelas_id')->unique();
        });
        $kelasList = Cache::remember("rekap_kelas_list_guru_{$guruId}", 300, function() use ($kelasIdsGuru) {
            return Kelas::with('waliKelas')->whereIn('id', $kelasIdsGuru)->orderBy('nama_kelas')->get();
        });

        // Hanya mapel yang diajarkan oleh guru ini
        $jadwalQuery = JadwalPelajaran::where('guru_id', $guruId);
        
        // Jika ada kelas yang dipilih, filter mapel hanya untuk kelas tersebut
        if ($selectedKelasId) {
            $jadwalQuery->where('kelas_id', $selectedKelasId);
        }
        
        $mapelIdsGuru = $jadwalQuery->pluck('pelajaran_id')->unique();
        $mapelList = MataPelajaran::whereIn('id', $mapelIdsGuru)->orderBy('nama_pelajaran')->get();

        // Hitung date range
        $dateRange = $this->getDateRange($rentang, $tanggal);
        $periodeLabel = $this->getPeriodeLabelWithDate($rentang, $tanggal);

        $rekapData = [];
        $selectedKelas = null;

        if ($selectedKelasId) {
            $this->ensureTeacherCanAccessKelas($selectedKelasId);
            $selectedKelas = Kelas::find($selectedKelasId);
            $siswaList = $selectedKelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();

            // Get all presensi for this date range and class filtering by logged-in teacher
            $presensiQuery = Presensi::whereHas('jadwal', function($q) use ($selectedKelasId, $selectedPelajaranId, $guruId) {
                    $q->where('kelas_id', $selectedKelasId)
                      ->where('guru_id', $guruId);
                    if ($selectedPelajaranId) {
                        $q->where('pelajaran_id', $selectedPelajaranId);
                    }
                })
                ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
                ->get();

            foreach ($siswaList as $siswa) {
                $siswaPresensi = $presensiQuery->where('siswa_id', $siswa->id);

                $rekapData[$siswa->id] = [
                    'siswa' => $siswa,
                    'summary' => [
                        'H' => $siswaPresensi->where('status_kehadiran', 'H')->count(),
                        'S' => $siswaPresensi->where('status_kehadiran', 'S')->count(),
                        'I' => $siswaPresensi->where('status_kehadiran', 'I')->count(),
                        'A' => $siswaPresensi->where('status_kehadiran', 'A')->count(),
                    ],
                ];
            }
        }

        return view('rekap-presensi', compact('kelasList', 'mapelList', 'selectedKelas', 'selectedPelajaranId', 'rekapData', 'tanggal', 'rentang', 'periodeLabel'));
    }

    private function getRekapDataForExport(Request $request)
    {
        $guruId = Auth::id();
        $selectedKelasId = $request->get('kelas_id');
        $selectedPelajaranId = $request->get('pelajaran_id');
        $tanggal = $request->get('tanggal', Carbon::today()->format('Y-m-d'));
        $rentang = $request->get('rentang', 'bulan_ini');

        if (!$selectedKelasId) {
            return ['selectedKelas' => null, 'rekapData' => [], 'periodeLabel' => '', 'selectedPelajaran' => null];
        }

        $this->ensureTeacherCanAccessKelas($selectedKelasId);
        $selectedKelas = Kelas::find($selectedKelasId);
        $selectedPelajaran = $selectedPelajaranId ? MataPelajaran::find($selectedPelajaranId) : null;
        $siswaList = $selectedKelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();

        $dateRange = $this->getDateRange($rentang, $tanggal);
        $periodeLabel = $this->getPeriodeLabelWithDate($rentang, $tanggal);

        $presensiQuery = Presensi::whereHas('jadwal', function($q) use ($selectedKelasId, $selectedPelajaranId, $guruId) {
                $q->where('kelas_id', $selectedKelasId)
                  ->where('guru_id', $guruId);
                if ($selectedPelajaranId) {
                    $q->where('pelajaran_id', $selectedPelajaranId);
                }
            })
            ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
            ->get();

        $rekapData = [];
        foreach ($siswaList as $siswa) {
            $siswaPresensi = $presensiQuery->where('siswa_id', $siswa->id);
            $rekapData[$siswa->id] = [
                'siswa' => $siswa,
                'summary' => [
                    'H' => $siswaPresensi->where('status_kehadiran', 'H')->count(),
                    'S' => $siswaPresensi->where('status_kehadiran', 'S')->count(),
                    'I' => $siswaPresensi->where('status_kehadiran', 'I')->count(),
                    'A' => $siswaPresensi->where('status_kehadiran', 'A')->count(),
                ],
            ];
        }

        return ['selectedKelas' => $selectedKelas, 'rekapData' => $rekapData, 'periodeLabel' => $periodeLabel, 'selectedPelajaran' => $selectedPelajaran];
    }

    public function exportRekapCsv(Request $request)
    {
        $data = $this->getRekapDataForExport($request);

        if (!$data['selectedKelas']) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        $kelas = $data['selectedKelas'];
        $rekapData = $data['rekapData'];
        $periodeLabel = $data['periodeLabel'];
        $selectedPelajaran = $data['selectedPelajaran'];

        $namaPelajaran = $selectedPelajaran ? '_' . str_replace(' ', '_', $selectedPelajaran->nama_pelajaran) : '';
        $filename = 'rekap_presensi_' . str_replace(' ', '_', $kelas->nama_kelas) . $namaPelajaran . '_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rekapData, $kelas, $periodeLabel, $selectedPelajaran) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

            // Header Section
            fputcsv($file, ['YAYASAN PENDIDIKAN DARUL FIKRI']);
            fputcsv($file, ['SD IT DARUL FIKRI']);
            fputcsv($file, ['REKAPITULASI PRESENSI SISWA']);
            fputcsv($file, []);
            fputcsv($file, ['Kelas', ':', $kelas->nama_kelas]);
            fputcsv($file, ['Mata Pelajaran', ':', $selectedPelajaran ? $selectedPelajaran->nama_pelajaran : 'Semua Mata Pelajaran']);
            fputcsv($file, ['Periode', ':', $periodeLabel]);
            fputcsv($file, ['Tanggal Cetak', ':', now()->translatedFormat('d F Y')]);
            fputcsv($file, []);

            // Data Header
            fputcsv($file, ['No', 'NIS', 'Nama Siswa', 'Hadir (H)', 'Sakit (S)', 'Izin (I)', 'Alpa (A)', 'Total']);
            $no = 1;
            foreach ($rekapData as $row) {
                $total = $row['summary']['H'] + $row['summary']['S'] + $row['summary']['I'] + $row['summary']['A'];
                fputcsv($file, [
                    $no++,
                    $row['siswa']->nis ?? '-',
                    $row['siswa']->nama_siswa ?? '-',
                    $row['summary']['H'],
                    $row['summary']['S'],
                    $row['summary']['I'],
                    $row['summary']['A'],
                    $total
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportRekapPdf(Request $request)
    {
        $data = $this->getRekapDataForExport($request);

        if (!$data['selectedKelas']) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        $selectedKelas = $data['selectedKelas'];
        $rekapData = $data['rekapData'];
        $periodeLabel = $data['periodeLabel'];
        $selectedPelajaran = $data['selectedPelajaran'];

        return view('exports.rekap-presensi-pdf', compact('selectedKelas', 'rekapData', 'periodeLabel', 'selectedPelajaran'));
    }

    private function getDateRange($rentang, $tanggal = null)
    {
        $now = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        return match ($rentang) {
            'minggu_ini' => ['start' => $now->copy()->startOfWeek(), 'end' => $now->copy()->endOfWeek()],
            'bulan_ini' => ['start' => $now->copy()->startOfMonth(), 'end' => $now->copy()->endOfMonth()],
            'semester_ini' => [
                'start' => $now->month >= 7 ? Carbon::create($now->year, 7, 1) : Carbon::create($now->year, 1, 1),
                'end' => $now->month >= 7 ? Carbon::create($now->year, 12, 31) : Carbon::create($now->year, 6, 30),
            ],
            default => ['start' => $now->copy()->startOfDay(), 'end' => $now->copy()->endOfDay()],
        };
    }

    private function getRentangLabel($rentang)
    {
        return match ($rentang) {
            'minggu_ini' => 'Minggu Ini',
            'bulan_ini' => 'Bulan Ini',
            'semester_ini' => 'Semester Ini',
            default => 'Hari Ini',
        };
    }

    private function getPeriodeLabelWithDate($rentang, $tanggal = null)
    {
        $now = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        return match ($rentang) {
            'minggu_ini' => 'Minggu ' . $now->copy()->startOfWeek()->translatedFormat('d M') . ' - ' . $now->copy()->endOfWeek()->translatedFormat('d M Y'),
            'bulan_ini' => $now->translatedFormat('F Y'),
            'semester_ini' => ($now->month >= 7 ? 'Semester Ganjil ' : 'Semester Genap ') . $now->year,
            default => $now->translatedFormat('d F Y'),
        };
    }
}
