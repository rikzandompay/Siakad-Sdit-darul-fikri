<?php

namespace App\Console\Commands;

use App\Models\Tugas;
use App\Notifications\TugasBaru;
use App\Observers\NotifikasiObserver;
use App\Support\Sekolah;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/** Pengingat H-1: wali murid diberi tahu tugas anak yang tenggatnya besok. */
class IngatkanTenggatTugas extends Command
{
    protected $signature = 'tugas:ingatkan-tenggat {--tanggal= : Tanggal hari ini (Y-m-d), default hari ini waktu sekolah}';

    protected $description = 'Kirim notifikasi pengingat ke wali untuk tugas yang tenggatnya besok';

    public function handle(): int
    {
        $hariIni = $this->option('tanggal') ? Carbon::parse($this->option('tanggal'), Sekolah::timezone()) : Sekolah::now();
        $besok = $hariIni->copy()->addDay()->toDateString();

        $jumlah = 0;
        Tugas::with('mataPelajaran')->whereDate('tenggat', $besok)->each(function (Tugas $tugas) use (&$jumlah) {
            $penerima = NotifikasiObserver::waliKelas($tugas->kelas_id);
            Notification::send($penerima, new TugasBaru($tugas, pengingat: true));
            $jumlah += $penerima->count();
        });

        $this->info("Pengingat tenggat {$besok} dikirim ke {$jumlah} wali.");

        return self::SUCCESS;
    }
}
