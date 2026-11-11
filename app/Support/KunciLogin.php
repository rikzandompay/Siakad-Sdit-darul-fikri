<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Penguncian login bertingkat per akun.
 *
 * Kata sandi wali adalah tanggal lahir siswa: untuk satu angkatan hanya ada sekitar
 * 365-730 kemungkinan. Kunci tetap 5 menit per 5 percobaan membuat semua kemungkinan
 * bisa dicoba dalam sehari. Di sini lima percobaan pertama bebas, lalu setiap salah
 * berikutnya mengunci dua kali lebih lama (5, 10, 20 menit, ... maksimal 24 jam).
 * Hitungan salah bertahan 7 hari sejak salah terakhir dan hilang saat login berhasil,
 * sehingga menebak satu akun butuh berbulan-bulan.
 */
class KunciLogin
{
    public const BEBAS = 5;

    public const KUNCI_AWAL = 300;

    public const KUNCI_MAKS = 86400;

    public const INGAT_DETIK = 604800;

    /** Nama kunci login aplikasi untuk akun wali dengan NIS ini (sama dengan Api\V1\AuthController). */
    public static function wali(string $nis): string
    {
        return 'api-login:wali:'.mb_strtolower(trim($nis));
    }

    /** Detik tersisa sampai akun boleh mencoba lagi; 0 bila tidak terkunci. */
    public static function sisa(string $akun): int
    {
        $sampai = (int) Cache::get(static::kunci($akun, 'sampai'), 0);

        return max(0, $sampai - now()->getTimestamp());
    }

    /** Catat satu percobaan salah; kembalikan lama kunci (detik) yang baru berlaku, 0 bila belum. */
    public static function gagal(string $akun): int
    {
        $kunciGagal = static::kunci($akun, 'gagal');
        $jumlah = (int) Cache::get($kunciGagal, 0) + 1;
        Cache::put($kunciGagal, $jumlah, static::INGAT_DETIK);

        if ($jumlah < static::BEBAS) {
            return 0;
        }

        $lama = (int) min(static::KUNCI_MAKS, static::KUNCI_AWAL * (2 ** min(20, $jumlah - static::BEBAS)));
        Cache::put(static::kunci($akun, 'sampai'), now()->getTimestamp() + $lama, $lama);

        return $lama;
    }

    public static function jumlahGagal(string $akun): int
    {
        return (int) Cache::get(static::kunci($akun, 'gagal'), 0);
    }

    /** Login berhasil, atau Tata Usaha membuka kunci. */
    public static function bersihkan(string $akun): void
    {
        Cache::forget(static::kunci($akun, 'gagal'));
        Cache::forget(static::kunci($akun, 'sampai'));
    }

    /** "5 menit", "2 jam 40 menit". */
    public static function teks(int $detik): string
    {
        $menit = max(1, (int) ceil($detik / 60));
        if ($menit < 60) {
            return "{$menit} menit";
        }
        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        return $sisa ? "{$jam} jam {$sisa} menit" : "{$jam} jam";
    }

    private static function kunci(string $akun, string $bagian): string
    {
        return 'kunci-login:'.sha1(mb_strtolower(trim($akun))).':'.$bagian;
    }
}
