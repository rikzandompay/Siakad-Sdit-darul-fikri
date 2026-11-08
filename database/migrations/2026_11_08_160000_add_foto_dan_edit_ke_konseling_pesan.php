<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('konseling_pesan', function (Blueprint $table) {
            $table->text('isi')->nullable()->change();
            $table->string('foto')->nullable()->after('isi')->comment('Path di disk privat (local)');
            $table->timestamp('diedit_pada')->nullable()->after('dibaca_pada');
        });
    }

    public function down(): void
    {
        Schema::table('konseling_pesan', function (Blueprint $table) {
            $table->dropColumn(['foto', 'diedit_pada']);
        });
    }
};
