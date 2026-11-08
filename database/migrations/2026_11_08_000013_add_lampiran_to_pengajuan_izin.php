<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Foto surat dokter / bukti izin yang diunggah wali dari aplikasi (disimpan privat). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_izin', function (Blueprint $table) {
            $table->string('lampiran', 255)->nullable()->after('alasan');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_izin', function (Blueprint $table) {
            $table->dropColumn('lampiran');
        });
    }
};
