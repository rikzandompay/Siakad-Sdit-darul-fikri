<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 150);
            $table->text('isi');
            $table->string('sasaran', 20)->default('Semua')->comment('Semua | Guru | Wali Murid');
            $table->boolean('penting')->default(false);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sasaran', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
