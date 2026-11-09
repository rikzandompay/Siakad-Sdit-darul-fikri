<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WajahGuru extends Model
{
    protected $table = 'wajah_guru';

    protected $fillable = ['guru_id', 'template', 'didaftarkan_pada', 'skor_foto', 'mirip_guru_id', 'mirip_skor', 'mirip_pada'];

    /** Template wajah tidak boleh bocor lewat serialisasi. */
    protected $hidden = ['template'];

    protected function casts(): array
    {
        return ['template' => 'encrypted', 'didaftarkan_pada' => 'datetime', 'mirip_pada' => 'datetime'];
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function miripGuru()
    {
        return $this->belongsTo(User::class, 'mirip_guru_id');
    }

    /** @return array<int, float> */
    public function vektor(): array
    {
        return json_decode($this->template, true) ?: [];
    }

    /** Cosine similarity mentah, -1 sampai 1. */
    public static function cosinus(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }
        $dot = $na = $nb = 0.0;
        foreach ($a as $i => $x) {
            $dot += $x * $b[$i];
            $na += $x * $x;
            $nb += $b[$i] * $b[$i];
        }

        return $na == 0.0 || $nb == 0.0 ? 0.0 : $dot / sqrt($na * $nb);
    }

    /** Cosine similarity -> persen, kalibrasi sama dengan aplikasi (cos 0.20 = 0%, cos 0.80 = 100%). */
    public static function skor(array $a, array $b): float
    {
        return round(max(0.0, min(1.0, (static::cosinus($a, $b) - 0.20) / 0.60)) * 100, 1);
    }

    /** Dua vektor sama persis berarti data hasil salin, bukan tangkapan kamera. */
    public static function identik(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }
        foreach ($a as $i => $x) {
            if (abs($x - $b[$i]) > 1e-6) {
                return false;
            }
        }

        return true;
    }

    /**
     * Guru lain yang wajahnya paling mirip dengan vektor ini.
     * Membandingkan cosine mentah karena skor persen mentok di 100 untuk wajah yang sangat mirip.
     *
     * @return array{guru_id: int, skor: float, cosinus: float}|null
     */
    public static function palingMirip(array $vektor, int $kecualiGuruId): ?array
    {
        $terbaik = null;
        foreach (static::where('guru_id', '!=', $kecualiGuruId)->get() as $lain) {
            $cos = static::cosinus($vektor, $lain->vektor());
            if (! $terbaik || $cos > $terbaik['cosinus']) {
                $terbaik = ['guru_id' => $lain->guru_id, 'skor' => static::skor($vektor, $lain->vektor()), 'cosinus' => $cos];
            }
        }

        return $terbaik;
    }

    /** Tandai sepasang guru yang wajahnya sangat mirip (mis. kembar) agar diketahui admin. */
    public static function tandaiMirip(int $guruA, int $guruB, float $skor): void
    {
        foreach ([[$guruA, $guruB], [$guruB, $guruA]] as [$guru, $mirip]) {
            static::where('guru_id', $guru)->update(['mirip_guru_id' => $mirip, 'mirip_skor' => $skor, 'mirip_pada' => now()]);
        }
    }
}
