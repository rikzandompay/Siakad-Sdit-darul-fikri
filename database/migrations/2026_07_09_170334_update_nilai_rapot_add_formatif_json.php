<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $renames = [
            'detail_formatif' => 'formatif_data',
            'nilai_sas' => 'sas',
            'nilai_kehadiran' => 'kehadiran',
            'nilai_akhir' => 'nilai_rapor',
        ];

        foreach ($renames as $from => $to) {
            if (Schema::hasColumn('nilai_rapot', $from) && ! Schema::hasColumn('nilai_rapot', $to)) {
                Schema::table('nilai_rapot', fn (Blueprint $table) => $table->renameColumn($from, $to));
            }
        }

        Schema::table('nilai_rapot', function (Blueprint $table): void {
            if (! Schema::hasColumn('nilai_rapot', 'formatif_data')) {
                $table->json('formatif_data')->nullable();
            }
            if (! Schema::hasColumn('nilai_rapot', 'sas')) {
                $table->decimal('sas', 5, 2)->default(0);
            }
            if (! Schema::hasColumn('nilai_rapot', 'kehadiran')) {
                $table->decimal('kehadiran', 5, 2)->default(0);
            }
            if (! Schema::hasColumn('nilai_rapot', 'nilai_rapor')) {
                $table->decimal('nilai_rapor', 5, 2)->default(0);
            }
            if (! Schema::hasColumn('nilai_rapot', 'pengurang_tidaktelat')) {
                $table->decimal('pengurang_tidaktelat', 5, 2)->default(0);
            }
            if (! Schema::hasColumn('nilai_rapot', 'pengurang_menyontek')) {
                $table->decimal('pengurang_menyontek', 5, 2)->default(0);
            }
        });

        // Preserve legacy grade columns. Removing them requires a separate, verified data migration.
    }

    public function down(): void
    {
        Schema::table('nilai_rapot', function (Blueprint $table): void {
            $table->decimal('nilai_formatif', 5, 2)->default(0);
            $table->decimal('nilai_pengurang', 5, 2)->default(0);
            $table->dropColumn(['pengurang_tidaktelat', 'pengurang_menyontek']);
            $table->renameColumn('formatif_data', 'detail_formatif');
            $table->renameColumn('sas', 'nilai_sas');
            $table->renameColumn('kehadiran', 'nilai_kehadiran');
            $table->renameColumn('nilai_rapor', 'nilai_akhir');
        });
    }
};