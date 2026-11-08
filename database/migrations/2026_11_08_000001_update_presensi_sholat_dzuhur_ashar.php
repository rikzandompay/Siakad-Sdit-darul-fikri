<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Presensi sholat mengikuti aplikasi MyDarfik:
 * - jenis: Dzuhur dan Ashar (Dhuha lama tetap tersimpan sebagai arsip)
 * - status: Hadir / Tidak Hadir (status H/I/S/A lama disimpan di status_lama)
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropPgsqlCheckConstraints();

        // Enum -> string supaya nilai baru bisa disimpan (aman di SQLite, MySQL, PostgreSQL).
        Schema::table('presensi_sholat', function (Blueprint $table) {
            $table->string('jenis_sholat', 20)->change();
            $table->string('status_kehadiran', 20)->change();
        });

        Schema::table('presensi_sholat', function (Blueprint $table) {
            $table->string('status_lama', 1)->nullable()->after('status_kehadiran')
                ->comment('Arsip status sebelum Nov 2026: H/I/S/A');
        });

        DB::table('presensi_sholat')->update(['status_lama' => DB::raw('status_kehadiran')]);
        DB::table('presensi_sholat')->where('status_lama', 'H')->update(['status_kehadiran' => 'Hadir']);
        DB::table('presensi_sholat')->whereIn('status_lama', ['I', 'S', 'A'])->update(['status_kehadiran' => 'Tidak Hadir']);
        DB::table('presensi_sholat')->where('jenis_sholat', 'Zuhur')->update(['jenis_sholat' => 'Dzuhur']);
    }

    public function down(): void
    {
        // Data Ashar tidak punya padanan di skema lama.
        DB::table('presensi_sholat')->where('jenis_sholat', 'Ashar')->delete();

        DB::table('presensi_sholat')->whereNull('status_lama')->where('status_kehadiran', 'Hadir')->update(['status_lama' => 'H']);
        DB::table('presensi_sholat')->whereNull('status_lama')->update(['status_lama' => 'A']);
        DB::table('presensi_sholat')->update(['status_kehadiran' => DB::raw('status_lama')]);
        DB::table('presensi_sholat')->where('jenis_sholat', 'Dzuhur')->update(['jenis_sholat' => 'Zuhur']);

        Schema::table('presensi_sholat', function (Blueprint $table) {
            $table->dropColumn('status_lama');
        });
    }

    /**
     * Di PostgreSQL, kolom enum Laravel adalah varchar + CHECK constraint.
     * Constraint itu harus dibuang sebelum nilai baru disimpan.
     */
    private function dropPgsqlCheckConstraints(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $constraints = DB::select(
            "SELECT conname, pg_get_constraintdef(oid) AS def
               FROM pg_constraint
              WHERE conrelid = 'presensi_sholat'::regclass AND contype = 'c'"
        );

        foreach ($constraints as $constraint) {
            if (str_contains($constraint->def, 'jenis_sholat') || str_contains($constraint->def, 'status_kehadiran')) {
                DB::statement('ALTER TABLE presensi_sholat DROP CONSTRAINT "'.$constraint->conname.'"');
            }
        }
    }
};
