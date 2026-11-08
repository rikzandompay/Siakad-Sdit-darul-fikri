<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WajahGuru extends Model
{
    protected $table = 'wajah_guru';

    protected $fillable = ['guru_id', 'template', 'didaftarkan_pada'];

    /** Template wajah tidak boleh bocor lewat serialisasi. */
    protected $hidden = ['template'];

    protected function casts(): array
    {
        return ['template' => 'encrypted', 'didaftarkan_pada' => 'datetime'];
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}
