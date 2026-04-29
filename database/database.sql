-- Update Tabel Guru
CREATE TABLE guru (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nip VARCHAR(30) UNIQUE DEFAULT NULL, -- Tambahan baru
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE DEFAULT NULL, -- Tambahan baru
    no_hp VARCHAR(20) DEFAULT NULL, -- Tambahan baru
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    foto_profil VARCHAR(255) DEFAULT 'default.png', -- Tambahan baru
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Update Tabel Siswa
CREATE TABLE siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(20) UNIQUE NOT NULL,
    nama_siswa VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    kelas_id INT,
    nama_wali VARCHAR(100) DEFAULT NULL, -- Tambahan baru
    no_hp_wali VARCHAR(20) DEFAULT NULL, -- Tambahan baru
    status ENUM('Aktif', 'Lulus', 'Pindah') DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL
);

-- Update Tabel Nilai Rapot (Rumus disesuaikan dengan UI)
CREATE TABLE nilai_rapot (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tahun_ajaran_id INT NOT NULL,
    siswa_id INT NOT NULL,
    pelajaran_id INT NOT NULL,
    nilai_tugas DECIMAL(5,2) DEFAULT 0.00,
    nilai_uts DECIMAL(5,2) DEFAULT 0.00,
    nilai_uas DECIMAL(5,2) DEFAULT 0.00,
    -- Rumus disesuaikan: Tugas 20%, UTS 30%, UAS 50%
    nilai_akhir DECIMAL(5,2) GENERATED ALWAYS AS ((nilai_tugas * 0.2) + (nilai_uts * 0.3) + (nilai_uas * 0.5)) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (pelajaran_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE,
    UNIQUE (tahun_ajaran_id, siswa_id, pelajaran_id)
);