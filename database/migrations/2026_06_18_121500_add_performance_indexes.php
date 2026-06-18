<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->index('status', 'siswa_status_index');
            $table->index('nama_siswa', 'siswa_nama_siswa_index');
        });

        Schema::table('presensi_sholat', function (Blueprint $table) {
            $table->index(['tanggal', 'jenis_sholat', 'kelas_id'], 'presensi_sholat_tanggal_jenis_kelas_index');
        });
    }

    public function down(): void
    {
        Schema::table('presensi_sholat', function (Blueprint $table) {
            $table->dropIndex('presensi_sholat_tanggal_jenis_kelas_index');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropIndex('siswa_status_index');
            $table->dropIndex('siswa_nama_siswa_index');
        });
    }
};
