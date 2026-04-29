<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiRapot extends Model
{
    protected $table = 'nilai_rapot';

    protected $fillable = [
        'tahun_ajaran_id',
        'siswa_id',
        'pelajaran_id',
        'nilai_tugas',
        'nilai_uts',
        'nilai_uas',
        'nilai_akhir',
    ];

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'pelajaran_id');
    }

    /**
     * Hitung nilai akhir: (tugas * 0.3) + (uts * 0.3) + (uas * 0.4)
     */
    public static function hitungNilaiAkhir($tugas, $uts, $uas): float
    {
        return round(($tugas * 0.3) + ($uts * 0.3) + ($uas * 0.4), 2);
    }

    /**
     * Dapatkan predikat berdasarkan nilai akhir
     */
    public static function getPredikat($nilaiAkhir): string
    {
        if ($nilaiAkhir >= 85) return 'A';
        if ($nilaiAkhir >= 75) return 'B';
        if ($nilaiAkhir >= 65) return 'C';
        return 'D';
    }

    public function getPredikatAttribute(): string
    {
        return static::getPredikat($this->nilai_akhir);
    }
}
