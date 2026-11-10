<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan sekolah (key-value) yang dipakai web dan aplikasi MyDarfik.
 */
class PengaturanSekolah extends Model
{
    public const LATITUDE = 'sekolah_latitude';

    public const LONGITUDE = 'sekolah_longitude';

    public const RADIUS = 'radius_meter';

    public const JAM_MASUK = 'jam_masuk_guru';

    public const JAM_PULANG = 'jam_pulang_guru';

    protected $table = 'pengaturan_sekolah';

    protected $primaryKey = 'kunci';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['kunci', 'nilai'];

    public static function semua(): array
    {
        return Cache::rememberForever('pengaturan_sekolah', fn () => static::pluck('nilai', 'kunci')->all());
    }

    public static function ambil(string $kunci, mixed $default = null): mixed
    {
        $nilai = static::semua()[$kunci] ?? null;

        return $nilai === null || $nilai === '' ? $default : $nilai;
    }

    public static function simpan(array $nilai): void
    {
        foreach ($nilai as $kunci => $isi) {
            static::updateOrCreate(['kunci' => $kunci], ['nilai' => $isi === null ? null : (string) $isi]);
        }
        Cache::forget('pengaturan_sekolah');
    }

    public static function jamMasuk(): string
    {
        return (string) static::ambil(self::JAM_MASUK, config('presensi_guru.jam_masuk'));
    }

    public static function jamPulang(): string
    {
        return (string) static::ambil(self::JAM_PULANG, config('presensi_guru.jam_pulang'));
    }

    public static function radiusMeter(): int
    {
        return (int) static::ambil(self::RADIUS, config('presensi_guru.radius_meter'));
    }

    /** @return array{latitude: ?float, longitude: ?float}|null null jika lokasi belum diatur */
    public static function lokasi(): ?array
    {
        $lat = static::ambil(self::LATITUDE);
        $lng = static::ambil(self::LONGITUDE);

        return $lat === null || $lng === null ? null : ['latitude' => (float) $lat, 'longitude' => (float) $lng];
    }
}
