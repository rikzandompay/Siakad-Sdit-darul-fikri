<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiTugas extends Model
{
    protected $table = 'nilai_tugas';

    protected $fillable = ['tugas_id', 'siswa_id', 'nilai', 'catatan'];

    protected function casts(): array
    {
        return ['nilai' => 'float'];
    }

    public function tugas()
    {
        return $this->belongsTo(Tugas::class, 'tugas_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
