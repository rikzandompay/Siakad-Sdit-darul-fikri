<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Support\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class PresensiController extends Controller
{
    protected function ensureTeacherCanAccessKelas(int $kelasId): void
    {
        abort_unless(Auth::user()->mengampuKelas($kelasId), 403, 'Anda tidak memiliki akses ke kelas ini.');
    }

    /** Mengisi presensi hanya boleh dilakukan wali kelas dari kelas tersebut (bukan admin, bukan guru pengajar mapel). */
    protected function ensureWaliKelas(int $kelasId): void
    {
        abort_unless(Auth::user()->mengampuKelas($kelasId, hanyaWali: true), 403, 'Presensi hanya dapat diisi oleh wali kelas.');
    }

    /**
     * Daftar kelas untuk presensi
     */
    public function index()
    {
        $guruId = Auth::id();
        $kelasList = Kelas::withCount('siswaAktif')
            ->with('waliKelas')
            ->where('wali_kelas_id', $guruId)
            ->orderBy('nama_kelas')
            ->get();

        $kelasIdsGuru = $kelasList->pluck('id')->all();
        $todayStr = Sekolah::now()->toDateString();
        $guruJadwalIds = JadwalPelajaran::whereIn('kelas_id', $kelasIdsGuru)->pluck('id')->all();

        $totalSiswaAktif = Cache::remember("total_siswa_aktif_guru_{$guruId}", 300, fn () => Siswa::where('status', 'Aktif')->whereIn('kelas_id', $kelasIdsGuru)->count());

        $presensiStats = Cache::remember("presensi_stats_guru_{$guruId}_{$todayStr}", 60, function () use ($todayStr, $guruJadwalIds) {
            $presensiToday = Presensi::where('tanggal', $todayStr)->whereIn('jadwal_id', $guruJadwalIds)->get();

            return [
                'hadirCount' => $presensiToday->where('status_kehadiran', 'H')->count(),
                'sakitCount' => $presensiToday->where('status_kehadiran', 'S')->count(),
                'izinCount' => $presensiToday->where('status_kehadiran', 'I')->count(),
                'alpaCount' => $presensiToday->where('status_kehadiran', 'A')->count(),
                'totalPresensi' => $presensiToday->count(),
            ];
        });

        extract($presensiStats);
        $persenHadir = $totalPresensi > 0 ? round(($hadirCount / $totalPresensi) * 100, 1) : 0;

        return view('presensikelas', compact(
            'kelasList', 'totalSiswaAktif',
            'hadirCount', 'sakitCount', 'izinCount', 'alpaCount', 'persenHadir'
        ));
    }

    /**
     * Form input presensi per kelas
     */
    public function show(Kelas $kelas, Request $request)
    {
        $this->ensureWaliKelas($kelas->id);

        $tanggal = $request->get('tanggal', Sekolah::now()->toDateString());
        $jadwalId = $request->get('jadwal_id');
        $guruId = Auth::id();

        $siswa = Cache::remember("siswa_aktif_kelas_{$kelas->id}", 300, fn () => $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get());
        $jadwalList = Cache::remember("jadwal_list_wali_kelas_{$kelas->id}", 300, fn () => $kelas->jadwalPelajaran()
            ->with('mataPelajaran', 'guru')
            ->urutHari()->orderBy('jam_mulai')->get());

        if (! $jadwalId && $jadwalList->isNotEmpty()) {
            $hariNow = Sekolah::hari(Carbon::parse($tanggal, Sekolah::timezone()));
            $jadwalId = ($jadwalList->firstWhere('hari', $hariNow) ?? $jadwalList->first())->id;
        }

        $existingPresensi = $jadwalId
            ? Presensi::where('jadwal_id', $jadwalId)->where('tanggal', $tanggal)->pluck('status_kehadiran', 'siswa_id')->toArray()
            : [];

        $hitung = array_count_values($existingPresensi);
        $hadirCount = $hitung['H'] ?? 0;
        $sakitCount = $hitung['S'] ?? 0;
        $izinCount = $hitung['I'] ?? 0;
        $alpaCount = $hitung['A'] ?? 0;

        $mapelList = $jadwalList->pluck('mataPelajaran')->unique('id')->values();
        // Hanya kelas perwalian agar dropdown tidak mengarah ke kelas yang memicu 403.
        $kelasList = Kelas::where('wali_kelas_id', $guruId)->orderBy('nama_kelas')->get();

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
        $jadwal = JadwalPelajaran::findOrFail($request->input('jadwal_id'));
        $this->ensureWaliKelas($jadwal->kelas_id);

        $validated = $request->validate([
            'jadwal_id' => 'required|exists:jadwal_pelajaran,id',
            'tanggal' => 'required|date',
            'presensi' => 'required|array',
            'presensi.*.siswa_id' => ['required', Rule::exists('siswa', 'id')->where('kelas_id', $jadwal->kelas_id)],
            'presensi.*.status' => 'required|in:H,I,S,A',
            'presensi.*.keterangan' => 'nullable|string|max:255',
        ]);

        $tanggalObj = Carbon::parse($validated['tanggal'], Sekolah::timezone());

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

        Cache::forget('presensi_stats_guru_'.Auth::id().'_'.$tanggalObj->toDateString());

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
        [$data, $dateRange, $rentangLabel] = $this->dataEksporKelas($kelas, $request);
        $filename = 'presensi_'.str_replace(' ', '_', $kelas->nama_kelas).'_'.Sekolah::now()->format('Ymd').'.csv';

        return $this->streamCsv($filename, function ($file) use ($data, $kelas, $dateRange, $rentangLabel) {
            $this->putKopCsv($file, 'LAPORAN KEHADIRAN SISWA', [
                ['Kelas', ':', $kelas->nama_kelas],
                ['Periode', ':', $rentangLabel.' ('.$dateRange['start']->format('d/m/Y').' - '.$dateRange['end']->format('d/m/Y').')'],
                ['Tanggal Cetak', ':', Sekolah::now()->translatedFormat('d F Y')],
            ]);

            fputcsv($file, ['No', 'Tanggal', 'NIS', 'Nama Siswa', 'Mata Pelajaran', 'Status', 'Keterangan']);
            foreach ($data as $i => $p) {
                $this->putSafeCsv($file, [
                    $i + 1,
                    $p->tanggal->format('d/m/Y'),
                    $p->siswa->nis ?? '-',
                    $p->siswa->nama_siswa ?? '-',
                    $p->jadwal->mataPelajaran->nama_pelajaran ?? '-',
                    $p->status_label,
                    $p->keterangan ?? '-',
                ]);
            }
        });
    }

    /**
     * Export presensi as PDF
     */
    public function exportPdf(Kelas $kelas, Request $request)
    {
        [$data, $dateRange, $rentangLabel] = $this->dataEksporKelas($kelas, $request);

        $grouped = $data->groupBy('siswa_id');
        $siswaList = $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $summary = [];
        foreach ($siswaList as $s) {
            $presensiSiswa = $grouped->get($s->id, collect());
            $summary[$s->id] = [
                'siswa' => $s,
                'hadir' => $presensiSiswa->where('status_kehadiran', 'H')->count(),
                'sakit' => $presensiSiswa->where('status_kehadiran', 'S')->count(),
                'izin' => $presensiSiswa->where('status_kehadiran', 'I')->count(),
                'alpa' => $presensiSiswa->where('status_kehadiran', 'A')->count(),
            ];
        }

        return view('exports.presensi-pdf', compact('kelas', 'data', 'summary', 'dateRange', 'rentangLabel'));
    }

    public function rekap(Request $request)
    {
        $guruId = Auth::id();
        $selectedKelasId = $request->get('kelas_id');
        $selectedPelajaranId = $request->get('pelajaran_id');
        $tanggal = $request->get('tanggal', Sekolah::now()->toDateString());
        $rentang = $request->get('rentang', 'bulan_ini');

        $kelasIdsGuru = Cache::remember("rekap_kelas_wali_ids_guru_{$guruId}", 300, fn () => Auth::user()->kelasDiampuIds(hanyaWali: true));
        abort_if($selectedKelasId && ! in_array((int) $selectedKelasId, $kelasIdsGuru, true), 403, 'Rekap presensi hanya untuk wali kelas ini.');

        $kelasList = Cache::remember("rekap_kelas_wali_list_guru_{$guruId}", 300, fn () => Kelas::with('waliKelas')->whereIn('id', $kelasIdsGuru)->orderBy('nama_kelas')->get());

        $mapelIdsGuru = JadwalPelajaran::whereIn('kelas_id', $kelasIdsGuru)
            ->when($selectedKelasId, fn ($q) => $q->where('kelas_id', $selectedKelasId))
            ->pluck('pelajaran_id')->unique();
        $mapelList = MataPelajaran::whereIn('id', $mapelIdsGuru)->orderBy('nama_pelajaran')->get();

        $periodeLabel = $this->periodeLabel($rentang, $tanggal);
        $rekapData = [];
        $selectedKelas = null;

        if ($selectedKelasId) {
            $hasil = $this->bangunRekap((int) $selectedKelasId, $selectedPelajaranId ? (int) $selectedPelajaranId : null, $rentang, $tanggal);
            $selectedKelas = $hasil['selectedKelas'];
            $rekapData = $hasil['rekapData'];
        }

        return view('rekap-presensi', compact('kelasList', 'mapelList', 'selectedKelas', 'selectedPelajaranId', 'rekapData', 'tanggal', 'rentang', 'periodeLabel'));
    }

    public function exportRekapCsv(Request $request)
    {
        $data = $this->getRekapDataForExport($request);
        if (! $data['selectedKelas']) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        $kelas = $data['selectedKelas'];
        $selectedPelajaran = $data['selectedPelajaran'];
        $namaPelajaran = $selectedPelajaran ? '_'.str_replace(' ', '_', $selectedPelajaran->nama_pelajaran) : '';
        $filename = 'rekap_presensi_'.str_replace(' ', '_', $kelas->nama_kelas).$namaPelajaran.'_'.Sekolah::now()->format('Ymd').'.csv';

        return $this->streamCsv($filename, function ($file) use ($data, $kelas, $selectedPelajaran) {
            $this->putKopCsv($file, 'REKAPITULASI PRESENSI SISWA', [
                ['Kelas', ':', $kelas->nama_kelas],
                ['Mata Pelajaran', ':', $selectedPelajaran ? $selectedPelajaran->nama_pelajaran : 'Semua Mata Pelajaran'],
                ['Periode', ':', $data['periodeLabel']],
                ['Tanggal Cetak', ':', Sekolah::now()->translatedFormat('d F Y')],
            ]);

            fputcsv($file, ['No', 'NIS', 'Nama Siswa', 'Hadir (H)', 'Sakit (S)', 'Izin (I)', 'Alpa (A)', 'Total']);
            $no = 1;
            foreach ($data['rekapData'] as $row) {
                $s = $row['summary'];
                $this->putSafeCsv($file, [
                    $no++,
                    $row['siswa']->nis ?? '-',
                    $row['siswa']->nama_siswa ?? '-',
                    $s['H'], $s['S'], $s['I'], $s['A'],
                    $s['H'] + $s['S'] + $s['I'] + $s['A'],
                ]);
            }
        });
    }

    public function exportRekapPdf(Request $request)
    {
        $data = $this->getRekapDataForExport($request);
        if (! $data['selectedKelas']) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        return view('exports.rekap-presensi-pdf', $data);
    }

    /** @return array{0: Collection, 1: array{start: Carbon, end: Carbon}, 2: string} */
    private function dataEksporKelas(Kelas $kelas, Request $request): array
    {
        $this->ensureTeacherCanAccessKelas($kelas->id);

        $rentang = $request->get('rentang', 'hari_ini');
        $dateRange = $this->periodeRange($rentang, $request->get('tanggal'));
        $data = Presensi::whereHas('jadwal', fn ($q) => $q->where('kelas_id', $kelas->id))
            ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
            ->when($request->get('jadwal_id'), fn ($q, $id) => $q->where('jadwal_id', $id))
            ->with(['siswa', 'jadwal.mataPelajaran'])
            ->orderBy('tanggal')
            ->get();

        $rentangLabel = match ($rentang) {
            'minggu_ini' => 'Minggu Ini',
            'bulan_ini' => 'Bulan Ini',
            'semester_ini' => 'Semester Ini',
            default => 'Hari Ini',
        };

        return [$data, $dateRange, $rentangLabel];
    }

    private function getRekapDataForExport(Request $request): array
    {
        $selectedKelasId = $request->get('kelas_id');
        if (! $selectedKelasId) {
            return ['selectedKelas' => null, 'rekapData' => [], 'periodeLabel' => '', 'selectedPelajaran' => null];
        }

        abort_unless(Auth::user()->mengampuKelas((int) $selectedKelasId, hanyaWali: true), 403, 'Rekap presensi hanya untuk wali kelas ini.');

        $selectedPelajaranId = $request->get('pelajaran_id');
        $tanggal = $request->get('tanggal', Sekolah::now()->toDateString());
        $rentang = $request->get('rentang', 'bulan_ini');

        return $this->bangunRekap((int) $selectedKelasId, $selectedPelajaranId ? (int) $selectedPelajaranId : null, $rentang, $tanggal) + [
            'selectedPelajaran' => $selectedPelajaranId ? MataPelajaran::find($selectedPelajaranId) : null,
        ];
    }

    private function bangunRekap(int $kelasId, ?int $pelajaranId, string $rentang, string $tanggal): array
    {
        $selectedKelas = Kelas::findOrFail($kelasId);
        $siswaList = $selectedKelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $dateRange = $this->periodeRange($rentang, $tanggal);

        $grouped = Presensi::whereHas('jadwal', function ($q) use ($kelasId, $pelajaranId) {
            $q->where('kelas_id', $kelasId);
            if ($pelajaranId) {
                $q->where('pelajaran_id', $pelajaranId);
            }
        })
            ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
            ->get()
            ->groupBy('siswa_id');

        $rekapData = [];
        foreach ($siswaList as $siswa) {
            $list = $grouped->get($siswa->id, collect());
            $rekapData[$siswa->id] = [
                'siswa' => $siswa,
                'summary' => [
                    'H' => $list->where('status_kehadiran', 'H')->count(),
                    'S' => $list->where('status_kehadiran', 'S')->count(),
                    'I' => $list->where('status_kehadiran', 'I')->count(),
                    'A' => $list->where('status_kehadiran', 'A')->count(),
                ],
            ];
        }

        return [
            'selectedKelas' => $selectedKelas,
            'rekapData' => $rekapData,
            'periodeLabel' => $this->periodeLabel($rentang, $tanggal),
        ];
    }
}
