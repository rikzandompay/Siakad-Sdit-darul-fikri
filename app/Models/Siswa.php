<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Siswa
 *
 * @property int $id
 * @property string $nis
 * @property string $nisn
 * @property string $nama_siswa
 * @property string $tanggal_lahir
 * @property string|null $alamat
 * @property string $jenis_kelamin
 * @property int $kelas_id
 * @property string|null $nama_wali
 * @property string|null $no_hp_wali
 * @property string $status
 * @property-read \App\Models\Kelas $kelas
 */
class Siswa extends Model
{
    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'nisn',
        'nama_siswa',
        'tanggal_lahir',
        'alamat',
        'jenis_kelamin',
        'kelas_id',
        'nama_wali',
        'no_hp_wali',
        'status',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function presensi()
    {
        return $this->hasMany(Presensi::class, 'siswa_id');
    }

    public function nilaiRapot()
    {
        return $this->hasMany(NilaiRapot::class, 'siswa_id');
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->nama_siswa);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return $initials;
    }
}
