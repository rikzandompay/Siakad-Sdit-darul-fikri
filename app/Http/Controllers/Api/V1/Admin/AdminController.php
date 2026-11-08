<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Guru\PresensiKelasController;
use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiRapot;
use App\Models\PengajuanIzin;
use App\Models\PengaturanSekolah;
use App\Models\Pengumuman;
use App\Models\Presensi;
use App\Models\PresensiGuru;
use App\Models\PresensiSholat;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Rapor;
use App\Support\Sekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/** Kepala sekolah / admin: pantau seluruh sekolah dan kelola data ringan dari aplikasi. */
class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $hariIni = Sekolah::now()->toDateString();
        $totalSiswa = Siswa::where('status', 'Aktif')->count();
        $hadir = Presensi::where('tanggal', $hariIni)->where('status_kehadiran', 'H')->distinct()->count('siswa_id');
        $tercatat = Presensi::where('tanggal', $hariIni)->distinct()->count('siswa_id');
        $guru = $this->guruAktif();
        $sudahAbsen = PresensiGuru::where('tanggal', $hariIni)->whereNotNull('jam_masuk')->whereIn('guru_id', $guru->pluck('id'))->count();

        return response()->json([
            'tanggal' => $hariIni,
            'siswa' => [
                'total' => $totalSiswa, 'hadir' => $hadir, 'tercatat' => $tercatat, 'kelas' => Kelas::count(),
                'persen_hadir' => $tercatat ? round($hadir / $tercatat * 100, 1) : null,
            ],
            'guru' => ['total' => $guru->count(), 'sudah_absen' => $sudahAbsen, 'belum' => max(0, $guru->count() - $sudahAbsen)],
            'izin_menunggu' => PengajuanIzin::where('status', PengajuanIzin::MENUNGGU)->count(),
        ]);
    }

    /** Pilihan filter: kelas, mapel, tahun ajaran. */
    public function opsi(): JsonResponse
    {
        return response()->json([
            'kelas' => Kelas::orderBy('nama_kelas')->get(['id', 'nama_kelas'])->map(fn ($k) => ['id' => $k->id, 'nama' => $k->nama_kelas]),
            'mapel' => MataPelajaran::orderBy('nama_pelajaran')->get(['id', 'nama_pelajaran'])->map(fn ($m) => ['id' => $m->id, 'nama' => $m->nama_pelajaran]),
            'tahun_ajaran' => TahunAjaran::orderByDesc('id')->get()->map(fn ($t) => ['id' => $t->id, 'nama' => $t->nama_semester, 'aktif' => $t->status_aktif === 'Y']),
        ]);
    }

    // ── Data ─────────────────────────────────────────────────────

    public function guru(Request $request): JsonResponse
    {
        $hariIni = Sekolah::now()->toDateString();
        $absen = PresensiGuru::where('tanggal', $hariIni)->get()->keyBy('guru_id');
        $q = trim((string) $request->input('q'));

        $guru = User::where('status', 'Aktif')
            ->whereNotIn('username', config('presensi_guru.kecualikan_username', []))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->whereRaw('LOWER(nama_lengkap) LIKE ?', ['%' . mb_strtolower($q) . '%'])
                        ->orWhere('nip', 'like', "%{$q}%");
                });
            })
            ->with(['jadwalPelajaran.mataPelajaran', 'kelasWali'])
            ->orderBy('nama_lengkap')
            ->get();

        return response()->json(['data' => $guru->map(fn ($g) => [
            'id' => $g->id, 'nama' => $g->nama_lengkap ?? $g->name, 'nip' => $g->nip, 'no_hp' => $g->no_hp,
            'inisial' => Sekolah::inisial($g->nama_lengkap ?? $g->name),
            'foto_url' => $g->fotoUrl(),
            'mata_pelajaran' => $g->jadwalPelajaran->pluck('mataPelajaran.nama_pelajaran')->filter()->unique()->values(),
            'wali_kelas' => $g->kelasWali->pluck('nama_kelas')->values(),
            'absen_hari_ini' => isset($absen[$g->id]) ? ['status' => $absen[$g->id]->status, 'jam_masuk' => $absen[$g->id]->jam_masuk ? substr($absen[$g->id]->jam_masuk, 0, 5) : null] : null,
        ])->values()]);
    }

    public function siswa(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q'));
        $siswa = Siswa::with('kelas')->where('status', 'Aktif')
            ->when($request->filled('kelas_id'), fn ($w) => $w->where('kelas_id', $request->input('kelas_id')))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nama_siswa', 'like', "%{$q}%")->orWhere('nisn', 'like', "%{$q}%")->orWhere('nis', 'like', "%{$q}%")))
            ->orderBy('nama_siswa')->paginate(30);

        return response()->json([
            'total' => $siswa->total(), 'halaman' => $siswa->currentPage(), 'halaman_terakhir' => $siswa->lastPage(),
            'kelas' => Kelas::count(),
            'data' => collect($siswa->items())->map(fn ($s) => [
                'id' => $s->id, 'nama' => $s->nama_siswa, 'nis' => $s->nis, 'nisn' => $s->nisn,
                'kelas' => $s->kelas?->nama_kelas, 'nama_wali' => $s->nama_wali, 'no_hp_wali' => $s->no_hp_wali,
            ]),
        ]);
    }

    public function kelas(): JsonResponse
    {
        return response()->json(['data' => Kelas::with(['waliKelas', 'jadwalPelajaran.mataPelajaran', 'jadwalPelajaran.guru'])->withCount('siswaAktif')->orderBy('nama_kelas')->get()
            ->map(fn ($k) => [
                'id' => $k->id, 'nama' => $k->nama_kelas, 'singkatan' => PresensiKelasController::singkatan($k->nama_kelas),
                'wali_kelas' => $k->waliKelas?->nama_lengkap, 'jumlah_siswa' => $k->siswa_aktif_count,
                'mapel' => $k->jadwalPelajaran->groupBy('pelajaran_id')->map(fn ($j) => [
                    'nama' => $j->first()->mataPelajaran?->nama_pelajaran,
                    'guru' => $j->pluck('guru.nama_lengkap')->filter()->unique()->join(', '),
                    'sesi' => $j->count(),
                ])->values(),
            ])]);
    }

    // ── Pengumuman ───────────────────────────────────────────────

    public function pengumuman(): JsonResponse
    {
        return response()->json(['data' => Pengumuman::orderByDesc('penting')->latest()->limit(100)->get()->map(fn ($p) => $this->dataPengumuman($p))]);
    }

    public function simpanPengumuman(Request $request, ?Pengumuman $pengumuman = null): JsonResponse
    {
        $data = $request->validate([
            'judul' => 'required|string|max:150', 'isi' => 'required|string|max:5000',
            'sasaran' => ['required', Rule::in(Pengumuman::SASARAN)], 'penting' => 'boolean',
        ]);
        $data['penting'] = $request->boolean('penting');
        if ($pengumuman?->exists) {
            $pengumuman->update($data);
        } else {
            $pengumuman = Pengumuman::create($data + ['dibuat_oleh' => $request->user()->id]);
        }

        return response()->json(['message' => 'Pengumuman tersimpan.', 'data' => $this->dataPengumuman($pengumuman)], 201);
    }

    public function hapusPengumuman(Pengumuman $pengumuman): JsonResponse
    {
        $pengumuman->delete();

        return response()->json(['message' => 'Pengumuman dihapus.']);
    }

    // ── Rekap ────────────────────────────────────────────────────

    public function rekapAbsensi(Request $request): JsonResponse
    {
        [$kelas, $rentang, $label] = $this->filterRekap($request);
        $request->validate(['pelajaran_id' => 'nullable|exists:mata_pelajaran,id']);
        $jadwalIds = JadwalPelajaran::where('kelas_id', $kelas->id)
            ->when($request->filled('pelajaran_id'), fn ($q) => $q->where('pelajaran_id', $request->input('pelajaran_id')))->pluck('id');
        $hitung = Presensi::whereIn('jadwal_id', $jadwalIds)->whereBetween('tanggal', $rentang)
            ->selectRaw('siswa_id, status_kehadiran, COUNT(*) as jumlah')->groupBy('siswa_id', 'status_kehadiran')->get()->groupBy('siswa_id');

        return response()->json($this->hasilRekap($kelas, $label, fn ($s) => collect(['H', 'S', 'I', 'A'])->mapWithKeys(
            fn ($k) => [$k => (int) optional($hitung->get($s->id)?->firstWhere('status_kehadiran', $k))->jumlah]
        )));
    }

    public function rekapSholat(Request $request): JsonResponse
    {
        [$kelas, $rentang, $label] = $this->filterRekap($request);
        $jenis = PresensiSholat::normalizeJenis($request->input('jenis'));
        $hitung = PresensiSholat::where('kelas_id', $kelas->id)->where('jenis_sholat', $jenis)->whereBetween('tanggal', $rentang)
            ->selectRaw('siswa_id, status_kehadiran, COUNT(*) as jumlah')->groupBy('siswa_id', 'status_kehadiran')->get()->groupBy('siswa_id');

        return response()->json(['jenis' => $jenis] + $this->hasilRekap($kelas, $label, fn ($s) => [
            'hadir' => (int) optional($hitung->get($s->id)?->firstWhere('status_kehadiran', PresensiSholat::HADIR))->jumlah,
            'tidak_hadir' => (int) optional($hitung->get($s->id)?->firstWhere('status_kehadiran', PresensiSholat::TIDAK_HADIR))->jumlah,
        ]));
    }

    public function rekapNilai(Request $request): JsonResponse
    {
        $data = $request->validate(['kelas_id' => 'required|exists:kelas,id', 'tahun_ajaran_id' => 'nullable|exists:tahun_ajaran,id']);
        $kelas = Kelas::findOrFail($data['kelas_id']);
        $ta = isset($data['tahun_ajaran_id']) ? TahunAjaran::find($data['tahun_ajaran_id']) : TahunAjaran::getAktif();
        $siswa = $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();
        $nilai = $ta ? NilaiRapot::with('mataPelajaran')->where('tahun_ajaran_id', $ta->id)->whereIn('siswa_id', $siswa->pluck('id'))->get()->groupBy('siswa_id') : collect();

        return response()->json([
            'kelas' => $kelas->nama_kelas, 'tahun_ajaran' => $ta?->nama_semester, 'kkm' => Rapor::KKM,
            'data' => $siswa->map(function ($s) use ($nilai) {
                $list = $nilai->get($s->id, collect());
                $rata = $list->whereNotNull('nilai_rapor')->avg('nilai_rapor');

                return [
                    'siswa_id' => $s->id, 'nama' => $s->nama_siswa, 'nis' => $s->nis,
                    'rata_rata' => $rata === null ? null : round($rata, 1), 'predikat' => Rapor::predikat($rata),
                    'mapel' => $list->sortBy('mataPelajaran.nama_pelajaran')->map(fn ($n) => [
                        'nama' => $n->mataPelajaran?->nama_pelajaran, 'kode' => $n->mataPelajaran?->kode_pelajaran,
                        'nilai' => $n->nilai_rapor === null ? null : round((float) $n->nilai_rapor, 1),
                    ])->values(),
                ];
            }),
        ]);
    }

    public function rekapGuru(Request $request): JsonResponse
    {
        $request->validate(['rentang' => 'nullable|in:hari_ini,minggu_ini,bulan_ini,semester_ini']);
        $rentangNama = $request->input('rentang', 'bulan_ini');
        $range = $this->periodeRange($rentangNama, Sekolah::now()->toDateString());
        $guru = $this->guruAktif()->load(['jadwalPelajaran.mataPelajaran', 'kelasWali']);
        $jamMasuk = PengaturanSekolah::jamMasuk().':59';
        $hitung = PresensiGuru::toBase()->whereBetween('tanggal', [$range['start']->toDateString(), $range['end']->toDateString()])
            ->selectRaw('guru_id,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = ? AND jam_masuk > ? THEN 1 ELSE 0 END) as telat,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as tidak',
                [PresensiGuru::HADIR, PresensiGuru::HADIR, $jamMasuk, PresensiGuru::TIDAK_HADIR])
            ->groupBy('guru_id')->get()->keyBy('guru_id');

        $data = $guru->map(function ($g) use ($hitung) {
            $h = $hitung->get($g->id);
            $hadir = (int) ($h->hadir ?? 0);
            $tidak = (int) ($h->tidak ?? 0);

            return [
                'id' => $g->id, 'nama' => $g->nama_lengkap ?? $g->name,
                'keterangan' => collect([$g->jadwalPelajaran->pluck('mataPelajaran.nama_pelajaran')->filter()->unique()->join(', '), $g->kelasWali->pluck('nama_kelas')->map(fn ($k) => "Wali $k")->join(', ')])->filter()->join(' · '),
                'hadir' => $hadir, 'terlambat' => (int) ($h->telat ?? 0), 'tidak_hadir' => $tidak,
                'persen' => $hadir + $tidak ? (int) round($hadir / ($hadir + $tidak) * 100) : null,
            ];
        })->values();

        return response()->json([
            'periode' => $this->periodeLabel($rentangNama, Sekolah::now()->toDateString()),
            'total' => ['hadir' => $data->sum('hadir'), 'terlambat' => $data->sum('terlambat'), 'tidak_hadir' => $data->sum('tidak_hadir')],
            'data' => $data,
        ]);
    }

    // ── Tahun ajaran & mapel ─────────────────────────────────────

    public function tahunAjaran(): JsonResponse
    {
        $adaNilai = NilaiRapot::distinct()->pluck('tahun_ajaran_id')->all();

        return response()->json(['data' => TahunAjaran::orderByDesc('id')->get()->map(fn ($t) => [
            'id' => $t->id, 'nama' => $t->nama_semester, 'aktif' => $t->status_aktif === 'Y',
            'bisa_dihapus' => $t->status_aktif !== 'Y' && ! in_array($t->id, $adaNilai),
        ] + Rapor::pecah($t))]);
    }

    public function simpanTahunAjaran(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tahun' => ['required', 'regex:/^\d{4}\/\d{4}$/'], 'semester' => 'required|in:Ganjil,Genap', 'aktif' => 'boolean',
        ], ['tahun.regex' => 'Format tahun: 2026/2027.']);
        $nama = "{$data['tahun']} - {$data['semester']}";
        abort_if(TahunAjaran::where('nama_semester', $nama)->exists(), 422, "Tahun ajaran {$nama} sudah ada.");
        $ta = TahunAjaran::create(['nama_semester' => $nama, 'status_aktif' => 'N']);
        if ($request->boolean('aktif')) {
            $ta->setSemesterAktif();
        }
        $this->bersihkanCache();

        return response()->json(['message' => 'Tahun ajaran ditambahkan.', 'id' => $ta->id], 201);
    }

    public function aktifkanTahunAjaran(TahunAjaran $tahunAjaran): JsonResponse
    {
        $tahunAjaran->setSemesterAktif();
        $this->bersihkanCache();

        return response()->json(['message' => "{$tahunAjaran->nama_semester} sekarang aktif."]);
    }

    public function hapusTahunAjaran(TahunAjaran $tahunAjaran): JsonResponse
    {
        abort_if($tahunAjaran->status_aktif === 'Y', 422, 'Semester aktif tidak bisa dihapus.');
        abort_if($tahunAjaran->nilaiRapot()->exists(), 422, 'Semester ini sudah punya nilai rapor, jadi tidak bisa dihapus.');
        $tahunAjaran->delete();
        $this->bersihkanCache();

        return response()->json(['message' => 'Tahun ajaran dihapus.']);
    }

    public function mapel(): JsonResponse
    {
        $dipakai = JadwalPelajaran::selectRaw('pelajaran_id, COUNT(*) as jumlah')->groupBy('pelajaran_id')->pluck('jumlah', 'pelajaran_id');

        return response()->json(['data' => MataPelajaran::orderBy('nama_pelajaran')->get()->map(fn ($m) => [
            'id' => $m->id, 'kode' => $m->kode_pelajaran, 'nama' => $m->nama_pelajaran, 'jadwal' => (int) ($dipakai[$m->id] ?? 0),
        ])]);
    }

    public function simpanMapel(Request $request): JsonResponse
    {
        // Kode disimpan huruf besar; samakan sebelum cek unik agar "mtk" bentrok dengan "MTK".
        $request->merge(['kode' => strtoupper(trim((string) $request->input('kode')))]);
        $data = $request->validate([
            'kode' => 'required|string|max:20|unique:mata_pelajaran,kode_pelajaran', 'nama' => 'required|string|max:100',
        ], ['kode.unique' => 'Kode pelajaran sudah dipakai.']);
        $m = MataPelajaran::create(['kode_pelajaran' => $data['kode'], 'nama_pelajaran' => $data['nama']]);
        $this->bersihkanCache();

        return response()->json(['message' => 'Mata pelajaran ditambahkan.', 'id' => $m->id], 201);
    }

    public function hapusMapel(MataPelajaran $mapel): JsonResponse
    {
        abort_if(JadwalPelajaran::where('pelajaran_id', $mapel->id)->exists(), 422, 'Mata pelajaran masih dipakai di jadwal.');
        $mapel->delete();
        $this->bersihkanCache();

        return response()->json(['message' => 'Mata pelajaran dihapus.']);
    }

    // ── Bantuan ──────────────────────────────────────────────────

    private function guruAktif(): Collection
    {
        return User::where('status', 'Aktif')->whereNotIn('username', config('presensi_guru.kecualikan_username'))->orderBy('nama_lengkap')->get();
    }

    /** @return array{0: Kelas, 1: array, 2: string} */
    private function filterRekap(Request $request): array
    {
        $data = $request->validate(['kelas_id' => 'required|exists:kelas,id', 'rentang' => 'nullable|in:hari_ini,minggu_ini,bulan_ini,semester_ini']);
        $rentang = $data['rentang'] ?? 'bulan_ini';
        $range = $this->periodeRange($rentang, Sekolah::now()->toDateString());

        return [Kelas::findOrFail($data['kelas_id']), [$range['start']->toDateString(), $range['end']->toDateString()], $this->periodeLabel($rentang, Sekolah::now()->toDateString())];
    }

    private function hasilRekap(Kelas $kelas, string $periode, callable $hitung): array
    {
        $siswa = $kelas->siswa()->where('status', 'Aktif')->orderBy('nama_siswa')->get();

        return [
            'kelas' => $kelas->nama_kelas, 'periode' => $periode, 'jumlah_siswa' => $siswa->count(),
            'data' => $siswa->map(fn ($s) => ['siswa_id' => $s->id, 'nama' => $s->nama_siswa, 'nis' => $s->nis, 'rekap' => $hitung($s)])->values(),
        ];
    }

    private function dataPengumuman(Pengumuman $p): array
    {
        return ['id' => $p->id, 'judul' => $p->judul, 'isi' => $p->isi, 'sasaran' => $p->sasaran, 'penting' => $p->penting, 'tanggal' => $p->created_at->toDateString()];
    }

    private function bersihkanCache(): void
    {
        // Cache yang dipakai halaman Pengaturan di web.
        Cache::forget('tahun_ajaran_list');
        Cache::forget('mapel_list_all');
    }
}
