<?php

namespace App\Notifications;

use App\Models\Pengumuman;

class PengumumanBaru extends NotifikasiAplikasi
{
    public function __construct(public Pengumuman $pengumuman)
    {
        parent::__construct();
    }

    protected function jenis(): string
    {
        return 'pengumuman';
    }

    protected function judul(object $notifiable): string
    {
        return ($this->pengumuman->penting ? 'Pengumuman penting: ' : 'Pengumuman: ').$this->pengumuman->judul;
    }

    protected function isi(object $notifiable): string
    {
        return static::ringkas(strip_tags((string) $this->pengumuman->isi), 140);
    }

    protected function rute(object $notifiable): string
    {
        return static::awalan($notifiable).'/pengumuman';
    }
}
