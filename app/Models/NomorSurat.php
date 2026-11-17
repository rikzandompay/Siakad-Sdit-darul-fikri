<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Buku nomor surat keluar (izin, pengumuman). */
class NomorSurat extends Model
{
    protected $table = 'nomor_surat';

    protected $fillable = ['jenis', 'tahun', 'urutan', 'nomor'];
}
