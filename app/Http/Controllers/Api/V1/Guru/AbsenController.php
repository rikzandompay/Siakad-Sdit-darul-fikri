<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\PengaturanSekolah;
use App\Models\PresensiGuru;
use App\Models\User;
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
        if (($batas = PengaturanSekolah::batasMasuk()) && $now->format('H:i') >= $batas) {
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
        ];
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
            return [$lokasi, null, $this->gagal('wajah_belum_terdaftar', 'Wajah belum terdaftar. Daftarkan wajah atau foto profil terlebih dahulu.')];
        }

        $template = $wajah->vektor();
        $embedding = array_map('floatval', $data['embedding']);
        $cosSelf = WajahGuru::cosinus($embedding, $template);
        $skor = WajahGuru::skor($embedding, $template);
        $min = (float) config('presensi_guru.min_skor_wajah');

        // Embedding identik dengan template = data hasil salin, bukan tangkapan kamera.
        if (WajahGuru::identik($embedding, $template)) {
            return [$lokasi, null, $this->gagal('wajah_tidak_cocok', 'Data wajah tidak valid. Ulangi verifikasi dengan kamera.', 422, ['skor_wajah' => 0.0, 'min_skor_wajah' => $min])];
        }

        // Perketat deteksi dan verifikasi jika ada orang kembar / profil serupa
        $ambangDeteksi = (float) config('presensi_guru.ambang_kembar_deteksi', 75);
        $minSelisihKembar = (float) config('presensi_guru.min_selisih_kembar', 0.03);
        $minSkorKembar = (float) config('presensi_guru.min_skor_kembar', 90);

        $mirip = WajahGuru::palingMirip($embedding, $guruId);
        $adaKembaran = $wajah->mirip_guru_id !== null || ($mirip && $mirip['skor'] >= $ambangDeteksi);

        if ($mirip && $mirip['skor'] >= $ambangDeteksi) {
            WajahGuru::tandaiMirip($guruId, $mirip['guru_id'], $mirip['skor']);
        }

        // 1. Wajah di kamera lebih mirip dengan guru lain
        if ($mirip && $mirip['cosinus'] >= $cosSelf) {
            return [$lokasi, null, $this->gagal('wajah_mirip_guru_lain', 'Wajah ini lebih mirip dengan data guru lain (indikasi orang kembar/serupa). Absen ditolak; hubungi Tata Usaha untuk verifikasi manual.', 422, [
                'skor_wajah' => $skor,
                'min_skor_wajah' => $min,
            ])];
        }

        // 2. Jika akun terindikasi kembar, selisih kemiripan harus jelas (tidak boleh ambigu)
        if ($adaKembaran && $mirip) {
            $selisih = $cosSelf - $mirip['cosinus'];
            if ($selisih < $minSelisihKembar) {
                return [$lokasi, null, $this->gagal('wajah_kembar_terdeteksi', 'Terdeteksi kemiripan sangat tinggi dengan profil guru kembar/serupa. Perbedaan kemiripan wajah terlalu tipis untuk memastikan identitas. Pastikan wajah tegak lurus, lepas kacamata/masker, dan pencahayaan terang.', 422, [
                    'skor_wajah' => $skor,
                    'selisih' => round($selisih * 100, 1),
                ])];
            }
        }

        // 3. Ambang batas kecocokan: lebih ketat untuk akun yang terindikasi kembar
        $minDiperlukan = $adaKembaran ? max($min, $minSkorKembar) : $min;
        if ($skor < $minDiperlukan) {
            $pesan = $adaKembaran
                ? "Akun Anda memiliki profil guru kembar/serupa di sistem. Diperlukan kecocokan minimal {$minDiperlukan}% (hasil verifikasi: {$skor}%). Pastikan pencahayaan cukup dan wajah menghadap kamera."
                : "Kecocokan wajah {$skor}% (minimal {$minDiperlukan}%).";
            return [$lokasi, null, $this->gagal('wajah_tidak_cocok', $pesan, 422, ['skor_wajah' => $skor, 'min_skor_wajah' => $minDiperlukan])];
        }

        return [$lokasi, $skor, null];
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
