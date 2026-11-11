<?php

namespace App\Support;

/** Foto profil tidak bisa dijadikan acuan wajah; [pesan] ditampilkan ke guru. */
class PengenalWajahGagal extends \RuntimeException
{
    public function __construct(public readonly string $kode, public readonly string $pesan)
    {
        parent::__construct($pesan);
    }
}
