<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\PresensiSholat;
use App\Models\Siswa;
use App\Support\Sekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Presensi sholat Dzuhur/Ashar lewat scan QR Kartu Pelajar.
 * QR terbaca = Hadir. Yang belum scan sampai batas waktu dicatat Tidak Hadir oleh server.
 * Hanya wali kelas (dan admin) yang mengelola presensi sholat kelasnya.
 */
class SholatController extends Controller
{
    public function kelas(Request $request): JsonResponse
    {
        $guru = $request->user();
        $kelas = Kelas::withCount('siswaAktif')
            ->where('wali_kelas_id', $guru->id)
            ->orderBy('nama_kelas')->get()
            ->map(fn ($k) => [
                'id' => $k->id, 'nama' => $k->nama_kelas, 'singkatan' => PresensiKelasController::singkatan($k->nama_kelas),
                'jumlah_siswa' => $k->siswa_aktif_count, 'wali_kelas' => $k->wali_kelas_id === $guru->id,
            ]);

        return response()->json(['data' => $kelas]);
    }

    /** Jenis sholat hari ini untuk kelas: waktu, tempat, dan progres scan. */
    public function jenis(Request $request, Kelas $kelas): JsonResponse
    {
        $this->pastikanWali($kelas);
        $now = Sekolah::now();
        $total = $kelas->siswaAktif()->count();
        $rekap = PresensiSholat::where('kelas_id', $kelas->id)->where('tanggal', $now->toDateString())
            ->selectRaw('jenis_sholat, status_kehadiran, COUNT(*) as jumlah')->groupBy('jenis_sholat', 'status_kehadiran')->get();

        return response()->json([
            'kelas' => ['id' => $kelas->id, 'nama' => $kelas->nama_kelas, 'jumlah_siswa' => $total],
            'tanggal' => $now->toDateString(),
            'data' => collect(PresensiSholat::JENIS)->map(function ($jenis) use ($rekap, $now, $total) {
                $waktu = config("sholat.waktu.{$jenis}");
                $hadir = (int) $rekap->where('jenis_sholat', $jenis)->where('status_kehadiran', PresensiSholat::HADIR)->sum('jumlah');
                $tercatat = (int) $rekap->where('jenis_sholat', $jenis)->sum('jumlah');

                return [
                    'jenis' => $jenis, 'waktu' => $waktu, 'tempat' => config('sholat.tempat'),
                    'sudah_masuk_waktu' => $now->format('H:i') >= $waktu,
                    'hadir' => $hadir, 'tercatat' => $tercatat, 'total' => $total,
                ];
            })->values(),
        ]);
    }

    /** Daftar siswa beserta status sholat hari ini. */
    public function show(Request $request, Kelas $kelas): JsonResponse
    {
        $this->pastikanWali($kelas);
        $jenis = $this->jenisValid($request->query('jenis'));
        $tanggal = Sekolah::now()->toDateString();
        $records = PresensiSholat::where('kelas_id', $kelas->id)->where('tanggal', $tanggal)->where('jenis_sholat', $jenis)
            ->get(['siswa_id', 'status_kehadiran', 'updated_at'])->keyBy('siswa_id');
        $siswa = $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();

        return response()->json([
            'kelas' => ['id' => $kelas->id, 'nama' => $kelas->nama_kelas],
            'jenis' => $jenis, 'waktu' => config("sholat.waktu.{$jenis}"), 'tempat' => config('sholat.tempat'),
            'tanggal' => $tanggal,
            'hadir' => $records->where('status_kehadiran', PresensiSholat::HADIR)->count(),
            'total' => $siswa->count(),
            'data' => $siswa->map(fn ($s) => [
                'siswa_id' => $s->id, 'nama' => $s->nama_siswa, 'nis' => $s->nis,
                'status' => $records[$s->id]->status_kehadiran ?? null,
            ]),
        ]);
    }

    /** Scan QR Kartu Pelajar: siswa langsung tercatat Hadir. */
    public function scan(Request $request, Kelas $kelas): JsonResponse
    {
        $this->pastikanWali($kelas);
        $data = $request->validate(['jenis' => ['required', Rule::in(PresensiSholat::JENIS)], 'qr' => 'required|string|max:200']);

        $token = str_starts_with($data['qr'], Siswa::QR_PREFIX) ? substr($data['qr'], strlen(Siswa::QR_PREFIX)) : null;
        $siswa = $token ? Siswa::where('qr_token', $token)->first() : null;

        if (! $siswa) {
            throw ValidationException::withMessages(['qr' => 'QR tidak dikenali. Pastikan yang di-scan adalah QR Sholat di Kartu Pelajar.']);
        }
        if ($siswa->status !== 'Aktif') {
            throw ValidationException::withMessages(['qr' => "{$siswa->nama_siswa} tidak berstatus aktif."]);
        }
        if ($siswa->kelas_id !== $kelas->id) {
            throw ValidationException::withMessages(['qr' => "{$siswa->nama_siswa} bukan siswa {$kelas->nama_kelas}."]);
        }

        $now = Sekolah::now();
        $sebelumnya = PresensiSholat::where('siswa_id', $siswa->id)->where('tanggal', $now->toDateString())->where('jenis_sholat', $data['jenis'])->first();
        $sudahHadir = $sebelumnya?->status_kehadiran === PresensiSholat::HADIR;

        if (! $sudahHadir) {
            PresensiSholat::updateOrCreate(
                ['siswa_id' => $siswa->id, 'tanggal' => $now->toDateString(), 'jenis_sholat' => $data['jenis']],
                ['kelas_id' => $kelas->id, 'status_kehadiran' => PresensiSholat::HADIR, 'keterangan' => 'Scan QR '.$now->format('H:i'), 'recorded_by' => Auth::id()]
            );
        }

        return response()->json([
            'sudah_tercatat_sebelumnya' => $sudahHadir,
            'siswa' => ['id' => $siswa->id, 'nama' => $siswa->nama_siswa, 'nis' => $siswa->nis, 'kelas' => $kelas->nama_kelas, 'inisial' => Sekolah::inisial($siswa->nama_siswa)],
            'status' => PresensiSholat::HADIR,
            'jam' => $now->format('H:i'),
            'hadir' => PresensiSholat::where('kelas_id', $kelas->id)->where('tanggal', $now->toDateString())->where('jenis_sholat', $data['jenis'])->where('status_kehadiran', PresensiSholat::HADIR)->count(),
            'total' => $kelas->siswaAktif()->count(),
        ]);
    }

    /** Simpan/koreksi status manual (mis. kartu tertinggal). Siswa tanpa status dibiarkan. */
    public function store(Request $request, Kelas $kelas): JsonResponse
    {
        $this->pastikanWali($kelas);
        $data = $request->validate([
            'jenis' => ['required', Rule::in(PresensiSholat::JENIS)],
            'presensi' => 'required|array',
            'presensi.*.siswa_id' => ['required', Rule::exists('siswa', 'id')->where('kelas_id', $kelas->id)],
            'presensi.*.status' => ['nullable', Rule::in(PresensiSholat::STATUS)],
        ]);

        $tanggal = Sekolah::now()->toDateString();
        $now = now();
        $rows = collect($data['presensi'])->filter(fn ($p) => ! empty($p['status']))->map(fn ($p) => [
            'siswa_id' => $p['siswa_id'], 'kelas_id' => $kelas->id, 'tanggal' => $tanggal, 'jenis_sholat' => $data['jenis'],
            'status_kehadiran' => $p['status'], 'keterangan' => null, 'recorded_by' => Auth::id(), 'created_at' => $now, 'updated_at' => $now,
        ])->values()->all();
        if ($rows) {
            PresensiSholat::upsert($rows, ['siswa_id', 'tanggal', 'jenis_sholat'], ['kelas_id', 'status_kehadiran', 'keterangan', 'recorded_by', 'updated_at']);
        }

        $rekap = PresensiSholat::where('kelas_id', $kelas->id)->where('tanggal', $tanggal)->where('jenis_sholat', $data['jenis'])
            ->selectRaw('status_kehadiran, COUNT(*) as jumlah')->groupBy('status_kehadiran')->pluck('jumlah', 'status_kehadiran');

        return response()->json([
            'message' => 'Presensi sholat tersimpan.',
            'rekap' => ['hadir' => (int) ($rekap[PresensiSholat::HADIR] ?? 0), 'tidak_hadir' => (int) ($rekap[PresensiSholat::TIDAK_HADIR] ?? 0), 'total' => $kelas->siswaAktif()->count()],
        ]);
    }

    private function pastikanWali(Kelas $kelas): void
    {
        $guru = Auth::user();
        abort_unless($kelas->wali_kelas_id === $guru->id, 403, 'Presensi sholat hanya untuk wali kelas ini.');
    }

    private function jenisValid(?string $jenis): string
    {
        if (! in_array($jenis, PresensiSholat::JENIS, true)) {
            throw ValidationException::withMessages(['jenis' => 'Jenis sholat harus Dhuha atau Dzuhur.']);
        }

        return $jenis;
    }
}
