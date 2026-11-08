<?php

namespace App\Models\Concerns;

use App\Models\PerangkatNotifikasi;
use Illuminate\Notifications\Notifiable;

/** Riwayat notifikasi (tabel notifications) + token push FCM per perangkat. */
trait MenerimaNotifikasi
{
    use Notifiable;

    public static function bootMenerimaNotifikasi(): void
    {
        static::deleting(function ($model) {
            $model->perangkatNotifikasi()->delete();
            $model->notifications()->delete();
        });
    }

    public function perangkatNotifikasi()
    {
        return $this->morphMany(PerangkatNotifikasi::class, 'pemilik');
    }

    /** Dipakai NotificationChannels\Fcm\FcmChannel. */
    public function routeNotificationForFcm(): array
    {
        return $this->perangkatNotifikasi()->masihLogin()->pluck('token')->all();
    }
}
