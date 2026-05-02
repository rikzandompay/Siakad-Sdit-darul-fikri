<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiSholat extends Model
{
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

    public function getStatusLabelAttribute(): string
    {
        return match($this->status_kehadiran) {
            'H' => 'Hadir',
            'I' => 'Izin',
            'S' => 'Sakit',
            'A' => 'Alpa',
            default => '-',
        };
    }
}
