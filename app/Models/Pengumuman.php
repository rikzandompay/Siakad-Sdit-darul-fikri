<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    public const SEMUA = 'Semua';

    public const GURU = 'Guru';

    public const WALI = 'Wali Murid';

    public const SASARAN = [self::SEMUA, self::GURU, self::WALI];

    protected $table = 'pengumuman';

    protected $fillable = ['judul', 'isi', 'sasaran', 'penting', 'dibuat_oleh'];

    protected function casts(): array
    {
        return ['penting' => 'boolean'];
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** Pengumuman yang boleh dilihat guru (admin melihat semua). */
    public function scopeUntukGuru(Builder $query): Builder
    {
        return $query->whereIn('sasaran', [self::SEMUA, self::GURU]);
    }
}
