<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiRapot;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NilaiRapotController extends Controller
{
    /**
     * Melihat/mengekspor nilai satu mapel: admin, wali kelas kelas itu (merekap rapor), atau
     * pengampu mapel itu di kelas itu. Guru mapel lain di kelas yang sama tidak boleh.
     */
    protected function ensureCanViewNilaiMapel(int $pelajaranId, int $kelasId): void
    {
        if (auth()->user()->isAdmin() || Auth::user()->mengampuKelas($kelasId, hanyaWali: true)) {
            return;
        }
        $this->ensureTeacherCanAccessPelajaran($pelajaranId, $kelasId);
    }

    protected function ensureTeacherCanAccessPelajaran(int $pelajaranId, int $kelasId): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        $hasAccess = JadwalPelajaran::where('guru_id', Auth::id())
            ->where('kelas_id', $kelasId)
            ->where('pelajaran_id', $pelajaranId)
            ->exists();

        abort_unless($hasAccess, 403, 'Anda tidak memiliki akses untuk menginput nilai di kelas dan mata pelajaran ini.');
    }

    public function index(Request $request)
    {
        $guruId = Auth::id();
        $tahunAjaranList = Cache::remember('tahun_ajaran_list', 300, fn () => TahunAjaran::orderByDesc('id')->get());
        $tahunAjaranId = $request->get('tahun_ajaran_id', TahunAjaran::getAktif()?->id);
        $kelasId = $request->get('kelas_id');
        $pelajaranId = $request->get('pelajaran_id');

        // Hanya kelas & mapel yang diampu guru ini
        $kelasIdsGuru = Cache::remember("kelas_ids_guru_{$guruId}", 300, fn () => JadwalPelajaran::where('guru_id', $guruId)->pluck('kelas_id')->unique()->toArray());
        $kelasList = Cache::remember("kelas_list_guru_{$guruId}", 300, fn () => Kelas::whereIn('id', $kelasIdsGuru)->orderBy('nama_kelas')->get());

        $mapelList = Cache::remember("mapel_list_guru_{$guruId}_kelas_".($kelasId ?? 'all'), 300, function () use ($guruId, $kelasId) {
            $mapelIdsGuru = JadwalPelajaran::where('guru_id', $guruId)
                ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))
                ->pluck('pelajaran_id')->unique();

            return MataPelajaran::whereIn('id', $mapelIdsGuru)->orderBy('nama_pelajaran')->get();
        });

        $siswaList = collect();
        $nilaiMap = [];
        $stats = ['rata_rata' => 0, 'nilai_tertinggi' => 0, 'perlu_remedial' => 0, 'tuntas' => 0];

        if ($kelasId && $pelajaranId && $tahunAjaranId) {
            [$siswaList, $existingNilai] = $this->dataNilaiMapel((int) $kelasId, (int) $pelajaranId, (int) $tahunAjaranId);
            $nilaiMap = $existingNilai->toArray();

            if ($existingNilai->isNotEmpty()) {
                $nilaiRaporValues = $existingNilai->pluck('nilai_rapor');
                $stats['rata_rata'] = round($nilaiRaporValues->avg(), 1);
                $stats['nilai_tertinggi'] = $nilaiRaporValues->max();
                $stats['perlu_remedial'] = $nilaiRaporValues->filter(fn ($v) => $v < 75)->count();
                $stats['tuntas'] = $nilaiRaporValues->filter(fn ($v) => $v >= 75)->count();
            }
        }

        $selectedMapel = $pelajaranId ? MataPelajaran::find($pelajaranId) : null;
        $selectedKelas = $kelasId ? Kelas::find($kelasId) : null;

        return view('inputnilai', compact(
            'tahunAjaranList', 'kelasList', 'mapelList',
            'tahunAjaranId', 'kelasId', 'pelajaranId',
            'siswaList', 'nilaiMap', 'stats',
            'selectedMapel', 'selectedKelas'
        ));
    }

    public function rekap(Request $request)
    {
        $user = Auth::user();
        $tahunAjaranList = Cache::remember('tahun_ajaran_list', 300, fn () => TahunAjaran::orderByDesc('id')->get());
        $kelasList = Kelas::where('wali_kelas_id', $user->id)->orderBy('nama_kelas')->get();

        $tahunAjaranId = $request->get('tahun_ajaran_id', TahunAjaran::getAktif()?->id);
        $kelasId = $request->get('kelas_id');

        if ($kelasId) {
            abort_unless($user->mengampuKelas((int) $kelasId, hanyaWali: true), 403, 'Anda tidak memiliki akses ke kelas ini.');
        }

        $rekap = ($kelasId && $tahunAjaranId)
            ? $this->dataRekap((int) $kelasId, (int) $tahunAjaranId)
            : [
                'siswaList' => collect(), 'mapelList' => collect(), 'nilaiMap' => [], 'rataRataSiswa' => [],
                'selectedKelas' => $kelasId ? Kelas::find($kelasId) : null,
                'tahunAjaranAktif' => $tahunAjaranId ? TahunAjaran::find($tahunAjaranId) : null,
            ];

        return view('rekapnilai', compact('tahunAjaranList', 'kelasList', 'tahunAjaranId', 'kelasId') + $rekap);
    }

    /**
     * Batch Update - Store all nilai in one request
     */
    public function store(Request $request)
    {
        $rules = [
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'nilai' => 'required|array',
            'nilai.*.siswa_id' => 'required|exists:siswa,id',
            'nilai.*.formatif' => 'required|array',
            'nilai.*.sas' => 'required|numeric|min:0|max:100',
            'nilai.*.kehadiran' => 'required|numeric|min:0|max:20',
            'nilai.*.pengurang_tidaktelat' => 'nullable|numeric|min:0|max:100',
            'nilai.*.pengurang_menyontek' => 'nullable|numeric|min:0|max:100',
        ];
        for ($i = 1; $i <= 4; $i++) {
            $rules["nilai.*.formatif.bab{$i}"] = 'required|array';
            foreach (['tp1', 'tp2', 'tp3', 'tp4', 'uh'] as $komp) {
                $rules["nilai.*.formatif.bab{$i}.{$komp}"] = 'required|numeric|min:0|max:100';
            }
        }
        $validated = $request->validate($rules);

        $siswaIds = array_values(array_unique(array_column($validated['nilai'], 'siswa_id')));
        $kelasIds = Siswa::whereIn('id', $siswaIds)->pluck('kelas_id')->unique();
        if ($kelasIds->count() !== 1 || ! $kelasIds->first() || Siswa::whereIn('id', $siswaIds)->count() !== count($siswaIds)) {
            abort(403, 'Data siswa tidak valid atau lintas kelas.');
        }
        $firstKelasId = (int) $kelasIds->first();
        $this->ensureTeacherCanAccessPelajaran($validated['pelajaran_id'], $firstKelasId);

        foreach ($validated['nilai'] as $data) {
            $pengurangTidaktelat = $data['pengurang_tidaktelat'] ?? 0;
            $pengurangMenyontek = $data['pengurang_menyontek'] ?? 0;

            NilaiRapot::updateOrCreate(
                [
                    'tahun_ajaran_id' => $validated['tahun_ajaran_id'],
                    'siswa_id' => $data['siswa_id'],
                    'pelajaran_id' => $validated['pelajaran_id'],
                ],
                [
                    'formatif_data' => $data['formatif'],
                    'sas' => $data['sas'],
                    'kehadiran' => $data['kehadiran'],
                    'pengurang_tidaktelat' => $pengurangTidaktelat,
                    'pengurang_menyontek' => $pengurangMenyontek,
                    'nilai_rapor' => NilaiRapot::calculateNilaiRapor($data['formatif'], $data['sas'], $data['kehadiran'], $pengurangTidaktelat, $pengurangMenyontek),
                ]
            );
        }

        return redirect()->route('nilai.index', [
            'tahun_ajaran_id' => $validated['tahun_ajaran_id'],
            'pelajaran_id' => $validated['pelajaran_id'],
            'kelas_id' => $firstKelasId,
        ])->with('success', 'Rekap nilai berhasil disimpan!');
    }

    /**
     * Export nilai as CSV
     */
    public function exportCsv(Request $request)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id');
        $kelasId = $request->get('kelas_id');
        $pelajaranId = $request->get('pelajaran_id');

        if (! $kelasId || ! $pelajaranId || ! $tahunAjaranId) {
            return redirect()->route('nilai.index')->with('error', 'Pilih filter terlebih dahulu.');
        }

        [$siswaList, $nilaiData, $kelas, $mapel, $tahunAjaran] = $this->dataNilaiMapel((int) $kelasId, (int) $pelajaranId, (int) $tahunAjaranId);
        $filename = 'nilai_'.str_replace(' ', '_', $kelas->nama_kelas ?? '').'_'.($mapel->kode_pelajaran ?? '').'_'.Sekolah::now()->format('Ymd').'.csv';

        return $this->streamCsv($filename, function ($file) use ($siswaList, $nilaiData, $kelas, $mapel, $tahunAjaran) {
            $this->putKopCsv($file, 'LAPORAN NILAI RAPOT', [
                ['Kelas', ':', $kelas->nama_kelas ?? '-'],
                ['Mata Pelajaran', ':', $mapel->nama_pelajaran ?? '-'],
                ['Tahun Ajaran', ':', $tahunAjaran->nama_semester ?? '-'],
                ['Tanggal Cetak', ':', Sekolah::now()->translatedFormat('d F Y')],
            ]);

            $headerRow = ['No', 'NIS', 'Nama Siswa'];
            for ($i = 1; $i <= 4; $i++) {
                array_push($headerRow, "BAB{$i} TP1", "BAB{$i} TP2", "BAB{$i} TP3", "BAB{$i} TP4", "BAB{$i} UH", "BAB{$i} Rata-rata");
            }
            array_push($headerRow, 'Formatif Total (50%)', 'SAS (30%)', 'Kehadiran', 'Pengurang', 'Nilai Rapor', 'Predikat');
            fputcsv($file, $headerRow);

            foreach ($siswaList as $idx => $s) {
                $n = $nilaiData[$s->id] ?? null;
                $row = [$idx + 1, $s->nis, $s->nama_siswa];

                $formatifData = $n ? $n->formatif_data : NilaiRapot::getDefaultFormatifData();
                if (is_string($formatifData)) {
                    $formatifData = json_decode($formatifData, true);
                }
                $formatifData = $formatifData ?: NilaiRapot::getDefaultFormatifData();
                $babAverages = $n ? $n->getBabAverages() : [];

                for ($i = 1; $i <= 4; $i++) {
                    $babKey = "bab{$i}";
                    $babData = $formatifData[$babKey] ?? [];
                    array_push(
                        $row,
                        $babData['tp1'] ?? 0, $babData['tp2'] ?? 0, $babData['tp3'] ?? 0,
                        $babData['tp4'] ?? 0, $babData['uh'] ?? 0, $babAverages[$babKey] ?? 0
                    );
                }

                array_push(
                    $row,
                    $n ? $n->getFormatifTotal() : 0,
                    $n ? $n->sas : 0,
                    $n ? $n->kehadiran : 0,
                    $n ? $n->getTotalPengurang() : 0,
                    $n ? $n->nilai_rapor : 0,
                    $n ? NilaiRapot::getPredikat($n->nilai_rapor) : 'F'
                );

                $this->putSafeCsv($file, $row);
            }
        });
    }

    /**
     * Export nilai as PDF (printable HTML)
     */
    public function exportPdf(Request $request)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id');
        $kelasId = $request->get('kelas_id');
        $pelajaranId = $request->get('pelajaran_id');

        if (! $kelasId || ! $pelajaranId || ! $tahunAjaranId) {
            return redirect()->route('nilai.index')->with('error', 'Pilih filter terlebih dahulu.');
        }

        [$siswaList, $nilaiData, $kelas, $mapel, $tahunAjaran] = $this->dataNilaiMapel((int) $kelasId, (int) $pelajaranId, (int) $tahunAjaranId);

        return view('exports.nilai-pdf', compact('siswaList', 'nilaiData', 'mapel', 'kelas', 'tahunAjaran'));
    }

    public function exportRekapCsv(Request $request)
    {
        $rekap = $this->rekapUntukEkspor($request);
        if (! $rekap) {
            return redirect()->route('nilai.rekap')->with('error', 'Pilih filter terlebih dahulu.');
        }

        $kelas = $rekap['selectedKelas'];
        $filename = 'rekap_nilai_'.str_replace(' ', '_', $kelas->nama_kelas ?? '').'_'.Sekolah::now()->format('Ymd').'.csv';

        return $this->streamCsv($filename, function ($file) use ($rekap, $kelas) {
            $this->putKopCsv($file, 'REKAPITULASI NILAI SISWA', [
                ['Kelas', ':', $kelas->nama_kelas ?? '-'],
                ['Tahun Ajaran', ':', $rekap['tahunAjaranAktif']->nama_semester ?? '-'],
                ['Tanggal Cetak', ':', Sekolah::now()->translatedFormat('d F Y')],
            ]);

            $headerRow = ['No', 'NIS', 'Nama Siswa', ...$rekap['mapelList']->pluck('nama_pelajaran')->all(), 'Rata-rata'];
            fputcsv($file, $headerRow);

            foreach ($rekap['siswaList'] as $idx => $s) {
                $row = [$idx + 1, $s->nis, $s->nama_siswa];
                foreach ($rekap['mapelList'] as $m) {
                    $nilai = $rekap['nilaiMap'][$s->id][$m->id] ?? null;
                    $row[] = $nilai !== null ? number_format($nilai, 1) : '-';
                }

                $rataRata = $rekap['rataRataSiswa'][$s->id] ?? 0;
                $row[] = $rataRata > 0 ? number_format($rataRata, 1) : '-';
                $this->putSafeCsv($file, $row);
            }
        });
    }

    public function exportRekapPdf(Request $request)
    {
        $rekap = $this->rekapUntukEkspor($request);
        if (! $rekap) {
            return redirect()->route('nilai.rekap')->with('error', 'Pilih filter terlebih dahulu.');
        }

        return view('exports.rekap-nilai-pdf', $rekap);
    }

    /** @return array{0: Collection, 1: Collection, 2: ?Kelas, 3: ?MataPelajaran, 4: ?TahunAjaran} */
    private function dataNilaiMapel(int $kelasId, int $pelajaranId, int $tahunAjaranId): array
    {
        $this->ensureCanViewNilaiMapel($pelajaranId, $kelasId);

        $siswaList = Siswa::where('kelas_id', $kelasId)->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $nilaiData = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('pelajaran_id', $pelajaranId)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->get()
            ->keyBy('siswa_id');

        return [$siswaList, $nilaiData, Kelas::find($kelasId), MataPelajaran::find($pelajaranId), TahunAjaran::find($tahunAjaranId)];
    }

    private function rekapUntukEkspor(Request $request): ?array
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id');
        $kelasId = $request->get('kelas_id');

        if ($kelasId) {
            abort_unless(Auth::user()->mengampuKelas((int) $kelasId, hanyaWali: true), 403, 'Anda tidak memiliki akses ke kelas ini.');
        }
        if (! $kelasId || ! $tahunAjaranId) {
            return null;
        }

        return $this->dataRekap((int) $kelasId, (int) $tahunAjaranId);
    }

    private function dataRekap(int $kelasId, int $tahunAjaranId): array
    {
        $siswaList = Siswa::where('kelas_id', $kelasId)->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $mapelList = MataPelajaran::orderBy('nama_pelajaran')->get();
        $existingNilai = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->get();

        $nilaiMap = [];
        foreach ($existingNilai as $nilai) {
            $nilaiMap[$nilai->siswa_id][$nilai->pelajaran_id] = $nilai->nilai_rapor;
        }

        $perSiswa = $existingNilai->groupBy('siswa_id');
        $rataRataSiswa = [];
        foreach ($siswaList as $s) {
            $avg = $perSiswa->get($s->id)?->whereNotNull('nilai_rapor')->avg('nilai_rapor');
            $rataRataSiswa[$s->id] = $avg !== null ? round((float) $avg, 1) : 0;
        }

        return [
            'siswaList' => $siswaList,
            'mapelList' => $mapelList,
            'nilaiMap' => $nilaiMap,
            'rataRataSiswa' => $rataRataSiswa,
            'selectedKelas' => Kelas::find($kelasId),
            'tahunAjaranAktif' => TahunAjaran::find($tahunAjaranId),
        ];
    }
}
