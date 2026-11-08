<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Pindahkan foto guru dan siswa dari disk public (bisa dibuka siapa saja lewat /storage/...)
 * ke disk privat. Aman dijalankan ulang: berkas yang sudah pindah dilewati.
 */
return new class extends Migration
{
    public function up(): void
    {
        $paths = DB::table('users')->whereNotNull('foto_profil')->pluck('foto_profil')
            ->merge(DB::table('siswa')->whereNotNull('foto')->pluck('foto'));

        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path) && ! Storage::disk('local')->exists($path)) {
                Storage::disk('local')->put($path, Storage::disk('public')->get($path));
            }
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    public function down(): void
    {
        // Sengaja tidak mengembalikan foto ke penyimpanan publik.
    }
};
