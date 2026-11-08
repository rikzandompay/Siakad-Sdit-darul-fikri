<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class PresensiSholat extends Model
{
    public const DHUHA = 'Dhuha';

    public const DZUHUR = 'Dzuhur';

    /** Arsip: Ashar tidak dipakai lagi, hanya bisa dilihat di rekap jika ada data lama. */
    public const ASHAR_ARSIP = 'Ashar';

    /** Jenis yang bisa diisi. */
    public const JENIS = [self::DHUHA, self::DZUHUR];

    /** Jenis yang bisa direkap (termasuk arsip). */
    public const JENIS_REKAP = [self::DHUHA, self::DZUHUR, self::ASHAR_ARSIP];

    public const HADIR = 'Hadir';

    public const TIDAK_HADIR = 'Tidak Hadir';

    public const STATUS = [self::HADIR, self::TIDAK_HADIR];

    protected $table = 'presensi_sholat';

    protected $fillable = [
        'siswa_id',
        'kelas_id',
        'tanggal',
        'jenis_sholat',
        'status_kehadiran',
        'keterangan',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    /**
     * Simpan tanggal selalu sebagai Y-m-d supaya cocok dengan query `where('tanggal', ...)`
     * dan unique key (siswa_id, tanggal, jenis_sholat) di semua database, termasuk SQLite.
     */
    protected function tanggal(): Attribute
    {
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Ambil jenis sholat dari input. Nilai lama ("Zuhur") tetap diterima
     * supaya tautan/bookmark lama tidak rusak.
     */
    public static function normalizeJenis(?string $jenis, bool $termasukArsip = false): string
    {
        if ($jenis === 'Zuhur') {
            $jenis = self::DZUHUR;
        }

        $allowed = $termasukArsip ? self::JENIS_REKAP : self::JENIS;

        return in_array($jenis, $allowed, true) ? $jenis : self::DZUHUR;
    }
}
