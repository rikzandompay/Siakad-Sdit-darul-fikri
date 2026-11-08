# PRD MyDarfik (SisforSinta Mobile) – Update November 2026

Nov 8, 2026 · rikzan

## Ringkasan

SisforSinta Mobile adalah satu APK Android untuk SDIT Darul Fikri yang melayani tiga peran, yaitu Guru, Wali Murid, dan Kepala Sekolah/Admin, dan terhubung ke SIAKAD web. Desain di Figma sudah mencakup 65 layar prototype yang saling terhubung, ditambah kartu pelajar, kartu guru, splash, dan karakter 2D. Nama aplikasi ditetapkan menjadi **MyDarfik**.

Pembaruan November 2026 menambahkan beberapa hal. Status sholat disederhanakan menjadi Hadir dan Tidak Hadir. Ada fitur Rekap Absen Saya untuk guru, Kartu Guru untuk guru dan kepala sekolah, layar splash, alur rapor per semester, pencarian jadwal guru, dan konseling antara wali dan guru.

**Latar belakang.** Saat ini guru dan wali murid bergantung pada SIAKAD web yang kurang praktis dibuka di HP. Aplikasi mobile membawa pekerjaan harian, yaitu presensi, sholat, izin, nilai, dan komunikasi, ke HP dengan data yang sama seperti di web.

## Tujuan dan ruang lingkup

Tujuannya: setiap peran bisa menyelesaikan tugas hariannya dari HP dalam beberapa ketukan, dan datanya langsung tercatat di SIAKAD.

| Tujuan | Ukuran keberhasilan (usulan) |
| --- | --- |
| Presensi kelas dan sholat dicatat dari HP | Semua kelas tercatat sebelum hari selesai |
| Absen guru tanpa kertas | Guru absen masuk dan pulang lewat pengenalan wajah di area sekolah |
| Wali murid tahu kondisi anak setiap hari | Wali bisa melihat kehadiran, sholat, PR, dan pengumuman di dashboard |
| Izin dan konseling lewat aplikasi | Izin diverifikasi guru, konseling lewat chat, tanpa harus datang ke sekolah |
| Kepala sekolah memantau dari satu layar | Rekap siswa, guru, sholat, dan nilai bisa dibuka dari dashboard |

Angka target pasti (misalnya persentase adopsi) belum ditetapkan dan perlu disepakati sekolah.

**Dalam lingkup:** aplikasi Android untuk tiga peran, login gabungan, presensi kelas, presensi sholat dengan QR, absen guru dengan wajah dan lokasi, izin, tugas dan nilai, rapor, jadwal, konseling, pengumuman, kartu pelajar dan kartu guru, rekap untuk kepala sekolah.

**Di luar lingkup (usulan, perlu dikonfirmasi):** aplikasi iOS, pembayaran SPP, dan fitur untuk siswa sebagai pengguna langsung. Pengelolaan data master yang besar tetap di SIAKAD web.

## Pengguna dan peran

Tiga peran memakai satu aplikasi. Login memisahkan Guru & Staf dan Wali Murid; kepala sekolah masuk sebagai guru dengan hak admin. Hak akses tiap peran diatur lewat middleware, sehingga kepala sekolah tidak perlu layar Profil sendiri.

| Peran | Siapa | Kebutuhan utama | Masuk dengan |
| --- | --- | --- | --- |
| Guru | Guru mata pelajaran dan wali kelas | Presensi kelas dan sholat, tugas dan nilai, verifikasi izin, konseling, absen diri | Username / NIP + kata sandi |
| Wali Murid | Orang tua atau wali siswa | Memantau kehadiran, sholat, PR, rapor, jadwal; mengajukan izin; konseling dengan guru | Akun wali + kata sandi |
| Kepala Sekolah / Admin | Kepala sekolah dan admin TU | Rekap siswa, guru, sholat, nilai; pengumuman; data master ringan; tetap bisa mengajar | Akun guru dengan hak admin |

Siswa tidak memakai aplikasi. Siswa cukup membawa Kartu Pelajar yang QR-nya di-scan guru saat sholat.

## Fitur per peran

Semua peran mulai dari splash (sekitar 3,5 detik), lalu ke halaman login, lalu ke dashboard sesuai peran. Menu utama memakai grid tile 4×2 dengan ikon 3D; wali murid tidak memakai navbar bawah.

```
Splash (±3,5 detik) ──► Login
                          ├──► Guru: presensi kelas, sholat Dzuhur/Ashar (scan QR), bagi & nilai tugas, surat izin, konseling, absen wajah, rekap absen
                          ├──► Wali Murid: kehadiran & sholat anak, nilai rapor per semester, jadwal & tas, jadwal guru, izin, konseling, info sekolah, profil
                          └──► Kepala Sekolah / Admin: rekap siswa, guru, sholat, nilai; data guru, siswa, mapel; pengumuman; tahun ajaran; Mengajar Saya
```

Login memisahkan Guru & Staf dan Wali Murid; kepala sekolah mendapat dashboard admin dengan tab Mengajar Saya.

### Guru

| Fitur | Alur | Status |
| --- | --- | --- |
| Dashboard | Kartu Guru, Kehadiran Hari Ini, 8 tile aksi, Presensi Saya, Jadwal Mengajar Hari Ini | Diperbarui |
| Presensi Kelas | Pilih kelas → pilih mata pelajaran → isi kehadiran siswa → tersimpan | Ada |
| Sholat | Pilih kelas → pilih Dzuhur/Ashar → scan QR kartu pelajar → QR terbaca (otomatis Hadir) → tersimpan | Diperbarui |
| Bagi Tugas | Buat tugas → tugas terkirim | Ada |
| Nilai Tugas | Input nilai tugas per siswa | Ada |
| Surat Izin | Lihat izin dari wali → setujui / tolak (badge jumlah izin baru) | Ada |
| Konseling | Daftar percakapan → chat dengan wali murid | Baru |
| Absen Saya | Daftarkan wajah (sekali) → verifikasi lokasi → verifikasi wajah → berhasil; layar gagal: di luar area, wajah tidak cocok | Ada |
| Rekap Absen | Ringkasan bulan (Hadir, Tidak Hadir, persentase) + daftar harian jam masuk–pulang | Baru, menggantikan tile Profil |
| Profil | Dibuka dari avatar: Kartu Guru, identitas, info mengajar, ubah kata sandi, jadwal mengajar, bantuan TU, keluar | Diperbarui |

### Wali Murid

| Fitur | Alur | Status |
| --- | --- | --- |
| Dashboard | Kartu Pelajar, Aktivitas Hari Ini (kehadiran, Sholat Dzuhur, Sholat Ashar), Menu 4×2, Pengingat PR, Kegiatan Sekolah | Diperbarui, tanpa navbar |
| Absensi | Kehadiran dan sholat anak per hari | Ada |
| Nilai Rapor | Pilih kelas → pilih semester ganjil/genap → pilih: lihat hasil studi 2 semester (grafik batang) atau lanjut lihat nilai mata pelajaran | Baru |
| Jadwal & Tas | Jadwal pelajaran dan barang yang dibawa | Ada |
| Info Sekolah | Pengumuman sekolah | Ada |
| Izin | Ajukan izin anak → izin terkirim | Ada |
| Jadwal Guru | Cari nama guru → lihat jadwal mengajarnya | Baru |
| Konseling | Halaman konseling → riwayat → chat dengan guru | Baru |
| Profil | Kartu Pelajar, Data Ananda, Data Wali, Ubah Data | Diperbarui |

### Kepala Sekolah / Admin

| Fitur | Alur | Status |
| --- | --- | --- |
| Dashboard | Kartu Guru (data kepala sekolah), tab Sekolah / Mengajar Saya, kehadiran siswa hari ini, presensi guru ringkas | Diperbarui |
| Menu | Data Guru, Data Siswa, Kelas & Mapel, Info Sekolah (buat pengumuman) | Ada |
| Presensi | Input Presensi, Rekap Absensi, Sholat Dzuhur, Sholat Ashar, Rekap Sholat, Surat Izin, Rekap Presensi Guru | Diperbarui |
| Nilai & Pengaturan | Rekap Nilai, Tahun Ajaran (+ tambah), Mata Pelajaran (+ tambah) | Ada |
| Mengajar Saya | Fitur guru untuk kepala sekolah yang juga mengajar | Ada |

### Kartu identitas

| Kartu | Isi depan | Isi belakang | QR |
| --- | --- | --- | --- |
| Kartu Pelajar | Foto, nama, NIS, NISN, kelas, tanggal lahir | Foto, nama wali, no HP wali, alamat | Ya, QR SHOLAT untuk presensi sholat |
| Kartu Guru | Foto, nama, NIP, jabatan (Guru / Kepala Sekolah) | Foto, no HP, tanggal lahir, alamat sekolah | Tidak; guru absen dengan pengenalan wajah |

## Aturan bisnis

**Presensi sholat hanya punya dua status: Hadir dan Tidak Hadir.**

1. Guru memilih kelas dan jenis sholat (Dzuhur pukul 12:00 atau Ashar pukul 15:15), lalu men-scan QR di Kartu Pelajar siswa.
2. Setiap QR yang terbaca langsung tercatat **Hadir** di database presensi sholat hari itu. Guru tidak memilih status secara manual.
3. Siswa yang belum di-scan sampai pukul 13:00 otomatis tercatat **Tidak Hadir**.
4. Status lama (Berjamaah, Masbuq, Udzur Syar'i, Alpa) dihapus dari semua layar. Rekap Sholat kepala sekolah hanya menampilkan kolom Hadir dan Tidak Hadir. Sholat Dzuhur dan Ashar berlaku untuk kelas 1–6; aturan web lama (Dhuha kelas 1–6, Zuhur kelas 3–6) tidak dipakai lagi.

**Absen guru memakai pengenalan wajah dan lokasi.**

1. Guru mendaftarkan wajah sekali di awal.
2. Setiap absen, aplikasi memeriksa lokasi (harus di area sekolah) lalu mencocokkan wajah.
3. Gagal lokasi atau gagal wajah menampilkan layar penjelasan dan absen tidak tercatat.
4. Jam masuk acuan 07:00. Rekap Absen Saya menampilkan jam masuk–pulang per hari; guru yang belum absen masuk sampai pukul 13:00 tercatat Tidak Hadir.

**Rapor dilihat per kelas dan per semester.** Wali memilih kelas, lalu semester ganjil atau genap. Setelah itu ada dua pilihan: hasil studi gabungan 2 semester dalam grafik batang, atau langsung ke nilai mata pelajaran. Semester tidak digabung semua sekaligus.

**Izin.** Wali mengajukan izin anak dari aplikasi; guru (wali kelas) menyetujui atau menolak. Izin yang disetujui otomatis tercatat di presensi kelas.

**Konseling.** Percakapan satu lawan satu antara wali murid dan guru, tersimpan sebagai riwayat.

## Desain dan identitas visual

Semua layar dibuat sebagai layer native yang bisa diedit di Figma (file Desain Mobile, halaman "Desain Mobile"), lengkap dengan link prototype.

| Elemen | Ketentuan |
| --- | --- |
| Warna | Latar gradien putih ke biru muda, teks navy, warna utama biru #1E7FC4; hijau untuk Hadir, merah untuk Tidak Hadir |
| Token | Variabel warna color/brand, ink, surface, line, ok, alpa, sakit, gold |
| Huruf | Inter; gaya Title 20, Heading 16, Label 14, Body 14, Body Small 13, Caption 12 |
| Ikon | Ikon 2D datar untuk navigasi, ikon gaya 3D untuk tile menu |
| Logo | Lambang sekolah di splash, login, dan kartu |
| Karakter | 3 karakter 2D (murid laki-laki, murid perempuan, guru perempuan) dan satu adegan kelas, belum dipakai di layar |
| Splash | Logo membesar dengan Smart Animate, judul dan tagline naik, lalu memudar ke login |
| Layar | Ukuran 412×844; menu grid 4×2 |

| Section Figma | Isi |
| --- | --- |
| Login & Guru | 2 layar splash, login guru, 27 layar guru termasuk Rekap Absen Saya |
| Wali Murid | Login wali dan 18 layar wali |
| Kepala Sekolah | 16 layar admin dan kepala sekolah |
| Kartu Pelajar & Guru | Kartu Pelajar dan Kartu Guru, depan dan belakang (856×540) |
| Karakter | Tiga karakter 2D dan adegan kelas |
| Komponen | Tombol, chip status, dan komponen dasar |

## Kebutuhan non-fungsional dan integrasi

Aplikasi tidak punya data sendiri: semua data dibaca dan ditulis ke SIAKAD web, sehingga web dan aplikasi selalu sama. Detail teknis di bawah adalah usulan yang perlu disepakati dengan tim pengembang.

| Aspek | Kebutuhan |
| --- | --- |
| Platform | Satu APK Android untuk tiga peran; peran ditentukan saat login |
| Integrasi | API SIAKAD untuk siswa, guru, kelas, mapel, presensi, sholat, izin, nilai, rapor, pengumuman |
| Presensi sholat | Scan QR memakai kamera; hasil scan langsung terkirim; status Tidak Hadir diisi otomatis oleh server pukul 13:00 |
| Absen guru | Verifikasi lokasi (batas area sekolah) dan pengenalan wajah; data wajah disimpan aman dan hanya untuk absensi |
| Keamanan | Login dengan sesi; opsi "Ingat saya"; hak akses per peran diatur middleware; tiap peran hanya melihat datanya sendiri (wali hanya anaknya) |
| Notifikasi | Usulan: izin baru, pesan konseling, pengumuman |
| Koneksi | Usulan: scan QR tetap bisa disimpan sementara saat sinyal lemah lalu dikirim ulang |
| Bahasa | Bahasa Indonesia |

## Perubahan, pertanyaan terbuka, dan langkah berikutnya

### Daftar perubahan (November 2026, terbaru di atas)

| Area | Perubahan |
| --- | --- |
| Branding | Karakter 2D (2 murid, 1 guru) dan adegan kelas; nama aplikasi MyDarfik |
| Semua peran | Splash animasi sebelum login |
| Guru, Kepala Sekolah | Kartu Guru tanpa QR, dipasang di dashboard guru, dashboard kepala sekolah, dan profil guru |
| Guru | Tile Profil diganti Rekap Absen; layar Rekap Absen Saya; Profil lewat avatar |
| Sholat | Status jadi Hadir / Tidak Hadir; scan QR otomatis Hadir; belum scan sampai pukul 13:00 = Tidak Hadir |
| Menu | Semua menu tile memakai grid 4×2 |
| Konseling | Fitur baru wali ↔ guru (landing, riwayat, chat) |
| Sholat | Presensi Dzuhur dan Ashar dengan scan QR Kartu Pelajar |
| Wali Murid | Jadwal Guru (cari guru → jadwal) |
| Wali Murid | Alur Nilai Rapor per kelas dan semester, dengan grafik hasil studi |
| Wali Murid | Navbar bawah dihapus, navigasi lewat tile |
| Wali Murid | Kartu Pelajar di dashboard dan profil |
| Prototype | Semua link diperiksa; 62 layar terjangkau tanpa link rusak (sebelum layar Rekap Absen dan splash ditambah) |

### Keputusan dan pertanyaan terbuka

| Pertanyaan | Keputusan |
| --- | --- |
| Nama final aplikasi | MyDarfik |
| Aturan kelas untuk sholat | Pakai aturan aplikasi: Dzuhur dan Ashar untuk kelas 1–6 |
| Batas "hari selesai" untuk Tidak Hadir otomatis | Pukul 13:00, untuk murid dan guru |
| Izin yang disetujui mengubah presensi kelas | Ya, otomatis |
| Layar Profil kepala sekolah | Tidak perlu; akses diatur middleware peran |
| Kartu di dashboard bisa diketuk | Tidak; kartu hanya tampilan |
| Target angka keberhasilan | Ditutup, belum ditetapkan |

Masih terbuka:

- [ ] Sholat Ashar pukul 15:15, setelah batas 13:00. Apakah batas Tidak Hadir untuk Ashar dibuat terpisah (misalnya setelah waktu Ashar), atau 13:00 hanya berlaku untuk Dzuhur dan absen guru?

### Langkah berikutnya

1. Audit ulang link prototype setelah layar Rekap Absen Saya, splash, dan Kartu Guru ditambahkan.
2. Ganti teks "SisforSinta" di layar menjadi MyDarfik, lalu buat ikon aplikasi. Cek juga ketersediaan nama di Play Store.
3. Putuskan pemakaian karakter 2D (splash atau onboarding 3 slide).
4. Ganti data contoh (nama, NIP, nomor HP) dengan data asli sebelum uji coba.
5. Sepakati daftar endpoint API SIAKAD dengan tim pengembang.
