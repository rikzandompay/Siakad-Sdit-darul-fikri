<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('nisn', 20)->nullable()->unique()->after('nis');
            $table->date('tanggal_lahir')->nullable()->after('nama_siswa');
            $table->string('alamat', 100)->nullable()->after('tanggal_lahir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn(['nisn', 'tanggal_lahir', 'alamat']);
        });
    }
};
