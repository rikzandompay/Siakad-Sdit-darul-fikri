<?php

namespace App\Notifications;

use App\Models\Tugas;

/** Tugas baru untuk kelas anak; dengan $pengingat = true dipakai untuk pengingat H-1 tenggat. */
class TugasBaru extends NotifikasiAplikasi
{
    public function __construct(public Tugas $tugas, public bool $pengingat = false)
    {
        parent::__construct();
    }

    protected function jenis(): string
    {
        return 'tugas';
    }

    protected function judul(object $notifiable): string
    {
        $mapel = $this->tugas->mataPelajaran?->nama_pelajaran;

        return ($this->pengingat ? 'Tenggat besok: ' : 'Tugas baru: ').$this->tugas->judul.($mapel ? " ({$mapel})" : '');
    }

    protected function isi(object $notifiable): string
    {
        $tenggat = $this->tugas->tenggat?->translatedFormat('l, j M Y');

        return $this->pengingat
            ? "Jangan lupa, tugas ini dikumpulkan besok ({$tenggat})."
            : ($tenggat ? "Tenggat {$tenggat}. " : '').static::ringkas($this->tugas->deskripsi, 100);
    }

    protected function rute(object $notifiable): string
    {
        return '/wali/tugas';
    }
}
