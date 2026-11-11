<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu guru hanya boleh absen dari satu perangkat.
 *
 * Perangkat pertama langsung aktif. Perangkat lain harus disetujui admin/kepala
 * sekolah. Id perangkat berasal dari aplikasi (ANDROID_ID / identifierForVendor),
 * jadi tidak bisa dipalsukan lewat request biasa tanpa mengubah aplikasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perangkat_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->string('perangkat_id', 191)->comment('ID perangkat dari aplikasi');
            $table->string('nama', 120)->nullable()->comment('Merek dan model, mis. Samsung SM-A125F');
            $table->string('platform', 20)->nullable()->comment('android / ios');
            $table->string('versi_os', 40)->nullable();

            // aktif = boleh absen, menunggu = menunggu persetujuan admin
            $table->string('status', 20)->default('aktif');

            $table->timestamp('disetujui_pada')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditolak_pada')->nullable();
            $table->string('catatan', 200)->nullable();

            $table->timestamp('terakhir_dipakai')->nullable();
            $table->timestamps();

            // Satu perangkat fisik hanya boleh terikat ke satu guru.
            $table->unique('perangkat_id');
            // Cegah dua baris "aktif" untuk guru yang sama.
            $table->index(['guru_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perangkat_guru');
    }
};
