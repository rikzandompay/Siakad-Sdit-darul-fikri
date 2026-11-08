<?php

namespace App\Notifications;

use App\Models\PengajuanIzin;

class IzinDitinjau extends NotifikasiAplikasi
{
    public function __construct(public PengajuanIzin $izin)
    {
        parent::__construct();
    }

    protected function jenis(): string
    {
        return 'izin';
    }

    protected function judul(object $notifiable): string
    {
        return "Izin {$this->izin->status}";
    }

    protected function isi(object $notifiable): string
    {
        $i = $this->izin;
        $teks = "Pengajuan {$i->jenis} ".($i->siswa?->nama_siswa ?? '')." mulai {$i->tanggal_mulai->translatedFormat('j M Y')} ".strtolower($i->status).'.';

        return $i->catatan_peninjau ? $teks.' Catatan: '.static::ringkas($i->catatan_peninjau, 80) : $teks;
    }

    protected function rute(object $notifiable): string
    {
        return '/wali/izin';
    }
}
