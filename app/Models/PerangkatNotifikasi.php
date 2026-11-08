<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken;

/** Token FCM satu perangkat. Pemilik: User (guru/admin) atau Wali. */
class PerangkatNotifikasi extends Model
{
    protected $table = 'perangkat_notifikasi';

    protected $fillable = ['pemilik_type', 'pemilik_id', 'akses_id', 'token', 'platform'];

    public function pemilik()
    {
        return $this->morphTo();
    }

    public function akses()
    {
        return $this->belongsTo(PersonalAccessToken::class, 'akses_id');
    }

    /** Hanya perangkat yang sesi loginnya masih berlaku (belum logout/kedaluwarsa). */
    public function scopeMasihLogin(Builder $query): Builder
    {
        $menit = config('sanctum.expiration');

        return $query->whereHas('akses', fn ($q) => $q
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->when($menit, fn ($q) => $q->where('created_at', '>', now()->subMinutes($menit))));
    }
}
