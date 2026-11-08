<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akun wali murid untuk aplikasi MyDarfik. Satu wali bisa memantau beberapa anak.
 * Akun dibuat dan di-reset oleh admin di web; wali hanya login di aplikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wali', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('no_hp', 20)->nullable();
            $table->string('status', 10)->default('Aktif')->comment('Aktif | Nonaktif');
            $table->timestamp('terakhir_login_pada')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('siswa_wali', function (Blueprint $table) {
            $table->foreignId('wali_id')->constrained('wali')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->primary(['wali_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_wali');
        Schema::dropIfExists('wali');
    }
};
