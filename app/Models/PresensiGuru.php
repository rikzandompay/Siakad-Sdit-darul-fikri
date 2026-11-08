<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Absen guru dari aplikasi MyDarfik (lokasi + wajah). Satu baris per guru per hari.
 */
class PresensiGuru extends Model
{
    public const HADIR = 'Hadir';

    public const TIDAK_HADIR = 'Tidak Hadir';

    public const STATUS = [self::HADIR, self::TIDAK_HADIR];

    protected $table = 'presensi_guru';

    protected $fillable = [
        'guru_id', 'tanggal', 'status', 'jam_masuk', 'jam_pulang',
        'masuk_latitude', 'masuk_longitude', 'masuk_jarak_meter', 'masuk_skor_wajah',
        'pulang_latitude', 'pulang_longitude', 'pulang_jarak_meter', 'pulang_skor_wajah',
        'sumber', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'masuk_skor_wajah' => 'float',
            'pulang_skor_wajah' => 'float',
        ];
    }

    /** Simpan tanggal sebagai Y-m-d agar cocok dengan unique key di semua database. */
    protected function tanggal(): Attribute
    {
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /** Terlambat jika jam masuk melewati jam masuk acuan sekolah. */
    public function getTerlambatAttribute(): bool
    {
        return $this->jam_masuk !== null
            && substr($this->jam_masuk, 0, 5) > PengaturanSekolah::jamMasuk();
    }
}
