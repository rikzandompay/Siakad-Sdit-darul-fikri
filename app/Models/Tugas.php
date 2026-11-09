<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Tugas extends Model
{
    protected $table = 'tugas';

    protected $fillable = ['kelas_id', 'pelajaran_id', 'guru_id', 'judul', 'deskripsi', 'tenggat'];

    protected function casts(): array
    {
        return ['tenggat' => 'date'];
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'pelajaran_id');
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function nilai()
    {
        return $this->hasMany(NilaiTugas::class, 'tugas_id');
    }

    /** Hanya tugas milik guru itu sendiri. Kepala sekolah/admin tidak otomatis melihat semuanya. */
    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        return $query->where('guru_id', $user->id);
    }

    /**
     * Ruang tugas milik mata pelajaran di kelas: hanya guru yang mengajarnya di kelas itu (guru pengampu).
     * Kepala sekolah/admin tidak ikut memberi tugas kecuali ia sendiri mengampu mata pelajaran itu.
     */
    public static function bisaMengelolaRuang(User $user, int $kelasId, int $pelajaranId): bool
    {
        return JadwalPelajaran::where('guru_id', $user->id)
            ->where('kelas_id', $kelasId)
            ->where('pelajaran_id', $pelajaranId)
            ->exists();
    }

    public function bisaDikelolaOleh(User $user): bool
    {
        return static::bisaMengelolaRuang($user, $this->kelas_id, $this->pelajaran_id);
    }
}
