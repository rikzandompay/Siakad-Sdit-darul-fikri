<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Foto guru dan siswa disimpan di disk privat (storage/app/private), bukan public,
 * dan hanya disajikan lewat rute yang memeriksa login (FotoController).
 */
class FotoPrivat
{
    public const DISK = 'local';

    public static function simpan(UploadedFile $file, string $folder, ?string $lama): string
    {
        self::hapus($lama);

        return $file->store($folder, self::DISK);
    }

    /** Hapus berkas di disk privat, juga sisa lama di disk public bila ada. */
    public static function hapus(?string $path): void
    {
        if (! $path) {
            return;
        }
        foreach ([self::DISK, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    /** Disk tempat berkas berada (privat; public hanya untuk data lama yang belum dipindah). */
    public static function disk(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        foreach ([self::DISK, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    /** Foto sebagai data-URI untuk PDF (berkasnya privat, dompdf tidak membawa sesi login). */
    public static function dataUri(?string $path): ?string
    {
        $disk = static::disk($path);
        if (! $disk) {
            return null;
        }
        $storage = Storage::disk($disk);

        return 'data:'.($storage->mimeType($path) ?: 'image/jpeg').';base64,'.base64_encode($storage->get($path));
    }

    /** Penanda versi agar gambar lama di cache HP/browser tergantikan setelah foto diganti. */
    public static function versi(string $path): string
    {
        return substr(md5($path), 0, 10);
    }
}
