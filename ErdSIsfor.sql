-- 1. Buat Tabel Tahun Ajaran / Semester (Konfigurasi Sistem)
CREATE TABLE tahun_ajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_semester VARCHAR(50) NOT NULL, -- Contoh: "Ganjil 2023/2024"
    status_aktif ENUM('Y', 'N') DEFAULT 'N',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Buat Tabel Users (Untuk Guru dan Admin Settings)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Guru') DEFAULT 'Guru',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Buat Tabel Mata Pelajaran
CREATE TABLE mata_pelajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pelajaran VARCHAR(20) UNIQUE NOT NULL,
    nama_pelajaran VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Buat Tabel Kelas
CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(50) NOT NULL, -- Contoh: "1A", "6B"
    wali_kelas_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wali_kelas_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 5. Buat Tabel Data Siswa
CREATE TABLE siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(20) UNIQUE NOT NULL,
    nama_siswa VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    kelas_id INT,
    status ENUM('Aktif', 'Lulus', 'Pindah') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL
);

-- 6. Buat Tabel Jadwal Pelajaran (Relasi Guru, Kelas, dan Mapel)
CREATE TABLE jadwal_pelajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id INT NOT NULL,
    pelajaran_id INT NOT NULL,
    guru_id INT NOT NULL,
    hari ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    FOREIGN KEY (pelajaran_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. Buat Tabel Presensi Setiap Pelajaran
CREATE TABLE presensi (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    jadwal_id INT NOT NULL,
    siswa_id INT NOT NULL,
    tanggal DATE NOT NULL,
    status_kehadiran ENUM('H', 'I', 'S', 'A') NOT NULL COMMENT 'H=Hadir, I=Izin, S=Sakit, A=Alpa',
    keterangan VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal_pelajaran(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    UNIQUE (jadwal_id, siswa_id, tanggal) -- Mencegah 1 siswa diabsen 2 kali di pelajaran dan hari yang sama
);

-- 8. Buat Tabel Input Nilai Rapot
CREATE TABLE nilai_rapot (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tahun_ajaran_id INT NOT NULL,
    siswa_id INT NOT NULL,
    pelajaran_id INT NOT NULL,
    nilai_tugas DECIMAL(5,2) DEFAULT 0.00,
    nilai_uts DECIMAL(5,2) DEFAULT 0.00,
    nilai_uas DECIMAL(5,2) DEFAULT 0.00,
    nilai_akhir DECIMAL(5,2) GENERATED ALWAYS AS ((nilai_tugas * 0.3) + (nilai_uts * 0.3) + (nilai_uas * 0.4)) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (pelajaran_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE,
    UNIQUE (tahun_ajaran_id, siswa_id, pelajaran_id) -- 1 siswa hanya punya 1 rapot per mapel di semester tersebut
);