<?php

namespace App\Support;

use App\Models\HariLibur;
use App\Models\User;
use Carbon\Carbon;

/**
 * Waktu dan kalender sekolah. Aplikasi berjalan di UTC, sedangkan semua aturan
 * presensi memakai jam sekolah (WIB, Kalimantan Barat).
 */
class Sekolah
{
    public const NAMA = 'SDIT Darul Fikri';

    /** Nama kepala sekolah tanpa gelar: akun admin pertama menurut urutan ADMIN_USERNAMES. */
    public static function kepalaSekolah(): string
    {
        foreach (config('admin.usernames', []) as $username) {
            $user = User::where('status', 'Aktif')->whereRaw('lower(username) = ?', [strtolower($username)])->first();
            if ($user) {
                return trim(explode(',', (string) ($user->nama_lengkap ?? $user->name))[0]);
            }
        }

        return '';
    }

    public const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public static function timezone(): string
    {
        return config('presensi_guru.timezone', 'Asia/Pontianak');
    }

    public static function now(): Carbon
    {
        return Carbon::now(static::timezone());
    }

    public static function hari(?Carbon $tanggal = null): string
    {
        return static::HARI[($tanggal ?? static::now())->dayOfWeekIso];
    }

    public static function hariSekolah(?Carbon $tanggal = null): bool
    {
        $tanggal ??= static::now();

        return in_array($tanggal->dayOfWeekIso, config('presensi_guru.hari_sekolah'), true)
            && ! HariLibur::isLibur($tanggal);
    }

    /** Jarak dua titik GPS dalam meter (haversine). */
    public static function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public static function inisial(?string $nama): string
    {
        $kata = preg_split('/\s+/', trim(preg_replace('/^(ust\.?|ustadz|ustadzah|hj\.?|h\.?|bu|pak)\s+/i', '', (string) $nama)));

        return strtoupper(implode('', array_map(fn ($k) => mb_substr($k, 0, 1), array_slice(array_filter($kata), 0, 2))));
    }
}
