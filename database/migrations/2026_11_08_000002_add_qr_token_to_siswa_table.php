<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Token acak untuk QR di Kartu Pelajar (scan presensi sholat di MyDarfik).
 * Token tidak memuat NIS/NISN supaya QR tidak bisa ditebak dari data siswa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('status');
        });

        DB::table('siswa')->whereNull('qr_token')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('siswa')->where('id', $row->id)->update(['qr_token' => Str::random(40)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
