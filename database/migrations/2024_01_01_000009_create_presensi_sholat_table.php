<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi_sholat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('jenis_sholat', ['Zuhur', 'Dhuha']);
            $table->enum('status_kehadiran', ['H', 'I', 'S', 'A'])->comment('H=Hadir, I=Izin, S=Sakit, A=Alpa');
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['siswa_id', 'tanggal', 'jenis_sholat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi_sholat');
    }
};
