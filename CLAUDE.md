# SisforSinta (Sistem Informasi Akademik SDIT Darul Fikri)

## Tentang Project
**SisforSinta** adalah sebuah Sistem Informasi Akademik berbasis web yang dirancang khusus untuk kebutuhan manajemen operasional di SDIT Darul Fikri. Sistem ini berfungsi untuk mengelola seluruh kegiatan administratif dan akademik sekolah secara digital, aman, dan terpusat.

## Tujuan Project
Tujuan utama dari pengembangan sistem SisforSinta ini adalah:
- **Digitalisasi Data Sekolah:** Mengelola kelengkapan data siswa (seperti NISN, Tanggal Lahir, Alamat), data guru,dan staf sekolah secara efisien.
- **Manajemen Akademik Terpusat:** Memudahkan proses pengelolaan (CRUD) jadwal akademik, penjadwalan mata pelajaran, dan penugasan guru pengampu.
- **Pemantauan (Monitoring) Aktivitas:** Menyediakan *Principal Dashboard* (Dashboard Kepala Sekolah) untuk memantau berjalannya seluruh aktivitas dan operasional sekolah.
- **Pencatatan Keagamaan/Ibadah:** Memiliki fitur khusus untuk melakukan pencatatan dan pelacakan kehadiran ibadah (sholat) siswa/i.
- **Keamanan dan Hak Akses:** Menerapkan sistem multi-role (Admin, Guru, Kepala Sekolah) untuk memastikan data hanya dapat diakses oleh pihak yang berwenang.
- **Otomatisasi Pelaporan:** Memfasilitasi pembuatan laporan dan ekspor data (misalnya dalam format PDF) untuk kemudahan pengarsipan data.

## Teknologi yang Digunakan

### Bahasa Pemrograman
- **PHP** (v8.3) - Sebagai bahasa pemrograman backend utama.
- **JavaScript / HTML / CSS** - Digunakan untuk mengatur struktur, interaktivitas, dan tampilan antarmuka (frontend).

### Framework
- **Laravel** (v12.0) - Framework backend PHP modern untuk menangani logika sistem, routing, dan interaksi database (ORM).
- **Vite** - Build tool generasi terbaru yang digunakan untuk memproses aset (CSS dan JS) agar lebih cepat dan optimal.

### Desain Sistem & Styling
- **Tailwind CSS** (v4.0.0) - Framework CSS utility-first yang digunakan sebagai dasar sistem desain. Tailwind memungkinkan pembuatan tampilan antarmuka yang modern, responsif, dan konsisten di seluruh aplikasi dengan cepat.
- **Blade Templating Engine** - Digunakan secara terintegrasi dengan Tailwind CSS untuk membangun antarmuka pengguna (UI) yang dinamis pada sisi server.

### Database
- **PostgreSQL** - Basis data relasional yang digunakan dalam sistem ini.
- **Supabase** - Layanan penyedia database *cloud* yang menaungi server PostgreSQL untuk project ini, menggunakan *connection pooler* untuk menjaga performa koneksi ke database.

### Packages & Dependencies Utama
**Backend (Composer / PHP):**
- `laravel/framework`: Inti dari framework Laravel.
- `laravel/tinker`: REPL interaktif untuk interaksi dengan aplikasi Laravel melalui terminal.
- `fakerphp/faker`: (Environment Dev) Digunakan untuk membuat *dummy data* saat melakukan proses *seeding* di database.
- `laravel/pint`: (Environment Dev) Alat bantu otomatis untuk merapikan gaya penulisan kode PHP.

**Frontend (NPM / Node.js):**
- `tailwindcss` & `@tailwindcss/vite`: Core engine Tailwind CSS dan integrasinya untuk Vite.
- `vite` & `laravel-vite-plugin`: Alat untuk *bundling* aset dan *Hot Module Replacement* (HMR) yang mempercepat proses pengembangan tampilan web.
