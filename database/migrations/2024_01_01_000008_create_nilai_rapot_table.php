<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_rapot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('pelajaran_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->decimal('nilai_tugas', 5, 2)->default(0);
            $table->decimal('nilai_uts', 5, 2)->default(0);
            $table->decimal('nilai_uas', 5, 2)->default(0);
            $table->decimal('nilai_akhir', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['tahun_ajaran_id', 'siswa_id', 'pelajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_rapot');
    }
};
