<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto profil guru menjadi acuan identitas wajah: foto baru menunggu persetujuan admin.
 * Wajah guru mencatat kecocokan dengan foto profil dan tanda bila sangat mirip guru lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto_pengajuan')->nullable();
            $table->timestamp('foto_pengajuan_pada')->nullable();
        });

        Schema::table('wajah_guru', function (Blueprint $table) {
            $table->float('skor_foto')->nullable();
            $table->foreignId('mirip_guru_id')->nullable()->constrained('users')->nullOnDelete();
            $table->float('mirip_skor')->nullable();
            $table->timestamp('mirip_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wajah_guru', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mirip_guru_id');
            $table->dropColumn(['skor_foto', 'mirip_skor', 'mirip_pada']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['foto_pengajuan', 'foto_pengajuan_pada']);
        });
    }
};
