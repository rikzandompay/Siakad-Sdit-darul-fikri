<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konseling wali murid <-> guru pengampu konseling kelas.
 * Pihak wali diidentifikasi lewat siswa (wali dari siswa tsb) sampai akun wali
 * di aplikasi MyDarfik tersedia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->foreignId('pengampu_konseling_id')->nullable()->after('wali_kelas_id')
                ->constrained('users')->nullOnDelete();
        });

        Schema::create('konseling_percakapan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->string('topik', 150);
            $table->string('status', 10)->default('Aktif')->comment('Aktif | Selesai');
            $table->timestamp('pesan_terakhir_pada')->nullable();
            $table->timestamps();

            $table->index(['status', 'pesan_terakhir_pada']);
        });

        Schema::create('konseling_pesan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('percakapan_id')->constrained('konseling_percakapan')->cascadeOnDelete();
            $table->string('pengirim', 10)->comment('wali | guru');
            $table->foreignId('guru_id')->nullable()->constrained('users')->nullOnDelete()->comment('Diisi jika pengirim guru');
            $table->text('isi');
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamps();

            $table->index(['percakapan_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konseling_pesan');
        Schema::dropIfExists('konseling_percakapan');

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pengampu_konseling_id');
        });
    }
};
