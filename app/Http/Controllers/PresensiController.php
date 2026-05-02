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
use Carbon\Carbon;

class PresensiController extends Controller
{
    /**
     * Daftar kelas untuk presensi
     */
    public function index()
    {
        $guruId = Auth::id();

        // Hanya tampilkan kelas dimana guru ini punya jadwal mengajar
        $kelasIdsGuru = JadwalPelajaran::where('guru_id', $guruId)->pluck('kelas_id')->unique();

        $kelasList = Kelas::withCount(['siswa' => fn($q) => $q->where('status', 'Aktif')])
            ->with('waliKelas')
            ->whereIn('id', $kelasIdsGuru)
            ->orderBy('nama_kelas')
            ->get();

        $today = Carbon::today();
        // Hanya hitung presensi dari jadwal guru ini
        $guruJadwalIds = JadwalPelajaran::where('guru_id', $guruId)->pluck('id');
        $presensiToday = Presensi::where('tanggal', $today)->whereIn('jadwal_id', $guruJadwalIds)->get();
        $totalSiswaAktif = Siswa::where('status', 'Aktif')->whereIn('kelas_id', $kelasIdsGuru)->count();
        $hadirCount = $presensiToday->where('status_kehadiran', 'H')->count();
        $sakitCount = $presensiToday->where('status_kehadiran', 'S')->count();
        $izinCount = $presensiToday->where('status_kehadiran', 'I')->count();
        $alpaCount = $presensiToday->where('status_kehadiran', 'A')->count();
        $totalPresensi = $presensiToday->count();
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
        $tanggal = $request->get('tanggal', Carbon::today()->format('Y-m-d'));
        $jadwalId = $request->get('jadwal_id');
        $rentang = $request->get('rentang', 'hari_ini');

        $siswa = $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();

        $guruId = Auth::id();

        // Ambil jadwal kelas ini - hanya yang diampu guru login
        $jadwalList = $kelas->jadwalPelajaran()
            ->where('guru_id', $guruId)
            ->with('mataPelajaran', 'guru')
            ->orderByRaw("
                CASE hari
                    WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3
                    WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6
                END
            ")->orderBy('jam_mulai')->get();

        // Auto-select jadwal if not specified
        if (!$jadwalId && $jadwalList->count() > 0) {
            $jadwalId = $jadwalList->first()->id;
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

        // Mapel list for filter - hanya yang diampu guru login
        $mapelList = $kelas->jadwalPelajaran()
            ->where('guru_id', $guruId)
            ->with('mataPelajaran')
            ->get()
            ->pluck('mataPelajaran')
            ->unique('id')
            ->values();

        // Kelas list for dropdown - hanya kelas yang diampu guru login
        $kelasIdsGuru = JadwalPelajaran::where('guru_id', $guruId)->pluck('kelas_id')->unique();
        $kelasList = Kelas::whereIn('id', $kelasIdsGuru)->orderBy('nama_kelas')->get();

        return view('presensiswa', compact(
            'kelas', 'siswa', 'jadwalList', 'mapelList', 'kelasList', 'tanggal', 'jadwalId', 'rentang',
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

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for UTF-8
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
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);

        // Hanya kelas dimana guru ini punya jadwal
        $kelasIdsGuru = JadwalPelajaran::where('guru_id', $guruId)->pluck('kelas_id')->unique();
        $kelasList = Kelas::with('waliKelas')->whereIn('id', $kelasIdsGuru)->orderBy('nama_kelas')->get();

        // Hanya mapel yang diajarkan oleh guru ini
        $jadwalQuery = JadwalPelajaran::where('guru_id', $guruId);
        
        // Jika ada kelas yang dipilih, filter mapel hanya untuk kelas tersebut
        if ($selectedKelasId) {
            $jadwalQuery->where('kelas_id', $selectedKelasId);
        }
        
        $mapelIdsGuru = $jadwalQuery->pluck('pelajaran_id')->unique();
        $mapelList = MataPelajaran::whereIn('id', $mapelIdsGuru)->orderBy('nama_pelajaran')->get();

        $rekapData = [];
        $selectedKelas = null;

        if ($selectedKelasId) {
            $selectedKelas = Kelas::find($selectedKelasId);
            $siswaList = $selectedKelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();
            
            // Generate list of dates for the selected month (excluding Sundays)
            $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;
            $dates = [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $date = Carbon::create($tahun, $bulan, $i);
                if ($date->dayOfWeek !== Carbon::SUNDAY) {
                    $dates[] = $date->format('Y-m-d');
                }
            }

            // Get all presensi for this month and class filtering by logged-in teacher
            $presensiQuery = Presensi::whereHas('jadwal', function($q) use ($selectedKelasId, $selectedPelajaranId, $guruId) {
                    $q->where('kelas_id', $selectedKelasId)
                      ->where('guru_id', $guruId);
                    if ($selectedPelajaranId) {
                        $q->where('pelajaran_id', $selectedPelajaranId);
                    }
                })
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
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

        return view('rekap-presensi', compact('kelasList', 'mapelList', 'selectedKelas', 'selectedPelajaranId', 'rekapData', 'bulan', 'tahun'));
    }

    private function getRekapDataForExport(Request $request)
    {
        $guruId = Auth::id();
        $selectedKelasId = $request->get('kelas_id');
        $selectedPelajaranId = $request->get('pelajaran_id');
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);

        if (!$selectedKelasId) {
            return ['selectedKelas' => null, 'rekapData' => [], 'selectedPelajaran' => null];
        }

        $selectedKelas = Kelas::find($selectedKelasId);
        $selectedPelajaran = $selectedPelajaranId ? MataPelajaran::find($selectedPelajaranId) : null;
        $siswaList = $selectedKelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        
        $presensiQuery = Presensi::whereHas('jadwal', function($q) use ($selectedKelasId, $selectedPelajaranId, $guruId) {
                $q->where('kelas_id', $selectedKelasId)
                  ->where('guru_id', $guruId);
                if ($selectedPelajaranId) {
                    $q->where('pelajaran_id', $selectedPelajaranId);
                }
            })
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
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

        return ['selectedKelas' => $selectedKelas, 'rekapData' => $rekapData, 'selectedPelajaran' => $selectedPelajaran];
    }

    public function exportRekapCsv(Request $request)
    {
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);
        $data = $this->getRekapDataForExport($request);
        
        if (!$data['selectedKelas']) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        $kelas = $data['selectedKelas'];
        $pelajaran = $data['selectedPelajaran'];
        $rekapData = $data['rekapData'];

        $namaPelajaran = $pelajaran ? '_' . str_replace(' ', '_', $pelajaran->nama_pelajaran) : '';
        $filename = 'rekap_presensi_' . str_replace(' ', '_', $kelas->nama_kelas) . $namaPelajaran . '_' . $bulan . '_' . $tahun . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rekapData) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
            fputcsv($file, ['No', 'NIS', 'Nama Siswa', 'Hadir', 'Sakit', 'Izin', 'Alfa', 'Total']);
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
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);
        $data = $this->getRekapDataForExport($request);
        
        if (!$data['selectedKelas']) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        $selectedKelas = $data['selectedKelas'];
        $selectedPelajaran = $data['selectedPelajaran'];
        $rekapData = $data['rekapData'];

        return view('exports.rekap-presensi-pdf', compact('selectedKelas', 'selectedPelajaran', 'rekapData', 'bulan', 'tahun'));
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
}
