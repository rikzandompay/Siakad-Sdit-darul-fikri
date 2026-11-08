<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HariLibur extends Model
{
    protected $table = 'hari_libur';

    protected $fillable = ['tanggal', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    protected function tanggal(): Attribute
    {
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('hari_libur_tanggal'));
        static::deleted(fn () => Cache::forget('hari_libur_tanggal'));
    }

    /** Daftar tanggal libur (Y-m-d) sebagai kunci untuk pencarian cepat. */
    public static function daftarTanggal(): array
    {
        return Cache::remember('hari_libur_tanggal', 3600, fn () => static::pluck('keterangan', 'tanggal')->mapWithKeys(
            fn ($ket, $tgl) => [Carbon::parse($tgl)->toDateString() => $ket]
        )->all());
    }

    public static function isLibur(Carbon|string $tanggal): bool
    {
        return array_key_exists(Carbon::parse($tanggal)->toDateString(), static::daftarTanggal());
    }
}
