<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Pembatas salah login guru (web dan aplikasi).
 *
 * Kunci jangka pendek per username + IP, sehingga orang yang tahu username/NIP guru tidak
 * bisa mengunci akun itu dari perangkat lain hanya dengan 5 kali salah. Batas lebar per
 * username dari semua IP tetap ada agar penebakan dari banyak IP sekaligus tetap lambat.
 */
class BatasLoginGuru
{
    public const MAKS_PER_IP = 5;

    public const DETIK_PER_IP = 300;

    public const MAKS_LEBAR = 30;

    public const DETIK_LEBAR = 900;

    /** Detik sampai boleh mencoba lagi; 0 bila tidak terkunci. */
    public static function sisa(string $username, ?string $ip): int
    {
        [$perIp, $lebar] = static::kunci($username, $ip);
        $sisa = 0;
        if (RateLimiter::tooManyAttempts($perIp, static::MAKS_PER_IP)) {
            $sisa = RateLimiter::availableIn($perIp);
        }
        if (RateLimiter::tooManyAttempts($lebar, static::MAKS_LEBAR)) {
            $sisa = max($sisa, RateLimiter::availableIn($lebar));
        }

        return $sisa;
    }

    public static function gagal(string $username, ?string $ip): void
    {
        [$perIp, $lebar] = static::kunci($username, $ip);
        RateLimiter::hit($perIp, static::DETIK_PER_IP);
        RateLimiter::hit($lebar, static::DETIK_LEBAR);
    }

    /** Login berhasil: hapus hitungan dari IP ini (batas lebar dibiarkan habis sendiri). */
    public static function berhasil(string $username, ?string $ip): void
    {
        RateLimiter::clear(static::kunci($username, $ip)[0]);
    }

    /** @return array{0: string, 1: string} */
    private static function kunci(string $username, ?string $ip): array
    {
        $u = sha1(mb_strtolower(trim($username)));

        return ["login-guru:{$u}:".($ip ?? '-'), "login-guru-lebar:{$u}"];
    }
}
