<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\NilaiRapotController;
use App\Http\Controllers\PengaturanController;

// ── AUTH ROUTES ──
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── PROTECTED ROUTES ──
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Siswa CRUD + Export
    Route::get('/siswa', [SiswaController::class, 'index'])->name('siswa.index');
    Route::get('/siswa/export/csv', [SiswaController::class, 'exportCsv'])->name('siswa.export.csv');
    Route::get('/siswa/export/pdf', [SiswaController::class, 'exportPdf'])->name('siswa.export.pdf');
    Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
    Route::put('/siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
    Route::delete('/siswa/{siswa}', [SiswaController::class, 'destroy'])->name('siswa.destroy');

    // Kelas CRUD
    Route::get('/kelas', [KelasController::class, 'index'])->name('kelas.index');
    Route::get('/kelas/{kelas}', [KelasController::class, 'show'])->name('kelas.show');
    Route::post('/kelas', [KelasController::class, 'store'])->name('kelas.store');
    Route::put('/kelas/{kelas}', [KelasController::class, 'update'])->name('kelas.update');
    Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy'])->name('kelas.destroy');

    // Jadwal Pelajaran
    Route::post('/jadwal', [JadwalPelajaranController::class, 'store'])->name('jadwal.store');
    Route::put('/jadwal/{jadwal}', [JadwalPelajaranController::class, 'update'])->name('jadwal.update');
    Route::delete('/jadwal/{jadwal}', [JadwalPelajaranController::class, 'destroy'])->name('jadwal.destroy');
    Route::get('/jadwal/{kelas}/export/csv', [JadwalPelajaranController::class, 'exportCsv'])->name('jadwal.export.csv');
    Route::get('/jadwal/{kelas}/export/pdf', [JadwalPelajaranController::class, 'exportPdf'])->name('jadwal.export.pdf');

    // Presensi + Export
    Route::get('/presensi', [PresensiController::class, 'index'])->name('presensi.index');
    Route::get('/presensi/rekap', [PresensiController::class, 'rekap'])->name('presensi.rekap');
    Route::get('/presensi/{kelas}', [PresensiController::class, 'show'])->name('presensi.show');
    Route::post('/presensi', [PresensiController::class, 'store'])->name('presensi.store');
    Route::get('/presensi/{kelas}/export/csv', [PresensiController::class, 'exportCsv'])->name('presensi.export.csv');
    Route::get('/presensi/{kelas}/export/pdf', [PresensiController::class, 'exportPdf'])->name('presensi.export.pdf');

    // Nilai Rapot + Export
    Route::get('/nilai', [NilaiRapotController::class, 'index'])->name('nilai.index');
    Route::get('/nilai/rekap', [NilaiRapotController::class, 'rekap'])->name('nilai.rekap');
    Route::get('/nilai/rekap/export/csv', [NilaiRapotController::class, 'exportRekapCsv'])->name('nilai.rekap.export.csv');
    Route::get('/nilai/rekap/export/pdf', [NilaiRapotController::class, 'exportRekapPdf'])->name('nilai.rekap.export.pdf');
    Route::post('/nilai', [NilaiRapotController::class, 'store'])->name('nilai.store');
    Route::get('/nilai/export/csv', [NilaiRapotController::class, 'exportCsv'])->name('nilai.export.csv');
    Route::get('/nilai/export/pdf', [NilaiRapotController::class, 'exportPdf'])->name('nilai.export.pdf');

    // Pengaturan
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::put('/pengaturan/profil', [PengaturanController::class, 'updateProfil'])->name('pengaturan.profil.update');
    Route::put('/pengaturan/password', [PengaturanController::class, 'updatePassword'])->name('pengaturan.password.update');

    // Pengaturan - Tahun Ajaran
    Route::post('/pengaturan/tahun-ajaran', [PengaturanController::class, 'storeTahunAjaran'])->name('pengaturan.tahun-ajaran.store');
    Route::put('/pengaturan/tahun-ajaran/{tahunAjaran}', [PengaturanController::class, 'updateTahunAjaran'])->name('pengaturan.tahun-ajaran.update');
    Route::put('/pengaturan/tahun-ajaran/{tahunAjaran}/aktif', [PengaturanController::class, 'setAktifTahunAjaran'])->name('pengaturan.tahun-ajaran.aktif');
    Route::delete('/pengaturan/tahun-ajaran/{tahunAjaran}', [PengaturanController::class, 'destroyTahunAjaran'])->name('pengaturan.tahun-ajaran.destroy');

    // Pengaturan - Mata Pelajaran
    Route::post('/pengaturan/mapel', [PengaturanController::class, 'storeMapel'])->name('pengaturan.mapel.store');
    Route::put('/pengaturan/mapel/{mapel}', [PengaturanController::class, 'updateMapel'])->name('pengaturan.mapel.update');
    Route::delete('/pengaturan/mapel/{mapel}', [PengaturanController::class, 'destroyMapel'])->name('pengaturan.mapel.destroy');
});
