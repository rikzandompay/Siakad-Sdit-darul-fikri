<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';

    protected $fillable = ['kode_pelajaran', 'nama_pelajaran'];

    public function jadwalPelajaran()
    {
        return $this->hasMany(JadwalPelajaran::class, 'pelajaran_id');
    }

    public function nilaiRapot()
    {
        return $this->hasMany(NilaiRapot::class, 'pelajaran_id');
    }
}
