# Project Structure

```
├── app
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── AuthController.php
│   │   │   ├── Controller.php
│   │   │   ├── DashboardController.php
│   │   │   ├── GuruController.php
│   │   │   ├── JadwalPelajaranController.php
│   │   │   ├── KelasController.php
│   │   │   ├── MataPelajaranController.php
│   │   │   ├── NilaiRapotController.php
│   │   │   ├── PengaturanController.php
│   │   │   ├── PresensiController.php
│   │   │   ├── PresensiSholatController.php
│   │   │   └── SiswaController.php
│   │   └── Middleware
│   │       ├── IsAdmin.php
│   │       └── SecurityHeaders.php
│   ├── Models
│   │   ├── JadwalPelajaran.php
│   │   ├── Kelas.php
│   │   ├── MataPelajaran.php
│   │   ├── NilaiRapot.php
│   │   ├── Presensi.php
│   │   ├── PresensiSholat.php
│   │   ├── Siswa.php
│   │   ├── TahunAjaran.php
│   │   └── User.php
│   ├── Providers
│   │   └── AppServiceProvider.php
│   └── Services
│       └── CacheService.php
├── bootstrap
│   ├── cache
│   │   ├── events.php
│   │   ├── packages.php
│   │   └── services.php
│   ├── app.php
│   └── providers.php
├── config
│   ├── admin.php
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database
│   ├── factories
│   │   └── UserFactory.php
│   ├── migrations
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2024_01_01_000001_create_tahun_ajaran_table.php
│   │   ├── 2024_01_01_000002_add_guru_fields_to_users_table.php
│   │   ├── 2024_01_01_000003_create_mata_pelajaran_table.php
│   │   ├── 2024_01_01_000004_create_kelas_table.php
│   │   ├── 2024_01_01_000005_create_siswa_table.php
│   │   ├── 2024_01_01_000006_create_jadwal_pelajaran_table.php
│   │   ├── 2024_01_01_000007_create_presensi_table.php
│   │   ├── 2024_01_01_000008_create_nilai_rapot_table.php
│   │   ├── 2024_01_01_000009_create_presensi_sholat_table.php
│   │   ├── 2026_05_09_120420_add_nisn_tanggal_lahir_alamat_to_siswa_table.php
│   │   ├── 2026_06_18_121500_add_performance_indexes.php
│   │   ├── 2026_07_04_111843_add_gender_status_to_users_table.php
│   │   └── 2026_07_09_170334_update_nilai_rapot_add_formatif_json.php
│   ├── seeders
│   │   └── DatabaseSeeder.php
│   ├── database.sql
│   └── database.sqlite
├── docker
│   ├── nginx.conf
│   ├── start.sh
│   ├── supervisord.conf
│   └── www.conf
├── public
│   ├── images
│   │   └── logo.png
│   ├── favicon.ico
│   ├── index.php
│   ├── Lgo.png
│   └── robots.txt
├── resources
│   ├── css
│   │   └── app.css
│   ├── js
│   │   └── app.js
│   └── views
│       ├── auth
│       │   └── login.blade.php
│       ├── exports
│       │   ├── guru-pdf.blade.php
│       │   ├── jadwal-pdf.blade.php
│       │   ├── nilai-pdf.blade.php
│       │   ├── presensi-pdf.blade.php
│       │   ├── rekap-nilai-pdf.blade.php
│       │   ├── rekap-presensi-pdf.blade.php
│       │   ├── rekap-presensi-sholat-pdf.blade.php
│       │   └── siswa-pdf.blade.php
│       ├── kelas
│       │   └── index.blade.php
│       ├── layouts
│       │   └── app.blade.php
│       ├── partials
│       │   ├── analytics-body.blade.php
│       │   └── analytics.blade.php
│       ├── dashboard.blade.php
│       ├── dataguru.blade.php
│       ├── datasiswa.blade.php
│       ├── inputnilai.blade.php
│       ├── kelasmapel.blade.php
│       ├── pengaturan.blade.php
│       ├── presensi-sholat-kelas.blade.php
│       ├── presensi-sholat-siswa.blade.php
│       ├── presensikelas.blade.php
│       ├── presensiswa.blade.php
│       ├── rekap-presensi-sholat.blade.php
│       ├── rekap-presensi.blade.php
│       ├── rekapnilai.blade.php
│       └── welcome.blade.php
├── routes
│   ├── console.php
│   └── web.php
├── storage
│   ├── app
│   │   ├── private
│   │   └── public
│   ├── framework
│   │   ├── cache
│   │   │   └── data
│   │   ├── sessions
│   │   │   ├── Aj9pkbZncrrHQxOEU75uD0ZRUlx9ixmNHT6upjpo
│   │   │   ├── EhSHYQGDnBwlweINibNPOYuTC46ZvjfpLdVeeM95
│   │   │   ├── GOhImsGFMQsTpk01TOKUlEwtDHmbaYnP3ugBB0hE
│   │   │   ├── mE8dNnwXi5yxEKPEJpgzvzJonkVhovcOVfIHM6HB
│   │   │   ├── qnx9v6gaUj8iXFXdTjVgW9XCFkodc2rM9JphB7p9
│   │   │   └── zIdSDews4nUnvLfqnzXstYCXBFyqFAa1OiKxAXOG
│   │   ├── testing
│   │   └── views
│   │       ├── 00cf4cbb3afb4ade697a0ecbff6dce43.php
│   │       ├── 01c87b9101a44d12d22f32022a95af32.php
│   │       ├── 0ffd4361689d4948d058cabe476a848e.php
│   │       ├── 209047cb105826501634e6b3ba367892.php
│   │       ├── 3cb7ce123731b7aa56c15fd0a5459fbd.php
│   │       ├── 43513e5273546bd9e6da163f52c3d6f1.php
│   │       ├── 4367a6ca398e72443d2f63950d65b387.php
│   │       ├── 46e9506688761ba893bd998f2fa7c28d.php
│   │       ├── 47aeea572f8c023a0a23c4a32c347e15.php
│   │       ├── 48529d4ed831a4749b738f7df64445f9.php
│   │       ├── 4b87a106b24dde57a28bfde95396c962.php
│   │       ├── 4ef6cd75b54621659a456b80b48b2617.php
│   │       ├── 5266e7c6fc025429493bbf0cefd2772d.php
│   │       ├── 5e85ad0be4aa8e08b9ae25334eb0f691.php
│   │       ├── 643c65eda00e7732906dc31e143e3535.php
│   │       ├── 6977121a2ae2fafdb518744858f62ed6.php
│   │       ├── 7176fa1518895ee007c49a38cfdf7865.php
│   │       ├── 72609ec7a0b6b75fa4db56add0599cb5.php
│   │       ├── 76e11cd41b733ea489be6d320c9d8f21.php
│   │       ├── 7fb692604fe3def5c4b627aaf5451740.php
│   │       ├── 822f1d1654d88efc3709076834c69acc.php
│   │       ├── 852f2ed1ae277f3e213c416024e30bf7.php
│   │       ├── 9206890c8c0533e89c0dbb1be2c57e09.php
│   │       ├── 93ac566b60f89264057632af3a0e44ec.php
│   │       ├── 97d3aeb708c2429b034c55540323bf76.php
│   │       ├── 9d8964d0f1a30c3da3c85ff8352822fb.php
│   │       ├── a44b90810e51bb386b769b925096fa38.php
│   │       ├── ab6bfcd89eb5ca2fcb499cc5833335e7.php
│   │       ├── af53870663e698840bbf487261bf8945.php
│   │       ├── cdd85ab942fec56a74f2abdab636ac2c.php
│   │       ├── d3a8f7a2fe5053c50997a77876188b6a.php
│   │       ├── f2d9d82632c2a5e0a684083f8d55ae45.php
│   │       ├── f58c917d3f38ceb650a9521aae7d51a3.php
│   │       └── feb5aef3189538117900c3f396326616.php
│   └── logs
├── tests
│   ├── Feature
│   │   └── ExampleTest.php
│   ├── Unit
│   │   └── ExampleTest.php
│   └── TestCase.php
├── artisan
├── CLAUDE.md
├── composer.json
├── composer.lock
├── Dockerfile
├── Lgo.png
├── loadtest.py
├── package-lock.json
├── package.json
├── performance_analysis.md
├── phpunit.xml
├── README.md
├── Screenshot From 2026-07-22 23-12-22.png
├── update_pdfs.py
└── vite.config.js
```
