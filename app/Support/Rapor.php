<?php

namespace App\Support;

use App\Models\TahunAjaran;

/** Aturan rapor yang sama dengan web: predikat, KKM, dan pemisahan nama semester. */
class Rapor
{
    public const KKM = 75;

    public static function predikat(?float $nilai): string
    {
        return match (true) {
            $nilai === null => '-',
            $nilai >= 85 => 'A',
            $nilai >= 75 => 'B',
            $nilai >= 65 => 'C',
            $nilai >= 55 => 'D',
            $nilai >= 45 => 'E',
            default => 'F',
        };
    }

    public static function keterangan(string $predikat): string
    {
        return ['A' => 'Sangat Baik', 'B' => 'Baik', 'C' => 'Cukup', 'D' => 'Kurang', 'E' => 'Sangat Kurang', 'F' => 'Gagal'][$predikat] ?? '-';
    }

    /** "2026/2027 - Ganjil" => ['tahun' => '2026/2027', 'semester' => 'Ganjil'] */
    public static function pecah(TahunAjaran $ta): array
    {
        $bagian = array_map('trim', explode('-', $ta->nama_semester, 2));

        return ['tahun' => $bagian[0], 'semester' => $bagian[1] ?? $ta->nama_semester];
    }

    /** Nilai kehadiran rapor (0–20 poin) ditampilkan sebagai persen. */
    public static function persenKehadiran(?float $poin): ?int
    {
        return $poin === null ? null : (int) round(min(20, max(0, $poin)) / 20 * 100);
    }
}
