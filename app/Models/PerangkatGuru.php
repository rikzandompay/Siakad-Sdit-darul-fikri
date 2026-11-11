<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Perangkat yang diizinkan untuk absen atas nama seorang guru.
 *
 * Aturan: satu guru = satu perangkat aktif. Perangkat baru masuk status
 * "menunggu" sampai admin/kepala sekolah menyetujui.
 */
class PerangkatGuru extends Model
{
    protected $table = 'perangkat_guru';

    public const AKTIF = 'aktif';

    public const MENUNGGU = 'menunggu';

    public const DITOLAK = 'ditolak';

    protected $fillable = [
        'guru_id', 'perangkat_id', 'nama', 'platform', 'versi_os', 'status',
        'disetujui_pada', 'disetujui_oleh', 'ditolak_pada', 'catatan', 'terakhir_dipakai',
    ];

    protected function casts(): array
    {
        return [
            'disetujui_pada' => 'datetime',
            'ditolak_pada' => 'datetime',
            'terakhir_dipakai' => 'datetime',
        ];
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function penyetuju()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function aktif(): bool
    {
        return $this->status === self::AKTIF;
    }

    /** Perangkat aktif milik guru ini (maksimal satu menurut alur). */
    public static function aktifMilik(int $guruId): ?self
    {
        return static::where('guru_id', $guruId)->where('status', self::AKTIF)->first();
    }

    /**
     * Daftarkan atau periksa perangkat untuk absen.
     *
     * - Belum ada perangkat aktif milik guru  → perangkat ini langsung aktif.
     * - Perangkat ini sudah aktif             → lanjut, perbarui jejak pakai.
     * - Selain itu (perangkat baru / ganti HP) → status menunggu, absen ditolak.
     *
     * @return array{0: self, 1: bool} [perangkat, boleh absen]
     */
    public static function periksa(int $guruId, string $perangkatId, ?string $nama, ?string $platform, ?string $versiOs): array
    {
        $milikSendiri = static::where('perangkat_id', $perangkatId)->first();
        // Perangkat sudah terikat ke guru lain: jangan pernah izinkan.
        if ($milikSendiri && $milikSendiri->guru_id !== $guruId) {
            return [$milikSendiri, false];
        }

        if ($milikSendiri) {
            if ($milikSendiri->aktif()) {
                $milikSendiri->update(['terakhir_dipakai' => now(), 'nama' => $nama ?: $milikSendiri->nama]);

                return [$milikSendiri, true];
            }
            // Menunggu / ditolak: segarkan info perangkat, tetap tidak boleh absen.
            $milikSendiri->update([
                'nama' => $nama ?: $milikSendiri->nama,
                'platform' => $platform ?: $milikSendiri->platform,
                'versi_os' => $versiOs ?: $milikSendiri->versi_os,
                'terakhir_dipakai' => now(),
            ]);

            return [$milikSendiri, false];
        }

        $sudahAdaAktif = static::aktifMilik($guruId);

        $perangkat = static::create([
            'guru_id' => $guruId,
            'perangkat_id' => $perangkatId,
            'nama' => $nama,
            'platform' => $platform,
            'versi_os' => $versiOs,
            'status' => $sudahAdaAktif ? self::MENUNGGU : self::AKTIF,
            'disetujui_pada' => $sudahAdaAktif ? null : now(),
            'terakhir_dipakai' => now(),
        ]);

        return [$perangkat, $perangkat->aktif()];
    }

    /**
     * Setujui perangkat ini. Perangkat aktif lama otomatis dicabut supaya tetap
     * satu perangkat per guru.
     */
    public function setujui(?int $olehId = null): void
    {
        static::where('guru_id', $this->guru_id)
            ->where('id', '!=', $this->id)
            ->where('status', self::AKTIF)
            ->update(['status' => self::DITOLAK, 'catatan' => 'Diganti perangkat baru', 'ditolak_pada' => now()]);

        $this->update([
            'status' => self::AKTIF,
            'disetujui_pada' => now(),
            'disetujui_oleh' => $olehId,
            'ditolak_pada' => null,
            'catatan' => null,
        ]);
    }

    public function tolak(?string $catatan = null): void
    {
        $this->update([
            'status' => self::DITOLAK,
            'ditolak_pada' => now(),
            'catatan' => $catatan,
        ]);
    }
}
