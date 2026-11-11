<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\NilaiRapotController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PresensiSholatController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\KonselingController;
use App\Http\Controllers\RekapPresensiGuruController;
use App\Http\Controllers\PengaturanSekolahController;
use App\Http\Controllers\WaliController;
use App\Http\Controllers\IzinController;
use App\Http\Controllers\TugasController;

// ── AUTH ROUTES ──
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── PROTECTED ROUTES ──
// Foto guru/siswa: hanya untuk akun yang login (sesi web atau token aplikasi MyDarfik).
Route::middleware(['auth:web,sanctum', 'throttle:240,1'])->group(function () {
    Route::get('/foto/guru/{guru}', [\App\Http\Controllers\FotoController::class, 'guru'])->name('foto.guru');
    Route::get('/foto/guru/{guru}/pengajuan', [\App\Http\Controllers\FotoController::class, 'guruPengajuan'])->name('foto.guru.pengajuan');
    Route::get('/foto/siswa/{siswa}', [\App\Http\Controllers\FotoController::class, 'siswa'])->name('foto.siswa');
});

Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Guru CRUD + Export (Admin Only)
    Route::middleware('is_admin')->group(function () {
        Route::get('/guru', [GuruController::class, 'index'])->name('guru.index');
        Route::get('/guru/export/csv', [GuruController::class, 'exportCsv'])->name('guru.export.csv');
        Route::get('/guru/export/pdf', [GuruController::class, 'exportPdf'])->name('guru.export.pdf');
        Route::post('/guru', [GuruController::class, 'store'])->middleware('throttle:30,1')->name('guru.store');
        Route::put('/guru/{guru}', [GuruController::class, 'update'])->middleware('throttle:30,1')->name('guru.update');
        Route::delete('/guru/{guru}', [GuruController::class, 'destroy'])->middleware('throttle:30,1')->name('guru.destroy');
    });

    // Siswa CRUD + Export
    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('/siswa/export/csv', [SiswaController::class, 'exportCsv'])->name('siswa.export.csv');
    Route::get('/siswa/export/pdf', [SiswaController::class, 'exportPdf'])->name('siswa.export.pdf');
    Route::middleware('is_admin')->group(function () {
        Route::post('/siswa', [SiswaController::class, 'store'])->middleware('throttle:30,1')->name('siswa.store');
        Route::put('/siswa/{siswa}', [SiswaController::class, 'update'])->middleware('throttle:30,1')->name('siswa.update');
        Route::delete('/siswa/{siswa}', [SiswaController::class, 'destroy'])->middleware('throttle:30,1')->name('siswa.destroy');
        Route::get('/siswa/{siswa}/kartu', [SiswaController::class, 'kartu'])->name('siswa.kartu');
        Route::post('/siswa/{siswa}/qr', [SiswaController::class, 'regenerateQr'])->middleware('throttle:30,1')->name('siswa.qr.regenerate');
    });

    // Kelas CRUD
    Route::get('/kelas', [KelasController::class, 'index'])->name('kelas.index');
    Route::get('/kelas/{kelas}', [KelasController::class, 'show'])->name('kelas.show');
    Route::middleware('is_admin')->group(function () {
        Route::post('/kelas', [KelasController::class, 'store'])->middleware('throttle:30,1')->name('kelas.store');
        Route::put('/kelas/{kelas}', [KelasController::class, 'update'])->middleware('throttle:30,1')->name('kelas.update');
        Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy'])->middleware('throttle:30,1')->name('kelas.destroy');
    });

    Route::middleware('is_admin')->group(function () {
        Route::post('/jadwal', [JadwalPelajaranController::class, 'store'])->middleware('throttle:30,1')->name('jadwal.store');
        Route::put('/jadwal/{jadwal}', [JadwalPelajaranController::class, 'update'])->middleware('throttle:30,1')->name('jadwal.update');
        Route::delete('/jadwal/{jadwal}', [JadwalPelajaranController::class, 'destroy'])->middleware('throttle:30,1')->name('jadwal.destroy');
    });
    Route::get('/jadwal/{kelas}/export/csv', [JadwalPelajaranController::class, 'exportCsv'])->name('jadwal.export.csv');
    Route::get('/jadwal/{kelas}/export/pdf', [JadwalPelajaranController::class, 'exportPdf'])->name('jadwal.export.pdf');

    // Presensi + Export
    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
    Route::get('/presensi/rekap', [PresensiController::class, 'rekap'])->name('presensi.rekap');
    Route::get('/presensi/rekap/export/csv', [PresensiController::class, 'exportRekapCsv'])->name('presensi.rekap.export.csv');
    Route::get('/presensi/rekap/export/pdf', [PresensiController::class, 'exportRekapPdf'])->name('presensi.rekap.export.pdf');
    Route::get('/presensi/{kelas}', [PresensiController::class, 'show'])->middleware('wali.kelas')->name('presensi.show');
    Route::post('/presensi', [PresensiController::class, 'store'])->middleware(['throttle:30,1', 'wali.kelas'])->name('presensi.store');
    Route::get('/presensi/{kelas}/export/csv', [PresensiController::class, 'exportCsv'])->name('presensi.export.csv');
    Route::get('/presensi/{kelas}/export/pdf', [PresensiController::class, 'exportPdf'])->name('presensi.export.pdf');

    // Nilai Rapot + Export
    Route::get('/nilai', [NilaiRapotController::class, 'index'])->name('nilai.index');
    Route::get('/nilai/rekap', [NilaiRapotController::class, 'rekap'])->name('nilai.rekap');
    Route::get('/nilai/rekap/export/csv', [NilaiRapotController::class, 'exportRekapCsv'])->name('nilai.rekap.export.csv');
    Route::get('/nilai/rekap/export/pdf', [NilaiRapotController::class, 'exportRekapPdf'])->name('nilai.rekap.export.pdf');
    Route::post('/nilai', [NilaiRapotController::class, 'store'])->middleware('throttle:120,1')->name('nilai.store');
    Route::get('/nilai/export/csv', [NilaiRapotController::class, 'exportCsv'])->name('nilai.export.csv');
    Route::get('/nilai/export/pdf', [NilaiRapotController::class, 'exportPdf'])->name('nilai.export.pdf');

    // Pengaturan
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::put('/pengaturan/profil', [PengaturanController::class, 'updateProfil'])->middleware('throttle:30,1')->name('pengaturan.profil.update');
    Route::post('/pengaturan/foto', [PengaturanController::class, 'updateFoto'])->middleware('throttle:20,1')->name('pengaturan.foto.update');
    Route::delete('/pengaturan/foto', [PengaturanController::class, 'hapusFoto'])->middleware('throttle:20,1')->name('pengaturan.foto.destroy');
    Route::put('/pengaturan/password', [PengaturanController::class, 'updatePassword'])->middleware('throttle:30,1')->name('pengaturan.password.update');

    // Pengaturan - Tahun Ajaran & Mata Pelajaran (Admin Only)
    Route::middleware('is_admin')->group(function () {
        Route::post('/pengaturan/tahun-ajaran', [PengaturanController::class, 'storeTahunAjaran'])->middleware('throttle:30,1')->name('pengaturan.tahun-ajaran.store');
        Route::put('/pengaturan/tahun-ajaran/{tahunAjaran}', [PengaturanController::class, 'updateTahunAjaran'])->middleware('throttle:30,1')->name('pengaturan.tahun-ajaran.update');
        Route::put('/pengaturan/tahun-ajaran/{tahunAjaran}/aktif', [PengaturanController::class, 'setAktifTahunAjaran'])->middleware('throttle:30,1')->name('pengaturan.tahun-ajaran.aktif');
        Route::delete('/pengaturan/tahun-ajaran/{tahunAjaran}', [PengaturanController::class, 'destroyTahunAjaran'])->middleware('throttle:30,1')->name('pengaturan.tahun-ajaran.destroy');

        Route::post('/pengaturan/mapel', [PengaturanController::class, 'storeMapel'])->middleware('throttle:30,1')->name('pengaturan.mapel.store');
        Route::put('/pengaturan/mapel/{mapel}', [PengaturanController::class, 'updateMapel'])->middleware('throttle:30,1')->name('pengaturan.mapel.update');
        Route::delete('/pengaturan/mapel/{mapel}', [PengaturanController::class, 'destroyMapel'])->middleware('throttle:30,1')->name('pengaturan.mapel.destroy');
    });

    // Presensi Sholat
    Route::get('/presensi-sholat', [PresensiSholatController::class, 'index'])->name('presensi-sholat.index');
    Route::get('/presensi-sholat/rekap', [PresensiSholatController::class, 'rekap'])->name('presensi-sholat.rekap');
    Route::get('/presensi-sholat/rekap/export/csv', [PresensiSholatController::class, 'exportRekapCsv'])->name('presensi-sholat.rekap.export.csv');
    Route::get('/presensi-sholat/rekap/export/pdf', [PresensiSholatController::class, 'exportRekapPdf'])->name('presensi-sholat.rekap.export.pdf');
    Route::get('/presensi-sholat/{kelas}', [PresensiSholatController::class, 'show'])->name('presensi-sholat.show');
    Route::post('/presensi-sholat', [PresensiSholatController::class, 'store'])->middleware('throttle:30,1')->name('presensi-sholat.store');

    // Pengumuman: semua guru membaca, admin mengelola
    Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman.index');
    Route::middleware('is_admin')->group(function () {
        Route::get('/pengumuman/buat', [PengumumanController::class, 'create'])->name('pengumuman.create');
        Route::post('/pengumuman', [PengumumanController::class, 'store'])->middleware('throttle:30,1')->name('pengumuman.store');
        Route::get('/pengumuman/{pengumuman}/edit', [PengumumanController::class, 'edit'])->name('pengumuman.edit');
        Route::put('/pengumuman/{pengumuman}', [PengumumanController::class, 'update'])->middleware('throttle:30,1')->name('pengumuman.update');
        Route::delete('/pengumuman/{pengumuman}', [PengumumanController::class, 'destroy'])->middleware('throttle:30,1')->name('pengumuman.destroy');
    });

    // Konseling: privat antara pengampu kelas dan wali; admin hanya mengatur pengampu
    Route::get('/konseling', [KonselingController::class, 'index'])->name('konseling.index');
    Route::middleware('is_admin')->group(function () {
        Route::get('/konseling/pengampu', [KonselingController::class, 'pengampu'])->name('konseling.pengampu');
        Route::put('/konseling/pengampu', [KonselingController::class, 'updatePengampu'])->middleware('throttle:30,1')->name('konseling.pengampu.update');
    });
    Route::get('/konseling/baru', [KonselingController::class, 'create'])->name('konseling.create');
    Route::post('/konseling', [KonselingController::class, 'store'])->middleware('throttle:30,1')->name('konseling.store');
    Route::get('/konseling/{percakapan}', [KonselingController::class, 'show'])->name('konseling.show');
    Route::post('/konseling/{percakapan}/balas', [KonselingController::class, 'reply'])->middleware('throttle:60,1')->name('konseling.reply');
    Route::put('/konseling/{percakapan}/pesan/{pesan}', [KonselingController::class, 'updatePesan'])->middleware('throttle:60,1')->name('konseling.pesan.update');
    Route::delete('/konseling/{percakapan}/pesan/{pesan}', [KonselingController::class, 'destroyPesan'])->middleware('throttle:60,1')->name('konseling.pesan.destroy');
    Route::get('/konseling/{percakapan}/pesan/{pesan}/foto', [KonselingController::class, 'foto'])->name('konseling.pesan.foto');
    Route::put('/konseling/{percakapan}/status', [KonselingController::class, 'updateStatus'])->middleware('throttle:30,1')->name('konseling.status');

    // Rekap presensi guru (data absen wajah + lokasi dari aplikasi) — admin
    Route::middleware('is_admin')->group(function () {
        Route::get('/presensi-guru/rekap', [RekapPresensiGuruController::class, 'index'])->name('presensi-guru.rekap');
        Route::get('/presensi-guru/rekap/export/csv', [RekapPresensiGuruController::class, 'exportCsv'])->name('presensi-guru.rekap.export.csv');
        Route::get('/presensi-guru/rekap/export/pdf', [RekapPresensiGuruController::class, 'exportPdf'])->name('presensi-guru.rekap.export.pdf');
    });

    Route::middleware('is_admin')->group(function () {
        Route::post('/presensi-guru/manual', [RekapPresensiGuruController::class, 'simpanManual'])->middleware('throttle:30,1')->name('presensi-guru.manual');
        Route::post('/presensi-guru/foto/{guru}/setujui', [RekapPresensiGuruController::class, 'setujuiFoto'])->middleware('throttle:30,1')->name('presensi-guru.foto.setujui');
        Route::post('/presensi-guru/foto/{guru}/tolak', [RekapPresensiGuruController::class, 'tolakFoto'])->middleware('throttle:30,1')->name('presensi-guru.foto.tolak');
        Route::delete('/presensi-guru/wajah/{guru}', [RekapPresensiGuruController::class, 'resetWajah'])->middleware('throttle:30,1')->name('presensi-guru.wajah.reset');

        // Pengaturan sekolah: lokasi, radius, jam masuk, hari libur
        Route::get('/pengaturan-sekolah', [PengaturanSekolahController::class, 'index'])->name('pengaturan-sekolah.index');
        Route::put('/pengaturan-sekolah', [PengaturanSekolahController::class, 'update'])->middleware('throttle:30,1')->name('pengaturan-sekolah.update');
        Route::post('/pengaturan-sekolah/libur', [PengaturanSekolahController::class, 'storeLibur'])->middleware('throttle:30,1')->name('pengaturan-sekolah.libur.store');
        Route::delete('/pengaturan-sekolah/libur/{libur}', [PengaturanSekolahController::class, 'destroyLibur'])->middleware('throttle:30,1')->name('pengaturan-sekolah.libur.destroy');

        // Akun wali murid: otomatis dari data siswa (NIS + nama wali); halaman ini hanya daftar & sinkron
        Route::get('/wali', [WaliController::class, 'index'])->name('wali.index');
        Route::post('/wali/sinkron', [WaliController::class, 'sinkron'])->middleware('throttle:10,1')->name('wali.sinkron');
        Route::post('/wali/{wali}/buka-kunci', [WaliController::class, 'bukaKunci'])->middleware('throttle:30,1')->name('wali.buka-kunci');
    });

    // Izin siswa: wali kelas / admin meninjau
    Route::get('/izin', [IzinController::class, 'index'])->name('izin.index');
    Route::post('/izin/{izin}/setujui', [IzinController::class, 'approve'])->middleware('throttle:60,1')->name('izin.approve');
    Route::post('/izin/{izin}/tolak', [IzinController::class, 'reject'])->middleware('throttle:60,1')->name('izin.reject');

    // Tugas & nilai tugas: tiap mata pelajaran di sebuah kelas punya ruang tugas sendiri (dari Kelas & Mapel)
    Route::get('/tugas', fn () => redirect()->route('kelas.index'))->name('tugas.index');
    Route::get('/kelas/{kelas}/mapel/{mapel}/tugas', [TugasController::class, 'ruang'])->name('tugas.ruang');
    Route::get('/kelas/{kelas}/mapel/{mapel}/tugas/buat', [TugasController::class, 'create'])->name('tugas.create');
    Route::post('/kelas/{kelas}/mapel/{mapel}/tugas', [TugasController::class, 'store'])->middleware('throttle:30,1')->name('tugas.store');
    Route::get('/tugas/{tugas}', [TugasController::class, 'show'])->name('tugas.show');
    Route::get('/tugas/{tugas}/edit', [TugasController::class, 'edit'])->name('tugas.edit');
    Route::put('/tugas/{tugas}', [TugasController::class, 'update'])->middleware('throttle:30,1')->name('tugas.update');
    Route::delete('/tugas/{tugas}', [TugasController::class, 'destroy'])->middleware('throttle:30,1')->name('tugas.destroy');
    Route::post('/tugas/{tugas}/nilai', [TugasController::class, 'simpanNilai'])->middleware('throttle:60,1')->name('tugas.nilai');
});
