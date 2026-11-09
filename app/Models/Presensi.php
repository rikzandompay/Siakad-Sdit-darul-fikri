<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Presensi extends Model
{
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::flush();
        });

        static::deleted(function () {
            Cache::flush();
        });
    }

    protected $table = 'presensi';

    protected $fillable = [
        'jadwal_id',
        'siswa_id',
        'tanggal',
        'status_kehadiran',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    /** Simpan tanggal sebagai Y-m-d agar cocok dengan query dan unique key di semua database. */
    protected function tanggal(): Attribute
    {
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function jadwal()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'jadwal_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public static function getRekapBulan($kelasId, $bulan, $tahun)
    {
        return static::whereHas('jadwal', function ($q) use ($kelasId) {
            $q->where('kelas_id', $kelasId);
        })
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->with(['siswa', 'jadwal.mataPelajaran'])
            ->get();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_kehadiran) {
            'H' => 'Hadir',
            'I' => 'Izin',
            'S' => 'Sakit',
            'A' => 'Alpa',
            default => '-',
        };
    }
}
