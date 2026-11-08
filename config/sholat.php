<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zona waktu sekolah
    |--------------------------------------------------------------------------
    |
    | Aplikasi berjalan dengan timezone UTC, sedangkan batas waktu sholat
    | mengikuti jam sekolah (Kalimantan Barat = WIB).
    |
    */

    'timezone' => env('SEKOLAH_TIMEZONE', 'Asia/Pontianak'),

    /*
    |--------------------------------------------------------------------------
    | Waktu sholat berjamaah (informasi tampilan)
    |--------------------------------------------------------------------------
    */

    'waktu' => [
        'Dhuha' => '07:30',
        'Dzuhur' => '12:00',
    ],

    // Tempat sholat berjamaah (ditampilkan di aplikasi).
    'tempat' => env('SHOLAT_TEMPAT', 'Musholla Sekolah'),

    /*
    |--------------------------------------------------------------------------
    | Batas otomatis "Tidak Hadir"
    |--------------------------------------------------------------------------
    |
    | Siswa yang belum tercatat (belum scan QR) sampai jam ini otomatis
    | dicatat Tidak Hadir. Kosongkan (null) untuk mematikan otomatisasi.
    |
    */

    'batas_tidak_hadir' => [
        'Dhuha' => env('SHOLAT_BATAS_DHUHA', '08:30'),
        'Dzuhur' => env('SHOLAT_BATAS_DZUHUR', '13:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hari sekolah (ISO: 1 = Senin ... 7 = Minggu)
    |--------------------------------------------------------------------------
    */

    'hari_sekolah' => [1, 2, 3, 4, 5, 6],

];
