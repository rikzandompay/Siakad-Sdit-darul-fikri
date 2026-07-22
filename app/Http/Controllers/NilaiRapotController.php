<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiRapot;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NilaiRapotController extends Controller
{
    protected function ensureTeacherCanAccessKelas(int $kelasId): void
    {
        $guruId = Auth::id();
        $hasAccess = JadwalPelajaran::where('guru_id', $guruId)
            ->where('kelas_id', $kelasId)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini.');
        }
    }

    protected function ensureTeacherCanAccessPelajaran(int $pelajaranId, int $kelasId): void
    {
        $guruId = Auth::id();
        $hasAccess = JadwalPelajaran::where('guru_id', $guruId)
            ->where('kelas_id', $kelasId)
            ->where('pelajaran_id', $pelajaranId)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses untuk menginput nilai di kelas dan mata pelajaran ini.');
        }
    }

    public function index(Request $request)
    {
        $guruId = Auth::id();

        $tahunAjaranList = Cache::remember('tahun_ajaran_list', 300, function () {
            return TahunAjaran::orderByDesc('id')->get();
        });

        $tahunAjaranId = $request->get('tahun_ajaran_id', TahunAjaran::getAktif()?->id);
        $kelasId = $request->get('kelas_id');
        $pelajaranId = $request->get('pelajaran_id');

        // Hanya kelas & mapel yang diampu guru ini
        $kelasIdsGuru = Cache::remember("kelas_ids_guru_{$guruId}", 300, function () use ($guruId) {
            return JadwalPelajaran::where('guru_id', $guruId)->pluck('kelas_id')->unique()->toArray();
        });

        $kelasList = Cache::remember("kelas_list_guru_{$guruId}", 300, function () use ($kelasIdsGuru) {
            return Kelas::whereIn('id', $kelasIdsGuru)->orderBy('nama_kelas')->get();
        });

        $mapelList = Cache::remember("mapel_list_guru_{$guruId}_kelas_".($kelasId ?? 'all'), 300, function () use ($guruId, $kelasId) {
            $guruJadwalQuery = JadwalPelajaran::where('guru_id', $guruId);
            if ($kelasId) {
                $guruJadwalQuery->where('kelas_id', $kelasId);
            }
            $mapelIdsGuru = $guruJadwalQuery->pluck('pelajaran_id')->unique();

            return MataPelajaran::whereIn('id', $mapelIdsGuru)->orderBy('nama_pelajaran')->get();
        });

        $siswaList = collect();
        $nilaiMap = [];
        $stats = ['rata_rata' => 0, 'nilai_tertinggi' => 0, 'perlu_remedial' => 0, 'tuntas' => 0];

        if ($kelasId && $pelajaranId && $tahunAjaranId) {
            $this->ensureTeacherCanAccessKelas($kelasId);
            $siswaList = Siswa::where('kelas_id', $kelasId)
                ->where('status', 'Aktif')
                ->orderBy('nama_siswa')
                ->get();

            $existingNilai = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
                ->where('pelajaran_id', $pelajaranId)
                ->whereIn('siswa_id', $siswaList->pluck('id'))
                ->get()
                ->keyBy('siswa_id');

            $nilaiMap = $existingNilai->toArray();

            if ($existingNilai->count() > 0) {
                $nilaiRaporValues = $existingNilai->pluck('nilai_rapor');
                $stats['rata_rata'] = round($nilaiRaporValues->avg(), 1);
                $stats['nilai_tertinggi'] = $nilaiRaporValues->max();
                $stats['perlu_remedial'] = $nilaiRaporValues->filter(fn ($v) => $v < 75)->count();
                $stats['tuntas'] = $nilaiRaporValues->filter(fn ($v) => $v >= 75)->count();
            }
        }

        // Get selected mapel & kelas names for export
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

        if ($user->isAdmin()) {
            $kelasList = Cache::remember('kelas_list_all_ordered', 300, fn () => Kelas::orderBy('nama_kelas')->get());
        } else {
            $kelasList = Kelas::where('wali_kelas_id', $user->id)->orderBy('nama_kelas')->get();
        }

        $tahunAjaranId = $request->get('tahun_ajaran_id', TahunAjaran::where('status_aktif', 'Y')->value('id'));
        $kelasId = $request->get('kelas_id');

        if (! $user->isAdmin() && $kelasId) {
            $isWaliKelas = $kelasList->contains('id', $kelasId);
            if (! $isWaliKelas) {
                abort(403, 'Anda tidak memiliki akses ke kelas ini.');
            }
        }

        $siswaList = collect();
        $mapelList = collect();
        $nilaiMap = [];
        $rataRataSiswa = [];

        if ($kelasId && $tahunAjaranId) {
            $siswaList = Siswa::where('kelas_id', $kelasId)
                ->where('status', 'Aktif')
                ->orderBy('nama_siswa')
                ->get();

            $mapelList = MataPelajaran::orderBy('nama_pelajaran')->get();

            $existingNilai = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
                ->whereIn('siswa_id', $siswaList->pluck('id'))
                ->get();

            foreach ($existingNilai as $nilai) {
                $nilaiMap[$nilai->siswa_id][$nilai->pelajaran_id] = $nilai->nilai_rapor;
            }

            $rataRataQuery = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
                ->whereIn('siswa_id', $siswaList->pluck('id'))
                ->selectRaw('siswa_id, ROUND(AVG(nilai_rapor), 1) as avg_nilai')
                ->groupBy('siswa_id')
                ->pluck('avg_nilai', 'siswa_id')
                ->toArray();

            foreach ($siswaList as $s) {
                $rataRataSiswa[$s->id] = $rataRataQuery[$s->id] ?? 0;
            }
        }

        $selectedKelas = $kelasId ? Kelas::find($kelasId) : null;
        $tahunAjaranAktif = TahunAjaran::find($tahunAjaranId);

        return view('rekapnilai', compact(
            'tahunAjaranList', 'kelasList', 'tahunAjaranId', 'kelasId',
            'siswaList', 'mapelList', 'nilaiMap', 'rataRataSiswa',
            'selectedKelas', 'tahunAjaranAktif'
        ));
    }

    /**
     * Batch Update - Store all nilai in one request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'nilai' => 'required|array',
            'nilai.*.siswa_id' => 'required|exists:siswa,id',
            // Formatif data - 4 BABs with TP1-4 and UH each
            'nilai.*.formatif' => 'required|array',
            'nilai.*.formatif.bab1' => 'required|array',
            'nilai.*.formatif.bab1.tp1' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab1.tp2' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab1.tp3' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab1.tp4' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab1.uh' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab2' => 'required|array',
            'nilai.*.formatif.bab2.tp1' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab2.tp2' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab2.tp3' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab2.tp4' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab2.uh' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab3' => 'required|array',
            'nilai.*.formatif.bab3.tp1' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab3.tp2' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab3.tp3' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab3.tp4' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab3.uh' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab4' => 'required|array',
            'nilai.*.formatif.bab4.tp1' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab4.tp2' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab4.tp3' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab4.tp4' => 'required|numeric|min:0|max:100',
            'nilai.*.formatif.bab4.uh' => 'required|numeric|min:0|max:100',
            // SAS - 0 to 100
            'nilai.*.sas' => 'required|numeric|min:0|max:100',
            // Kehadiran - 0 to 20
            'nilai.*.kehadiran' => 'required|numeric|min:0|max:20',
            // Pengurang - optional, default 0
            'nilai.*.pengurang_tidaktelat' => 'nullable|numeric|min:0|max:100',
            'nilai.*.pengurang_menyontek' => 'nullable|numeric|min:0|max:100',
        ]);

        $firstKelasId = Siswa::find($validated['nilai'][0]['siswa_id'])?->kelas_id;
        if ($firstKelasId) {
            $this->ensureTeacherCanAccessPelajaran($validated['pelajaran_id'], $firstKelasId);
        }

        foreach ($validated['nilai'] as $data) {
            $formatifData = $data['formatif'];
            $sas = $data['sas'];
            $kehadiran = $data['kehadiran'];
            $pengurangTidaktelat = $data['pengurang_tidaktelat'] ?? 0;
            $pengurangMenyontek = $data['pengurang_menyontek'] ?? 0;

            // Calculate nilai_rapor using model method
            $nilaiRapor = NilaiRapot::calculateNilaiRapor(
                $formatifData,
                $sas,
                $kehadiran,
                $pengurangTidaktelat,
                $pengurangMenyontek
            );

            NilaiRapot::updateOrCreate(
                [
                    'tahun_ajaran_id' => $validated['tahun_ajaran_id'],
                    'siswa_id' => $data['siswa_id'],
                    'pelajaran_id' => $validated['pelajaran_id'],
                ],
                [
                    'formatif_data' => $formatifData,
                    'sas' => $sas,
                    'kehadiran' => $kehadiran,
                    'pengurang_tidaktelat' => $pengurangTidaktelat,
                    'pengurang_menyontek' => $pengurangMenyontek,
                    'nilai_rapor' => $nilaiRapor,
                ]
            );
        }

        return redirect()->route('nilai.index', [
            'tahun_ajaran_id' => $validated['tahun_ajaran_id'],
            'pelajaran_id' => $validated['pelajaran_id'],
            'kelas_id' => Siswa::find($validated['nilai'][0]['siswa_id'])?->kelas_id,
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

        $this->ensureTeacherCanAccessKelas($kelasId);

        $siswaList = Siswa::where('kelas_id', $kelasId)->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $nilaiData = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('pelajaran_id', $pelajaranId)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->get()->keyBy('siswa_id');

        $mapel = MataPelajaran::find($pelajaranId);
        $kelas = Kelas::find($kelasId);
        $filename = 'nilai_'.str_replace(' ', '_', $kelas->nama_kelas ?? '').'_'.($mapel->kode_pelajaran ?? '').'_'.now()->format('Ymd').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($siswaList, $nilaiData, $kelas, $mapel, $tahunAjaranId) {
            $tahunAjaran = TahunAjaran::find($tahunAjaranId);
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Section
            fputcsv($file, ['YAYASAN PENDIDIKAN DARUL FIKRI']);
            fputcsv($file, ['SD IT DARUL FIKRI']);
            fputcsv($file, ['LAPORAN NILAI RAPOT']);
            fputcsv($file, []);
            fputcsv($file, ['Kelas', ':', $kelas->nama_kelas ?? '-']);
            fputcsv($file, ['Mata Pelajaran', ':', $mapel->nama_pelajaran ?? '-']);
            fputcsv($file, ['Tahun Ajaran', ':', $tahunAjaran->nama_semester ?? '-']);
            fputcsv($file, ['Tanggal Cetak', ':', now()->translatedFormat('d F Y')]);
            fputcsv($file, []);

            // Detailed Header
            $headerRow = ['No', 'NIS', 'Nama Siswa'];
            // BAB headers
            for ($i = 1; $i <= 4; $i++) {
                $headerRow[] = "BAB{$i} TP1";
                $headerRow[] = "BAB{$i} TP2";
                $headerRow[] = "BAB{$i} TP3";
                $headerRow[] = "BAB{$i} TP4";
                $headerRow[] = "BAB{$i} UH";
                $headerRow[] = "BAB{$i} Rata-rata";
            }
            $headerRow[] = 'Formatif Total (50%)';
            $headerRow[] = 'SAS (30%)';
            $headerRow[] = 'Kehadiran';
            $headerRow[] = 'Pengurang';
            $headerRow[] = 'Nilai Rapor';
            $headerRow[] = 'Predikat';
            fputcsv($file, $headerRow);

            $no = 1;
            foreach ($siswaList as $s) {
                $n = $nilaiData[$s->id] ?? null;
                $row = [$no++, $s->nis, $s->nama_siswa];

                $formatifData = $n ? $n->formatif_data : NilaiRapot::getDefaultFormatifData();
                if (is_string($formatifData)) {
                    $formatifData = json_decode($formatifData, true);
                }
                $formatifData = $formatifData ?: NilaiRapot::getDefaultFormatifData();

                $babAverages = $n ? $n->getBabAverages() : [];

                // Add BAB data
                for ($i = 1; $i <= 4; $i++) {
                    $babKey = "bab{$i}";
                    $babData = $formatifData[$babKey] ?? [];
                    $row[] = $babData['tp1'] ?? 0;
                    $row[] = $babData['tp2'] ?? 0;
                    $row[] = $babData['tp3'] ?? 0;
                    $row[] = $babData['tp4'] ?? 0;
                    $row[] = $babData['uh'] ?? 0;
                    $row[] = $babAverages[$babKey] ?? 0;
                }

                $row[] = $n ? $n->getFormatifTotal() : 0;
                $row[] = $n ? $n->sas : 0;
                $row[] = $n ? $n->kehadiran : 0;
                $row[] = $n ? $n->getTotalPengurang() : 0;
                $row[] = $n ? $n->nilai_rapor : 0;
                $row[] = $n ? NilaiRapot::getPredikat($n->nilai_rapor) : 'F';

                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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

        $this->ensureTeacherCanAccessKelas($kelasId);

        $siswaList = Siswa::where('kelas_id', $kelasId)->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $nilaiData = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('pelajaran_id', $pelajaranId)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->get()->keyBy('siswa_id');

        $mapel = MataPelajaran::find($pelajaranId);
        $kelas = Kelas::find($kelasId);
        $tahunAjaran = TahunAjaran::find($tahunAjaranId);

        return view('exports.nilai-pdf', compact('siswaList', 'nilaiData', 'mapel', 'kelas', 'tahunAjaran'));
    }

    public function exportRekapCsv(Request $request)
    {
        $user = Auth::user();
        $tahunAjaranId = $request->get('tahun_ajaran_id');
        $kelasId = $request->get('kelas_id');

        if (! $kelasId || ! $tahunAjaranId) {
            return redirect()->route('nilai.rekap')->with('error', 'Pilih filter terlebih dahulu.');
        }

        if (! $user->isAdmin()) {
            $isWaliKelas = Kelas::where('id', $kelasId)->where('wali_kelas_id', $user->id)->exists();
            if (! $isWaliKelas) {
                return redirect()->route('nilai.rekap')->with('error', 'Anda tidak memiliki akses ke kelas ini.');
            }
        }

        $siswaList = Siswa::where('kelas_id', $kelasId)->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $mapelList = MataPelajaran::orderBy('nama_pelajaran')->get();
        $existingNilai = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->get();

        $nilaiMap = [];
        foreach ($existingNilai as $nilai) {
            $nilaiMap[$nilai->siswa_id][$nilai->pelajaran_id] = $nilai->nilai_rapor;
        }

        $kelas = Kelas::find($kelasId);
        $filename = 'rekap_nilai_'.str_replace(' ', '_', $kelas->nama_kelas ?? '').'_'.now()->format('Ymd').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($siswaList, $mapelList, $nilaiMap, $kelas, $tahunAjaranId) {
            $tahunAjaranAktif = TahunAjaran::find($tahunAjaranId);
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

            // Header Section
            fputcsv($file, ['YAYASAN PENDIDIKAN DARUL FIKRI']);
            fputcsv($file, ['SD IT DARUL FIKRI']);
            fputcsv($file, ['REKAPITULASI NILAI SISWA']);
            fputcsv($file, []);
            fputcsv($file, ['Kelas', ':', $kelas->nama_kelas ?? '-']);
            fputcsv($file, ['Tahun Ajaran', ':', $tahunAjaranAktif->nama_semester ?? '-']);
            fputcsv($file, ['Tanggal Cetak', ':', now()->translatedFormat('d F Y')]);
            fputcsv($file, []);

            $headerRow = ['No', 'NIS', 'Nama Siswa'];
            foreach ($mapelList as $m) {
                $headerRow[] = $m->nama_pelajaran;
            }
            $headerRow[] = 'Rata-rata';
            fputcsv($file, $headerRow);

            $rataRataSiswa = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
                ->whereIn('siswa_id', $siswaList->pluck('id'))
                ->selectRaw('siswa_id, ROUND(AVG(nilai_rapor), 1) as avg_nilai')
                ->groupBy('siswa_id')
                ->pluck('avg_nilai', 'siswa_id')
                ->toArray();

            $no = 1;
            foreach ($siswaList as $s) {
                $row = [$no++, $s->nis, $s->nama_siswa];
                foreach ($mapelList as $m) {
                    $nilai = $nilaiMap[$s->id][$m->id] ?? null;
                    if ($nilai !== null) {
                        $row[] = number_format($nilai, 1);
                    } else {
                        $row[] = '-';
                    }
                }

                $rataRata = $rataRataSiswa[$s->id] ?? 0;
                $row[] = $rataRata > 0 ? number_format($rataRata, 1) : '-';
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportRekapPdf(Request $request)
    {
        $user = Auth::user();
        $tahunAjaranId = $request->get('tahun_ajaran_id');
        $kelasId = $request->get('kelas_id');

        if (! $kelasId || ! $tahunAjaranId) {
            return redirect()->route('nilai.rekap')->with('error', 'Pilih filter terlebih dahulu.');
        }

        if (! $user->isAdmin()) {
            $isWaliKelas = Kelas::where('id', $kelasId)->where('wali_kelas_id', $user->id)->exists();
            if (! $isWaliKelas) {
                return redirect()->route('nilai.rekap')->with('error', 'Anda tidak memiliki akses ke kelas ini.');
            }
        }

        $siswaList = Siswa::where('kelas_id', $kelasId)->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $mapelList = MataPelajaran::orderBy('nama_pelajaran')->get();
        $existingNilai = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->get();

        $nilaiMap = [];
        $rataRataSiswa = [];
        foreach ($existingNilai as $nilai) {
            $nilaiMap[$nilai->siswa_id][$nilai->pelajaran_id] = $nilai->nilai_rapor;
        }

        $rataRataSiswa = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->selectRaw('siswa_id, ROUND(AVG(nilai_rapor), 1) as avg_nilai')
            ->groupBy('siswa_id')
            ->pluck('avg_nilai', 'siswa_id')
            ->toArray();

        $selectedKelas = Kelas::find($kelasId);
        $tahunAjaranAktif = TahunAjaran::find($tahunAjaranId);

        return view('exports.rekap-nilai-pdf', compact(
            'siswaList', 'mapelList', 'nilaiMap', 'rataRataSiswa',
            'selectedKelas', 'tahunAjaranAktif'
        ));
    }
}
