<?php

use App\Models\Wali;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Buat akun wali untuk semua siswa yang sudah ada (username = NIS, kata sandi = nama wali).
 * Siswa baru otomatis lewat Siswa::saved.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wali') && Schema::hasTable('siswa_wali')) {
            Wali::sinkronSemua();
        }
    }

    public function down(): void
    {
        // Akun turunan dari data siswa; tidak ada yang perlu dikembalikan.
    }
};
