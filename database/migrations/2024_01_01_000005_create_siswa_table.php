<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 20)->unique();
            $table->string('nama_siswa', 100);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->string('nama_wali', 100)->nullable();
            $table->string('no_hp_wali', 20)->nullable();
            $table->enum('status', ['Aktif', 'Lulus', 'Pindah'])->default('Aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
