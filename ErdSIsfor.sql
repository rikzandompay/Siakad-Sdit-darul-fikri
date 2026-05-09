-- 1. Tabel Tahun Ajaran
CREATE TABLE tahun_ajaran (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    nama_semester VARCHAR(50) NOT NULL, -- Contoh: "Ganjil 2023/2024"
    status_aktif ENUM('Y', 'N') DEFAULT 'N',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 2. Tabel Users (Guru & Admin)
CREATE TABLE users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(30) UNIQUE NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NULL,
    password VARCHAR(255) NOT NULL,
    no_hp VARCHAR(20) NULL,
    foto_profil VARCHAR(255) NULL,
    role ENUM('Admin', 'Guru') DEFAULT 'Guru',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 3. Tabel Mata Pelajaran
CREATE TABLE mata_pelajaran (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    kode_pelajaran VARCHAR(20) UNIQUE NOT NULL,
    nama_pelajaran VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- 4. Tabel Kelas
CREATE TABLE kelas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(50) NOT NULL,
    wali_kelas_id BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (wali_kelas_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. Tabel Siswa
CREATE TABLE siswa (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(20) UNIQUE NOT NULL,
    nama_siswa VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    kelas_id BIGINT NULL,
    nama_wali VARCHAR(100) NULL,
    no_hp_wali VARCHAR(20) NULL,
    status ENUM('Aktif', 'Lulus', 'Pindah') DEFAULT 'Aktif',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL
);

-- 6. Tabel Jadwal Pelajaran
CREATE TABLE jadwal_pelajaran (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    kelas_id BIGINT NOT NULL,
    pelajaran_id BIGINT NOT NULL,
    guru_id BIGINT NOT NULL,
    hari ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (pelajaran_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. Tabel Presensi Mata Pelajaran
CREATE TABLE presensi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    jadwal_id BIGINT NOT NULL,
    siswa_id BIGINT NOT NULL,
    tanggal DATE NOT NULL,
    status_kehadiran ENUM('H', 'I', 'S', 'A') NOT NULL COMMENT 'H=Hadir, I=Izin, S=Sakit, A=Alpa',
    keterangan VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal_pelajaran(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    UNIQUE (jadwal_id, siswa_id, tanggal)
);

-- 8. Tabel Presensi Sholat (Update Terbaru)
CREATE TABLE presensi_sholat (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    siswa_id BIGINT NOT NULL,
    kelas_id BIGINT NOT NULL,
    tanggal DATE NOT NULL,
    jenis_sholat ENUM('Zuhur', 'Dhuha') NOT NULL,
    status_kehadiran ENUM('H', 'I', 'S', 'A') NOT NULL,
    keterangan VARCHAR(255) NULL,
    recorded_by BIGINT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE (siswa_id, tanggal, jenis_sholat)
);

-- 9. Tabel Nilai Rapot
CREATE TABLE nilai_rapot (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tahun_ajaran_id BIGINT NOT NULL,
    siswa_id BIGINT NOT NULL,
    pelajaran_id BIGINT NOT NULL,
    nilai_tugas DECIMAL(5,2) DEFAULT 0.00,
    nilai_uts DECIMAL(5,2) DEFAULT 0.00,
    nilai_uas DECIMAL(5,2) DEFAULT 0.00,
    nilai_akhir DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (pelajaran_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE,
    UNIQUE (tahun_ajaran_id, siswa_id, pelajaran_id)
);
