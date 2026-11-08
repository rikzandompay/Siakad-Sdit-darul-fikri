<?php

namespace App\Http\Controllers\Api\V1\Wali;

use App\Http\Controllers\Api\V1\Guru\PresensiKelasController;
use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\NilaiRapot;
use App\Models\NilaiTugas;
use App\Models\PengajuanIzin;
use App\Models\Pengumuman;
use App\Models\Presensi;
use App\Models\PresensiSholat;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use App\Support\Rapor;
use App\Support\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Endpoint wali murid. Semua data dibatasi pada anak yang terhubung ke akun wali
 * (parameter siswa_id; default anak pertama).
 */
class WaliController extends Controller
{
    /** Anak yang diminta, dan dipastikan milik wali yang login. */
    protected function anak(Request $request): Siswa
    {
        $wali = $request->user();
        $id = $request->input('siswa_id');
        $siswa = $id ? $wali->siswa()->whereKey($id)->first() : $wali->siswa()->orderBy('nama_siswa')->first();
        abort_unless($siswa, 404, 'Data anak tidak ditemukan.');

        return $siswa->load('kelas.waliKelas');
    }

    public function dashboard(Request $request): JsonResponse
    {
        $siswa = $this->anak($request);
        $now = Sekolah::now();
        $hariIni = $now->toDateString();

        $statusKelas = Presensi::where('siswa_id', $siswa->id)->where('tanggal', $hariIni)->pluck('status_kehadiran');
        $sholat = PresensiSholat::where('siswa_id', $siswa->id)->where('tanggal', $hariIni)->pluck('status_kehadiran', 'jenis_sholat');

        $tugas = $siswa->kelas_id ? Tugas::with(['mataPelajaran', 'guru'])->where('kelas_id', $siswa->kelas_id)
            ->where(fn ($q) => $q->whereNull('tenggat')->orWhere('tenggat', '>=', $hariIni))
            ->orderByRaw('tenggat IS NULL')->orderBy('tenggat')->first() : null;
        $pengumuman = Pengumuman::whereIn('sasaran', [Pengumuman::SEMUA, Pengumuman::WALI])->orderByDesc('penting')->latest()->first();

        return response()->json([
            'tanggal' => $hariIni,
            'anak' => self::dataAnak($siswa),
            'aktivitas' => [
                'kehadiran' => $statusKelas->isEmpty() ? null : self::statusHari($statusKelas->all()),
                'sholat' => collect(PresensiSholat::JENIS)->map(fn ($j) => [
                    'jenis' => $j,
                    'status' => $sholat[$j] ?? null,
                    'sudah_masuk_waktu' => $now->format('H:i') >= config("sholat.waktu.{$j}"),
                ])->values(),
            ],
            'tugas' => $tugas ? $this->dataTugas($tugas, $siswa) : null,
            'pengumuman' => $pengumuman ? $this->dataPengumuman($pengumuman) : null,
        ]);
    }

    /** Rekap kehadiran kelas + sholat per bulan. */
    public function kehadiran(Request $request): JsonResponse
    {
        $request->validate(['bulan' => 'nullable|date_format:Y-m']);
        $siswa = $this->anak($request);
        $bulan = $request->filled('bulan') ? Carbon::createFromFormat('Y-m', $request->input('bulan'))->startOfMonth() : Sekolah::now()->startOfMonth();
        $rentang = [$bulan->toDateString(), $bulan->copy()->endOfMonth()->toDateString()];

        $kelas = Presensi::where('siswa_id', $siswa->id)->whereBetween('tanggal', $rentang)->get(['tanggal', 'status_kehadiran'])
            ->groupBy(fn ($p) => Carbon::parse($p->tanggal)->toDateString());
        $sholat = PresensiSholat::where('siswa_id', $siswa->id)->whereBetween('tanggal', $rentang)->whereIn('jenis_sholat', PresensiSholat::JENIS)
            ->get(['tanggal', 'jenis_sholat', 'status_kehadiran'])->groupBy(fn ($p) => Carbon::parse($p->tanggal)->toDateString());

        $tanggal = $kelas->keys()->merge($sholat->keys())->unique()->sortDesc()->values();
        $harian = $tanggal->map(fn ($t) => [
            'tanggal' => $t,
            'status' => isset($kelas[$t]) ? self::statusHari($kelas[$t]->pluck('status_kehadiran')->all()) : null,
            'sholat' => isset($sholat[$t]) ? $sholat[$t]->pluck('status_kehadiran', 'jenis_sholat') : (object) [],
        ]);
        $hitung = $harian->pluck('status')->filter()->countBy();
        $total = $hitung->sum();

        return response()->json([
            'bulan' => $bulan->format('Y-m'),
            'persen_hadir' => $total ? round(($hitung['Hadir'] ?? 0) / $total * 100, 1) : null,
            'hadir' => $hitung['Hadir'] ?? 0, 'sakit' => $hitung['Sakit'] ?? 0, 'izin' => $hitung['Izin'] ?? 0, 'alpa' => $hitung['Alpa'] ?? 0,
            'data' => $harian,
        ]);
    }

    /** Jadwal seminggu + persiapan tas untuk hari sekolah berikutnya. */
    public function jadwal(Request $request): JsonResponse
    {
        $siswa = $this->anak($request);
        $jadwal = $siswa->kelas_id ? JadwalPelajaran::with(['mataPelajaran', 'guru'])->where('kelas_id', $siswa->kelas_id)->orderBy('jam_mulai')->get() : collect();

        $besok = Sekolah::now()->addDay();
        while (! Sekolah::hariSekolah($besok) && $besok->diffInDays(Sekolah::now()) < 10) {
            $besok->addDay();
        }
        $hariBesok = Sekolah::hari($besok);
        $map = fn ($j) => [
            'mata_pelajaran' => $j->mataPelajaran?->nama_pelajaran, 'guru' => $j->guru?->nama_lengkap,
            'jam_mulai' => substr($j->jam_mulai, 0, 5), 'jam_selesai' => substr($j->jam_selesai, 0, 5),
        ];

        return response()->json([
            'kelas' => $siswa->kelas?->nama_kelas,
            'besok' => [
                'tanggal' => $besok->toDateString(), 'hari' => $hariBesok,
                'jadwal' => $jadwal->where('hari', $hariBesok)->map($map)->values(),
                'bawa' => $jadwal->where('hari', $hariBesok)->pluck('mataPelajaran.nama_pelajaran')->filter()->unique()->values(),
            ],
            'minggu' => collect(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])
                ->mapWithKeys(fn ($h) => [$h => $jadwal->where('hari', $h)->map($map)->values()]),
        ]);
    }

    public function pengumuman(): JsonResponse
    {
        return response()->json(['data' => Pengumuman::whereIn('sasaran', [Pengumuman::SEMUA, Pengumuman::WALI])
            ->orderByDesc('penting')->latest()->limit(50)->get()->map(fn ($p) => $this->dataPengumuman($p))]);
    }

    public function tugas(Request $request): JsonResponse
    {
        $siswa = $this->anak($request);
        $tugas = $siswa->kelas_id ? Tugas::with(['mataPelajaran', 'guru'])->where('kelas_id', $siswa->kelas_id)->latest()->limit(50)->get() : collect();

        return response()->json(['data' => $tugas->map(fn ($t) => $this->dataTugas($t, $siswa))]);
    }

    // ── Izin ─────────────────────────────────────────────────────

    public function izin(Request $request): JsonResponse
    {
        $siswa = $this->anak($request);

        return response()->json(['data' => PengajuanIzin::where('siswa_id', $siswa->id)->latest()->limit(30)->get()->map(fn ($i) => [
            'id' => $i->id, 'jenis' => $i->jenis, 'status' => $i->status,
            'tanggal_mulai' => $i->tanggal_mulai->toDateString(), 'tanggal_selesai' => $i->tanggal_selesai->toDateString(),
            'alasan' => $i->alasan, 'catatan_peninjau' => $i->catatan_peninjau, 'ada_lampiran' => (bool) $i->lampiran,
        ])]);
    }

    public function ajukanIzin(Request $request): JsonResponse
    {
        $siswa = $this->anak($request);
        $data = $request->validate([
            'jenis' => ['required', Rule::in(PengajuanIzin::JENIS)],
            'tanggal_mulai' => 'required|date|after_or_equal:'.Sekolah::now()->subDays(7)->toDateString(),
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai|before_or_equal:'.Sekolah::now()->addDays(30)->toDateString(),
            'alasan' => 'required|string|max:1000',
            'lampiran' => 'nullable|image|max:2048',
        ], ['lampiran.max' => 'Foto maksimal 2 MB.', 'tanggal_mulai.after_or_equal' => 'Izin hanya bisa diajukan paling lama 7 hari ke belakang.']);

        $izin = PengajuanIzin::create([
            'siswa_id' => $siswa->id, 'wali_id' => $request->user()->id, 'jenis' => $data['jenis'], 'status' => PengajuanIzin::MENUNGGU,
            'tanggal_mulai' => $data['tanggal_mulai'], 'tanggal_selesai' => $data['tanggal_selesai'], 'alasan' => $data['alasan'],
            'lampiran' => $request->file('lampiran')?->store('izin', 'local'),
        ]);

        return response()->json([
            'message' => 'Izin terkirim ke wali kelas.',
            'izin' => ['id' => $izin->id, 'jenis' => $izin->jenis, 'status' => $izin->status],
            'anak' => $siswa->nama_siswa, 'kelas' => $siswa->kelas?->nama_kelas,
        ], 201);
    }

    // ── Rapor ────────────────────────────────────────────────────

    /** Daftar tahun ajaran yang punya nilai, dikelompokkan per tahun (Ganjil/Genap). */
    public function rapor(Request $request): JsonResponse
    {
        $siswa = $this->anak($request);
        $rata = NilaiRapot::where('siswa_id', $siswa->id)->whereNotNull('nilai_rapor')
            ->selectRaw('tahun_ajaran_id, AVG(nilai_rapor) as rata, MIN(nilai_rapor) as terendah')->groupBy('tahun_ajaran_id')->get()->keyBy('tahun_ajaran_id');
        $aktif = TahunAjaran::getAktif();

        $tahun = TahunAjaran::whereIn('id', $rata->keys())->orderByDesc('id')->get()
            ->groupBy(fn ($ta) => Rapor::pecah($ta)['tahun'])
            ->map(fn ($list, $tahun) => [
                'tahun' => $tahun,
                'kelas_saat_ini' => $aktif && Rapor::pecah($aktif)['tahun'] === $tahun,
                'semester' => $list->sortBy('id')->map(fn ($ta) => [
                    'tahun_ajaran_id' => $ta->id, 'semester' => Rapor::pecah($ta)['semester'],
                    'rata_rata' => round((float) $rata[$ta->id]->rata, 2),
                    'tuntas' => (float) $rata[$ta->id]->terendah >= Rapor::KKM,
                ])->values(),
            ])->values();

        return response()->json(['kelas' => $siswa->kelas?->nama_kelas, 'data' => $tahun]);
    }

    public function raporSemester(Request $request, TahunAjaran $tahunAjaran): JsonResponse
    {
        $siswa = $this->anak($request);
        $nilai = NilaiRapot::with('mataPelajaran')->where('siswa_id', $siswa->id)->where('tahun_ajaran_id', $tahunAjaran->id)->get();
        $guru = $siswa->kelas_id ? JadwalPelajaran::with('guru')->where('kelas_id', $siswa->kelas_id)->get()->groupBy('pelajaran_id') : collect();
        $rata = $nilai->whereNotNull('nilai_rapor')->avg('nilai_rapor');

        return response()->json([
            'semester' => Rapor::pecah($tahunAjaran) + ['tahun_ajaran_id' => $tahunAjaran->id],
            'rata_rata' => $rata === null ? null : round($rata, 2),
            'predikat' => Rapor::predikat($rata), 'keterangan' => Rapor::keterangan(Rapor::predikat($rata)),
            'tuntas' => $nilai->isNotEmpty() && $nilai->every(fn ($n) => (float) $n->nilai_rapor >= Rapor::KKM),
            'kkm' => Rapor::KKM,
            'data' => $nilai->sortBy('mataPelajaran.nama_pelajaran')->map(fn ($n) => [
                'mata_pelajaran' => $n->mataPelajaran?->nama_pelajaran,
                'guru' => optional($guru->get($n->pelajaran_id)?->first()?->guru)->nama_lengkap,
                'nilai' => $n->nilai_rapor === null ? null : round((float) $n->nilai_rapor, 1),
                'predikat' => Rapor::predikat($n->nilai_rapor === null ? null : (float) $n->nilai_rapor),
                'tuntas' => (float) $n->nilai_rapor >= Rapor::KKM,
                'formatif' => round(NilaiRapot::calculateFormatifTotal($n->formatif_data ?: NilaiRapot::getDefaultFormatifData()), 1),
                'sas' => $n->sas === null ? null : round((float) $n->sas, 1),
                'kehadiran_persen' => Rapor::persenKehadiran($n->kehadiran === null ? null : (float) $n->kehadiran),
            ])->values(),
        ]);
    }

    /** Hasil studi dua semester (Ganjil & Genap) dalam satu tahun ajaran. */
    public function raporGrafik(Request $request): JsonResponse
    {
        $request->validate(['tahun' => 'required|string|max:20']);
        $siswa = $this->anak($request);
        $semester = TahunAjaran::orderBy('id')->get()->filter(fn ($ta) => Rapor::pecah($ta)['tahun'] === $request->input('tahun'))->values();
        $nilai = NilaiRapot::with('mataPelajaran')->where('siswa_id', $siswa->id)->whereIn('tahun_ajaran_id', $semester->pluck('id'))->get();

        return response()->json([
            'tahun' => $request->input('tahun'), 'kelas' => $siswa->kelas?->nama_kelas,
            'semester' => $semester->map(fn ($ta) => [
                'semester' => Rapor::pecah($ta)['semester'],
                'rata_rata' => ($r = $nilai->where('tahun_ajaran_id', $ta->id)->avg('nilai_rapor')) === null ? null : round($r, 2),
            ]),
            'mapel' => $nilai->groupBy('pelajaran_id')->map(fn ($list) => [
                'mata_pelajaran' => $list->first()->mataPelajaran?->nama_pelajaran,
                'nilai' => $semester->mapWithKeys(fn ($ta) => [Rapor::pecah($ta)['semester'] => optional($list->firstWhere('tahun_ajaran_id', $ta->id))->nilai_rapor === null ? null : round((float) $list->firstWhere('tahun_ajaran_id', $ta->id)->nilai_rapor, 1)]),
            ])->sortBy('mata_pelajaran')->values(),
        ]);
    }

    // ── Jadwal guru ──────────────────────────────────────────────

    public function guru(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q'));
        $guru = User::where('status', 'Aktif')->whereNotIn('username', config('presensi_guru.kecualikan_username'))
            ->when($q !== '', fn ($w) => $w->where('nama_lengkap', 'like', "%{$q}%"))
            ->with(['jadwalPelajaran.mataPelajaran', 'kelasWali'])->orderBy('nama_lengkap')->get();

        return response()->json(['data' => $guru->map(fn ($g) => [
            'id' => $g->id, 'nama' => $g->nama_lengkap ?? $g->name, 'inisial' => Sekolah::inisial($g->nama_lengkap ?? $g->name),
            'mata_pelajaran' => $g->jadwalPelajaran->pluck('mataPelajaran.nama_pelajaran')->filter()->unique()->values(),
            'wali_kelas' => $g->kelasWali->pluck('nama_kelas')->values(),
        ])]);
    }

    public function jadwalGuru(User $guru): JsonResponse
    {
        abort_unless($guru->status === 'Aktif', 404);
        $jadwal = JadwalPelajaran::with(['mataPelajaran', 'kelas'])->where('guru_id', $guru->id)->orderBy('jam_mulai')->get();

        return response()->json([
            'guru' => [
                'id' => $guru->id, 'nama' => $guru->nama_lengkap ?? $guru->name, 'inisial' => Sekolah::inisial($guru->nama_lengkap ?? $guru->name),
                'mata_pelajaran' => $jadwal->pluck('mataPelajaran.nama_pelajaran')->filter()->unique()->values(),
                'wali_kelas' => $guru->kelasWali()->pluck('nama_kelas'),
            ],
            'hari_ini' => Sekolah::hari(),
            'minggu' => collect(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])->mapWithKeys(fn ($h) => [$h => $jadwal->where('hari', $h)->map(fn ($j) => [
                'mata_pelajaran' => $j->mataPelajaran?->nama_pelajaran, 'kelas' => $j->kelas?->nama_kelas,
                'jam_mulai' => substr($j->jam_mulai, 0, 5), 'jam_selesai' => substr($j->jam_selesai, 0, 5),
            ])->values()]),
        ]);
    }

    // ── Profil ───────────────────────────────────────────────────

    public function ubahData(Request $request, Siswa $siswa): JsonResponse
    {
        abort_unless($request->user()->siswa()->whereKey($siswa->id)->exists(), 403);
        // Tanggal lahir = kata sandi login, hanya bisa diubah sekolah (tidak divalidasi = diabaikan).
        $data = $request->validate([
            'jenis_kelamin' => 'required|in:L,P',
            'alamat' => 'nullable|string|max:100',
            'nama_wali' => 'required|string|min:3|max:100',
            'no_hp_wali' => 'nullable|string|max:20',
        ]);
        $siswa->update($data);

        return response()->json(['message' => 'Data tersimpan.', 'anak' => self::dataAnak($siswa->fresh('kelas.waliKelas'))]);
    }

    // ── Bantuan ──────────────────────────────────────────────────

    /** Status satu hari dari beberapa jadwal: Hadir jika hadir di salah satu pelajaran. */
    public static function statusHari(array $kode): string
    {
        $label = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'];
        if (in_array('H', $kode, true)) {
            return 'Hadir';
        }
        $hitung = array_count_values($kode);
        arsort($hitung);

        return $label[array_key_first($hitung)] ?? 'Alpa';
    }

    public static function dataAnak(Siswa $s): array
    {
        return [
            'id' => $s->id, 'nama' => $s->nama_siswa, 'nis' => $s->nis, 'nisn' => $s->nisn,
            'kelas' => $s->kelas?->nama_kelas, 'kelas_singkat' => $s->kelas ? PresensiKelasController::singkatan($s->kelas->nama_kelas) : null,
            'wali_kelas' => $s->kelas?->waliKelas?->nama_lengkap, 'tanggal_lahir' => $s->tanggal_lahir ? Carbon::parse($s->tanggal_lahir)->toDateString() : null,
            'jenis_kelamin' => $s->jenis_kelamin, 'alamat' => $s->alamat, 'nama_wali' => $s->nama_wali, 'no_hp_wali' => $s->no_hp_wali,
            'foto_url' => $s->fotoUrl(),
            'inisial' => Sekolah::inisial($s->nama_siswa), 'qr' => $s->qr_payload,
        ];
    }

    public function uploadFotoSiswa(Request $request): JsonResponse
    {
        $siswa = $this->anak($request);
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $siswa->simpanFoto($request->file('foto'));

        return response()->json([
            'message' => 'Foto siswa berhasil diperbarui.',
            'foto_url' => $siswa->fotoUrl(),
        ]);
    }

    private function dataTugas(Tugas $t, Siswa $siswa): array
    {
        $nilai = NilaiTugas::where('tugas_id', $t->id)->where('siswa_id', $siswa->id)->first();

        return [
            'id' => $t->id, 'judul' => $t->judul, 'deskripsi' => $t->deskripsi,
            'mata_pelajaran' => $t->mataPelajaran?->nama_pelajaran, 'guru' => $t->guru?->nama_lengkap,
            'tenggat' => $t->tenggat?->toDateString(), 'nilai' => $nilai?->nilai, 'catatan' => $nilai?->catatan,
        ];
    }

    private function dataPengumuman(Pengumuman $p): array
    {
        return ['id' => $p->id, 'judul' => $p->judul, 'isi' => $p->isi, 'sasaran' => $p->sasaran, 'penting' => $p->penting, 'tanggal' => $p->created_at->toDateString()];
    }
}
