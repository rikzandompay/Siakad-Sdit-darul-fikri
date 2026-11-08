<?php

namespace App\Http\Controllers;

use App\Models\PengaturanSekolah;
use App\Models\PresensiGuru;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Rekap absen guru (data dari aplikasi MyDarfik: lokasi + wajah). Admin saja.
 * Ringkasan per guru per periode, atau detail harian satu guru.
 */
class RekapPresensiGuruController extends Controller
{
    public function index(Request $request)
    {
        return view('rekap-presensi-guru', $this->data($request));
    }

    /**
     * Koreksi/isi manual absen guru oleh admin (HP rusak, wajah gagal, lupa absen).
     * Menimpa catatan hari itu dan menandainya sumber "admin".
     */
    public function simpanManual(Request $request)
    {
        $data = $request->validate([
            'guru_id' => ['required', Rule::in($this->guruList()->pluck('id')->all())],
            'tanggal' => 'required|date|before_or_equal:today',
            'status' => ['required', Rule::in(PresensiGuru::STATUS)],
            'jam_masuk' => 'nullable|required_if:status,'.PresensiGuru::HADIR.'|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i|after_or_equal:jam_masuk',
            'keterangan' => 'required|string|max:255',
        ], ['keterangan.required' => 'Tulis alasan koreksi supaya tercatat di rekap.']);

        $hadir = $data['status'] === PresensiGuru::HADIR;
        PresensiGuru::updateOrCreate(
            ['guru_id' => $data['guru_id'], 'tanggal' => $data['tanggal']],
            [
                'status' => $data['status'],
                'jam_masuk' => $hadir ? $data['jam_masuk'].':00' : null,
                'jam_pulang' => $hadir && ! empty($data['jam_pulang']) ? $data['jam_pulang'].':00' : null,
                'sumber' => 'admin',
                'keterangan' => $data['keterangan'].' (diubah oleh '.($request->user()->nama_lengkap ?? $request->user()->name).')',
            ]
        );

        return redirect()->route('presensi-guru.rekap', $request->only('rentang') + ['guru_id' => $data['guru_id'], 'tanggal' => $data['tanggal']])
            ->with('success', 'Absen guru dikoreksi.');
    }

    /** Hapus data wajah supaya guru mendaftarkan ulang dari aplikasi. */
    public function resetWajah(Request $request, User $guru)
    {
        $guru->wajah()->delete();

        return redirect()->back()->with('success', 'Data wajah '.($guru->nama_lengkap ?? $guru->name).' dihapus. Guru perlu mendaftarkan wajah lagi di aplikasi.');
    }

    public function exportCsv(Request $request)
    {
        $data = $this->data($request);
        $filename = 'rekap_presensi_guru_'.($data['selectedGuru'] ? str($data['selectedGuru']->nama_lengkap)->slug('_').'_' : '').now()->format('Ymd').'.csv';

        return response()->stream(function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM

            fputcsv($file, ['YAYASAN PENDIDIKAN DARUL FIKRI']);
            fputcsv($file, ['SD IT DARUL FIKRI']);
            fputcsv($file, ['REKAPITULASI PRESENSI GURU']);
            fputcsv($file, []);
            fputcsv($file, ['Periode', ':', $data['periodeLabel']]);
            if ($data['selectedGuru']) {
                $this->putSafeCsv($file, ['Guru', ':', $data['selectedGuru']->nama_lengkap]);
            }
            fputcsv($file, ['Jam masuk acuan', ':', $data['jamMasuk']]);
            fputcsv($file, []);

            if ($data['selectedGuru']) {
                fputcsv($file, ['No', 'Tanggal', 'Status', 'Jam Masuk', 'Jam Pulang', 'Terlambat', 'Jarak Masuk (m)', 'Kecocokan Wajah (%)', 'Keterangan']);
                foreach ($data['detail'] as $i => $row) {
                    $this->putSafeCsv($file, [
                        $i + 1,
                        $row->tanggal->format('Y-m-d'),
                        $row->status,
                        $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-',
                        $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-',
                        $row->terlambat ? 'Ya' : '-',
                        $row->masuk_jarak_meter ?? '-',
                        $row->masuk_skor_wajah ?? '-',
                        $row->keterangan ?? '-',
                    ]);
                }
            } else {
                fputcsv($file, ['No', 'NIP', 'Nama Guru', 'Hadir', 'Terlambat', 'Tidak Hadir', '% Hadir']);
                foreach ($data['ringkasan'] as $i => $row) {
                    $this->putSafeCsv($file, [
                        $i + 1,
                        $row['guru']->nip ?? '-',
                        $row['guru']->nama_lengkap ?? $row['guru']->name,
                        $row['hadir'],
                        $row['terlambat'],
                        $row['tidak_hadir'],
                        $row['persen'],
                    ]);
                }
            }
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportPdf(Request $request)
    {
        return view('exports.rekap-presensi-guru-pdf', $this->data($request));
    }

    private function data(Request $request): array
    {
        $request->validate([
            'tanggal' => 'nullable|date',
            'rentang' => 'nullable|in:hari_ini,minggu_ini,bulan_ini,semester_ini',
            'guru_id' => 'nullable|integer',
        ]);

        $rentang = $request->get('rentang', 'bulan_ini');
        $tanggal = $request->get('tanggal', now(config('presensi_guru.timezone'))->toDateString());
        $range = $this->periodeRange($rentang, $tanggal);
        $start = $range['start']->toDateString();
        $end = $range['end']->toDateString();

        $guruList = $this->guruList();
        $selectedGuru = $request->filled('guru_id') ? $guruList->firstWhere('id', (int) $request->get('guru_id')) : null;

        $detail = collect();
        if ($selectedGuru) {
            $detail = PresensiGuru::where('guru_id', $selectedGuru->id)
                ->whereBetween('tanggal', [$start, $end])
                ->orderBy('tanggal')
                ->get();
        }

        return [
            'rentang' => $rentang,
            'tanggal' => $tanggal,
            'periodeLabel' => $this->periodeLabel($rentang, $tanggal),
            'jamMasuk' => PengaturanSekolah::jamMasuk(),
            'guruList' => $guruList,
            'selectedGuru' => $selectedGuru,
            'ringkasan' => $this->ringkasan($guruList, $start, $end),
            'detail' => $detail,
        ];
    }

    private function guruList(): Collection
    {
        return User::where('status', 'Aktif')
            ->whereNotIn('username', config('presensi_guru.kecualikan_username'))
            ->orderBy('nama_lengkap')
            ->get();
    }

    private function ringkasan(Collection $guruList, string $start, string $end): Collection
    {
        $jamMasuk = PengaturanSekolah::jamMasuk().':59';

        // toBase(): hasil agregat, bukan model (hindari accessor `terlambat`).
        $counts = PresensiGuru::toBase()->whereBetween('tanggal', [$start, $end])
            ->whereIn('guru_id', $guruList->pluck('id'))
            ->selectRaw('guru_id,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as hadir_count,
                SUM(CASE WHEN status = ? AND jam_masuk > ? THEN 1 ELSE 0 END) as terlambat_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as tidak_hadir_count',
                [PresensiGuru::HADIR, PresensiGuru::HADIR, $jamMasuk, PresensiGuru::TIDAK_HADIR])
            ->groupBy('guru_id')
            ->get()
            ->keyBy('guru_id');

        return $guruList->map(function (User $guru) use ($counts) {
            $row = $counts->get($guru->id);
            $hadir = (int) ($row->hadir_count ?? 0);
            $tidakHadir = (int) ($row->tidak_hadir_count ?? 0);
            $total = $hadir + $tidakHadir;

            return [
                'guru' => $guru,
                'hadir' => $hadir,
                'terlambat' => (int) ($row->terlambat_count ?? 0),
                'tidak_hadir' => $tidakHadir,
                'persen' => $total > 0 ? round($hadir / $total * 100, 1) : 0,
            ];
        })->values();
    }
}
