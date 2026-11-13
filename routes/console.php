<?php

use App\Support\Sekolah;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Presensi sholat: siswa yang belum scan QR sampai batas waktu dicatat Tidak Hadir.
foreach (config('sholat.batas_tidak_hadir', []) as $jenis => $jam) {
    if ($jam) {
        Schedule::command("sholat:tandai-tidak-hadir {$jenis}")
            ->dailyAt($jam)
            ->timezone(config('sholat.timezone'))
            ->withoutOverlapping();
    }
}

// Absen guru: guru yang belum absen masuk sampai batas waktu dicatat Tidak Hadir.
if ($batasGuru = config('presensi_guru.batas_tidak_hadir')) {
    Schedule::command('presensi-guru:tandai-tidak-hadir')
        ->dailyAt($batasGuru)
        ->timezone(config('presensi_guru.timezone'))
        ->withoutOverlapping();
}

// Pengingat tugas H-1 tenggat ke wali murid.
Schedule::command('tugas:ingatkan-tenggat')
    ->dailyAt(config('notifikasi.pengingat_tugas_jam'))
    ->timezone(Sekolah::timezone())
    ->withoutOverlapping();
