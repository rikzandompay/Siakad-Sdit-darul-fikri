<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Presensi;
use App\Support\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Presensi kelas per jadwal mengajar (aturan sama dengan web):
 * hanya wali kelas (atau admin) yang mengisi; status H/I/S/A.
 */
class PresensiKelasController extends Controller
{
    /** Kelas yang diajar guru (berdasarkan jadwal). */
    public function kelas(Request $request): JsonResponse
    {
        $guru = $request->user();
        // Hanya kelas yang diwalikan; admin dan guru pengajar mapel tidak mengisi presensi.
        $kelas = Kelas::withCount('siswaAktif')
            ->where('wali_kelas_id', $guru->id)
            ->orderBy('nama_kelas')->get()
            ->map(fn ($k) => [
                'id' => $k->id,
                'nama' => $k->nama_kelas,
                'singkatan' => self::singkatan($k->nama_kelas),
                'jumlah_siswa' => $k->siswa_aktif_count,
                'wali_kelas' => $k->wali_kelas_id === $guru->id,
            ]);

        return response()->json(['data' => $kelas]);
    }

    /** Jadwal guru di kelas ini pada tanggal tertentu (default hari ini), dengan status sudah/belum diabsen. */
    public function jadwal(Request $request, Kelas $kelas): JsonResponse
    {
        $request->validate(['tanggal' => 'nullable|date']);
        $tanggal = $request->filled('tanggal') ? Carbon::parse($request->input('tanggal'), Sekolah::timezone()) : Sekolah::now();
        $hari = Sekolah::hari($tanggal);

        // Wali kelas mengisi presensi seluruh jadwal kelasnya (middleware wali.kelas).
        $admin = $request->user()->isAdmin();
        $jadwal = JadwalPelajaran::with(['mataPelajaran', 'guru'])
            ->where('kelas_id', $kelas->id)->where('hari', $hari)
            ->orderBy('jam_mulai')->get();

        $sudah = Presensi::whereIn('jadwal_id', $jadwal->pluck('id'))->where('tanggal', $tanggal->toDateString())
            ->distinct()->pluck('jadwal_id')->all();

        return response()->json([
            'kelas' => ['id' => $kelas->id, 'nama' => $kelas->nama_kelas],
            'tanggal' => $tanggal->toDateString(),
            'hari' => $hari,
            'semua_guru' => $admin,
            'data' => $jadwal->map(fn ($j) => [
                'jadwal_id' => $j->id,
                'mata_pelajaran' => $j->mataPelajaran?->nama_pelajaran,
                'guru' => $j->guru?->nama_lengkap ?? $j->guru?->name,
                'jam_mulai' => substr($j->jam_mulai, 0, 5),
                'jam_selesai' => substr($j->jam_selesai, 0, 5),
                'sudah_diabsen' => in_array($j->id, $sudah, true),
            ]),
        ]);
    }

    public function show(Request $request, JadwalPelajaran $jadwal): JsonResponse
    {
        $request->validate(['tanggal' => 'nullable|date']);
        $tanggal = $request->input('tanggal', Sekolah::now()->toDateString());

        $status = Presensi::where('jadwal_id', $jadwal->id)->where('tanggal', Carbon::parse($tanggal)->toDateString())
            ->pluck('status_kehadiran', 'siswa_id');
        $siswa = $jadwal->kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();

        return response()->json([
            'jadwal' => [
                'id' => $jadwal->id, 'kelas' => $jadwal->kelas->nama_kelas, 'kelas_id' => $jadwal->kelas_id,
                'mata_pelajaran' => $jadwal->mataPelajaran?->nama_pelajaran,
                'jam_mulai' => substr($jadwal->jam_mulai, 0, 5), 'jam_selesai' => substr($jadwal->jam_selesai, 0, 5),
            ],
            'tanggal' => Carbon::parse($tanggal)->toDateString(),
            'sudah_diabsen' => $status->isNotEmpty(),
            'data' => $siswa->map(fn ($s) => [
                'siswa_id' => $s->id, 'nama' => $s->nama_siswa, 'nis' => $s->nis,
                'jenis_kelamin' => $s->jenis_kelamin, 'status' => $status[$s->id] ?? null,
            ]),
        ]);
    }

    public function store(Request $request, JadwalPelajaran $jadwal): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => 'required|date|before_or_equal:'.Sekolah::now()->toDateString(),
            'presensi' => 'required|array|min:1',
            'presensi.*.siswa_id' => ['required', Rule::exists('siswa', 'id')->where('kelas_id', $jadwal->kelas_id)],
            'presensi.*.status' => 'required|in:H,I,S,A',
            'presensi.*.keterangan' => 'nullable|string|max:255',
        ]);

        $tanggal = Carbon::parse($data['tanggal'])->toDateString();
        $now = now();
        Presensi::upsert(collect($data['presensi'])->map(fn ($p) => [
            'jadwal_id' => $jadwal->id, 'siswa_id' => $p['siswa_id'], 'tanggal' => $tanggal,
            'status_kehadiran' => $p['status'], 'keterangan' => $p['keterangan'] ?? null,
            'created_at' => $now, 'updated_at' => $now,
        ])->all(), ['jadwal_id', 'siswa_id', 'tanggal'], ['status_kehadiran', 'keterangan', 'updated_at']);

        $rekap = Presensi::where('jadwal_id', $jadwal->id)->where('tanggal', $tanggal)
            ->selectRaw('status_kehadiran, COUNT(*) as jumlah')->groupBy('status_kehadiran')->pluck('jumlah', 'status_kehadiran');

        return response()->json([
            'message' => 'Presensi tersimpan.',
            'rekap' => ['hadir' => (int) ($rekap['H'] ?? 0), 'sakit' => (int) ($rekap['S'] ?? 0), 'izin' => (int) ($rekap['I'] ?? 0), 'alpa' => (int) ($rekap['A'] ?? 0)],
        ]);
    }

    /** "Kelas 5A" => "5A" */
    public static function singkatan(string $namaKelas): string
    {
        $s = trim(preg_replace('/^kelas\s*/i', '', $namaKelas));

        return mb_strtoupper(mb_substr($s !== '' ? $s : $namaKelas, 0, 3));
    }
}
