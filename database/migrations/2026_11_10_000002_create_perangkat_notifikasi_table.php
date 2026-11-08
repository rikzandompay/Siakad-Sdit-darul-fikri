<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token FCM perangkat milik guru (users) atau wali murid (wali) untuk notifikasi push.
 * Terikat ke token login (Sanctum): saat logout, token dicabut, atau akun dinonaktifkan,
 * baris ini ikut terhapus sehingga HP itu tidak lagi menerima notifikasi akun tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perangkat_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->morphs('pemilik');
            $table->foreignId('akses_id')->nullable()->constrained('personal_access_tokens')->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->string('platform', 20)->default('android');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perangkat_notifikasi');
    }
};
