<?php

namespace App\Notifications;

class FotoProfilDitinjau extends NotifikasiAplikasi
{
    public function __construct(public bool $disetujui, public ?string $catatan = null)
    {
        parent::__construct();
    }

    protected function jenis(): string
    {
        return 'foto';
    }

    protected function judul(object $notifiable): string
    {
        return $this->disetujui ? 'Foto profil disetujui' : 'Foto profil ditolak';
    }

    protected function isi(object $notifiable): string
    {
        if ($this->disetujui) {
            return 'Foto profil baru sudah aktif. Daftarkan wajah Anda lagi sebelum absen.';
        }

        return 'Ajukan foto lain yang jelas menampilkan wajah Anda.'.($this->catatan ? ' Catatan: '.static::ringkas($this->catatan, 80) : '');
    }

    protected function rute(object $notifiable): string
    {
        return '/guru/profil';
    }
}
