<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = ['nama_kelas', 'wali_kelas_id'];

    public function waliKelas()
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    /** Guru yang melayani konseling wali murid kelas ini (diatur admin). */
    public function pengampuKonseling()
    {
        return $this->belongsTo(User::class, 'pengampu_konseling_id');
    }

    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'kelas_id');
    }

    public function jadwalPelajaran()
    {
        return $this->hasMany(JadwalPelajaran::class, 'kelas_id');
    }

    public function siswaAktif()
    {
        return $this->siswa()->where('status', 'Aktif');
    }

    /**
     * Apakah kelas ini mengikuti presensi sholat jenis tertentu.
     * Sholat Dzuhur hanya untuk kelas 3 hingga 6; kelas 1 dan 2 hanya mengikuti Dhuha.
     */
    public function ikutSholat(string $jenis): bool
    {
        if ($jenis !== PresensiSholat::DZUHUR) {
            return true;
        }

        if (preg_match('/\d+/', $this->nama_kelas, $matches)) {
            return (int) $matches[0] >= 3;
        }

        return true;
    }

    /** @return array<int, string> */
    public function jenisSholat(): array
    {
        return array_values(array_filter(PresensiSholat::JENIS, fn ($jenis) => $this->ikutSholat($jenis)));
    }
}
