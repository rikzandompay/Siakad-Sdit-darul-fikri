<?php

namespace App\Support;

use App\Models\NomorSurat as ModelNomorSurat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Buku nomor surat sekolah. Urutan terpisah per jenis surat dan per tahun,
 * contoh: 001/SDIT-DF/IZIN/XI/2026 dan 001/SDIT-DF/PENG/XI/2026.
 */
class NomorSurat
{
    public const IZIN = 'izin';

    public const PENGUMUMAN = 'pengumuman';

    private const KODE = [self::IZIN => 'IZIN', self::PENGUMUMAN => 'PENG'];

    private const BULAN_ROMAWI = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /** Nomor surat berikutnya untuk jenis ini; dicatat ke buku nomor seketika. */
    public static function berikutnya(string $jenis, Carbon|string|null $tanggal = null): string
    {
        $tanggal = self::keCarbon($tanggal)->copy()->locale('id');
        $kode = self::KODE[$jenis] ?? strtoupper(substr($jenis, 0, 4));
        $tahun = (int) $tanggal->year;

        return DB::transaction(function () use ($jenis, $kode, $tahun, $tanggal) {
            $urutan = (int) ModelNomorSurat::where('jenis', $jenis)->where('tahun', $tahun)->max('urutan') + 1;
            $nomor = sprintf('%03d/SDIT-DF/%s/%s/%d', $urutan, $kode, self::BULAN_ROMAWI[$tanggal->month], $tahun);
            ModelNomorSurat::create(['jenis' => $jenis, 'tahun' => $tahun, 'urutan' => $urutan, 'nomor' => $nomor]);

            return $nomor;
        });
    }

    /** Tanggal surat (Carbon atau "Y-m-d" dari form/API); kosong berarti hari ini. */
    private static function keCarbon(Carbon|string|null $tanggal): Carbon
    {
        if ($tanggal === null || $tanggal === '') {
            return Sekolah::now();
        }

        return $tanggal instanceof Carbon ? $tanggal : Carbon::parse($tanggal);
    }
}
