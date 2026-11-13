<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Percobaan absen dengan aplikasi lokasi palsu. Hanya ditambah (tidak ada fitur ubah/hapus),
 * supaya tetap menjadi bukti walau pelakunya admin sendiri.
 */
class InsidenLokasiPalsu extends Model
{
    protected $table = 'insiden_lokasi_palsu';

    public const UPDATED_AT = null;

    protected $fillable = ['guru_id', 'tanggal', 'jam', 'tahap', 'jarak_meter', 'ip'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'jarak_meter' => 'integer'];
    }

    /** Simpan tanggal sebagai Y-m-d (sama seperti PresensiGuru) agar perbandingan tanggal cocok di semua database. */
    protected function tanggal(): Attribute
    {
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /** Jam percobaan pertama per guru per tanggal: [guru_id => ['Y-m-d' => 'H:i']]. */
    public static function petaPerHari(array $guruIds, string $mulai, string $selesai): Collection
    {
        return static::whereIn('guru_id', $guruIds)->whereBetween('tanggal', [$mulai, $selesai])->orderBy('jam')->get()
            ->groupBy('guru_id')
            ->map(fn ($rows) => $rows->groupBy(fn ($r) => $r->tanggal->toDateString())->map(fn ($r) => substr($r->first()->jam, 0, 5)));
    }
}
