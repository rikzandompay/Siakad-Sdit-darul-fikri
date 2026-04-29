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
}
