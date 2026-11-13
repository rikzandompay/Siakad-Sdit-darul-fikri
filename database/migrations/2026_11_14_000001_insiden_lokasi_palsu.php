<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti permanen percobaan absen dengan lokasi palsu, dan tanda koreksi absen milik sendiri.
 * Dibutuhkan karena kepala sekolah adalah satu-satunya admin: tidak ada atasan di sistem yang
 * bisa diberi tahu, jadi kejadian harus tercatat dan tampil di rekap tanpa bisa dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insiden_lokasi_palsu', function (Blueprint $t) {
            $t->id();
            $t->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $t->date('tanggal');
            $t->time('jam');
            $t->string('tahap', 30);
            $t->unsignedInteger('jarak_meter')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['guru_id', 'tanggal']);
        });

        Schema::table('presensi_guru', fn (Blueprint $t) => $t->boolean('koreksi_mandiri')->default(false));
    }

    public function down(): void
    {
        Schema::dropIfExists('insiden_lokasi_palsu');
        Schema::table('presensi_guru', fn (Blueprint $t) => $t->dropColumn('koreksi_mandiri'));
    }
};
