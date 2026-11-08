<?php

namespace App\Console\Commands;

use App\Models\HariLibur;
use App\Models\PresensiGuru;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Guru aktif yang belum absen masuk sampai batas waktu dicatat Tidak Hadir.
 * Catatan yang sudah ada tidak diubah.
 */
class TandaiGuruTidakHadir extends Command
{
    protected $signature = 'presensi-guru:tandai-tidak-hadir
        {--tanggal= : Tanggal (Y-m-d), default hari ini waktu sekolah}
        {--paksa : Tetap jalan walau bukan hari sekolah/hari libur}';

    protected $description = 'Catat Tidak Hadir untuk guru yang belum absen masuk';

    public function handle(): int
    {
        $tz = config('presensi_guru.timezone');
        $tanggal = $this->option('tanggal') ? Carbon::parse($this->option('tanggal'), $tz) : Carbon::now($tz);

        if (! $this->option('paksa') && ! in_array($tanggal->dayOfWeekIso, config('presensi_guru.hari_sekolah'), true)) {
            $this->info("{$tanggal->toDateString()} bukan hari sekolah, dilewati.");

            return self::SUCCESS;
        }

        if (! $this->option('paksa') && HariLibur::isLibur($tanggal)) {
            $this->info("{$tanggal->toDateString()} hari libur, dilewati.");

            return self::SUCCESS;
        }

        $tanggalStr = $tanggal->toDateString();
        $batas = config('presensi_guru.batas_tidak_hadir');
        $now = now();

        $rows = User::where('status', 'Aktif')
            ->whereNotIn('username', config('presensi_guru.kecualikan_username'))
            ->whereNotIn('id', PresensiGuru::where('tanggal', $tanggalStr)->select('guru_id'))
            ->pluck('id')
            ->map(fn ($id) => [
                'guru_id' => $id,
                'tanggal' => $tanggalStr,
                'status' => PresensiGuru::TIDAK_HADIR,
                'sumber' => 'otomatis',
                'keterangan' => 'Otomatis: belum absen masuk'.($batas ? " sampai {$batas}" : ''),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        $total = $rows ? PresensiGuru::insertOrIgnore($rows) : 0;
        $this->info("{$total} guru dicatat Tidak Hadir untuk {$tanggalStr}.");

        return self::SUCCESS;
    }
}
