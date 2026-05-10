<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nip')) {
                $table->string('nip', 30)->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'nama_lengkap')) {
                $table->string('nama_lengkap', 100)->nullable()->after('nip');
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 50)->unique()->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->after('username');
            }
            if (!Schema::hasColumn('users', 'foto_profil')) {
                $table->string('foto_profil')->nullable()->after('no_hp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nip', 'nama_lengkap', 'username', 'no_hp', 'foto_profil']);
        });
    }
};
