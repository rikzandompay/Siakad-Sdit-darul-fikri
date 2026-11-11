<?php

namespace App\Notifications;

/** Foto profil yang disetujui tidak bisa dijadikan acuan wajah presensi. */
class AcuanWajahGagal extends NotifikasiAplikasi
{
    public function __construct(public string $pesan)
    {
        parent::__construct();
    }

    protected function jenis(): string
    {
        return 'foto';
    }

    protected function judul(object $notifiable): string
    {
        return 'Foto profil belum bisa dipakai untuk presensi';
    }

    protected function isi(object $notifiable): string
    {
        return $this->pesan;
    }

    protected function rute(object $notifiable): string
    {
        return '/guru/profil';
    }
}
