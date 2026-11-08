<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\PengaturanSekolah;
use App\Models\PresensiGuru;
use App\Models\WajahGuru;
use App\Support\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'jam_pulang' => config('presensi_guru.jam_pulang'),
            'batas_masuk' => config('presensi_guru.batas_tidak_hadir'),
            'min_skor_wajah' => (float) config('presensi_guru.min_skor_wajah'),
            'lokasi' => ($lok = PengaturanSekolah::lokasi()) ? $lok + ['radius_meter' => PengaturanSekolah::radiusMeter(), 'nama' => Sekolah::NAMA] : null,
            'wajah_terdaftar' => WajahGuru::where('guru_id', $guru->id)->exists(),
            'hari_ini' => self::ringkasHariIni($absen),
        ]);
    }

    /** Cek jarak ke sekolah (langkah 1). Tidak menyimpan apa pun. */
    public function cekLokasi(Request $request): JsonResponse
    {
        $data = $request->validate($this->aturanLokasi());

        return response()->json($this->hitungLokasi($data));
    }

    public function masuk(Request $request): JsonResponse
    {
        $guru = $request->user();
        $data = $request->validate($this->aturanWajah() + $this->aturanLokasi());
        $now = Sekolah::now();

        $this->pastikanHariSekolah($now);
        $absen = PresensiGuru::where('guru_id', $guru->id)->where('tanggal', $now->toDateString())->first();
        if ($absen?->jam_masuk) {
            return $this->gagal('sudah_absen', 'Anda sudah absen masuk hari ini pukul '.substr($absen->jam_masuk, 0, 5).'.', 409);
        }
        if (($batas = config('presensi_guru.batas_tidak_hadir')) && $now->format('H:i') >= $batas) {
            return $this->gagal('lewat_batas', "Absen masuk ditutup pukul {$batas}. Hubungi Tata Usaha untuk koreksi.");
        }
        [$lokasi, $skor, $err] = $this->verifikasi($guru->id, $data);
        if ($err) {
            return $err;
        }

        $absen = PresensiGuru::updateOrCreate(
            ['guru_id' => $guru->id, 'tanggal' => $now->toDateString()],
            [
                'status' => PresensiGuru::HADIR, 'jam_masuk' => $now->format('H:i:s'), 'jam_pulang' => null,
                'masuk_latitude' => $data['latitude'], 'masuk_longitude' => $data['longitude'],
                'masuk_jarak_meter' => $lokasi['jarak_meter'], 'masuk_skor_wajah' => $skor,
                'sumber' => 'aplikasi', 'keterangan' => null,
            ]
        );

        return response()->json(['message' => 'Absen masuk berhasil.', 'skor_wajah' => $skor, 'hari_ini' => self::ringkasHariIni($absen)]);
    }

    public function pulang(Request $request): JsonResponse
    {
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
        [$lokasi, $skor, $err] = $this->verifikasi($guru->id, $data);
        if ($err) {
            return $err;
        }

        $absen->update([
            'jam_pulang' => $now->format('H:i:s'),
            'pulang_latitude' => $data['latitude'], 'pulang_longitude' => $data['longitude'],
            'pulang_jarak_meter' => $lokasi['jarak_meter'], 'pulang_skor_wajah' => $skor,
        ]);

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

    public function daftarWajah(Request $request): JsonResponse
    {
        $data = $request->validate([
            'template' => 'required|array|min:64|max:1024',
            'template.*' => 'numeric|between:-100,100',
        ]);

        if (WajahGuru::where('guru_id', $request->user()->id)->exists()) {
            return $this->gagal('sudah_terdaftar', 'Wajah sudah terdaftar. Minta admin mereset data wajah jika perlu mendaftar ulang.', 409);
        }

        WajahGuru::create([
            'guru_id' => $request->user()->id,
            'template' => json_encode(array_map('floatval', $data['template'])),
            'didaftarkan_pada' => now(),
        ]);

        return response()->json(['message' => 'Wajah berhasil didaftarkan.'], 201);
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
            'liveness' => 'required|accepted',
        ];
    }

    /** Cosine similarity -> persen, kalibrasi sama dengan aplikasi (cos 0.20 = 0%, cos 0.80 = 100%). */
    public static function skorWajah(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }
        $dot = $na = $nb = 0.0;
        foreach ($a as $i => $x) {
            $dot += $x * $b[$i];
            $na += $x * $x;
            $nb += $b[$i] * $b[$i];
        }
        if ($na == 0.0 || $nb == 0.0) {
            return 0.0;
        }
        $cos = $dot / sqrt($na * $nb);

        return round(max(0.0, min(1.0, ($cos - 0.20) / 0.60)) * 100, 1);
    }

    private function hitungLokasi(array $data): array
    {
        $sekolah = PengaturanSekolah::lokasi();
        if (! $sekolah) {
            throw ValidationException::withMessages(['latitude' => 'Lokasi sekolah belum diatur admin. Hubungi Tata Usaha.']);
        }

        $jarak = Sekolah::jarakMeter((float) $data['latitude'], (float) $data['longitude'], $sekolah['latitude'], $sekolah['longitude']);
        $radius = PengaturanSekolah::radiusMeter();

        return [
            'jarak_meter' => $jarak,
            'radius_meter' => $radius,
            'akurasi_meter' => isset($data['akurasi']) ? (int) round($data['akurasi']) : null,
            'di_dalam_area' => $jarak <= $radius,
            'sekolah' => $sekolah + ['nama' => Sekolah::NAMA],
        ];
    }

    /** @return array{0: array, 1: float|null, 2: JsonResponse|null} */
    private function verifikasi(int $guruId, array $data): array
    {
        $lokasi = $this->hitungLokasi($data);
        if (! $lokasi['di_dalam_area']) {
            return [$lokasi, null, $this->gagal('di_luar_area', 'Anda berada di luar area sekolah.', 422, $lokasi)];
        }

        $wajah = WajahGuru::where('guru_id', $guruId)->first();
        if (! $wajah) {
            return [$lokasi, null, $this->gagal('wajah_belum_terdaftar', 'Wajah belum terdaftar. Daftarkan wajah terlebih dahulu.')];
        }

        $template = json_decode($wajah->template, true) ?: [];
        $embedding = array_map('floatval', $data['embedding']);
        $skor = self::skorWajah($embedding, $template);
        $min = (float) config('presensi_guru.min_skor_wajah');

        // Embedding identik dengan template = data hasil salin, bukan tangkapan kamera.
        if ($this->identik($embedding, $template)) {
            return [$lokasi, null, $this->gagal('wajah_tidak_cocok', 'Data wajah tidak valid. Ulangi verifikasi dengan kamera.', 422, ['skor_wajah' => 0.0, 'min_skor_wajah' => $min])];
        }
        if ($skor < $min) {
            return [$lokasi, null, $this->gagal('wajah_tidak_cocok', "Kecocokan wajah {$skor}% (minimal {$min}%).", 422, ['skor_wajah' => $skor, 'min_skor_wajah' => $min])];
        }

        return [$lokasi, $skor, null];
    }

    private function identik(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }
        foreach ($a as $i => $x) {
            if (abs($x - $b[$i]) > 1e-6) {
                return false;
            }
        }

        return true;
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
