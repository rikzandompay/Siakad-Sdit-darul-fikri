<?php

use App\Models\Wali;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Kata sandi wali berpindah dari nama wali ke tanggal lahir siswa (YY-MM-DD).
 * Sinkron ulang meng-hash sandi baru dan mencabut token lama.
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
        // Sandi lama (nama wali) sengaja tidak dikembalikan.
    }
};
