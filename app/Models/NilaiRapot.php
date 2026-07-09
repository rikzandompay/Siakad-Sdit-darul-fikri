<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class NilaiRapot extends Model
{
    protected $table = 'nilai_rapot';

    protected $fillable = [
        'tahun_ajaran_id',
        'siswa_id',
        'pelajaran_id',
        'formatif_data',
        'sas',
        'kehadiran',
        'pengurang_tidaktelat',
        'pengurang_menyontek',
        'nilai_rapor',
    ];

    protected $casts = [
        'formatif_data' => 'array', // JSONB cast to array
    ];

    /**
     * Default formatif data structure
     */
    public static function getDefaultFormatifData(): array
    {
        return [
            'bab1' => ['tp1' => 0, 'tp2' => 0, 'tp3' => 0, 'tp4' => 0, 'uh' => 0],
            'bab2' => ['tp1' => 0, 'tp2' => 0, 'tp3' => 0, 'tp4' => 0, 'uh' => 0],
            'bab3' => ['tp1' => 0, 'tp2' => 0, 'tp3' => 0, 'tp4' => 0, 'uh' => 0],
            'bab4' => ['tp1' => 0, 'tp2' => 0, 'tp3' => 0, 'tp4' => 0, 'uh' => 0],
        ];
    }

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
     * Calculate average per BAB
     * Formula: (TP1 + TP2 + TP3 + TP4 + UH) / 5
     */
    public static function calculateBabAverage(array $babData): float
    {
        $sum = ($babData['tp1'] ?? 0) + ($babData['tp2'] ?? 0) + 
               ($babData['tp3'] ?? 0) + ($babData['tp4'] ?? 0) + ($babData['uh'] ?? 0);
        return round($sum / 5, 2);
    }

    /**
     * Calculate Formatif Total (average of all 4 BABs)
     * Formula: (BAB1_avg + BAB2_avg + BAB3_avg + BAB4_avg) / 4
     */
    public static function calculateFormatifTotal(array $formatifData): float
    {
        $babAverages = [];
        for ($i = 1; $i <= 4; $i++) {
            $babKey = "bab{$i}";
            if (isset($formatifData[$babKey])) {
                $babAverages[] = self::calculateBabAverage($formatifData[$babKey]);
            } else {
                $babAverages[] = 0;
            }
        }
        
        return round(array_sum($babAverages) / 4, 2);
    }

    /**
     * Calculate Nilai Rapor
     * Formula: (Formatif Total × 0.50) + (SAS × 0.30) + Kehadiran - Total Pengurang
     */
    public static function calculateNilaiRapor(
        array $formatifData, 
        float $sas, 
        float $kehadiran, 
        float $pengurangTidaktelat = 0, 
        float $pengurangMenyontek = 0
    ): float {
        $formatifTotal = self::calculateFormatifTotal($formatifData);
        $totalPengurang = $pengurangTidaktelat + $pengurangMenyontek;
        
        $nilaiRapor = ($formatifTotal * 0.50) + ($sas * 0.30) + $kehadiran - $totalPengurang;
        
        // Ensure nilai_rapor is between 0 and 100
        return round(max(0, min(100, $nilaiRapor)), 2);
    }

    /**
     * Get all BAB averages for this record
     */
    public function getBabAverages(): array
    {
        $formatifData = $this->formatif_data;
        if (is_string($formatifData)) {
            $formatifData = json_decode($formatifData, true);
        }
        $formatifData = $formatifData ?: self::getDefaultFormatifData();
        
        $averages = [];
        
        for ($i = 1; $i <= 4; $i++) {
            $babKey = "bab{$i}";
            $averages[$babKey] = self::calculateBabAverage($formatifData[$babKey] ?? []);
        }
        
        return $averages;
    }

    /**
     * Get Formatif Total for this record
     */
    public function getFormatifTotal(): float
    {
        $formatifData = $this->formatif_data;
        if (is_string($formatifData)) {
            $formatifData = json_decode($formatifData, true);
        }
        $formatifData = $formatifData ?: self::getDefaultFormatifData();
        
        return self::calculateFormatifTotal($formatifData);
    }

    /**
     * Get total pengurang
     */
    public function getTotalPengurang(): float
    {
        return ($this->pengurang_tidaktelat ?? 0) + ($this->pengurang_menyontek ?? 0);
    }

    /**
     * Get predikat based on nilai_rapor
     */
    public static function getPredikat($nilaiRapor): string
    {
        if ($nilaiRapor >= 85) return 'A';
        if ($nilaiRapor >= 75) return 'B';
        if ($nilaiRapor >= 65) return 'C';
        return 'D';
    }

    public function getPredikatAttribute(): string
    {
        return static::getPredikat($this->nilai_rapor);
    }

    /**
     * Check if student passes (Tuntas) - threshold is 75
     */
    public function isTuntas(): bool
    {
        return $this->nilai_rapor >= 75;
    }

    /**
     * Scope for students who need remedial
     */
    public function scopePerluRemedial($query)
    {
        return $query->where('nilai_rapor', '<', 75);
    }

    /**
     * Scope for students who pass
     */
    public function scopeTuntas($query)
    {
        return $query->where('nilai_rapor', '>=', 75);
    }
}
