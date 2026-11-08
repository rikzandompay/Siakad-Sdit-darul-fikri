<?php

return [

    // Zona waktu sekolah (Kalimantan Barat = WIB); aplikasi sendiri berjalan di UTC.
    'timezone' => env('SEKOLAH_TIMEZONE', 'Asia/Pontianak'),

    // Jam masuk acuan (PRD). Absen masuk setelah jam ini ditandai terlambat.
    'jam_masuk' => env('GURU_JAM_MASUK', '07:00'),

    // Jam pulang (informasi di aplikasi).
    'jam_pulang' => env('GURU_JAM_PULANG', '14:00'),

    // Kecocokan wajah minimal (persen) yang diterima server dari aplikasi.
    'min_skor_wajah' => (float) env('GURU_MIN_SKOR_WAJAH', 85),

    // Radius area sekolah (meter) jika belum diatur di Pengaturan Sekolah.
    'radius_meter' => (int) env('GURU_RADIUS_METER', 100),

    // Guru yang belum absen masuk sampai jam ini otomatis Tidak Hadir. null = mati.
    'batas_tidak_hadir' => env('GURU_BATAS_TIDAK_HADIR', '13:00'),

    // Hari sekolah (ISO: 1 = Senin ... 7 = Minggu).
    'hari_sekolah' => [1, 2, 3, 4, 5, 6],

    // Akun sistem (bukan orang) yang tidak ikut absen guru.
    'kecualikan_username' => array_filter(explode(',', env('GURU_ABSEN_KECUALIKAN', 'admin'))),

];
