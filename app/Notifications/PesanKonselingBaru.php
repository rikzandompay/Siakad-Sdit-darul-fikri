<?php

namespace App\Notifications;

use App\Models\KonselingPesan;

class PesanKonselingBaru extends NotifikasiAplikasi
{
    public function __construct(public KonselingPesan $pesan)
    {
        parent::__construct();
    }

    protected function jenis(): string
    {
        return 'konseling';
    }

    protected function judul(object $notifiable): string
    {
        $p = $this->pesan->percakapan;

        return $this->pesan->pengirim === KonselingPesan::WALI
            ? 'Konseling: wali '.($p->siswa?->nama_siswa ?? 'siswa')
            : 'Balasan konseling: '.($this->pesan->guru?->nama_lengkap ?? $this->pesan->guru?->name ?? 'guru pembimbing');
    }

    protected function isi(object $notifiable): string
    {
        $topik = $this->pesan->percakapan->topik;
        $teks = static::ringkas($this->pesan->isi) ?: '📷 Foto';

        return "{$topik} — {$teks}";
    }

    protected function rute(object $notifiable): string
    {
        return static::awalan($notifiable).'/konseling/'.$this->pesan->percakapan_id;
    }

    protected function tag(): ?string
    {
        return 'konseling-'.$this->pesan->percakapan_id;
    }
}
