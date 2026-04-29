<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pelajaran', 20)->unique();
            $table->string('nama_pelajaran', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_pelajaran');
    }
};
