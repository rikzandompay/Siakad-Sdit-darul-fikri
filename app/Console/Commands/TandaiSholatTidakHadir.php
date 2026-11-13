<?php

namespace App\Console\Commands;

use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\PresensiSholat;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Siswa aktif yang belum tercatat (belum scan QR) untuk sholat & tanggal tertentu
 * dicatat Tidak Hadir. Catatan yang sudah ada tidak diubah.
 */
class TandaiSholatTidakHadir extends Command
{
    protected $signature = 'sholat:tandai-tidak-hadir
        {jenis : Dhuha atau Dzuhur}
        {--tanggal= : Tanggal (Y-m-d), default hari ini waktu sekolah}
        {--paksa : Tetap jalan walau bukan hari sekolah/hari libur}';

    protected $description = 'Catat Tidak Hadir untuk siswa yang belum tercatat presensi sholatnya';

    public function handle(): int
    {
        $jenis = $this->argument('jenis');
        if (! in_array($jenis, PresensiSholat::JENIS, true)) {
            $this->error('Jenis sholat harus salah satu dari: '.implode(', ', PresensiSholat::JENIS));

            return self::FAILURE;
        }

        $tanggal = $this->option('tanggal')
            ? Carbon::parse($this->option('tanggal'), config('sholat.timezone'))
            : Carbon::now(config('sholat.timezone'));

        if (! $this->option('paksa') && ! in_array($tanggal->dayOfWeekIso, config('sholat.hari_sekolah'), true)) {
            $this->info("{$tanggal->toDateString()} bukan hari sekolah, dilewati.");

            return self::SUCCESS;
        }

        if (! $this->option('paksa') && HariLibur::isLibur($tanggal)) {
            $this->info("{$tanggal->toDateString()} hari libur, dilewati.");

            return self::SUCCESS;
        }

        $tanggalStr = $tanggal->toDateString();
        $kelasIds = Kelas::all()->filter->ikutSholat($jenis)->pluck('id');
        if ($kelasIds->isEmpty()) {
            $this->info("Tidak ada kelas yang mengikuti Sholat {$jenis}.");

            return self::SUCCESS;
        }

        $batas = config("sholat.batas_tidak_hadir.{$jenis}");
        $keterangan = 'Otomatis: belum scan'.($batas ? " sampai {$batas}" : '');
        $now = now();
        $total = 0;

        Siswa::where('status', 'Aktif')
            ->whereIn('kelas_id', $kelasIds)
            ->whereDoesntHave('presensiSholat', fn ($q) => $q->where('tanggal', $tanggalStr)->where('jenis_sholat', $jenis))
            ->select(['id', 'kelas_id'])
            ->chunkById(500, function ($siswaList) use ($tanggalStr, $jenis, $keterangan, $now, &$total) {
                $rows = $siswaList->map(fn ($siswa) => [
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $siswa->kelas_id,
                    'tanggal' => $tanggalStr,
                    'jenis_sholat' => $jenis,
                    'status_kehadiran' => PresensiSholat::TIDAK_HADIR,
                    'keterangan' => $keterangan,
                    'recorded_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                // Abaikan baris yang keburu dicatat (scan masuk saat command berjalan).
                $total += PresensiSholat::insertOrIgnore($rows);
            });

        $this->info("{$total} siswa dicatat Tidak Hadir untuk Sholat {$jenis} {$tanggalStr}.");

        return self::SUCCESS;
    }
}
