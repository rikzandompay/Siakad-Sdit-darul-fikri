<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\PengaturanSekolah;
use App\Models\PerangkatGuru;
use App\Models\PresensiGuru;
use App\Models\User;
use App\Models\WajahGuru;
use App\Notifications\LokasiPalsuTerdeteksi;
use App\Support\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Absen guru dengan verifikasi lokasi + wajah.
 * HP hanya mengirim embedding wajah hasil kamera; pencocokan dengan template (terenkripsi,
 * tidak pernah dikirim ke HP) dan skor dihitung di server. Server juga memeriksa jarak,
 * akurasi GPS, lokasi palsu, jam, dan hari sekolah.
 */
class AbsenController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $guru = $request->user();
        $now = Sekolah::now();
        $absen = PresensiGuru::where('guru_id', $guru->id)->where('tanggal', $now->toDateString())->first();

        return response()->json([
            'tanggal' => $now->toDateString(),
            'hari' => Sekolah::hari($now),
            'jam_sekarang' => $now->format('H:i'),
            'hari_sekolah' => Sekolah::hariSekolah($now),
            'jam_masuk' => PengaturanSekolah::jamMasuk(),
            'jam_pulang' => PengaturanSekolah::jamPulang(),
            'batas_masuk' => PengaturanSekolah::batasMasuk(),
            'min_skor_wajah' => (float) config('presensi_guru.min_skor_wajah'),
            'lokasi' => ($lok = PengaturanSekolah::lokasi()) ? $lok + ['radius_meter' => PengaturanSekolah::radiusMeter(), 'nama' => Sekolah::NAMA] : null,
            'wajah_terdaftar' => WajahGuru::where('guru_id', $guru->id)->exists(),
            'foto_acuan' => (bool) $guru->foto_profil,
            'foto_menunggu' => (bool) $guru->foto_pengajuan,
            'hari_ini' => self::ringkasHariIni($absen),
        ]);
    }

    /** Cek jarak ke sekolah (langkah 1). Tidak menyimpan apa pun. */
    public function cekLokasi(Request $request): JsonResponse
    {
        $data = $request->validate($this->aturanLokasi() + ['mock' => 'nullable|boolean']);
        if ($request->boolean('mock')) {
            return $this->laporLokasiPalsu($request, 'absen');
        }
        if ($err = $this->cekBlokirLokasiPalsu($request)) {
            return $err;
        }

        return response()->json($this->hitungLokasi($data));
    }

    /**
     * Status perangkat absen guru ini (dipakai aplikasi sebelum membuka kamera).
     * Tidak mendaftarkan perangkat; pendaftaran terjadi saat absen dikirim.
     */
    public function perangkat(Request $request): JsonResponse
    {
        $guru = $request->user();
        $data = $request->validate(['perangkat_id' => 'nullable|string|min:8|max:191']);

        $aktif = PerangkatGuru::aktifMilik($guru->id);
        $ini = isset($data['perangkat_id'])
            ? PerangkatGuru::where('perangkat_id', $data['perangkat_id'])->where('guru_id', $guru->id)->first()
            : null;

        return response()->json([
            'terdaftar' => (bool) $aktif,
            'perangkat_aktif' => $aktif ? ['nama' => $aktif->nama, 'platform' => $aktif->platform] : null,
            'perangkat_ini' => $ini ? ['nama' => $ini->nama, 'status' => $ini->status, 'catatan' => $ini->catatan] : null,
            'boleh_absen' => $aktif !== null && ($ini === null || $ini->aktif()),
        ]);
    }

    public function masuk(Request $request): JsonResponse
    {
        if ($request->boolean('mock')) {
            return $this->laporLokasiPalsu($request, 'absen masuk');
        }
        if ($err = $this->cekBlokirLokasiPalsu($request)) {
            return $err;
        }
        $guru = $request->user();
        $data = $request->validate($this->aturanWajah() + $this->aturanLokasi());
        $now = Sekolah::now();

        $this->pastikanHariSekolah($now);
        $absen = PresensiGuru::where('guru_id', $guru->id)->where('tanggal', $now->toDateString())->first();
        if ($absen?->jam_masuk) {
            return $this->gagal('sudah_absen', 'Anda sudah absen masuk hari ini pukul '.substr($absen->jam_masuk, 0, 5).'.', 409);
        }
        if (($batas = PengaturanSekolah::batasMasuk()) && $now->format('H:i') >= $batas) {
            return $this->gagal('lewat_batas', "Absen masuk ditutup pukul {$batas}. Hubungi Tata Usaha untuk koreksi.");
        }
        if ($err = $this->pastikanPerangkat($guru, $data)) {
            return $err;
        }
        [$lokasi, $skor, $err, $catatan] = $this->verifikasi($guru->id, $data);
        if ($err) {
            return $err;
        }

        $absen = PresensiGuru::updateOrCreate(
            ['guru_id' => $guru->id, 'tanggal' => $now->toDateString()],
            [
                'status' => PresensiGuru::HADIR, 'jam_masuk' => $now->format('H:i:s'), 'jam_pulang' => null,
                'masuk_latitude' => $data['latitude'], 'masuk_longitude' => $data['longitude'],
                'masuk_jarak_meter' => $lokasi['jarak_meter'], 'masuk_skor_wajah' => $skor,
                'sumber' => 'aplikasi', 'keterangan' => $catatan,
            ]
        );

        return response()->json(['message' => 'Absen masuk berhasil.', 'skor_wajah' => $skor, 'hari_ini' => self::ringkasHariIni($absen)]);
    }

    public function pulang(Request $request): JsonResponse
    {
        if ($request->boolean('mock')) {
            return $this->laporLokasiPalsu($request, 'absen pulang');
        }
        if ($err = $this->cekBlokirLokasiPalsu($request)) {
            return $err;
        }
        $guru = $request->user();
        $data = $request->validate($this->aturanWajah() + $this->aturanLokasi());
        $now = Sekolah::now();

        $absen = PresensiGuru::where('guru_id', $guru->id)->where('tanggal', $now->toDateString())->first();
        if (! $absen?->jam_masuk) {
            return $this->gagal('belum_masuk', 'Absen masuk dulu sebelum absen pulang.');
        }
        if ($absen->jam_pulang) {
            return $this->gagal('sudah_absen', 'Anda sudah absen pulang pukul '.substr($absen->jam_pulang, 0, 5).'.', 409);
        }
        if ($err = $this->pastikanPerangkat($guru, $data)) {
            return $err;
        }
        [$lokasi, $skor, $err, $catatan] = $this->verifikasi($guru->id, $data);
        if ($err) {
            return $err;
        }

        $absen->update([
            'jam_pulang' => $now->format('H:i:s'),
            'pulang_latitude' => $data['latitude'], 'pulang_longitude' => $data['longitude'],
            'pulang_jarak_meter' => $lokasi['jarak_meter'], 'pulang_skor_wajah' => $skor,
        ] + ($catatan && ! $absen->keterangan ? ['keterangan' => $catatan] : []));

        return response()->json(['message' => 'Absen pulang berhasil.', 'skor_wajah' => $skor, 'hari_ini' => self::ringkasHariIni($absen->fresh())]);
    }

    /** Rekap absen guru sendiri per bulan (YYYY-MM). */
    public function rekap(Request $request): JsonResponse
    {
        $request->validate(['bulan' => 'nullable|date_format:Y-m']);
        $bulan = $request->filled('bulan') ? Carbon::createFromFormat('Y-m', $request->input('bulan'), Sekolah::timezone())->startOfMonth() : Sekolah::now()->startOfMonth();

        $rows = PresensiGuru::where('guru_id', $request->user()->id)
            ->whereBetween('tanggal', [$bulan->toDateString(), $bulan->copy()->endOfMonth()->toDateString()])
            ->orderByDesc('tanggal')->get();
        $hadir = $rows->where('status', PresensiGuru::HADIR)->count();
        $tidak = $rows->where('status', PresensiGuru::TIDAK_HADIR)->count();

        return response()->json([
            'bulan' => $bulan->format('Y-m'),
            'hadir' => $hadir,
            'tidak_hadir' => $tidak,
            'terlambat' => $rows->filter->terlambat->count(),
            'persen' => $hadir + $tidak > 0 ? round($hadir / ($hadir + $tidak) * 100) : null,
            'data' => $rows->map(fn ($r) => [
                'tanggal' => $r->tanggal->toDateString(),
                'status' => $r->status,
                'jam_masuk' => $r->jam_masuk ? substr($r->jam_masuk, 0, 5) : null,
                'jam_pulang' => $r->jam_pulang ? substr($r->jam_pulang, 0, 5) : null,
                'terlambat' => $r->terlambat,
                'keterangan' => $r->keterangan,
            ])->values(),
        ]);
    }

    // ── Data wajah ─────────────────────────────────────────────────

    public function wajah(Request $request): JsonResponse
    {
        $wajah = WajahGuru::where('guru_id', $request->user()->id)->first();

        return response()->json([
            'terdaftar' => (bool) $wajah,
            'didaftarkan_pada' => $wajah?->didaftarkan_pada?->toIso8601String(),
        ]);
    }

    /**
     * Pendaftaran wajah: wajah di kamera harus cocok dengan foto profil yang sudah disetujui admin,
     * supaya seseorang tidak bisa mendaftarkan wajah orang lain di akunnya.
     */
    public function daftarWajah(Request $request): JsonResponse
    {
        $guru = $request->user();
        $data = $request->validate([
            'template' => 'nullable|array|min:64|max:1024',
            'template.*' => 'numeric|between:-100,100',
            'foto_embedding' => 'required|array|min:64|max:1024',
            'foto_embedding.*' => 'numeric|between:-100,100',
            'dari_foto_profil' => 'nullable|boolean',
        ]);

        if (WajahGuru::where('guru_id', $guru->id)->exists()) {
            return $this->gagal('sudah_terdaftar', 'Wajah sudah terdaftar. Minta admin mereset data wajah jika perlu mendaftar ulang.', 409);
        }
        if (! $guru->foto_profil) {
            return $this->gagal('foto_belum_disetujui', $guru->foto_pengajuan
                ? 'Foto profil Anda masih menunggu persetujuan kepala sekolah.'
                : 'Unggah foto profil yang jelas menampilkan wajah Anda, lalu tunggu persetujuan kepala sekolah.');
        }

        $fotoEmbedding = array_map('floatval', $data['foto_embedding']);
        $template = isset($data['template']) ? array_map('floatval', $data['template']) : $fotoEmbedding;

        $min = (float) config('presensi_guru.min_skor_foto');
        if (! $request->boolean('dari_foto_profil') && isset($data['template'])) {
            if (WajahGuru::identik($template, $fotoEmbedding)) {
                return $this->gagal('wajah_tidak_cocok_foto', 'Data wajah tidak valid. Ulangi dengan kamera.', 422, ['skor_foto' => 0.0, 'min_skor_foto' => $min]);
            }
            $skorFoto = WajahGuru::skor($template, $fotoEmbedding);
            if ($skorFoto < $min) {
                return $this->gagal('wajah_tidak_cocok_foto', "Wajah di kamera tidak cocok dengan foto profil ({$skorFoto}%, minimal {$min}%). Pastikan wajah terang dan menghadap kamera.", 422, ['skor_foto' => $skorFoto, 'min_skor_foto' => $min]);
            }
        } else {
            $skorFoto = 100.0;
        }

        // Acuan presensi wajah memakai embedding dari gambar profil akun
        WajahGuru::create([
            'guru_id' => $guru->id,
            'template' => json_encode($fotoEmbedding),
            'didaftarkan_pada' => now(),
            'skor_foto' => $skorFoto,
        ]);

        // Wajah yang sangat mirip guru lain (mis. kembar) ditandai agar diketahui admin
        $ambangDeteksi = (float) config('presensi_guru.ambang_kembar_deteksi', 75);
        $mirip = WajahGuru::palingMirip($fotoEmbedding, $guru->id);
        if ($mirip && $mirip['skor'] >= $ambangDeteksi) {
            WajahGuru::tandaiMirip($guru->id, $mirip['guru_id'], $mirip['skor']);
        }

        return response()->json(['message' => 'Wajah dari gambar profil akun berhasil didaftarkan.', 'skor_foto' => $skorFoto], 201);
    }

    // ── Bantuan ────────────────────────────────────────────────────

    public static function ringkasHariIni(?PresensiGuru $absen): array
    {
        return [
            'status' => $absen?->status,
            'jam_masuk' => $absen?->jam_masuk ? substr($absen->jam_masuk, 0, 5) : null,
            'jam_pulang' => $absen?->jam_pulang ? substr($absen->jam_pulang, 0, 5) : null,
            'terlambat' => (bool) $absen?->terlambat,
            'jarak_meter' => $absen?->masuk_jarak_meter,
            'skor_wajah' => $absen?->masuk_skor_wajah,
            'keterangan' => $absen?->keterangan,
        ];
    }

    private function aturanLokasi(): array
    {
        return [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'akurasi' => 'nullable|numeric|min:0|max:5000',
        ];
    }

    private function aturanWajah(): array
    {
        return [
            'akurasi' => 'required|numeric|min:0|max:'.(int) config('presensi_guru.maks_akurasi_meter'),
            'mock' => 'required|declined',
            'embedding' => 'required|array|min:64|max:1024',
            'embedding.*' => 'numeric|between:-100,100',
            'foto_embedding' => 'nullable|array|min:64|max:1024',
            'foto_embedding.*' => 'numeric|between:-100,100',
            'liveness' => 'required|accepted',
            // Verifikasi tambahan (wajah mirip guru lain): beberapa tangkapan kamera sekaligus.
            'embeddings' => 'nullable|array|min:2|max:6',
            'embeddings.*' => 'array|min:64|max:1024',
            'embeddings.*.*' => 'numeric|between:-100,100',
            'verifikasi_tambahan' => 'nullable|boolean',
            // Identitas perangkat: wajib supaya absen tidak bisa dititipkan ke HP lain.
            'perangkat_id' => 'required|string|min:8|max:191',
            'perangkat_nama' => 'nullable|string|max:120',
            'perangkat_platform' => 'nullable|string|max:20',
            'perangkat_versi_os' => 'nullable|string|max:40',
        ];
    }

    /**
     * Pastikan absen hanya dari perangkat yang terdaftar.
     *
     * Perangkat pertama otomatis aktif. Ganti HP membuat perangkat baru berstatus
     * menunggu, dan absen ditolak sampai admin/kepala sekolah menyetujui.
     */
    private function pastikanPerangkat(User $guru, array $data): ?JsonResponse
    {
        [$perangkat, $boleh] = PerangkatGuru::periksa(
            $guru->id,
            $data['perangkat_id'],
            $data['perangkat_nama'] ?? null,
            $data['perangkat_platform'] ?? null,
            $data['perangkat_versi_os'] ?? null,
        );

        if ($boleh) {
            return null;
        }

        $aktif = PerangkatGuru::aktifMilik($guru->id);

        return match ($perangkat->status) {
            PerangkatGuru::MENUNGGU => $this->gagal('perangkat_menunggu', 'Perangkat baru menunggu persetujuan admin. Absen bisa dilakukan setelah disetujui.', 423, [
                'perangkat' => [
                    'nama' => $perangkat->nama,
                    'status' => $perangkat->status,
                    'terdaftar_lain' => $aktif?->nama,
                ],
            ]),
            PerangkatGuru::DITOLAK => $this->gagal('perangkat_ditolak', 'Perangkat ini tidak diizinkan untuk absen. Hubungi Tata Usaha untuk mengaktifkan.', 423, [
                'perangkat' => ['nama' => $perangkat->nama, 'status' => $perangkat->status, 'catatan' => $perangkat->catatan],
            ]),
            default => $this->gagal('perangkat_lain', 'Perangkat ini terikat ke akun lain dan tidak bisa dipakai absen.', 423),
        };
    }

    /**
     * HP melaporkan lokasi palsu (fake GPS). Absen ditolak, absen lewat aplikasi untuk guru ini
     * diblokir sampai akhir hari, dan kepala sekolah diberi peringatan lewat notifikasi aplikasi
     * (riwayat + push). Satu peringatan per guru per 30 menit agar percobaan berulang tidak
     * membanjiri HP kepala sekolah.
     *
     * Blokir wajib: tanda lokasi palsu dari Android hanya menempel pada satu pembacaan GPS, jadi
     * percobaan berikutnya bisa lolos tanpa tanda itu walau aplikasi lokasi palsu masih aktif.
     */
    private function laporLokasiPalsu(Request $request, string $tahap): JsonResponse
    {
        $guru = $request->user();
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $sekolah = PengaturanSekolah::lokasi();
        $jarak = $sekolah && is_numeric($lat) && is_numeric($lng) && abs((float) $lat) <= 90 && abs((float) $lng) <= 180
            ? (int) round(Sekolah::jarakMeter((float) $lat, (float) $lng, $sekolah['latitude'], $sekolah['longitude']))
            : null;

        Log::warning('Lokasi palsu terdeteksi saat absen guru', ['guru_id' => $guru->id, 'tahap' => $tahap, 'jarak_meter' => $jarak, 'ip' => $request->ip()]);

        $now = Sekolah::now();
        Cache::add(self::kunciBlokir($guru->id), $now->format('H:i'), $now->copy()->endOfDay());

        if (Cache::add('lokasi-palsu:'.$guru->id, true, now()->addMinutes(30))) {
            $penerima = User::where('status', 'Aktif')->whereKeyNot($guru->id)->get()->filter->isAdmin();
            Notification::send($penerima, new LokasiPalsuTerdeteksi($guru, $tahap, Sekolah::now()->format('H:i'), $jarak));
        }

        return $this->gagal('lokasi_palsu', 'Terdeteksi aplikasi lokasi palsu. Absen ditolak dan kepala sekolah sudah diberi tahu. Absen lewat aplikasi diblokir sampai akhir hari ini; hubungi kepala sekolah untuk koreksi.', 423);
    }

    /** Guru yang hari ini tertangkap memakai lokasi palsu tidak bisa absen lewat aplikasi sampai besok. */
    private function cekBlokirLokasiPalsu(Request $request): ?JsonResponse
    {
        $jam = Cache::get(self::kunciBlokir($request->user()->id));

        return $jam === null ? null : $this->gagal('lokasi_palsu_diblokir', "Absen lewat aplikasi diblokir hari ini karena terdeteksi lokasi palsu pukul {$jam}. Hubungi kepala sekolah untuk koreksi.", 423);
    }

    private static function kunciBlokir(int $guruId): string
    {
        return 'lokasi-palsu-blokir:'.$guruId.':'.Sekolah::now()->toDateString();
    }

    private function hitungLokasi(array $data): array
    {
        $sekolah = PengaturanSekolah::lokasi();
        if (! $sekolah) {
            throw ValidationException::withMessages(['latitude' => 'Lokasi sekolah belum diatur admin. Hubungi Tata Usaha.']);
        }

        $jarak = Sekolah::jarakMeter((float) $data['latitude'], (float) $data['longitude'], $sekolah['latitude'], $sekolah['longitude']);
        $radius = PengaturanSekolah::radiusMeter();

        // GPS di dalam gedung bisa meleset puluhan meter: titik dianggap di area bila lingkaran
        // akurasinya menyentuh radius sekolah (toleransi dibatasi agar tidak terlalu longgar).
        $toleransi = min((float) ($data['akurasi'] ?? 0), (float) config('presensi_guru.toleransi_akurasi_meter'));

        return [
            'jarak_meter' => $jarak,
            'radius_meter' => $radius,
            'akurasi_meter' => isset($data['akurasi']) ? (int) round($data['akurasi']) : null,
            'maks_akurasi_meter' => (int) config('presensi_guru.maks_akurasi_meter'),
            'di_dalam_area' => $jarak - $toleransi <= $radius,
            'sekolah' => $sekolah + ['nama' => Sekolah::NAMA],
        ];
    }

    /** @return array{0: array, 1: float|null, 2: JsonResponse|null, 3: string|null} */
    private function verifikasi(int $guruId, array $data): array
    {
        $lokasi = $this->hitungLokasi($data);
        if (! $lokasi['di_dalam_area']) {
            return [$lokasi, null, $this->gagal('di_luar_area', 'Anda berada di luar area sekolah.', 422, $lokasi), null];
        }

        $wajah = WajahGuru::where('guru_id', $guruId)->first();
        if (! $wajah && ! empty($data['foto_embedding'])) {
            $guru = User::find($guruId);
            if ($guru?->foto_profil) {
                $fotoEmb = array_map('floatval', $data['foto_embedding']);
                $wajah = WajahGuru::create([
                    'guru_id' => $guruId,
                    'template' => json_encode($fotoEmb),
                    'didaftarkan_pada' => now(),
                    'skor_foto' => 100.0,
                ]);
                $ambangDeteksi = (float) config('presensi_guru.ambang_kembar_deteksi', 75);
                $m = WajahGuru::palingMirip($fotoEmb, $guruId);
                if ($m && $m['skor'] >= $ambangDeteksi) {
                    WajahGuru::tandaiMirip($guruId, $m['guru_id'], $m['skor']);
                }
            }
        }
        if (! $wajah) {
            return [$lokasi, null, $this->gagal('wajah_belum_terdaftar', 'Acuan wajah belum ada. Buka menu Presensi Guru; acuan dibuat dari foto profil yang disetujui kepala sekolah.'), null];
        }

        $template = $wajah->vektor();
        $sampel = [array_map('floatval', $data['embedding'])];
        foreach ($data['embeddings'] ?? [] as $e) {
            $sampel[] = array_map('floatval', $e);
        }
        $min = (float) config('presensi_guru.min_skor_wajah');

        // Embedding identik dengan template = data hasil salin, bukan tangkapan kamera.
        foreach ($sampel as $e) {
            if (WajahGuru::identik($e, $template)) {
                return [$lokasi, null, $this->gagal('wajah_tidak_cocok', 'Data wajah tidak valid. Ulangi verifikasi dengan kamera.', 422, ['skor_wajah' => 0.0, 'min_skor_wajah' => $min]), null];
            }
        }

        // Rata-rata beberapa tangkapan mengurangi derau cahaya dan sudut.
        $embedding = WajahGuru::rataRata($sampel);
        $cosSelf = WajahGuru::cosinus($embedding, $template);
        $skor = WajahGuru::skor($embedding, $template);

        // 1. Wajah harus cocok dengan foto profil akun ini.
        if ($skor < $min) {
            return [$lokasi, null, $this->gagal('wajah_tidak_cocok', "Kecocokan wajah {$skor}% (minimal {$min}%). Pastikan pencahayaan cukup, wajah tegak menghadap kamera, dan lepas kacamata/masker.", 422, ['skor_wajah' => $skor, 'min_skor_wajah' => $min, 'cosinus' => round($cosSelf, 4)]), null];
        }

        // 2. Ada guru lain yang wajahnya mirip (mis. kembar): jangan langsung ditolak, pastikan dulu.
        $ambangDeteksi = (float) config('presensi_guru.ambang_kembar_deteksi', 75);
        $minSelisih = (float) config('presensi_guru.min_selisih_kembar', 0.03);
        $mirip = WajahGuru::palingMirip($embedding, $guruId);
        if (! $mirip || $mirip['skor'] < $ambangDeteksi) {
            return [$lokasi, $skor, null, null];
        }
        WajahGuru::tandaiMirip($guruId, $mirip['guru_id'], $mirip['skor']);

        $selisih = $cosSelf - $mirip['cosinus'];
        if ($selisih >= $minSelisih) {
            return [$lokasi, $skor, null, null]; // jelas lebih cocok dengan diri sendiri
        }

        // Selisih tipis: minta verifikasi tambahan (beberapa tangkapan kamera sekaligus).
        $tambahan = ! empty($data['verifikasi_tambahan']) && count($sampel) >= 3;
        if (! $tambahan) {
            return [$lokasi, null, $this->gagal('verifikasi_tambahan', 'Wajah Anda mirip dengan guru lain. Kedipkan mata sekali lagi; beberapa foto diambil untuk memastikan.', 422, ['skor_wajah' => $skor]), null];
        }

        // Setelah verifikasi tambahan wajah tetap jelas lebih cocok dengan guru lain: kemungkinan
        // orang lain memakai akun dan HP ini.
        if ($selisih <= -$minSelisih) {
            return [$lokasi, null, $this->gagal('wajah_mirip_guru_lain', 'Wajah lebih cocok dengan data guru lain daripada foto profil akun ini. Absen ditolak; minta kepala sekolah mencatat absen secara manual bila ini keliru.', 422, ['skor_wajah' => $skor]), null];
        }

        // Kembar identik: wajah memang tidak bisa membedakan. Identitas dipastikan oleh akun dan HP
        // terdaftar guru ini (diperiksa sebelum wajah), lalu dicatat agar bisa ditinjau admin
        // (pasangan yang mirip terlihat di data wajah guru). Nama guru lain tidak disebut ke HP.
        return [$lokasi, $skor, null, 'Wajah sangat mirip guru lain; dipastikan lewat verifikasi tambahan, akun, dan HP terdaftar.'];
    }

    private function pastikanHariSekolah(Carbon $now): void
    {
        if (! Sekolah::hariSekolah($now)) {
            throw ValidationException::withMessages(['tanggal' => 'Hari ini bukan hari sekolah.']);
        }
    }

    private function gagal(string $kode, string $pesan, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(['message' => $pesan, 'kode' => $kode] + $extra, $status);
    }
}
