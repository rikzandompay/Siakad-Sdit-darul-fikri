<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\KonselingPercakapan;
use App\Models\KonselingPesan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Konseling wali murid <-> guru pengampu konseling kelas.
 * - Admin (Bu Sinta) menetapkan pengampu per kelas, tetapi tidak dapat membaca isi percakapan (privat).
 * - Pengampu membaca dan membalas percakapan siswa di kelas yang ia ampu.
 * Wali mengirim pesan dari aplikasi MyDarfik.
 */
class KonselingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->get('status', KonselingPercakapan::AKTIF);
        $search = trim((string) $request->get('search'));

        $percakapan = KonselingPercakapan::terlihatOleh($user)
            ->with(['siswa.kelas.pengampuKonseling', 'pesanTerakhir'])
            ->withCount('pesanWaliBelumDibaca')
            ->when(in_array($status, [KonselingPercakapan::AKTIF, KonselingPercakapan::SELESAI], true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('topik', 'like', "%{$search}%")
                ->orWhereHas('siswa', fn ($s) => $s->where('nama_siswa', 'like', "%{$search}%"))))
            ->orderByDesc('pesan_terakhir_pada')
            ->paginate(15)
            ->withQueryString();

        $kelasDiampu = Kelas::where('pengampu_konseling_id', $user->id)->orderBy('nama_kelas')->get();
        $kelasTanpaPengampu = $user->isAdmin() ? Kelas::whereNull('pengampu_konseling_id')->count() : 0;

        return view('konseling.index', compact('percakapan', 'status', 'search', 'kelasDiampu', 'kelasTanpaPengampu'));
    }

    public function show(Request $request, KonselingPercakapan $percakapan)
    {
        abort_unless($percakapan->bisaDibukaOleh($request->user()), 403);

        // Pengampu yang membuka percakapan menandai pesan wali sebagai dibaca.
        if ($percakapan->siswa?->kelas?->pengampu_konseling_id === $request->user()->id) {
            $percakapan->pesanWaliBelumDibaca()->update(['dibaca_pada' => now()]);
        }

        $percakapan->load(['siswa.kelas.pengampuKonseling', 'pesan.guru']);

        return view('konseling.show', compact('percakapan'));
    }

    public function create(Request $request)
    {
        $siswaList = $this->siswaBolehDihubungi($request->user())
            ->with('kelas')
            ->orderBy('nama_siswa')
            ->get();

        return view('konseling.create', compact('siswaList'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'siswa_id' => ['required', Rule::in($this->siswaBolehDihubungi($user)->pluck('id')->all())],
            'topik' => 'required|string|max:150',
            'isi' => 'required|string|max:2000',
        ], ['siswa_id.in' => 'Siswa ini tidak berada di kelas yang Anda ampu konselingnya.']);

        $percakapan = DB::transaction(function () use ($data, $user) {
            $percakapan = KonselingPercakapan::create([
                'siswa_id' => $data['siswa_id'],
                'topik' => $data['topik'],
                'status' => KonselingPercakapan::AKTIF,
                'pesan_terakhir_pada' => now(),
            ]);
            $percakapan->pesan()->create([
                'pengirim' => KonselingPesan::GURU,
                'guru_id' => $user->id,
                'isi' => $data['isi'],
            ]);

            return $percakapan;
        });

        return redirect()->route('konseling.show', $percakapan)->with('success', 'Percakapan dimulai. Wali murid akan menerima pesan di aplikasi MyDarfik.');
    }

    public function reply(Request $request, KonselingPercakapan $percakapan)
    {
        abort_unless($percakapan->bisaDibukaOleh($request->user()), 403);
        $data = $request->validate([
            'isi' => 'nullable|required_without:foto|string|max:2000',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], ['isi.required_without' => 'Tulis pesan atau lampirkan foto.']);
        $foto = $request->hasFile('foto') ? KonselingPesan::simpanFoto($request->file('foto')) : null;

        DB::transaction(function () use ($percakapan, $data, $request, $foto) {
            $percakapan->pesan()->create([
                'pengirim' => KonselingPesan::GURU,
                'guru_id' => $request->user()->id,
                'isi' => $data['isi'] ?? null,
                'foto' => $foto,
            ]);
            $percakapan->update(['pesan_terakhir_pada' => now(), 'status' => KonselingPercakapan::AKTIF]);
        });

        return redirect()->route('konseling.show', $percakapan);
    }

    public function updatePesan(Request $request, KonselingPercakapan $percakapan, KonselingPesan $pesan)
    {
        $this->pastikanPesanMilik($request, $percakapan, $pesan);
        $data = $request->validate([
            'isi' => [$pesan->foto ? 'nullable' : 'required', 'string', 'max:2000'],
        ]);
        $pesan->update(['isi' => $data['isi'] ?? null, 'diedit_pada' => now()]);

        return redirect()->route('konseling.show', $percakapan)->with('success', 'Pesan diperbarui.');
    }

    public function destroyPesan(Request $request, KonselingPercakapan $percakapan, KonselingPesan $pesan)
    {
        $this->pastikanPesanMilik($request, $percakapan, $pesan);
        $pesan->hapusBerkasFoto();
        $pesan->delete();

        return redirect()->route('konseling.show', $percakapan)->with('success', 'Pesan dihapus.');
    }

    /** Foto konseling disajikan hanya kepada pengampu kelas tersebut. */
    public function foto(Request $request, KonselingPercakapan $percakapan, KonselingPesan $pesan)
    {
        abort_unless($percakapan->bisaDibukaOleh($request->user()) && $pesan->percakapan_id === $percakapan->id && $pesan->foto, 404);

        return Storage::disk('local')->response($pesan->foto);
    }

    private function pastikanPesanMilik(Request $request, KonselingPercakapan $percakapan, KonselingPesan $pesan): void
    {
        abort_unless($percakapan->bisaDibukaOleh($request->user()) && $pesan->percakapan_id === $percakapan->id, 403);
        abort_unless($pesan->milikGuru($request->user()), 403, 'Hanya penulis pesan yang dapat mengubah atau menghapusnya.');
    }

    public function updateStatus(Request $request, KonselingPercakapan $percakapan)
    {
        abort_unless($percakapan->bisaDibukaOleh($request->user()), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in([KonselingPercakapan::AKTIF, KonselingPercakapan::SELESAI])],
        ]);

        $percakapan->update($data);

        return redirect()->route('konseling.show', $percakapan)->with('success', 'Status percakapan: '.$data['status'].'.');
    }

    // ── Pengampu konseling (admin) ─────────────────────────────────────

    public function pengampu()
    {
        $kelasList = Kelas::with(['pengampuKonseling', 'waliKelas'])->withCount('siswaAktif')->orderBy('nama_kelas')->get();
        $guruList = User::where('status', 'Aktif')
            ->whereNotIn('username', config('presensi_guru.kecualikan_username'))
            ->orderBy('nama_lengkap')
            ->get();

        return view('konseling.pengampu', compact('kelasList', 'guruList'));
    }

    public function updatePengampu(Request $request)
    {
        $data = $request->validate([
            'pengampu' => 'array',
            'pengampu.*' => ['nullable', Rule::exists('users', 'id')->where('status', 'Aktif')],
        ]);

        $kelasIds = Kelas::pluck('id')->all();
        DB::transaction(function () use ($data, $kelasIds) {
            foreach ($data['pengampu'] ?? [] as $kelasId => $guruId) {
                if (in_array((int) $kelasId, $kelasIds, true)) {
                    Kelas::whereKey($kelasId)->update(['pengampu_konseling_id' => $guruId ?: null]);
                }
            }
        });

        return redirect()->route('konseling.pengampu')->with('success', 'Pengampu konseling berhasil disimpan.');
    }

    /** Siswa aktif yang boleh dihubungi: hanya dari kelas yang diampu konselingnya oleh user. */
    private function siswaBolehDihubungi(User $user)
    {
        return Siswa::where('status', 'Aktif')
            ->whereHas('kelas', fn ($k) => $k->where('pengampu_konseling_id', $user->id));
    }
}
