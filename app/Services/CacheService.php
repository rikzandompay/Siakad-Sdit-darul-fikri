<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * CacheService - Helper untuk manajemen cache SisforSinta
 *
 * Strategi cache:
 * - Static data (kelas, siswa, mapel, jadwal): 30-60 menit
 * - Dynamic data (presensi hari ini): 5 menit
 * - Per-user cache key untuk data yang user-specific
 */
class CacheService
{
    // TTL Constants (dalam menit)
    const TTL_STATIC   = 60;   // Kelas, siswa, mata pelajaran
    const TTL_JADWAL   = 60;   // Jadwal pelajaran
    const TTL_PRESENSI = 5;    // Presensi hari ini
    const TTL_REKAP    = 15;   // Rekap bulanan
    const TTL_NILAI    = 30;   // Nilai rapot

    // ─── Cache Key Helpers ──────────────────────────────────────────────

    public static function keyKelasAll(): string
    {
        return 'kelas:all';
    }

    public static function keyKelasList(): string
    {
        return 'kelas:list_with_count';
    }

    public static function keyKelasDetail(int $kelasId): string
    {
        return "kelas:detail:{$kelasId}";
    }

    public static function keySiswaByKelas(int $kelasId): string
    {
        return "siswa:kelas:{$kelasId}";
    }

    public static function keyJadwalGuru(int $guruId): string
    {
        return "jadwal:guru:{$guruId}";
    }

    public static function keyJadwalKelas(int $kelasId): string
    {
        return "jadwal:kelas:{$kelasId}";
    }

    public static function keyKelasIdsByGuru(int $guruId): string
    {
        return "jadwal:kelas_ids:guru:{$guruId}";
    }

    public static function keyPresensiHariIni(int $guruId): string
    {
        return 'presensi:hari_ini:guru:' . $guruId . ':' . now()->format('Y-m-d');
    }

    public static function keyPresensiSholatHariIni(int $guruId, string $jenis): string
    {
        return 'presensi_sholat:hari_ini:guru:' . $guruId . ':jenis:' . $jenis . ':' . now()->format('Y-m-d');
    }

    public static function keyMataPelajaranAll(): string
    {
        return 'mata_pelajaran:all';
    }

    public static function keyTahunAjaranList(): string
    {
        return 'tahun_ajaran:list';
    }

    public static function keyTahunAjaranAktif(): string
    {
        return 'tahun_ajaran:aktif';
    }

    public static function keyDashboard(int $guruId): string
    {
        return 'dashboard:guru:' . $guruId . ':' . now()->format('Y-m-d');
    }

    // ─── Cache Invalidation ──────────────────────────────────────────────

    /**
     * Clear semua cache yang berhubungan dengan kelas
     */
    public static function clearKelas(int $kelasId): void
    {
        Cache::forget(self::keyKelasAll());
        Cache::forget(self::keyKelasList());
        Cache::forget('kelas:index_page');
        Cache::forget('kelas:dashboard_list');
        Cache::forget(self::keyKelasDetail($kelasId));
        Cache::forget(self::keySiswaByKelas($kelasId));
        Cache::forget(self::keyJadwalKelas($kelasId));
    }

    /**
     * Clear semua cache yang berhubungan dengan presensi guru
     */
    public static function clearPresensiGuru(int $guruId): void
    {
        Cache::forget(self::keyPresensiHariIni($guruId));
        Cache::forget(self::keyDashboard($guruId));
    }

    /**
     * Clear semua cache presensi sholat
     */
    public static function clearPresensiSholat(int $guruId, string $jenis): void
    {
        Cache::forget(self::keyPresensiSholatHariIni($guruId, $jenis));
        Cache::forget(self::keyDashboard($guruId));
    }

    /**
     * Clear cache jadwal guru
     */
    public static function clearJadwal(?int $guruId = null, ?int $kelasId = null): void
    {
        if ($guruId) {
            Cache::forget(self::keyJadwalGuru($guruId));
            Cache::forget(self::keyKelasIdsByGuru($guruId));
            Cache::forget(self::keyDashboard($guruId));
        }
        if ($kelasId) {
            Cache::forget(self::keyJadwalKelas($kelasId));
            Cache::forget(self::keyKelasDetail($kelasId));
        }
    }

    /**
     * Clear cache siswa
     */
    public static function clearSiswa(int $kelasId): void
    {
        Cache::forget(self::keySiswaByKelas($kelasId));
        Cache::forget(self::keyKelasAll());
        Cache::forget(self::keyKelasList());
        // Clear siswa count cache untuk semua guru (simpel: flush by tag kalau pakai Redis)
    }

    /**
     * Clear cache nilai rapot
     */
    public static function clearNilai(int $tahunAjaranId, int $pelajaranId): void
    {
        Cache::forget("nilai:tahun:{$tahunAjaranId}:mapel:{$pelajaranId}");
    }
}
