<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal_pelajaran')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status_kehadiran', ['H', 'I', 'S', 'A'])->comment('H=Hadir, I=Izin, S=Sakit, A=Alpa');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(['jadwal_id', 'siswa_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};
