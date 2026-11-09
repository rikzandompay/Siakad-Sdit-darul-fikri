<?php

namespace App\Notifications;

use App\Models\User;

class FotoProfilDiajukan extends NotifikasiAplikasi
{
    public function __construct(public User $guru)
    {
        parent::__construct();
    }

    protected function jenis(): string
    {
        return 'foto';
    }

    protected function judul(object $notifiable): string
    {
        return 'Pengajuan foto profil';
    }

    protected function isi(object $notifiable): string
    {
        return ($this->guru->nama_lengkap ?? $this->guru->name).' mengajukan foto profil baru. Periksa apakah foto itu benar wajah yang bersangkutan.';
    }

    protected function rute(object $notifiable): string
    {
        return '/admin/foto-pengajuan';
    }
}
