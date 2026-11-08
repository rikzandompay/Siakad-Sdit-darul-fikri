<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Absen guru dari aplikasi MyDarfik (verifikasi lokasi + wajah).
 * Satu baris per guru per hari: jam masuk dan jam pulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status', 20)->comment('Hadir | Tidak Hadir');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();

            $table->decimal('masuk_latitude', 10, 7)->nullable();
            $table->decimal('masuk_longitude', 10, 7)->nullable();
            $table->unsignedInteger('masuk_jarak_meter')->nullable();
            $table->decimal('masuk_skor_wajah', 5, 2)->nullable()->comment('Persen kecocokan wajah');

            $table->decimal('pulang_latitude', 10, 7)->nullable();
            $table->decimal('pulang_longitude', 10, 7)->nullable();
            $table->unsignedInteger('pulang_jarak_meter')->nullable();
            $table->decimal('pulang_skor_wajah', 5, 2)->nullable();

            $table->string('sumber', 20)->default('aplikasi')->comment('aplikasi | otomatis | admin');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(['guru_id', 'tanggal']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_guru');
    }
};
