<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data wajah guru untuk absen (didaftarkan sekali dari aplikasi).
 * Template disimpan terenkripsi; web hanya menampilkan status dan bisa mereset.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wajah_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('template')->comment('Terenkripsi (cast encrypted)');
            $table->timestamp('didaftarkan_pada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wajah_guru');
    }
};
