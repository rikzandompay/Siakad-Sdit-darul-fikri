<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table) {
            $table->id();
            $table->string('nama_semester', 50);
            $table->enum('status_aktif', ['Y', 'N'])->default('N');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_ajaran');
    }
};
