<?php

namespace App\Http\Controllers;

use App\Models\NilaiRapot;
use App\Models\TahunAjaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\JadwalPelajaran;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NilaiRapotController extends Controller
{
    public function index(Request $request)
    {
        $guruId = Auth::id();

        $tahunAjaranList = TahunAjaran::orderByDesc('id')->get();

        $tahunAjaranId = $request->get('tahun_ajaran_id', TahunAjaran::getAktif()?->id);
        $kelasId = $request->get('kelas_id');
        $pelajaranId = $request->get('pelajaran_id');

        // Hanya kelas & mapel yang diampu guru ini
        $guruJadwalQuery = JadwalPelajaran::where('guru_id', $guruId);
        
        $kelasIdsGuru = (clone $guruJadwalQuery)->pluck('kelas_id')->unique();
        $kelasList = Kelas::whereIn('id', $kelasIdsGuru)->orderBy('nama_kelas')->get();

        if ($kelasId) {
            $guruJadwalQuery->where('kelas_id', $kelasId);
        }
        
        $mapelIdsGuru = $guruJadwalQuery->pluck('pelajaran_id')->unique();
        $mapelList = MataPelajaran::whereIn('id', $mapelIdsGuru)->orderBy('nama_pelajaran')->get();

        $siswaList = collect();
        $nilaiMap = [];
        $stats = ['rata_rata' => 0, 'nilai_tertinggi' => 0, 'perlu_remedial' => 0, 'tuntas' => 0];

        if ($kelasId && $pelajaranId && $tahunAjaranId) {
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
                $nilaiAkhirValues = $existingNilai->pluck('nilai_akhir');
                $stats['rata_rata'] = round($nilaiAkhirValues->avg(), 1);
                $stats['nilai_tertinggi'] = $nilaiAkhirValues->max();
                $stats['perlu_remedial'] = $nilaiAkhirValues->filter(fn($v) => $v < 75)->count();
                $stats['tuntas'] = $nilaiAkhirValues->filter(fn($v) => $v >= 75)->count();
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
        $tahunAjaranList = TahunAjaran::orderByDesc('id')->get();
        $kelasList = Kelas::orderBy('nama_kelas')->get();

        // Menggunakan TahunAjar::where jika getAktif tidak jalan, tapi kita asumsikan getAktif() exists seperti di index()
        $tahunAjaranId = $request->get('tahun_ajaran_id', TahunAjaran::where('status_aktif', 'Y')->value('id'));
        $kelasId = $request->get('kelas_id');

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
                $nilaiMap[$nilai->siswa_id][$nilai->pelajaran_id] = $nilai->nilai_akhir;
            }

            foreach ($siswaList as $s) {
                $total = 0;
                $count = 0;
                foreach ($mapelList as $m) {
                    if (isset($nilaiMap[$s->id][$m->id])) {
                        $total += $nilaiMap[$s->id][$m->id];
                        $count++;
                    }
                }
                $rataRataSiswa[$s->id] = $count > 0 ? round($total / $count, 1) : 0;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'pelajaran_id' => 'required|exists:mata_pelajaran,id',
            'nilai' => 'required|array',
            'nilai.*.siswa_id' => 'required|exists:siswa,id',
            'nilai.*.nilai_tugas' => 'required|numeric|min:0|max:100',
            'nilai.*.nilai_uts' => 'required|numeric|min:0|max:100',
            'nilai.*.nilai_uas' => 'required|numeric|min:0|max:100',
        ]);

        foreach ($validated['nilai'] as $data) {
            $nilaiAkhir = NilaiRapot::hitungNilaiAkhir(
                $data['nilai_tugas'],
                $data['nilai_uts'],
                $data['nilai_uas']
            );

            NilaiRapot::updateOrCreate(
                [
                    'tahun_ajaran_id' => $validated['tahun_ajaran_id'],
                    'siswa_id' => $data['siswa_id'],
                    'pelajaran_id' => $validated['pelajaran_id'],
                ],
                [
                    'nilai_tugas' => $data['nilai_tugas'],
                    'nilai_uts' => $data['nilai_uts'],
                    'nilai_uas' => $data['nilai_uas'],
                    'nilai_akhir' => $nilaiAkhir,
                ]
            );
        }

        return redirect()->route('nilai.index', [
            'tahun_ajaran_id' => $validated['tahun_ajaran_id'],
            'pelajaran_id' => $validated['pelajaran_id'],
            'kelas_id' => Siswa::find($validated['nilai'][0]['siswa_id'])?->kelas_id,
        ])->with('success', 'Nilai berhasil disimpan!');
    }

    /**
     * Export nilai as CSV
     */
    public function exportCsv(Request $request)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id');
        $kelasId = $request->get('kelas_id');
        $pelajaranId = $request->get('pelajaran_id');

        if (!$kelasId || !$pelajaranId || !$tahunAjaranId) {
            return redirect()->route('nilai.index')->with('error', 'Pilih filter terlebih dahulu.');
        }

        $siswaList = Siswa::where('kelas_id', $kelasId)->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $nilaiData = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
            ->where('pelajaran_id', $pelajaranId)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->get()->keyBy('siswa_id');

        $mapel = MataPelajaran::find($pelajaranId);
        $kelas = Kelas::find($kelasId);
        $filename = 'nilai_' . str_replace(' ', '_', $kelas->nama_kelas ?? '') . '_' . ($mapel->kode_pelajaran ?? '') . '_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($siswaList, $nilaiData) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['No', 'NIS', 'Nama Siswa', 'Nilai Tugas', 'Nilai UTS', 'Nilai UAS', 'Nilai Akhir', 'Predikat']);
            $no = 1;
            foreach ($siswaList as $s) {
                $n = $nilaiData[$s->id] ?? null;
                fputcsv($file, [
                    $no++,
                    $s->nis,
                    $s->nama_siswa,
                    $n ? $n->nilai_tugas : 0,
                    $n ? $n->nilai_uts : 0,
                    $n ? $n->nilai_uas : 0,
                    $n ? $n->nilai_akhir : 0,
                    $n ? NilaiRapot::getPredikat($n->nilai_akhir) : 'D',
                ]);
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

        if (!$kelasId || !$pelajaranId || !$tahunAjaranId) {
            return redirect()->route('nilai.index')->with('error', 'Pilih filter terlebih dahulu.');
        }

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
        $tahunAjaranId = $request->get('tahun_ajaran_id');
        $kelasId = $request->get('kelas_id');

        if (!$kelasId || !$tahunAjaranId) {
            return redirect()->route('nilai.rekap')->with('error', 'Pilih filter terlebih dahulu.');
        }

        $siswaList = Siswa::where('kelas_id', $kelasId)->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $mapelList = MataPelajaran::orderBy('nama_pelajaran')->get();
        $existingNilai = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->get();

        $nilaiMap = [];
        foreach ($existingNilai as $nilai) {
            $nilaiMap[$nilai->siswa_id][$nilai->pelajaran_id] = $nilai->nilai_akhir;
        }

        $kelas = Kelas::find($kelasId);
        $filename = 'rekap_nilai_' . str_replace(' ', '_', $kelas->nama_kelas ?? '') . '_' . now()->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($siswaList, $mapelList, $nilaiMap) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for Excel

            $headerRow = ['No', 'NIS', 'Nama Siswa'];
            foreach ($mapelList as $m) {
                $headerRow[] = $m->nama_pelajaran;
            }
            $headerRow[] = 'Rata-rata';
            fputcsv($file, $headerRow);

            $no = 1;
            foreach ($siswaList as $s) {
                $row = [$no++, $s->nis, $s->nama_siswa];
                $total = 0;
                $count = 0;
                foreach ($mapelList as $m) {
                    $nilai = $nilaiMap[$s->id][$m->id] ?? null;
                    if ($nilai !== null) {
                        $row[] = number_format($nilai, 1);
                        $total += $nilai;
                        $count++;
                    } else {
                        $row[] = '-';
                    }
                }
                $rataRata = $count > 0 ? round($total / $count, 1) : 0;
                $row[] = $rataRata > 0 ? number_format($rataRata, 1) : '-';
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportRekapPdf(Request $request)
    {
        $tahunAjaranId = $request->get('tahun_ajaran_id');
        $kelasId = $request->get('kelas_id');

        if (!$kelasId || !$tahunAjaranId) {
            return redirect()->route('nilai.rekap')->with('error', 'Pilih filter terlebih dahulu.');
        }

        $siswaList = Siswa::where('kelas_id', $kelasId)->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $mapelList = MataPelajaran::orderBy('nama_pelajaran')->get();
        $existingNilai = NilaiRapot::where('tahun_ajaran_id', $tahunAjaranId)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->get();

        $nilaiMap = [];
        $rataRataSiswa = [];
        foreach ($existingNilai as $nilai) {
            $nilaiMap[$nilai->siswa_id][$nilai->pelajaran_id] = $nilai->nilai_akhir;
        }

        foreach ($siswaList as $s) {
            $total = 0;
            $count = 0;
            foreach ($mapelList as $m) {
                if (isset($nilaiMap[$s->id][$m->id])) {
                    $total += $nilaiMap[$s->id][$m->id];
                    $count++;
                }
            }
            $rataRataSiswa[$s->id] = $count > 0 ? round($total / $count, 1) : 0;
        }

        $selectedKelas = Kelas::find($kelasId);
        $tahunAjaranAktif = TahunAjaran::find($tahunAjaranId);

        return view('exports.rekap-nilai-pdf', compact(
            'siswaList', 'mapelList', 'nilaiMap', 'rataRataSiswa', 
            'selectedKelas', 'tahunAjaranAktif'
        ));
    }
}
