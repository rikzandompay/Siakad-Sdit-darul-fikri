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

    protected $fillable = [
        'judul', 'isi', 'sasaran', 'penting', 'dibuat_oleh',
        'nomor_surat', 'tanggal_surat', 'tempat',
        'jadwal_hari_tanggal', 'jadwal_waktu', 'jadwal_tempat',
    ];

    protected function casts(): array
    {
        return ['penting' => 'boolean', 'tanggal_surat' => 'date'];
    }

    /** Ada blok jadwal kegiatan untuk dicetak di surat. */
    public function punyaJadwal(): bool
    {
        return filled($this->jadwal_hari_tanggal) || filled($this->jadwal_waktu) || filled($this->jadwal_tempat);
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

    /** Bentuk JSON seragam untuk seluruh endpoint pengumuman MyDarfik (admin, guru, wali). */
    public function untukApi(): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'isi' => $this->isi,
            'sasaran' => $this->sasaran,
            'penting' => $this->penting,
            'tanggal' => $this->created_at->toDateString(),
            'nomor_surat' => $this->nomor_surat,
            'tanggal_surat' => $this->tanggal_surat?->toDateString(),
            'tempat' => $this->tempat,
            'jadwal_hari_tanggal' => $this->jadwal_hari_tanggal,
            'jadwal_waktu' => $this->jadwal_waktu,
            'jadwal_tempat' => $this->jadwal_tempat,
        ];
    }
}
