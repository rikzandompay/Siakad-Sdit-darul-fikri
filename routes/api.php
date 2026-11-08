<?php

use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\KelolaSekolahController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Guru\AbsenController;
use App\Http\Controllers\Api\V1\Guru\DashboardController;
use App\Http\Controllers\Api\V1\Guru\LainnyaController;
use App\Http\Controllers\Api\V1\Guru\PresensiKelasController;
use App\Http\Controllers\Api\V1\Guru\SholatController;
use App\Http\Controllers\Api\V1\KonselingController;
use App\Http\Controllers\Api\V1\NotifikasiController;
use App\Http\Controllers\Api\V1\Wali\WaliController;
use Illuminate\Support\Facades\Route;

/*
| API aplikasi MyDarfik (v1). Semua respons JSON.
| Autentikasi: Bearer token Sanctum dari POST /api/v1/auth/login.
*/

Route::prefix('v1')->group(function () {
    // Tes koneksi dari aplikasi (layar login → Atur alamat server).
    Route::get('/ping', fn () => response()->json(['ok' => true, 'aplikasi' => 'SIAKAD SDIT Darul Fikri', 'waktu' => now()->toIso8601String()]));
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api-login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Notifikasi (guru & wali): token push perangkat dan riwayat lonceng.
        Route::post('/perangkat', [NotifikasiController::class, 'simpanPerangkat']);
        Route::delete('/perangkat', [NotifikasiController::class, 'hapusPerangkat']);
        Route::get('/notifikasi', [NotifikasiController::class, 'index']);
        Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua']);
        Route::post('/notifikasi/{id}/baca', [NotifikasiController::class, 'baca']);

        Route::prefix('guru')->middleware('peran:guru')->group(function () {
            Route::post('/ubah-sandi', [AuthController::class, 'ubahSandi'])->middleware('throttle:api-sandi');
            Route::post('/profil/foto', [AuthController::class, 'uploadFotoGuru'])->middleware('throttle:api-foto');
            Route::get('/dashboard', DashboardController::class);

            // Presensi kelas
            Route::get('/presensi/kelas', [PresensiKelasController::class, 'kelas']);
            Route::get('/presensi/kelas/{kelas}/jadwal', [PresensiKelasController::class, 'jadwal'])->middleware('wali.kelas');
            Route::get('/presensi/jadwal/{jadwal}', [PresensiKelasController::class, 'show'])->middleware('wali.kelas');
            Route::post('/presensi/jadwal/{jadwal}', [PresensiKelasController::class, 'store'])->middleware('wali.kelas');

            // Presensi sholat (scan QR)
            Route::get('/sholat/kelas', [SholatController::class, 'kelas']);
            Route::get('/sholat/kelas/{kelas}', [SholatController::class, 'jenis']);
            Route::get('/sholat/kelas/{kelas}/siswa', [SholatController::class, 'show']);
            Route::post('/sholat/kelas/{kelas}/scan', [SholatController::class, 'scan'])->middleware('throttle:api-scan');
            Route::post('/sholat/kelas/{kelas}', [SholatController::class, 'store']);

            // Absen guru (lokasi + wajah)
            Route::get('/absen', [AbsenController::class, 'status']);
            Route::post('/absen/cek-lokasi', [AbsenController::class, 'cekLokasi']);
            Route::post('/absen/masuk', [AbsenController::class, 'masuk'])->middleware('throttle:api-absen');
            Route::post('/absen/pulang', [AbsenController::class, 'pulang'])->middleware('throttle:api-absen');
            Route::get('/absen/rekap', [AbsenController::class, 'rekap']);
            Route::get('/wajah', [AbsenController::class, 'wajah']);
            Route::post('/wajah', [AbsenController::class, 'daftarWajah'])->middleware('throttle:api-wajah');

            // Tugas & nilai tugas, izin, pengumuman, jadwal, konseling
            Route::get('/tugas', [LainnyaController::class, 'tugas']);
            Route::post('/tugas', [LainnyaController::class, 'buatTugas']);
            Route::get('/tugas/{tugas}', [LainnyaController::class, 'showTugas']);
            Route::post('/tugas/{tugas}/nilai', [LainnyaController::class, 'nilaiTugas']);
            Route::get('/izin', [LainnyaController::class, 'izin']);
            Route::post('/izin/{izin}/setujui', [LainnyaController::class, 'setujuiIzin']);
            Route::post('/izin/{izin}/tolak', [LainnyaController::class, 'tolakIzin']);
            Route::get('/izin/{izin}/lampiran', [LainnyaController::class, 'lampiranIzin']);
            Route::get('/pengumuman', [LainnyaController::class, 'pengumuman']);
            Route::get('/jadwal', [LainnyaController::class, 'jadwal']);
            Route::get('/konseling', [KonselingController::class, 'guruIndex']);
            Route::get('/konseling/{percakapan}', [KonselingController::class, 'guruShow']);
            Route::post('/konseling/{percakapan}', [KonselingController::class, 'guruBalas'])->middleware('throttle:api-foto');
            Route::put('/konseling/{percakapan}/pesan/{pesan}', [KonselingController::class, 'guruUbahPesan']);
            Route::delete('/konseling/{percakapan}/pesan/{pesan}', [KonselingController::class, 'guruHapusPesan']);
            Route::get('/konseling/{percakapan}/pesan/{pesan}/foto', [KonselingController::class, 'guruFoto']);
        });

        // Kepala sekolah / admin (akun guru dengan hak admin)
        Route::prefix('admin')->middleware('peran:admin')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/opsi', [AdminController::class, 'opsi']);
            Route::get('/guru', [AdminController::class, 'guru']);
            Route::get('/siswa', [AdminController::class, 'siswa']);
            Route::get('/kelas', [AdminController::class, 'kelas']);
            Route::get('/pengumuman', [AdminController::class, 'pengumuman']);
            Route::post('/pengumuman', [AdminController::class, 'simpanPengumuman']);
            Route::put('/pengumuman/{pengumuman}', [AdminController::class, 'simpanPengumuman']);
            Route::delete('/pengumuman/{pengumuman}', [AdminController::class, 'hapusPengumuman']);
            // Pengelolaan sekolah (kepala sekolah)
            Route::get('/pengaturan-sekolah', [KelolaSekolahController::class, 'pengaturan']);
            Route::put('/pengaturan-sekolah', [KelolaSekolahController::class, 'simpanPengaturan']);
            Route::post('/pengaturan-sekolah/libur', [KelolaSekolahController::class, 'tambahLibur']);
            Route::delete('/pengaturan-sekolah/libur/{libur}', [KelolaSekolahController::class, 'hapusLibur']);
            Route::get('/konseling-pengampu', [KelolaSekolahController::class, 'pengampu']);
            Route::put('/konseling-pengampu', [KelolaSekolahController::class, 'simpanPengampu']);
            Route::get('/presensi-guru/{guru}', [KelolaSekolahController::class, 'riwayatAbsenGuru']);
            Route::post('/presensi-guru/koreksi', [KelolaSekolahController::class, 'koreksiAbsenGuru']);
            Route::delete('/guru/{guru}/wajah', [KelolaSekolahController::class, 'resetWajah']);
            Route::get('/wali', [KelolaSekolahController::class, 'wali']);
            Route::post('/wali/sinkron', [KelolaSekolahController::class, 'sinkronWali']);
            Route::get('/ekspor/{jenis}/{format}', \App\Http\Controllers\Api\V1\Admin\EksporController::class)->middleware('throttle:30,1');
            Route::get('/rekap/absensi', [AdminController::class, 'rekapAbsensi']);
            Route::get('/rekap/sholat', [AdminController::class, 'rekapSholat']);
            Route::get('/rekap/nilai', [AdminController::class, 'rekapNilai']);
            Route::get('/rekap/guru', [AdminController::class, 'rekapGuru']);
            Route::get('/tahun-ajaran', [AdminController::class, 'tahunAjaran']);
            Route::post('/tahun-ajaran', [AdminController::class, 'simpanTahunAjaran']);
            Route::post('/tahun-ajaran/{tahunAjaran}/aktif', [AdminController::class, 'aktifkanTahunAjaran']);
            Route::delete('/tahun-ajaran/{tahunAjaran}', [AdminController::class, 'hapusTahunAjaran']);
            Route::get('/mapel', [AdminController::class, 'mapel']);
            Route::post('/mapel', [AdminController::class, 'simpanMapel']);
            Route::delete('/mapel/{mapel}', [AdminController::class, 'hapusMapel']);
        });

        // Wali murid
        Route::prefix('wali')->middleware('peran:wali')->group(function () {
            Route::get('/dashboard', [WaliController::class, 'dashboard']);
            Route::get('/kehadiran', [WaliController::class, 'kehadiran']);
            Route::get('/jadwal', [WaliController::class, 'jadwal']);
            Route::get('/pengumuman', [WaliController::class, 'pengumuman']);
            Route::get('/tugas', [WaliController::class, 'tugas']);
            Route::get('/izin', [WaliController::class, 'izin']);
            Route::post('/izin', [WaliController::class, 'ajukanIzin'])->middleware('throttle:api-sandi');
            Route::get('/rapor', [WaliController::class, 'rapor']);
            Route::get('/rapor/grafik', [WaliController::class, 'raporGrafik']);
            Route::get('/rapor/{tahunAjaran}', [WaliController::class, 'raporSemester']);
            Route::get('/guru', [WaliController::class, 'guru']);
            Route::get('/guru/{guru}/jadwal', [WaliController::class, 'jadwalGuru']);
            Route::post('/profil/foto', [WaliController::class, 'uploadFotoSiswa'])->middleware('throttle:api-foto');
            Route::put('/siswa/{siswa}', [WaliController::class, 'ubahData'])->middleware('throttle:api-sandi');
            Route::get('/konseling', [KonselingController::class, 'waliIndex']);
            Route::post('/konseling', [KonselingController::class, 'waliMulai'])->middleware('throttle:api-sandi');
            Route::get('/konseling/{percakapan}', [KonselingController::class, 'waliShow']);
            Route::post('/konseling/{percakapan}', [KonselingController::class, 'waliBalas'])->middleware('throttle:api-foto');
            Route::put('/konseling/{percakapan}/pesan/{pesan}', [KonselingController::class, 'waliUbahPesan']);
            Route::delete('/konseling/{percakapan}/pesan/{pesan}', [KonselingController::class, 'waliHapusPesan']);
            Route::get('/konseling/{percakapan}/pesan/{pesan}/foto', [KonselingController::class, 'waliFoto']);
        });
    });
});
