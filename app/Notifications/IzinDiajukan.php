<?php

namespace App\Notifications;

use App\Models\PengajuanIzin;

class IzinDiajukan extends NotifikasiAplikasi
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
        return "Pengajuan {$this->izin->jenis}: ".($this->izin->siswa?->nama_siswa ?? 'siswa');
    }

    protected function isi(object $notifiable): string
    {
        $i = $this->izin;
        $tanggal = $i->tanggal_mulai->equalTo($i->tanggal_selesai)
            ? $i->tanggal_mulai->translatedFormat('j M Y')
            : $i->tanggal_mulai->translatedFormat('j M').' – '.$i->tanggal_selesai->translatedFormat('j M Y');

        return "{$tanggal} · ".static::ringkas($i->alasan, 80).' Menunggu persetujuan.';
    }

    protected function rute(object $notifiable): string
    {
        return '/guru/izin';
    }
}
