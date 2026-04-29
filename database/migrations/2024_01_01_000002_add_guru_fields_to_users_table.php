<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 30)->nullable()->after('id');
            $table->string('nama_lengkap', 100)->nullable()->after('nip');
            $table->string('username', 50)->unique()->nullable()->after('email');
            $table->string('no_hp', 20)->nullable()->after('username');
            $table->string('foto_profil')->nullable()->after('no_hp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nip', 'nama_lengkap', 'username', 'no_hp', 'foto_profil']);
        });
    }
};
