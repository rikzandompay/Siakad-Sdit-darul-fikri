<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\PresensiSholat;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PresensiSholatController extends Controller
{
    /**
     * Daftar kelas untuk presensi sholat
     */
    public function index(Request $request)
    {
        $jenisSholat = $request->get('jenis', 'Zuhur');
        $today = Carbon::today();
        $dayName = $today->translatedFormat('l');

        if ($jenisSholat === 'Zuhur') {
            // Kelas 3-6 setiap hari
            $kelasList = Kelas::withCount(['siswa' => fn($q) => $q->where('status', 'Aktif')])
                ->with('waliKelas')
                ->where(function ($q) {
                    $q->where('nama_kelas', 'like', '%3%')
                      ->orWhere('nama_kelas', 'like', '%4%')
                      ->orWhere('nama_kelas', 'like', '%5%')
                      ->orWhere('nama_kelas', 'like', '%6%');
                })
                ->orderBy('nama_kelas')->get();
        } else {
            // Dhuha: Kelas 1-6 hari Jumat saja
            $kelasList = Kelas::withCount(['siswa' => fn($q) => $q->where('status', 'Aktif')])
                ->with('waliKelas')
                ->orderBy('nama_kelas')->get();
        }

        // Stats hari ini
        $presensiToday = PresensiSholat::where('tanggal', $today)
            ->where('jenis_sholat', $jenisSholat)->get();
        $hadirCount = $presensiToday->where('status_kehadiran', 'H')->count();
        $sakitCount = $presensiToday->where('status_kehadiran', 'S')->count();
        $izinCount = $presensiToday->where('status_kehadiran', 'I')->count();
        $alpaCount = $presensiToday->where('status_kehadiran', 'A')->count();
        $totalPresensi = $presensiToday->count();
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

        $kelasList = Kelas::orderBy('nama_kelas')->get();

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

        foreach ($validated['presensi'] as $data) {
            PresensiSholat::updateOrCreate(
                [
                    'siswa_id' => $data['siswa_id'],
                    'tanggal' => $validated['tanggal'],
                    'jenis_sholat' => $validated['jenis_sholat'],
                ],
                [
                    'kelas_id' => $validated['kelas_id'],
                    'status_kehadiran' => $data['status'],
                    'keterangan' => $data['keterangan'] ?? null,
                    'recorded_by' => Auth::id(),
                ]
            );
        }

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
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);

        if ($jenisSholat === 'Zuhur') {
            $kelasList = Kelas::where(function ($q) {
                    $q->where('nama_kelas', 'like', '%3%')
                      ->orWhere('nama_kelas', 'like', '%4%')
                      ->orWhere('nama_kelas', 'like', '%5%')
                      ->orWhere('nama_kelas', 'like', '%6%');
                })->orderBy('nama_kelas')->get();
        } else {
            $kelasList = Kelas::orderBy('nama_kelas')->get();
        }

        $rekapData = [];
        $selectedKelas = null;

        if ($selectedKelasId) {
            $selectedKelas = Kelas::find($selectedKelasId);
            $siswaList = $selectedKelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();
            
            $presensiQuery = PresensiSholat::where('kelas_id', $selectedKelasId)
                ->where('jenis_sholat', $jenisSholat)
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

        return view('rekap-presensi-sholat', compact('kelasList', 'selectedKelas', 'rekapData', 'bulan', 'tahun', 'jenisSholat'));
    }

    private function getRekapDataForExport(Request $request)
    {
        $jenisSholat = $request->get('jenis', 'Zuhur');
        $selectedKelasId = $request->get('kelas_id');
        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);

        if (!$selectedKelasId) {
            return ['selectedKelas' => null, 'rekapData' => [], 'jenisSholat' => $jenisSholat];
        }

        $selectedKelas = Kelas::find($selectedKelasId);
        $siswaList = $selectedKelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        
        $presensiQuery = PresensiSholat::where('kelas_id', $selectedKelasId)
            ->where('jenis_sholat', $jenisSholat)
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

        return ['selectedKelas' => $selectedKelas, 'rekapData' => $rekapData, 'jenisSholat' => $jenisSholat];
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
        $rekapData = $data['rekapData'];
        $jenisSholat = $data['jenisSholat'];

        $filename = 'rekap_presensi_sholat_' . $jenisSholat . '_' . str_replace(' ', '_', $kelas->nama_kelas) . '_' . $bulan . '_' . $tahun . '.csv';

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
        $rekapData = $data['rekapData'];
        $jenisSholat = $data['jenisSholat'];

        return view('exports.rekap-presensi-sholat-pdf', compact('selectedKelas', 'jenisSholat', 'rekapData', 'bulan', 'tahun'));
    }
}
