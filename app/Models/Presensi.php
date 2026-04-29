<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
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
        return match($this->status_kehadiran) {
            'H' => 'Hadir',
            'I' => 'Izin',
            'S' => 'Sakit',
            'A' => 'Alpa',
            default => '-',
        };
    }
}
