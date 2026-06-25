<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\PresensiSholat;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PresensiSholatController extends Controller
{
    /**
     * Daftar kelas untuk presensi sholat
     */
    public function index(Request $request)
    {
        $jenisSholat = $request->get('jenis', 'Zuhur');
        $guruId = Auth::id();
        $today = Carbon::today();
        $todayStr = $today->toDateString();

        $kelasList = Cache::remember("sholat_kelas_list_guru_{$guruId}_jenis_{$jenisSholat}", 300, function() use ($jenisSholat, $guruId) {
            if ($jenisSholat === 'Zuhur') {
                // Kelas 3-6 setiap hari, hanya wali kelas
                return Kelas::withCount('siswaAktif')
                    ->with('waliKelas')
                    ->where('wali_kelas_id', $guruId)
                    ->where(function ($q) {
                        $q->where('nama_kelas', 'like', '%3%')
                          ->orWhere('nama_kelas', 'like', '%4%')
                          ->orWhere('nama_kelas', 'like', '%5%')
                          ->orWhere('nama_kelas', 'like', '%6%');
                    })
                    ->orderBy('nama_kelas')->get();
            } else {
                // Dhuha: Kelas 1-6
                return Kelas::withCount('siswaAktif')
                    ->with('waliKelas')
                    ->where('wali_kelas_id', $guruId)
                    ->orderBy('nama_kelas')->get();
            }
        });

        // Stats hari ini - filter by teacher's classes (di-cache untuk performa)
        $kelasIds = $kelasList->pluck('id');
        $stats = Cache::remember("sholat_presensi_stats_guru_{$guruId}_jenis_{$jenisSholat}_{$todayStr}", 120, function() use ($today, $jenisSholat, $kelasIds) {
            $presensiToday = PresensiSholat::where('tanggal', $today)
                ->where('jenis_sholat', $jenisSholat)
                ->whereIn('kelas_id', $kelasIds)
                ->get();
            return [
                'hadirCount' => $presensiToday->where('status_kehadiran', 'H')->count(),
                'sakitCount' => $presensiToday->where('status_kehadiran', 'S')->count(),
                'izinCount' => $presensiToday->where('status_kehadiran', 'I')->count(),
                'alpaCount' => $presensiToday->where('status_kehadiran', 'A')->count(),
                'totalPresensi' => $presensiToday->count(),
            ];
        });

        $hadirCount = $stats['hadirCount'];
        $sakitCount = $stats['sakitCount'];
        $izinCount = $stats['izinCount'];
        $alpaCount = $stats['alpaCount'];
        $totalPresensi = $stats['totalPresensi'];
        $persenHadir = $totalPresensi > 0 ? round(($hadirCount / $totalPresensi) * 100, 1) : 0;

        $isFriday = $today->isFriday();

        return view('presensi-sholat-kelas', compact(
            'kelasList', 'jenisSholat', 'hadirCount', 'sakitCount',
            'izinCount', 'alpaCount', 'persenHadir', 'isFriday'
        ));
    }

    /**
     * Form input presensi sholat per kelas
     */
    public function show(Kelas $kelas, Request $request)
    {
        $jenisSholat = $request->get('jenis', 'Zuhur');
        $tanggal = $request->get('tanggal', Carbon::today()->format('Y-m-d'));

        $siswa = $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();

        // Existing presensi data
        $existingPresensi = PresensiSholat::where('kelas_id', $kelas->id)
            ->where('tanggal', $tanggal)
            ->where('jenis_sholat', $jenisSholat)
            ->pluck('status_kehadiran', 'siswa_id')
            ->toArray();

        $hadirCount = collect($existingPresensi)->filter(fn($v) => $v === 'H')->count();
        $sakitCount = collect($existingPresensi)->filter(fn($v) => $v === 'S')->count();
        $izinCount = collect($existingPresensi)->filter(fn($v) => $v === 'I')->count();
        $alpaCount = collect($existingPresensi)->filter(fn($v) => $v === 'A')->count();

        $kelasList = Cache::remember('kelas_list_for_sholat', 300, function () {
            return Kelas::orderBy('nama_kelas')->get();
        });

        return view('presensi-sholat-siswa', compact(
            'kelas', 'siswa', 'jenisSholat', 'tanggal', 'kelasList',
            'existingPresensi', 'hadirCount', 'sakitCount', 'izinCount', 'alpaCount'
        ));
    }

    /**
     * Simpan presensi sholat
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'jenis_sholat' => 'required|in:Zuhur,Dhuha',
            'tanggal' => 'required|date',
            'presensi' => 'required|array',
            'presensi.*.siswa_id' => 'required|exists:siswa,id',
            'presensi.*.status' => 'required|in:H,I,S,A',
        ]);

        $now = now();
        $records = collect($validated['presensi'])->map(function ($data) use ($validated, $now) {
            return [
                'siswa_id' => $data['siswa_id'],
                'tanggal' => $validated['tanggal'],
                'jenis_sholat' => $validated['jenis_sholat'],
                'kelas_id' => $validated['kelas_id'],
                'status_kehadiran' => $data['status'],
                'keterangan' => $data['keterangan'] ?? null,
                'recorded_by' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        PresensiSholat::upsert(
            $records,
            ['siswa_id', 'tanggal', 'jenis_sholat'],
            ['kelas_id', 'status_kehadiran', 'keterangan', 'recorded_by', 'updated_at']
        );

        // Invalidate cache
        $guruId = Auth::id();
        $tanggalObj = \Carbon\Carbon::parse($validated['tanggal']);
        $todayStr = $tanggalObj->toDateString();
        Cache::forget("sholat_presensi_stats_guru_{$guruId}_jenis_{$validated['jenis_sholat']}_{$todayStr}");

        return redirect()->route('presensi-sholat.show', [
            'kelas' => $validated['kelas_id'],
            'jenis' => $validated['jenis_sholat'],
            'tanggal' => $validated['tanggal'],
        ])->with('success', 'Presensi Sholat ' . $validated['jenis_sholat'] . ' berhasil disimpan!');
    }

    public function rekap(Request $request)
    {
        $jenisSholat = $request->get('jenis', 'Zuhur');
        $selectedKelasId = $request->get('kelas_id');
        $tanggal = $request->get('tanggal', Carbon::today()->format('Y-m-d'));
        $rentang = $request->get('rentang', 'bulan_ini');

        if ($jenisSholat === 'Zuhur') {
            $kelasList = Kelas::where('wali_kelas_id', Auth::id())
                ->where(function ($q) {
                    $q->where('nama_kelas', 'like', '%3%')
                      ->orWhere('nama_kelas', 'like', '%4%')
                      ->orWhere('nama_kelas', 'like', '%5%')
                      ->orWhere('nama_kelas', 'like', '%6%');
                })->orderBy('nama_kelas')->get();
        } else {
            $kelasList = Kelas::where('wali_kelas_id', Auth::id())->orderBy('nama_kelas')->get();
        }

        $dateRange = $this->getDateRange($rentang, $tanggal);
        $periodeLabel = $this->getPeriodeLabelWithDate($rentang, $tanggal);

        $rekapData = [];
        $selectedKelas = null;

        if ($selectedKelasId) {
            $selectedKelas = Kelas::find($selectedKelasId);
            $siswaList = $selectedKelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();

            $summaryBySiswa = PresensiSholat::where('kelas_id', $selectedKelasId)
                ->where('jenis_sholat', $jenisSholat)
                ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
                ->selectRaw("siswa_id,
                    SUM(CASE WHEN status_kehadiran = 'H' THEN 1 ELSE 0 END) as hadir_count,
                    SUM(CASE WHEN status_kehadiran = 'S' THEN 1 ELSE 0 END) as sakit_count,
                    SUM(CASE WHEN status_kehadiran = 'I' THEN 1 ELSE 0 END) as izin_count,
                    SUM(CASE WHEN status_kehadiran = 'A' THEN 1 ELSE 0 END) as alpa_count")
                ->groupBy('siswa_id')
                ->get()
                ->keyBy('siswa_id');

            foreach ($siswaList as $siswa) {
                $summary = $summaryBySiswa->get($siswa->id);

                $rekapData[$siswa->id] = [
                    'siswa' => $siswa,
                    'summary' => [
                        'H' => (int) ($summary->hadir_count ?? 0),
                        'S' => (int) ($summary->sakit_count ?? 0),
                        'I' => (int) ($summary->izin_count ?? 0),
                        'A' => (int) ($summary->alpa_count ?? 0),
                    ],
                ];
            }
        }

        return view('rekap-presensi-sholat', compact('kelasList', 'selectedKelas', 'rekapData', 'tanggal', 'rentang', 'periodeLabel', 'jenisSholat'));
    }

    private function getRekapDataForExport(Request $request)
    {
        $jenisSholat = $request->get('jenis', 'Zuhur');
        $selectedKelasId = $request->get('kelas_id');
        $tanggal = $request->get('tanggal', Carbon::today()->format('Y-m-d'));
        $rentang = $request->get('rentang', 'bulan_ini');

        if (!$selectedKelasId) {
            return ['selectedKelas' => null, 'rekapData' => [], 'jenisSholat' => $jenisSholat, 'periodeLabel' => ''];
        }

        $selectedKelas = Kelas::find($selectedKelasId);
        $siswaList = $selectedKelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();

        $dateRange = $this->getDateRange($rentang, $tanggal);
        $periodeLabel = $this->getPeriodeLabelWithDate($rentang, $tanggal);

        $summaryBySiswa = PresensiSholat::where('kelas_id', $selectedKelasId)
            ->where('jenis_sholat', $jenisSholat)
            ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
            ->selectRaw("siswa_id,
                SUM(CASE WHEN status_kehadiran = 'H' THEN 1 ELSE 0 END) as hadir_count,
                SUM(CASE WHEN status_kehadiran = 'S' THEN 1 ELSE 0 END) as sakit_count,
                SUM(CASE WHEN status_kehadiran = 'I' THEN 1 ELSE 0 END) as izin_count,
                SUM(CASE WHEN status_kehadiran = 'A' THEN 1 ELSE 0 END) as alpa_count")
            ->groupBy('siswa_id')
            ->get()
            ->keyBy('siswa_id');

        $rekapData = [];
        foreach ($siswaList as $siswa) {
            $summary = $summaryBySiswa->get($siswa->id);
            $rekapData[$siswa->id] = [
                'siswa' => $siswa,
                'summary' => [
                    'H' => (int) ($summary->hadir_count ?? 0),
                    'S' => (int) ($summary->sakit_count ?? 0),
                    'I' => (int) ($summary->izin_count ?? 0),
                    'A' => (int) ($summary->alpa_count ?? 0),
                ],
            ];
        }

        return ['selectedKelas' => $selectedKelas, 'rekapData' => $rekapData, 'jenisSholat' => $jenisSholat, 'periodeLabel' => $periodeLabel];
    }

    public function exportRekapCsv(Request $request)
    {
        $data = $this->getRekapDataForExport($request);

        if (!$data['selectedKelas']) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        $kelas = $data['selectedKelas'];
        $rekapData = $data['rekapData'];
        $jenisSholat = $data['jenisSholat'];
        $periodeLabel = $data['periodeLabel'];

        $filename = 'rekap_presensi_sholat_' . $jenisSholat . '_' . str_replace(' ', '_', $kelas->nama_kelas) . '_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rekapData, $kelas, $jenisSholat, $periodeLabel) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

            // Header Section
            fputcsv($file, ['YAYASAN PENDIDIKAN DARUL FIKRI']);
            fputcsv($file, ['SD IT DARUL FIKRI']);
            fputcsv($file, ['REKAPITULASI PRESENSI SHOLAT ' . strtoupper($jenisSholat)]);
            fputcsv($file, []);
            fputcsv($file, ['Kelas', ':', $kelas->nama_kelas]);
            fputcsv($file, ['Periode', ':', $periodeLabel]);
            fputcsv($file, ['Tanggal Cetak', ':', now()->translatedFormat('d F Y')]);
            fputcsv($file, []);

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
        $data = $this->getRekapDataForExport($request);

        if (!$data['selectedKelas']) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        $selectedKelas = $data['selectedKelas'];
        $rekapData = $data['rekapData'];
        $jenisSholat = $data['jenisSholat'];
        $periodeLabel = $data['periodeLabel'];

        return view('exports.rekap-presensi-sholat-pdf', compact('selectedKelas', 'jenisSholat', 'rekapData', 'periodeLabel'));
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
