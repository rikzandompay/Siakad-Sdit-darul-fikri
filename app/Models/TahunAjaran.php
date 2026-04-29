<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';

    protected $fillable = ['nama_semester', 'status_aktif'];

    public function nilaiRapot()
    {
        return $this->hasMany(NilaiRapot::class, 'tahun_ajaran_id');
    }

    public static function getAktif()
    {
        return static::where('status_aktif', 'Y')->first();
    }

    public function setSemesterAktif(): bool
    {
        static::where('id', '!=', $this->id)->where('status_aktif', 'Y')->update(['status_aktif' => 'N']);
        $this->status_aktif = 'Y';
        return $this->save();
    }
}
