<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengaturan sekolah (lokasi, radius, jam masuk) yang dibaca aplikasi MyDarfik.
        Schema::create('pengaturan_sekolah', function (Blueprint $table) {
            $table->string('kunci', 50)->primary();
            $table->text('nilai')->nullable();
            $table->timestamps();
        });

        // Hari libur: sholat dan absen guru tidak dicatat Tidak Hadir otomatis pada tanggal ini.
        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('keterangan', 150);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_libur');
        Schema::dropIfExists('pengaturan_sekolah');
    }
};
