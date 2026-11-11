<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WajahGuru;
use App\Notifications\AcuanWajahGagal;
use App\Support\FotoPrivat;
use App\Support\PengenalWajah;
use App\Support\PengenalWajahGagal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Membuat acuan wajah presensi dari foto profil guru yang sudah disetujui, di server.
 * HP tidak pernah mengirim atau menerima acuan ini.
 */
class BuatAcuanWajah implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $guruId) {}

    public static function kunciGagal(int $guruId): string
    {
        return 'acuan-wajah-gagal:'.$guruId;
    }

    public function handle(PengenalWajah $pengenal): void
    {
        $guru = User::find($this->guruId);
        $foto = $guru?->foto_profil;
        $disk = FotoPrivat::disk($foto);
        if (! $guru || ! $foto || ! $disk) {
            return;
        }

        try {
            $embedding = $pengenal->dariFoto(Storage::disk($disk)->path($foto));
        } catch (PengenalWajahGagal $e) {
            Cache::forever(self::kunciGagal($guru->id), $e->pesan);
            $guru->notify(new AcuanWajahGagal($e->pesan));

            return;
        } finally {
            Cache::forget('acuan-wajah-antre:'.$guru->id);
        }

        // Foto diganti lagi selama job berjalan: biarkan job berikutnya yang menyimpan.
        if ($guru->fresh()->foto_profil !== $foto) {
            return;
        }

        Cache::forget(self::kunciGagal($guru->id));
        WajahGuru::updateOrCreate(['guru_id' => $guru->id], [
            'template' => json_encode($embedding),
            'didaftarkan_pada' => now(),
            'skor_foto' => 100.0,
            'mirip_guru_id' => null, 'mirip_skor' => null, 'mirip_pada' => null,
        ]);

        // Wajah yang sangat mirip guru lain (mis. kembar) ditandai agar terlihat admin.
        $mirip = WajahGuru::palingMirip($embedding, $guru->id);
        if ($mirip && $mirip['skor'] >= (float) config('presensi_guru.ambang_kembar_deteksi', 75)) {
            WajahGuru::tandaiMirip($guru->id, $mirip['guru_id'], $mirip['skor']);
        }
    }
}
