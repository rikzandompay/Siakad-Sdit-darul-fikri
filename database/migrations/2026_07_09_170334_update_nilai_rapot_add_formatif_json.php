<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Mengubah struktur tabel nilai_rapot yang sudah ada untuk mendukung:
     * - Data Formatif detail dalam JSONB (4 BAB × 5 komponen = 20 kolom)
     * - SAS (Sumatif Akhir Semester)
     * - Kehadiran (maks 20 poin)
     * - Pengurang detail (Tidak/Telat dan Menyontek)
     * 
     * Kolom yang sudah ada: nilai_formatif, nilai_sas, nilai_kehadiran, nilai_pengurang, detail_formatif (json)
     */
    public function up(): void
    {
        // Rename existing columns to match new naming convention
        Schema::table('nilai_rapot', function (Blueprint $table) {
            // Rename detail_formatif to formatif_data (JSONB for detailed Formatif data)
            $table->renameColumn('detail_formatif', 'formatif_data');
            
            // Rename nilai_sas to sas
            $table->renameColumn('nilai_sas', 'sas');
            
            // Rename nilai_kehadiran to kehadiran
            $table->renameColumn('nilai_kehadiran', 'kehadiran');
            
            // Rename nilai_akhir to nilai_rapor
            $table->renameColumn('nilai_akhir', 'nilai_rapor');
        });

        // Drop nilai_formatif and nilai_pengurang columns, replace with detailed columns
        Schema::table('nilai_rapot', function (Blueprint $table) {
            $table->dropColumn(['nilai_formatif', 'nilai_pengurang']);
        });

        // Add new pengurang columns
        Schema::table('nilai_rapot', function (Blueprint $table) {
            $table->decimal('pengurang_tidaktelat', 5, 2)->default(0)->after('kehadiran');
            $table->decimal('pengurang_menyontek', 5, 2)->default(0)->after('pengurang_tidaktelat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nilai_rapot', function (Blueprint $table) {
            // Restore old columns
            $table->decimal('nilai_formatif', 5, 2)->default(0);
            $table->decimal('nilai_pengurang', 5, 2)->default(0);
            
            // Drop new pengurang columns
            $table->dropColumn(['pengurang_tidaktelat', 'pengurang_menyontek']);
            
            // Rename columns back
            $table->renameColumn('formatif_data', 'detail_formatif');
            $table->renameColumn('sas', 'nilai_sas');
            $table->renameColumn('kehadiran', 'nilai_kehadiran');
            $table->renameColumn('nilai_rapor', 'nilai_akhir');
        });
    }
};
