<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Percakapan konseling antara wali dari seorang siswa dan pengampu konseling kelasnya.
 */
class KonselingPercakapan extends Model
{
    public const AKTIF = 'Aktif';

    public const SELESAI = 'Selesai';

    protected $table = 'konseling_percakapan';

    protected $fillable = ['siswa_id', 'topik', 'status', 'pesan_terakhir_pada'];

    protected function casts(): array
    {
        return ['pesan_terakhir_pada' => 'datetime'];
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function pesan()
    {
        return $this->hasMany(KonselingPesan::class, 'percakapan_id');
    }

    public function pesanTerakhir()
    {
        return $this->hasOne(KonselingPesan::class, 'percakapan_id')->latestOfMany();
    }

    /** Pesan wali yang belum dibaca pihak sekolah. */
    public function pesanWaliBelumDibaca()
    {
        return $this->pesan()->where('pengirim', KonselingPesan::WALI)->whereNull('dibaca_pada');
    }

    /**
     * Percakapan bersifat privat: hanya pengampu konseling kelas siswa (dan wali murid
     * lewat aplikasi). Admin/kepala sekolah tidak ikut membaca isinya.
     */
    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        return $query->whereHas('siswa.kelas', fn ($q) => $q->where('pengampu_konseling_id', $user->id));
    }

    public function bisaDibukaOleh(User $user): bool
    {
        return $this->siswa?->kelas?->pengampu_konseling_id === $user->id;
    }
}
