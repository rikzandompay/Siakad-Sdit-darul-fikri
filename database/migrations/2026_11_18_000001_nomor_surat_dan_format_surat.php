<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Surat resmi sekolah: buku nomor surat (urutan terpisah per jenis), nomor pada
 * pengajuan izin & pengumuman, serta data surat pada pengumuman (tanggal surat,
 * tempat, jadwal kegiatan) dan detail surat izin keperluan dari wali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomor_surat', function (Blueprint $t) {
            $t->id();
            $t->string('jenis', 20);
            $t->unsignedSmallInteger('tahun');
            $t->unsignedInteger('urutan');
            $t->string('nomor', 60);
            $t->timestamps();
            $t->unique(['jenis', 'tahun', 'urutan']);
        });

        Schema::table('pengajuan_izin', function (Blueprint $t) {
            $t->string('nomor_surat', 60)->nullable()->after('lampiran');
            $t->string('nama_orang_tua', 150)->nullable()->after('nomor_surat');
            $t->string('kota', 100)->nullable()->after('nama_orang_tua');
            $t->date('tanggal_surat')->nullable()->after('kota');
            $t->unsignedTinyInteger('jumlah_hari')->nullable()->after('tanggal_surat');
            $t->string('kontak', 100)->nullable()->after('jumlah_hari');
        });

        Schema::table('pengumuman', function (Blueprint $t) {
            $t->string('nomor_surat', 60)->nullable()->after('sasaran');
            $t->date('tanggal_surat')->nullable()->after('nomor_surat');
            $t->string('tempat', 100)->nullable()->after('tanggal_surat');
            $t->string('jadwal_hari_tanggal', 150)->nullable()->after('tempat');
            $t->string('jadwal_waktu', 100)->nullable()->after('jadwal_hari_tanggal');
            $t->string('jadwal_tempat', 150)->nullable()->after('jadwal_waktu');
        });
    }

    public function down(): void
    {
        Schema::table('pengumuman', function (Blueprint $t) {
            $t->dropColumn(['nomor_surat', 'tanggal_surat', 'tempat', 'jadwal_hari_tanggal', 'jadwal_waktu', 'jadwal_tempat']);
        });

        Schema::table('pengajuan_izin', function (Blueprint $t) {
            $t->dropColumn(['nomor_surat', 'nama_orang_tua', 'kota', 'tanggal_surat', 'jumlah_hari', 'kontak']);
        });

        Schema::dropIfExists('nomor_surat');
    }
};
