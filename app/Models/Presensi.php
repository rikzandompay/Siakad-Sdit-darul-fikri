<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    // Sengaja tanpa hook cache: tabel cache hanya berisi angka ringkasan (mis. total_siswa_aktif),
    // bukan salinan presensi, jadi tidak perlu dikosongkan setiap kali presensi disimpan.

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
