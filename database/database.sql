-- SisforSinta - SDIT Darul Fikri
-- Database Schema dengan Relasi Lengkap

CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nip VARCHAR(30) NULL,
    nama_lengkap VARCHAR(100) NULL,
    email VARCHAR(100) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    username VARCHAR(50) NULL,
    password VARCHAR(255) NOT NULL,
    no_hp VARCHAR(20) NULL,
    foto_profil VARCHAR(255) NULL,
    jenis_kelamin ENUM('L','P') NULL,
    status ENUM('Aktif','Pensiun','Pindah') NOT NULL DEFAULT 'Aktif',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE tahun_ajaran (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_semester VARCHAR(50) NOT NULL,
    status_aktif ENUM('Y','N') NOT NULL DEFAULT 'N',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE mata_pelajaran (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode_pelajaran VARCHAR(20) NOT NULL,
    nama_pelajaran VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE kelas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_kelas VARCHAR(50) NOT NULL,
    wali_kelas_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE siswa (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nis VARCHAR(20) NOT NULL,
    nisn VARCHAR(20) NULL,
    nama_siswa VARCHAR(100) NOT NULL,
    tanggal_lahir DATE NULL,
    alamat VARCHAR(100) NULL,
    jenis_kelamin ENUM('L','P') NOT NULL,
    kelas_id BIGINT UNSIGNED NULL,
    nama_wali VARCHAR(100) NULL,
    no_hp_wali VARCHAR(20) NULL,
    status ENUM('Aktif','Lulus','Pindah') NOT NULL DEFAULT 'Aktif',
    qr_token VARCHAR(64) NULL UNIQUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE jadwal_pelajaran (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kelas_id BIGINT UNSIGNED NOT NULL,
    pelajaran_id BIGINT UNSIGNED NOT NULL,
    guru_id BIGINT UNSIGNED NOT NULL,
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE presensi (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    jadwal_id BIGINT UNSIGNED NOT NULL,
    siswa_id BIGINT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    status_kehadiran ENUM('H','I','S','A') NOT NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE presensi_sholat (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    siswa_id BIGINT UNSIGNED NOT NULL,
    kelas_id BIGINT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    jenis_sholat VARCHAR(20) NOT NULL,      -- 'Dzuhur','Ashar' ('Dhuha' = arsip sebelum Nov 2026)
    status_kehadiran VARCHAR(20) NOT NULL,  -- 'Hadir','Tidak Hadir'
    status_lama CHAR(1) NULL,               -- arsip status H/I/S/A sebelum Nov 2026
    keterangan VARCHAR(255) NULL,
    recorded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE pengumuman (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    judul VARCHAR(150) NOT NULL,
    isi TEXT NOT NULL,
    sasaran VARCHAR(20) NOT NULL DEFAULT 'Semua',   -- 'Semua','Guru','Wali Murid'
    penting BOOLEAN NOT NULL DEFAULT FALSE,
    dibuat_oleh BIGINT UNSIGNED NULL,               -- FK users
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

-- kelas.pengampu_konseling_id BIGINT UNSIGNED NULL (FK users): guru pengampu konseling kelas
CREATE TABLE konseling_percakapan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    siswa_id BIGINT UNSIGNED NOT NULL,              -- pihak wali = wali dari siswa ini
    topik VARCHAR(150) NOT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'Aktif',    -- 'Aktif','Selesai'
    pesan_terakhir_pada TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE konseling_pesan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    percakapan_id BIGINT UNSIGNED NOT NULL,
    pengirim VARCHAR(10) NOT NULL,                  -- 'wali','guru'
    guru_id BIGINT UNSIGNED NULL,                   -- diisi jika pengirim guru
    isi TEXT NOT NULL,
    dibaca_pada TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE presensi_guru (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    guru_id BIGINT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    status VARCHAR(20) NOT NULL,                    -- 'Hadir','Tidak Hadir'
    jam_masuk TIME NULL,
    jam_pulang TIME NULL,
    masuk_latitude DECIMAL(10,7) NULL,
    masuk_longitude DECIMAL(10,7) NULL,
    masuk_jarak_meter INT UNSIGNED NULL,
    masuk_skor_wajah DECIMAL(5,2) NULL,
    pulang_latitude DECIMAL(10,7) NULL,
    pulang_longitude DECIMAL(10,7) NULL,
    pulang_jarak_meter INT UNSIGNED NULL,
    pulang_skor_wajah DECIMAL(5,2) NULL,
    sumber VARCHAR(20) NOT NULL DEFAULT 'aplikasi', -- 'aplikasi','otomatis','admin'
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_presensi_guru_tanggal (guru_id, tanggal)
);

CREATE TABLE pengaturan_sekolah (
    kunci VARCHAR(50) NOT NULL,                     -- sekolah_latitude, sekolah_longitude, radius_meter, jam_masuk_guru
    nilai TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (kunci)
);

CREATE TABLE hari_libur (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tanggal DATE NOT NULL,
    keterangan VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_hari_libur_tanggal (tanggal)
);

CREATE TABLE wali (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    no_hp VARCHAR(20) NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'Aktif',    -- 'Aktif','Nonaktif'
    terakhir_login_pada TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_wali_username (username)
);

CREATE TABLE siswa_wali (
    wali_id BIGINT UNSIGNED NOT NULL,
    siswa_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (wali_id, siswa_id)
);

CREATE TABLE pengajuan_izin (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    siswa_id BIGINT UNSIGNED NOT NULL,
    wali_id BIGINT UNSIGNED NULL,
    jenis VARCHAR(10) NOT NULL,                     -- 'Sakit','Izin'
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    alasan TEXT NOT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'Menunggu', -- 'Menunggu','Disetujui','Ditolak'
    ditinjau_oleh BIGINT UNSIGNED NULL,
    ditinjau_pada TIMESTAMP NULL,
    catatan_peninjau VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE tugas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kelas_id BIGINT UNSIGNED NOT NULL,
    pelajaran_id BIGINT UNSIGNED NOT NULL,
    guru_id BIGINT UNSIGNED NULL,
    judul VARCHAR(150) NOT NULL,
    deskripsi TEXT NULL,
    tenggat DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

CREATE TABLE nilai_tugas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tugas_id BIGINT UNSIGNED NOT NULL,
    siswa_id BIGINT UNSIGNED NOT NULL,
    nilai DECIMAL(5,2) NULL,
    catatan VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_nilai_tugas (tugas_id, siswa_id)
);

CREATE TABLE wajah_guru (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    guru_id BIGINT UNSIGNED NOT NULL,
    template TEXT NOT NULL,                         -- terenkripsi (APP_KEY)
    didaftarkan_pada TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_wajah_guru (guru_id)
);

CREATE TABLE nilai_rapot (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tahun_ajaran_id BIGINT UNSIGNED NOT NULL,
    siswa_id BIGINT UNSIGNED NOT NULL,
    pelajaran_id BIGINT UNSIGNED NOT NULL,
    formatif_data JSON NULL,
    sas DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    kehadiran DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    pengurang_tidaktelat DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    pengurang_menyontek DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    nilai_rapor DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

-- Foreign Keys

ALTER TABLE kelas
    ADD CONSTRAINT fk_kelas_wali
        FOREIGN KEY (wali_kelas_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE siswa
    ADD CONSTRAINT fk_siswa_kelas
        FOREIGN KEY (kelas_id) REFERENCES kelas (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD UNIQUE KEY uq_siswa_nis (nis),
    ADD UNIQUE KEY uq_siswa_nisn (nisn);

ALTER TABLE jadwal_pelajaran
    ADD CONSTRAINT fk_jadwal_kelas
        FOREIGN KEY (kelas_id) REFERENCES kelas (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_jadwal_pelajaran
        FOREIGN KEY (pelajaran_id) REFERENCES mata_pelajaran (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_jadwal_guru
        FOREIGN KEY (guru_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE presensi
    ADD CONSTRAINT fk_presensi_jadwal
        FOREIGN KEY (jadwal_id) REFERENCES jadwal_pelajaran (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_presensi_siswa
        FOREIGN KEY (siswa_id) REFERENCES siswa (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD UNIQUE KEY uq_presensi_jadwal_siswa_tanggal (jadwal_id, siswa_id, tanggal);

ALTER TABLE presensi_sholat
    ADD CONSTRAINT fk_presholat_siswa
        FOREIGN KEY (siswa_id) REFERENCES siswa (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_presholat_kelas
        FOREIGN KEY (kelas_id) REFERENCES kelas (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_presholat_recorder
        FOREIGN KEY (recorded_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD UNIQUE KEY uq_presholat_siswa_tanggal_jenis (siswa_id, tanggal, jenis_sholat);

ALTER TABLE nilai_rapot
    ADD CONSTRAINT fk_nilai_tahun_ajaran
        FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_nilai_siswa
        FOREIGN KEY (siswa_id) REFERENCES siswa (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD CONSTRAINT fk_nilai_pelajaran
        FOREIGN KEY (pelajaran_id) REFERENCES mata_pelajaran (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    ADD UNIQUE KEY uq_nilai_ta_siswa_pelajaran (tahun_ajaran_id, siswa_id, pelajaran_id);
