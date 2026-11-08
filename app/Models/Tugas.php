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

    /** Admin melihat semua; guru hanya tugas miliknya. */
    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where('guru_id', $user->id);
    }

    /** Ruang tugas milik mata pelajaran di kelas: admin atau guru yang mengajarnya di kelas itu. */
    public static function bisaMengelolaRuang(User $user, int $kelasId, int $pelajaranId): bool
    {
        return $user->isAdmin() || JadwalPelajaran::where('guru_id', $user->id)
            ->where('kelas_id', $kelasId)
            ->where('pelajaran_id', $pelajaranId)
            ->exists();
    }

    public function bisaDikelolaOleh(User $user): bool
    {
        return static::bisaMengelolaRuang($user, $this->kelas_id, $this->pelajaran_id);
    }
}
