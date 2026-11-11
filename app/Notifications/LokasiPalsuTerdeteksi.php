<?php

namespace App\Notifications;

use App\Models\User;

/** Peringatan ke kepala sekolah: guru mencoba absen dengan aplikasi lokasi palsu (fake GPS). */
class LokasiPalsuTerdeteksi extends NotifikasiAplikasi
{
    public function __construct(public User $guru, public string $tahap, public string $jam, public ?int $jarakMeter)
    {
        parent::__construct();
    }

    protected function jenis(): string
    {
        return 'lokasi';
    }

    protected function judul(object $notifiable): string
    {
        return 'Lokasi palsu terdeteksi';
    }

    protected function isi(object $notifiable): string
    {
        $nama = $this->guru->nama_lengkap ?? $this->guru->name;
        $jarak = $this->jarakMeter === null ? '' : ' Lokasi yang dikirim berjarak '.number_format($this->jarakMeter, 0, ',', '.').' m dari sekolah.';

        return "{$nama} mencoba {$this->tahap} pukul {$this->jam} memakai aplikasi lokasi palsu. Absen ditolak.{$jarak}";
    }

    protected function rute(object $notifiable): string
    {
        return '/admin/guru/'.$this->guru->id.'/absen';
    }

    protected function tag(): ?string
    {
        return 'lokasi-palsu-'.$this->guru->id;
    }
}
