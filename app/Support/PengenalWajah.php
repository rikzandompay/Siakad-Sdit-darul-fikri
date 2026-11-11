<?php

namespace App\Support;

/** Membuat embedding wajah dari sebuah foto di server (acuan presensi guru). */
interface PengenalWajah
{
    /**
     * @return array<int, float> embedding yang sudah dinormalisasi L2
     *
     * @throws PengenalWajahGagal foto tidak bisa dipakai (tidak ada/lebih dari satu wajah, terlalu kecil)
     * @throws \RuntimeException gangguan teknis (Python/model tidak tersedia)
     */
    public function dariFoto(string $pathFoto): array;
}
