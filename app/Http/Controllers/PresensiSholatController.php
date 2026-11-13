<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\PresensiSholat;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Presensi sholat Dhuha (kelas 1-6) & Dzuhur (kelas 3-6), status Hadir / Tidak Hadir.
 * Di aplikasi MyDarfik status Hadir berasal dari scan QR Kartu Pelajar;
 * halaman web ini dipakai wali kelas/admin untuk melihat dan mengoreksi.
 */
class PresensiSholatController extends Controller
{
    /** Mengisi presensi sholat hanya wali kelas dari kelas tersebut. Admin tidak ikut mengisi. */
    protected function ensureWaliKelasSaja(int $kelasId): void
    {
        abort_unless(Kelas::where('id', $kelasId)->where('wali_kelas_id', Auth::id())->exists(), 403, 'Presensi sholat hanya dapat diisi oleh wali kelas.');
    }

    protected function ensureTeacherIsWaliKelas(int $kelasId): void
    {
        abort_unless(Auth::user()->mengampuKelas($kelasId, hanyaWali: true), 403, 'Anda tidak memiliki akses ke kelas ini.');
    }

    /**
     * Kelas yang boleh dikelola: hanya kelas yang diwalikan.
     */
    protected function kelasSholat(): Collection
    {
        $user = Auth::user();

        return Cache::remember('sholat:kelas:guru:'.$user->id, 300, function () use ($user) {
            return Kelas::withCount('siswaAktif')
                ->with('waliKelas')
                ->where('wali_kelas_id', $user->id)
                ->orderBy('nama_kelas')
                ->get();
        });
    }

    protected function todaySekolah(): string
    {
        return Carbon::now(config('sholat.timezone'))->toDateString();
    }

    public function index(Request $request)
    {
        $jenisSholat = PresensiSholat::normalizeJenis($request->get('jenis'));
        $today = $this->todaySekolah();
        // Halaman input hanya menampilkan kelas yang diwalikan dan mengikuti jenis sholat tersebut.
        $semuaKelasWali = $this->kelasSholat()->where('wali_kelas_id', Auth::id())->values();
        $punyaKelasWali = $semuaKelasWali->isNotEmpty();
        $kelasList = $semuaKelasWali->filter->ikutSholat($jenisSholat)->values();
        $kelasIds = $kelasList->pluck('id');
        $totalSiswa = (int) $kelasList->sum('siswa_aktif_count');

        $stats = $kelasIds->isEmpty()
            ? ['hadir' => 0, 'tidakHadir' => 0]
            : Cache::remember(
                CacheService::keyPresensiSholatHariIni(Auth::id(), $jenisSholat),
                120,
                function () use ($today, $jenisSholat, $kelasIds) {
                    $counts = PresensiSholat::where('tanggal', $today)
                        ->where('jenis_sholat', $jenisSholat)
                        ->whereIn('kelas_id', $kelasIds)
                        ->selectRaw('status_kehadiran, COUNT(*) as jumlah')
                        ->groupBy('status_kehadiran')
                        ->pluck('jumlah', 'status_kehadiran');

                    return [
                        'hadir' => (int) ($counts[PresensiSholat::HADIR] ?? 0),
                        'tidakHadir' => (int) ($counts[PresensiSholat::TIDAK_HADIR] ?? 0),
                    ];
                }
            );

        $hadirCount = $stats['hadir'];
        $tidakHadirCount = $stats['tidakHadir'];
        $belumCount = max(0, $totalSiswa - $hadirCount - $tidakHadirCount);
        $persenHadir = $totalSiswa > 0 ? round(($hadirCount / $totalSiswa) * 100, 1) : 0;
        $waktuSholat = config('sholat.waktu.'.$jenisSholat);
        $batasTidakHadir = config('sholat.batas_tidak_hadir.'.$jenisSholat);

        return view('presensi-sholat-kelas', compact(
            'kelasList', 'punyaKelasWali', 'jenisSholat', 'hadirCount', 'tidakHadirCount', 'belumCount',
            'persenHadir', 'waktuSholat', 'batasTidakHadir'
        ));
    }

    public function show(Kelas $kelas, Request $request)
    {
        $this->ensureWaliKelasSaja($kelas->id);
        $request->validate(['tanggal' => 'nullable|date']);

        $jenisSholat = PresensiSholat::normalizeJenis($request->get('jenis'));
        $tanggal = $request->filled('tanggal')
            ? Carbon::parse($request->get('tanggal'))->toDateString()
            : $this->todaySekolah();

        $ikutSholat = $kelas->ikutSholat($jenisSholat);
        $siswa = $ikutSholat
            ? $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get()
            : collect();

        $existingPresensi = $ikutSholat
            ? PresensiSholat::where('kelas_id', $kelas->id)
                ->where('tanggal', $tanggal)
                ->where('jenis_sholat', $jenisSholat)
                ->get(['siswa_id', 'status_kehadiran', 'keterangan', 'recorded_by'])
                ->keyBy('siswa_id')
            : collect();

        $hadirCount = $existingPresensi->where('status_kehadiran', PresensiSholat::HADIR)->count();
        $tidakHadirCount = $existingPresensi->where('status_kehadiran', PresensiSholat::TIDAK_HADIR)->count();
        $belumCount = max(0, $siswa->count() - $hadirCount - $tidakHadirCount);

        $kelasList = $this->kelasSholat()->filter->ikutSholat($jenisSholat)->values();

        return view('presensi-sholat-siswa', compact(
            'kelas', 'siswa', 'jenisSholat', 'tanggal', 'kelasList', 'ikutSholat',
            'existingPresensi', 'hadirCount', 'tidakHadirCount', 'belumCount'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'jenis_sholat' => ['required', Rule::in(PresensiSholat::JENIS)],
            'tanggal' => 'required|date|before_or_equal:'.$this->todaySekolah(),
            'presensi' => 'required|array',
            'presensi.*.siswa_id' => ['required', Rule::exists('siswa', 'id')->where('kelas_id', $request->input('kelas_id'))],
            'presensi.*.status' => ['nullable', Rule::in(PresensiSholat::STATUS)],
            'presensi.*.keterangan' => 'nullable|string|max:255',
        ]);

        $this->ensureWaliKelasSaja($validated['kelas_id']);

        $kelas = Kelas::findOrFail($validated['kelas_id']);
        if (! $kelas->ikutSholat($validated['jenis_sholat'])) {
            throw ValidationException::withMessages([
                'jenis_sholat' => 'Kelas 1 dan 2 tidak mengikuti presensi sholat Dzuhur.',
            ]);
        }

        $now = now();
        $tanggal = Carbon::parse($validated['tanggal'])->toDateString();

        // Siswa yang tidak dipilih statusnya dibiarkan "belum tercatat".
        $records = collect($validated['presensi'])
            ->filter(fn ($data) => ! empty($data['status']))
            ->map(fn ($data) => [
                'siswa_id' => $data['siswa_id'],
                'tanggal' => $tanggal,
                'jenis_sholat' => $validated['jenis_sholat'],
                'kelas_id' => $validated['kelas_id'],
                'status_kehadiran' => $data['status'],
                'keterangan' => $data['keterangan'] ?? null,
                'recorded_by' => Auth::id(),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if (empty($records)) {
            return back()->with('error', 'Pilih status minimal satu siswa sebelum menyimpan.');
        }

        PresensiSholat::upsert(
            $records,
            ['siswa_id', 'tanggal', 'jenis_sholat'],
            ['kelas_id', 'status_kehadiran', 'keterangan', 'recorded_by', 'updated_at']
        );

        CacheService::clearPresensiSholat(Auth::id(), $validated['jenis_sholat']);

        return redirect()->route('presensi-sholat.show', [
            'kelas' => $validated['kelas_id'],
            'jenis' => $validated['jenis_sholat'],
            'tanggal' => $tanggal,
        ])->with('success', 'Presensi Sholat '.$validated['jenis_sholat'].' berhasil disimpan!');
    }

    public function rekap(Request $request)
    {
        $jenisSholat = PresensiSholat::normalizeJenis($request->get('jenis'), termasukArsip: true);
        $tanggal = $request->get('tanggal', $this->todaySekolah());
        $rentang = $request->get('rentang', 'bulan_ini');
        $kelasList = $this->kelasSholat()->filter->ikutSholat($jenisSholat)->values();

        $periodeLabel = $this->periodeLabel($rentang, $tanggal);
        $selectedKelas = null;
        $rekapData = [];

        if ($selectedKelasId = $request->get('kelas_id')) {
            $this->ensureTeacherIsWaliKelas((int) $selectedKelasId);
            $selectedKelas = Kelas::findOrFail($selectedKelasId);
            $rekapData = $this->buildRekap($selectedKelas, $jenisSholat, $this->periodeRange($rentang, $tanggal));
        }

        return view('rekap-presensi-sholat', compact('kelasList', 'selectedKelas', 'rekapData', 'tanggal', 'rentang', 'periodeLabel', 'jenisSholat'));
    }

    /**
     * Ringkasan Hadir / Tidak Hadir per siswa aktif di kelas.
     */
    private function buildRekap(Kelas $kelas, string $jenisSholat, array $dateRange): array
    {
        $siswaList = $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();

        $summaryBySiswa = PresensiSholat::where('kelas_id', $kelas->id)
            ->where('jenis_sholat', $jenisSholat)
            ->whereBetween('tanggal', [$dateRange['start']->toDateString(), $dateRange['end']->toDateString()])
            ->selectRaw('siswa_id,
                SUM(CASE WHEN status_kehadiran = ? THEN 1 ELSE 0 END) as hadir_count,
                SUM(CASE WHEN status_kehadiran = ? THEN 1 ELSE 0 END) as tidak_hadir_count',
                [PresensiSholat::HADIR, PresensiSholat::TIDAK_HADIR])
            ->groupBy('siswa_id')
            ->get()
            ->keyBy('siswa_id');

        $rekapData = [];
        foreach ($siswaList as $siswa) {
            $summary = $summaryBySiswa->get($siswa->id);
            $hadir = (int) ($summary->hadir_count ?? 0);
            $tidakHadir = (int) ($summary->tidak_hadir_count ?? 0);
            $total = $hadir + $tidakHadir;

            $rekapData[$siswa->id] = [
                'siswa' => $siswa,
                'summary' => [
                    'hadir' => $hadir,
                    'tidak_hadir' => $tidakHadir,
                    'total' => $total,
                    'persen' => $total > 0 ? round($hadir / $total * 100, 1) : 0,
                ],
            ];
        }

        return $rekapData;
    }

    private function getRekapDataForExport(Request $request): array
    {
        $jenisSholat = PresensiSholat::normalizeJenis($request->get('jenis'), termasukArsip: true);
        $tanggal = $request->get('tanggal', $this->todaySekolah());
        $rentang = $request->get('rentang', 'bulan_ini');
        $selectedKelasId = $request->get('kelas_id');

        if (! $selectedKelasId) {
            return ['selectedKelas' => null, 'rekapData' => [], 'jenisSholat' => $jenisSholat, 'periodeLabel' => ''];
        }

        $this->ensureTeacherIsWaliKelas((int) $selectedKelasId);
        $selectedKelas = Kelas::findOrFail($selectedKelasId);

        return [
            'selectedKelas' => $selectedKelas,
            'rekapData' => $this->buildRekap($selectedKelas, $jenisSholat, $this->periodeRange($rentang, $tanggal)),
            'jenisSholat' => $jenisSholat,
            'periodeLabel' => $this->periodeLabel($rentang, $tanggal),
        ];
    }

    public function exportRekapCsv(Request $request)
    {
        $data = $this->getRekapDataForExport($request);

        if (! $data['selectedKelas']) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        $kelas = $data['selectedKelas'];
        $rekapData = $data['rekapData'];
        $jenisSholat = $data['jenisSholat'];
        $periodeLabel = $data['periodeLabel'];

        $filename = 'rekap_presensi_sholat_'.$jenisSholat.'_'.str_replace(' ', '_', $kelas->nama_kelas).'_'.now()->format('Ymd').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rekapData, $kelas, $jenisSholat, $periodeLabel) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM

            fputcsv($file, ['YAYASAN PENDIDIKAN DARUL FIKRI']);
            fputcsv($file, ['SD IT DARUL FIKRI']);
            fputcsv($file, ['REKAPITULASI PRESENSI SHOLAT '.strtoupper($jenisSholat)]);
            fputcsv($file, []);
            $this->putSafeCsv($file, ['Kelas', ':', $kelas->nama_kelas]);
            fputcsv($file, ['Periode', ':', $periodeLabel]);
            fputcsv($file, ['Tanggal Cetak', ':', now()->translatedFormat('d F Y')]);
            fputcsv($file, []);

            fputcsv($file, ['No', 'NIS', 'Nama Siswa', 'Hadir', 'Tidak Hadir', 'Total', '% Hadir']);
            $no = 1;
            foreach ($rekapData as $row) {
                $this->putSafeCsv($file, [
                    $no++,
                    $row['siswa']->nis ?? '-',
                    $row['siswa']->nama_siswa ?? '-',
                    $row['summary']['hadir'],
                    $row['summary']['tidak_hadir'],
                    $row['summary']['total'],
                    $row['summary']['persen'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportRekapPdf(Request $request)
    {
        $data = $this->getRekapDataForExport($request);

        if (! $data['selectedKelas']) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        $selectedKelas = $data['selectedKelas'];
        $rekapData = $data['rekapData'];
        $jenisSholat = $data['jenisSholat'];
        $periodeLabel = $data['periodeLabel'];

        return view('exports.rekap-presensi-sholat-pdf', compact('selectedKelas', 'jenisSholat', 'rekapData', 'periodeLabel'));
    }
}
