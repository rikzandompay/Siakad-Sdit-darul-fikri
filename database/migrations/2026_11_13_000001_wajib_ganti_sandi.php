<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tandai akun yang masih memakai kata sandi bawaan (dari admin / tanggal lahir siswa).
 * Aplikasi mengarahkan akun bertanda ke halaman ganti kata sandi sebelum fitur lain.
 * Semua akun yang sudah ada dianggap masih bawaan.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'wali'] as $tabel) {
            Schema::table($tabel, fn (Blueprint $t) => $t->boolean('wajib_ganti_sandi')->default(true));
            DB::table($tabel)->update(['wajib_ganti_sandi' => true]);
        }
    }

    public function down(): void
    {
        foreach (['users', 'wali'] as $tabel) {
            Schema::table($tabel, fn (Blueprint $t) => $t->dropColumn('wajib_ganti_sandi'));
        }
    }
};
