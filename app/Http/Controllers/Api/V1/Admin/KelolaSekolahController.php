<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\PengaturanSekolah;
use App\Models\PresensiGuru;
use App\Models\User;
use App\Models\WajahGuru;
use App\Models\Wali;
use App\Support\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Pengelolaan sekolah untuk kepala sekolah di aplikasi: aturan sama dengan halaman web
 * (Pengaturan Sekolah, Pengampu Konseling, koreksi absen guru, akun wali).
 */
class KelolaSekolahController extends Controller
{
    // ── Pengaturan sekolah ───────────────────────────────────────────

    public function pengaturan(): JsonResponse
    {
        $lokasi = PengaturanSekolah::lokasi();

        return response()->json([
            'latitude' => $lokasi['latitude'] ?? null,
            'longitude' => $lokasi['longitude'] ?? null,
            'radius_meter' => PengaturanSekolah::radiusMeter(),
            'jam_masuk' => PengaturanSekolah::jamMasuk(),
            'jam_pulang' => PengaturanSekolah::jamPulang(),
            'libur' => HariLibur::orderByDesc('tanggal')->limit(60)->get()->map(fn ($l) => [
                'id' => $l->id, 'tanggal' => Carbon::parse($l->tanggal)->toDateString(), 'keterangan' => $l->keterangan,
            ]),
        ]);
    }

    public function simpanPengaturan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
            'radius_meter' => 'required|integer|between:20,2000',
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_pulang' => ['required', 'date_format:H:i', 'after:jam_masuk'],
        ]);

        PengaturanSekolah::simpan([
            PengaturanSekolah::LATITUDE => $data['latitude'] ?? null,
            PengaturanSekolah::LONGITUDE => $data['longitude'] ?? null,
            PengaturanSekolah::RADIUS => $data['radius_meter'],
            PengaturanSekolah::JAM_MASUK => $data['jam_masuk'],
            PengaturanSekolah::JAM_PULANG => $data['jam_pulang'],
        ]);

        return response()->json(['message' => 'Pengaturan sekolah disimpan.']);
    }

    public function tambahLibur(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => 'required|date|unique:hari_libur,tanggal',
            'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal',
            'keterangan' => 'required|string|max:150',
        ], ['tanggal.unique' => 'Tanggal ini sudah terdaftar sebagai hari libur.']);

        $mulai = Carbon::parse($data['tanggal']);
        $akhir = Carbon::parse($data['tanggal_akhir'] ?? $data['tanggal']);
        abort_if($mulai->diffInDays($akhir) > 60, 422, 'Maksimal 60 hari sekali input.');

        for ($d = $mulai->copy(); $d->lte($akhir); $d->addDay()) {
            HariLibur::firstOrCreate(['tanggal' => $d->toDateString()], ['keterangan' => $data['keterangan']]);
        }

        return response()->json(['message' => 'Hari libur ditambahkan.'], 201);
    }

    public function hapusLibur(HariLibur $libur): JsonResponse
    {
        $libur->delete();

        return response()->json(['message' => 'Hari libur dihapus.']);
    }

    // ── Pengampu konseling ───────────────────────────────────────────

    public function pengampu(): JsonResponse
    {
        return response()->json([
            'kelas' => Kelas::with('waliKelas')->withCount('siswaAktif')->orderBy('nama_kelas')->get()->map(fn ($k) => [
                'id' => $k->id, 'nama' => $k->nama_kelas, 'jumlah_siswa' => $k->siswa_aktif_count,
                'wali_kelas' => $k->waliKelas?->nama_lengkap ?? $k->waliKelas?->name,
                'pengampu_id' => $k->pengampu_konseling_id,
            ]),
            'guru' => $this->guruAktif()->map(fn ($g) => ['id' => $g->id, 'nama' => $g->nama_lengkap ?? $g->name]),
        ]);
    }

    public function simpanPengampu(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pengampu' => 'required|array',
            'pengampu.*' => ['nullable', Rule::exists('users', 'id')->where('status', 'Aktif')],
        ]);

        $kelasIds = Kelas::pluck('id')->all();
        DB::transaction(function () use ($data, $kelasIds) {
            foreach ($data['pengampu'] as $kelasId => $guruId) {
                if (in_array((int) $kelasId, $kelasIds, true)) {
                    Kelas::whereKey($kelasId)->update(['pengampu_konseling_id' => $guruId ?: null]);
                }
            }
        });

        return response()->json(['message' => 'Pengampu konseling disimpan.']);
    }

    // ── Koreksi absen guru & data wajah ──────────────────────────────

    /** Riwayat absen satu guru (30 hari terakhir) untuk dikoreksi. */
    public function riwayatAbsenGuru(User $guru): JsonResponse
    {
        $this->pastikanGuru($guru);
        $mulai = Sekolah::now()->subDays(29)->toDateString();
        $data = PresensiGuru::where('guru_id', $guru->id)->where('tanggal', '>=', $mulai)->orderByDesc('tanggal')->get()
            ->map(fn ($p) => [
                'tanggal' => Carbon::parse($p->tanggal)->toDateString(),
                'status' => $p->status,
                'jam_masuk' => $p->jam_masuk ? substr($p->jam_masuk, 0, 5) : null,
                'jam_pulang' => $p->jam_pulang ? substr($p->jam_pulang, 0, 5) : null,
                'terlambat' => $p->terlambat,
                'sumber' => $p->sumber,
                'keterangan' => $p->keterangan,
            ]);

        return response()->json([
            'guru' => ['id' => $guru->id, 'nama' => $guru->nama_lengkap ?? $guru->name, 'wajah_terdaftar' => $guru->wajah()->exists()],
            'data' => $data,
        ]);
    }

    public function koreksiAbsenGuru(Request $request): JsonResponse
    {
        $data = $request->validate([
            'guru_id' => ['required', Rule::in($this->guruAktif()->pluck('id')->all())],
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

        return response()->json(['message' => 'Absen guru dikoreksi.']);
    }

    public function resetWajah(User $guru): JsonResponse
    {
        $this->pastikanGuru($guru);
        $guru->hapusWajah();

        return response()->json(['message' => 'Data wajah dihapus. Guru perlu mendaftarkan wajah lagi di aplikasi.']);
    }

    /** Foto profil yang menunggu persetujuan dan pasangan guru yang wajahnya sangat mirip. */
    public function fotoPengajuan(): JsonResponse
    {
        return response()->json([
            'data' => User::whereNotNull('foto_pengajuan')->orderBy('foto_pengajuan_pada')->get()->map(fn (User $g) => [
                'id' => $g->id,
                'nama' => $g->nama_lengkap ?? $g->name,
                'inisial' => Sekolah::inisial($g->nama_lengkap ?? $g->name),
                'foto_url' => $g->fotoUrl(),
                'foto_pengajuan_url' => $g->fotoPengajuanUrl(),
                'diajukan' => $g->foto_pengajuan_pada?->toIso8601String(),
            ])->values(),
            'wajah_mirip' => WajahGuru::with(['guru', 'miripGuru'])->whereNotNull('mirip_guru_id')->get()
                ->filter(fn (WajahGuru $w) => $w->guru_id < $w->mirip_guru_id)
                ->map(fn (WajahGuru $w) => [
                    'guru' => $w->guru?->nama_lengkap ?? $w->guru?->name,
                    'mirip_dengan' => $w->miripGuru?->nama_lengkap ?? $w->miripGuru?->name,
                    'skor' => $w->mirip_skor,
                ])->values(),
        ]);
    }

    public function setujuiFoto(User $guru): JsonResponse
    {
        abort_unless($guru->foto_pengajuan, 409, 'Tidak ada foto yang menunggu persetujuan.');
        $guru->setujuiFotoPengajuan();

        return response()->json(['message' => 'Foto disetujui. Guru perlu mendaftarkan wajah lagi di aplikasi.']);
    }

    public function tolakFoto(Request $request, User $guru): JsonResponse
    {
        abort_unless($guru->foto_pengajuan, 409, 'Tidak ada foto yang menunggu persetujuan.');
        $data = $request->validate(['catatan' => 'nullable|string|max:200']);
        $guru->tolakFotoPengajuan($data['catatan'] ?? null);

        return response()->json(['message' => 'Foto ditolak.']);
    }

    // ── Akun wali ────────────────────────────────────────────────────

    public function wali(Request $request): JsonResponse
    {
        $cari = trim((string) $request->query('q'));
        $hasil = Wali::with('siswa.kelas')
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('username', 'like', "%{$cari}%")
                ->orWhereHas('siswa', fn ($s) => $s->where('nama_siswa', 'like', "%{$cari}%"))))
            ->orderBy('nama')->paginate(30);

        return response()->json([
            'total' => $hasil->total(),
            'halaman' => $hasil->currentPage(),
            'halaman_terakhir' => $hasil->lastPage(),
            'data' => $hasil->getCollection()->map(fn ($w) => [
                'id' => $w->id, 'nama' => $w->nama, 'login_nis' => $w->username, 'no_hp' => $w->no_hp,
                'aktif' => $w->isAktif(), 'terakhir_login' => $w->terakhir_login_pada?->toIso8601String(),
                'anak' => $w->siswa->map(fn ($s) => ['nama' => $s->nama_siswa, 'kelas' => $s->kelas?->nama_kelas])->values(),
            ])->values(),
        ]);
    }

    public function sinkronWali(): JsonResponse
    {
        return response()->json(['message' => 'Akun wali disinkronkan dari data siswa ('.Wali::sinkronSemua().' akun).']);
    }

    private function guruAktif()
    {
        return User::where('status', 'Aktif')->whereNotIn('username', config('presensi_guru.kecualikan_username'))->orderBy('nama_lengkap')->get();
    }

    private function pastikanGuru(User $guru): void
    {
        abort_unless($this->guruAktif()->contains('id', $guru->id), 404, 'Guru tidak ditemukan.');
    }
}
