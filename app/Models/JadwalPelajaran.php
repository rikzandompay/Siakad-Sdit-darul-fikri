<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPelajaran extends Model
{
    protected $table = 'jadwal_pelajaran';

    protected $fillable = [
        'kelas_id',
        'pelajaran_id',
        'guru_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'pelajaran_id');
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function presensi()
    {
        return $this->hasMany(Presensi::class, 'jadwal_id');
    }

    public static function getByGuru($guruId)
    {
        return static::where('guru_id', $guruId)
            ->with(['kelas', 'mataPelajaran'])
            ->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('jam_mulai')
            ->get();
    }
}
