# Laporan Arsitektur Hak Akses & Laporan Web SisforSinta
**Sistem Informasi Akademik SDIT Darul Fikri Sidoarjo**  
*Dokumen Analisis Aktor, Matriks Otorisasi, dan Spesifikasi Laporan Sistem Web*

---

## Daftar Isi
1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Klasifikasi Aktor Pengguna Web](#2-klasifikasi-aktor-pengguna-web)
3. [Matriks Hak Akses (Access Control Matrix)](#3-matriks-hak-akses-access-control-matrix)
4. [Rincian Kewenangan per Modul Web](#4-rincian-kewenangan-per-modul-web)
5. [Katalog Laporan & Berkas Ekspor Web](#5-katalog-laporan--berkas-ekspor-web)
6. [Implementasi Keamanan & Otorisasi Kode](#6-implementasi-keamanan--otorisasi-kode)

---

## 1. Ringkasan Eksekutif

Aplikasi web **SisforSinta** dirancang untuk mengelola operasional akademik di tingkat sekolah dasar Islam terpadu (SDIT Darul Fikri). Sistem ini berfokus pada manajemen data induk sekolah, pembagian jadwal mengajar, pencatatan presensi pelajaran harian, pembiasaan ibadah sholat berjamaah, serta pengolahan nilai rapot berbasis kurikulum merdeka (penilaian formatif BAB 1–4 dan sumatif SAS).

Sistem web menggunakan prinsip *Role-Based Access Control* (RBAC) yang dikombinasikan dengan pembatasan relasional (*Attribute/Relationship-Based Scope*) berbasis wali kelas dan penugasan jadwal guru.

---

## 2. Klasifikasi Aktor Pengguna Web

Pada sistem web saat ini terdapat **3 Aktor Utama** yang memiliki akses masuk (*login*) melalui tabel `users`:

### A. Administrator / Kepala Sekolah (`Admin`)
* **Identifikasi:** Metode `isAdmin()` pada model `User` memeriksa apakah username pengguna terdaftar pada konfigurasi `config/admin.php` (default: `admin`).
* **Peran:** Penanggung jawab teknis dan manajerial tertinggi.
* **Tanggung Jawab:**
  * Manajemen data guru dan kepegawaian.
  * Manajemen master data siswa, kelas, mata pelajaran, dan tahun ajaran aktif.
  * Penyusunan jadwal mengajar mingguan.
  * Pemantauan laporan global kehadiran siswa, sholat, dan nilai rapot di seluruh tingkat kelas.

### B. Wali Kelas
* **Identifikasi:** Guru aktif yang ID-nya tercatat pada kolom `wali_kelas_id` di tabel `kelas`.
* **Peran:** Pembina dan penanggung jawab satu rombongan belajar (kelas tertentu).
* **Tanggung Jawab:**
  * Melakukan pencatatan **Presensi Sholat Berjamaah** (Sholat Dhuha kelas 1–6 dan Sholat Zuhur kelas 3–6).
  * Memantau dan mencetak rekap absensi kumulatif kelas binaannya.
  * Memvalidasi dan mencetak **Laporan Lembar Rapot Siswa** lengkap (Formatif & SAS) untuk seluruh siswa di kelasnya.
  * Menjalankan tugas pengajaran jika ditugaskan mengajar mapel tertentu.

### C. Guru Mata Pelajaran / Tenaga Pendidik
* **Identifikasi:** Guru aktif yang terdaftar sebagai pengajar pada tabel `jadwal_pelajaran`.
* **Peran:** Pelaksana kegiatan belajar mengajar (KBM) pada mata pelajaran spesifik.
* **Tanggung Jawab:**
  * Mencatat presensi kehadiran siswa per jam pelajaran yang diampunya.
  * Menginput dan memperbarui nilai formatif (TP 1–4, Ulangan Harian) dan nilai SAS untuk mata pelajarannya.
  * Memantau jadwal mengajar harian.
  * Mengelola profil dan kredensial akun pribadi.

> **Catatan Terkait Wali Murid & Siswa:**  
> Wali murid dan siswa **tidak memiliki hak akses login pada sistem web desktop**. Jalur akses orang tua disediakan khusus melalui **Aplikasi Mobile (Flutter)** menggunakan autentikasi NISN dan Nama Wali Murid.

---

## 3. Matriks Hak Akses (Access Control Matrix)

| Modul Sistem | Fitur / Operasi | Administrator | Wali Kelas | Guru Pengajar |
| :--- | :--- | :---: | :---: | :---: |
| **Dashboard** | Statistik Global Sekolah | ✅ Ya | ❌ Tidak | ❌ Tidak |
| | Jadwal & Aktivitas Personal | ✅ Ya | ✅ Ya | ✅ Ya |
| **Data Guru** | Lihat Daftar Guru | ✅ Ya | ❌ Dibatasi | ❌ Dibatasi |
| | Tambah, Edit, Hapus Guru | ✅ Ya | ❌ Dibatasi | ❌ Dibatasi |
| | Ekspor Laporan Guru (CSV/PDF) | ✅ Ya | ❌ Dibatasi | ❌ Dibatasi |
| **Data Siswa** | Lihat Semua Siswa Sekolah | ✅ Ya | ❌ Khusus Kelasnya | ❌ Khusus Mapelnya |
| | Tambah, Edit, Hapus Siswa | ✅ Ya | ❌ Dibatasi | ❌ Dibatasi |
| | Ekspor Daftar Siswa (CSV/PDF) | ✅ Semua Kelas | ✅ Kelas Binaan | ✅ Kelas Terkait |
| **Kelas & Mapel** | Buat/Edit/Hapus Kelas | ✅ Ya | ❌ Dibatasi | ❌ Dibatasi |
| | Atur Wali Kelas | ✅ Ya | ❌ Dibatasi | ❌ Dibatasi |
| | Buat/Edit/Hapus Jadwal KBM | ✅ Ya | ❌ Dibatasi | ❌ Dibatasi |
| | Ekspor Jadwal Pelajaran | ✅ Ya | ✅ Ya | ✅ Ya |
| **Presensi Pelajaran** | Input Presensi Jam KBM | ✅ Semua Kelas | ✅ Mapel Diampunya | ✅ Mapel Diampunya |
| | Rekap Absensi Bulanan/Semester | ✅ Semua Kelas | ✅ Kelas Binaan | ❌ Dibatasi |
| | Ekspor Rekap Presensi (CSV/PDF)| ✅ Semua Kelas | ✅ Kelas Binaan | ❌ Dibatasi |
| **Presensi Sholat** | Input Sholat Dhuha & Zuhur | ✅ Semua Kelas | ✅ Kelas Binaan | ❌ Dibatasi |
| | Rekap Ibadah Sholat Siswa | ✅ Semua Kelas | ✅ Kelas Binaan | ❌ Dibatasi |
| | Ekspor Rekap Sholat (CSV/PDF) | ✅ Semua Kelas | ✅ Kelas Binaan | ❌ Dibatasi |
| **Nilai Rapot** | Input Nilai Formatif & SAS | ✅ Semua Mapel | ✅ Mapel Diampunya | ✅ Mapel Diampunya |
| | Cetak Lembar Rapot Siswa | ✅ Semua Siswa | ✅ Kelas Binaan | ❌ Dibatasi |
| | Ekspor Rekap Nilai (CSV/PDF) | ✅ Semua Kelas | ✅ Kelas Binaan | ❌ Khusus Mapelnya |
| **Pengaturan Master** | Kelola Tahun Ajaran & Semester | ✅ Ya | ❌ Ditolak (403) | ❌ Ditolak (403) |
| | Kelola Master Mata Pelajaran | ✅ Ya | ❌ Ditolak (403) | ❌ Ditolak (403) |
| **Pengaturan Akun** | Ubah Password & Profil Pribadi | ✅ Ya | ✅ Ya | ✅ Ya |

---

## 4. Rincian Kewenangan per Modul Web

### 4.1 Modul Data Guru (`/guru`)
* **Hak Akses:** Khusus **Administrator / Kepala Sekolah**.
* **Keamanan:** Dilindungi oleh middleware `is_admin`.
* **Fungsi:** Menambah NIP, Nama Lengkap, Jenis Kelamin, Nomor Handphone, membuat Username login, mengatur Password default, serta mengubah status kepegawaian (Aktif, Pensiun, Pindah).

### 4.2 Modul Data Siswa (`/siswa`)
* **Administrator:** Hak penuh mengelola pendaftaran siswa baru, mutasi, edit identitas (NIS, NISN, Nama Lengkap, Tanggal Lahir, Jenis Kelamin, Alamat, Nama Wali, No HP Wali), dan menghapus siswa.
* **Guru & Wali Kelas:** Hanya memiliki hak baca (*read-only*) terhadap siswa yang berada di dalam `accessibleKelasIds()`. Form tambah/edit/hapus ditutup dengan verifikasi `abort_unless(auth()->user()->isAdmin(), 403)`.

### 4.3 Modul Kelas & Jadwal (`/kelas`)
* **Administrator:** Mengatur rombongan belajar (Tingkat 1 s/d 6), menunjuk guru sebagai wali kelas, mengalokasikan jam pelajaran, serta menetapkan guru pengampu per sesi.
* **Guru / Wali Kelas:** Melihat pembagian jadwal kelas dan jadwal mengajar mingguan masing-masing.

### 4.4 Modul Presensi Kelas (`/presensi`)
* **Alur Input:** Memilih kelas dan jadwal KBM aktif $\rightarrow$ Checklist kehadiran (Hadir, Izin, Sakit, Alpa) $\rightarrow$ Simpan batch.
* **Pembatasan Guru:** Guru mata pelajaran hanya bisa memilih jadwal pelajaran yang `guru_id`-nya adalah ID akun mereka sendiri.
* **Pembatasan Rekap:** Rekap kehadiran siswa per kelas (`/presensi/rekap`) hanya dapat dibuka oleh Wali Kelas dan Administrator.

### 4.5 Modul Presensi Sholat (`/presensi-sholat`)
* **Aturan Khusus Sekolah:**
  * **Sholat Dhuha:** Wajib diikuti seluruh kelas (Kelas 1 s/d 6).
  * **Sholat Zuhur:** Wajib diikuti kelas atas (Kelas 3 s/d 6).
* **Hak Akses:** Hanya **Wali Kelas** yang berwenang membuka formulir input absensi sholat untuk siswa di kelas binaannya. Guru mapel biasa tidak diberikan akses ke modul ini.

### 4.6 Modul Nilai Rapot (`/nilai`)
* **Struktur Penilaian:**
  * **Penilaian Formatif BAB 1 s/d BAB 4:** Masing-masing terdiri atas Tugas Penilaian 1 (TP1), TP2, TP3, TP4, dan Ulangan Harian (UH).
  * **Sumatif Akhir Semester (SAS):** Nilai evaluasi akhir semester.
  * **Penetapan KKM:** Standar KKM adalah 75. Nilai di bawah 75 ditandai *Perlu Remedial*.
* **Pembagian Akses:**
  * Guru pengampu mapel mengisi nilai untuk mata pelajaran yang diajarkannya.
  * Wali kelas memiliki hak kompilasi untuk mencetak buku/lembar rapot utuh seluruh mata pelajaran untuk siswa kelasnya.

---

## 5. Katalog Laporan & Berkas Ekspor Web

Aplikasi web SisforSinta menyediakan 7 macam laporan dalam format siap cetak (**PDF**) dan format data (**CSV**):

```
resources/views/exports/
├── guru-pdf.blade.php                 # Laporan Direktori Guru & Tenaga Pendidik
├── siswa-pdf.blade.php                # Laporan Buku Induk Siswa per Rombel
├── jadwal-pdf.blade.php               # Laporan Jadwal KBM Mingguan Kelas
├── presensi-pdf.blade.php             # Laporan Presensi Harian Sesi KBM
├── rekap-presensi-pdf.blade.php       # Laporan Rekapitulasi Absensi Bulanan/Semester
├── rekap-presensi-sholat-pdf.blade.php# Laporan Rekap Pembiasaan Sholat Berjamaah
├── nilai-pdf.blade.php                # Laporan Transkrip Nilai per Mata Pelajaran
└── rekap-nilai-pdf.blade.php          # Laporan Lembar Rapot Siswa Komprehensif
```

### Rincian Spesifikasi Laporan:

#### 1. Laporan Rekapitulasi Absensi Siswa
* **Route Unduhan:** `/presensi/rekap/export/pdf` dan `/presensi/rekap/export/csv`
* **Filter:** Tahun Ajaran, Semester, Kelas, dan Rentang Bulan.
* **Isi Laporan:** Nomor, NISN, Nama Siswa, Total Hadir (H), Total Izin (I), Total Sakit (S), Total Alpa (A), serta Persentase Kehadiran (%).
* **Penerima Laporan:** Kepala Sekolah, Wali Kelas, dan Arsip Tata Usaha.

#### 2. Laporan Rekap Presensi Sholat Berjamaah
* **Route Unduhan:** `/presensi-sholat/rekap/export/pdf` dan `/presensi-sholat/rekap/export/csv`
* **Filter:** Jenis Sholat (Dhuha / Zuhur), Kelas, dan Bulan Pelaksanaan.
* **Isi Laporan:** Catatan kedisiplinan sholat harian per siswa (Berjamaah, Masbuq, Udzur Syar'i, Alpa).
* **Penerima Laporan:** Wali Kelas, Guru PAI, dan Koordinator Keagamaan/Kesiswaan.

#### 3. Laporan Lembar Rapot Siswa (Rekap Lengkap)
* **Route Unduhan:** `/nilai/rekap/export/pdf` dan `/nilai/rekap/export/csv`
* **Filter:** Kelas, Tahun Ajaran, dan Semester.
* **Isi Laporan:** Rincian nilai Formatif BAB 1–4, Nilai SAS, Rata-rata Akhir, Predikat Huruf (A/B/C/D), serta Catatan Ketercapaian Kompetensi / Remedial.
* **Penerima Laporan:** Orang Tua / Wali Murid (diserahkan pada akhir semester oleh Wali Kelas).

#### 4. Laporan Nilai per Mata Pelajaran
* **Route Unduhan:** `/nilai/export/pdf` dan `/nilai/export/csv`
* **Isi Laporan:** Rekap nilai 1 mata pelajaran spesifik yang diserahkan guru mata pelajaran kepada wali kelas/kurikulum.

#### 5. Laporan Data Guru & Pegawai
* **Route Unduhan:** `/guru/export/pdf` dan `/guru/export/csv`
* **Isi Laporan:** NIP, Nama Lengkap, Jenis Kelamin, Kontak, Username, dan Status Keaktifan.

#### 6. Laporan Data Siswa
* **Route Unduhan:** `/siswa/export/pdf` dan `/siswa/export/csv`
* **Isi Laporan:** NIS, NISN, Nama Siswa, Kelas, Jenis Kelamin, Nama Wali Murid, dan Kontak Wali.

#### 7. Laporan Jadwal Pelajaran
* **Route Unduhan:** `/jadwal/{kelas}/export/pdf` dan `/jadwal/{kelas}/export/csv`
* **Isi Laporan:** Tabel jadwal harian (Senin–Jumat/Sabtu), jam pelajaran, nama mapel, dan guru pengampu.

---

## 6. Implementasi Keamanan & Otorisasi Kode

Pemisahan hak akses dalam basis kode SisforSinta dijamin melalui tiga lapis pengamanan (*defense in depth*):

1. **Middleware Level (`IsAdmin.php`):**
   Memeriksa `auth()->user()->isAdmin()`. Jika request bukan admin, sistem langsung menghentikan eksekusi dengan kode HTTP `403 Forbidden` (*"Akses Ditolak: Fitur ini hanya untuk Kepala Sekolah"*).
2. **Controller Query Scope (`accessibleKelasIds()`):**
   Guru biasa yang mencoba mengakses URL siswa atau presensi kelas lain secara langsung via parameter URL dicegah karena query database dibatasi oleh method `accessibleKelasIds()`, yang hanya mengambil:
   $$\text{Accessible Kelas} = \{\text{Kelas Wali}\} \cup \{\text{Kelas dengan Jadwal Mengajar Guru}\}$$
3. **View Conditionals (`@if (Auth::user()->isAdmin())`):**
   Antarmuka navigasi bilah samping (*SideNavBar*) secara otomatis menyembunyikan tautan menu yang tidak berhak diakses oleh guru non-admin (seperti menu Data Guru).

