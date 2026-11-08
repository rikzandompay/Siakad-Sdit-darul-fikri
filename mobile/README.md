# MyDarfik (aplikasi mobile SIAKAD SDIT Darul Fikri)

Aplikasi Flutter (Android & iOS) yang memakai API Laravel di repo ini (`/api/v1`).
Desain mengikuti Figma "Mobile-app-sisforsinta".

## Tahap 1 (sudah ada)

- Splash, login Guru & Staf (username/NIP + kata sandi) dan Wali Murid (NIS + nama wali)
- Guru: beranda (kartu guru, kehadiran hari ini, menu, jadwal), profil + ubah kata sandi
- Presensi kelas: pilih kelas → pilih mata pelajaran → H/I/S/A → tersimpan
- Presensi sholat: pilih kelas → Dzuhur/Ashar → scan QR Kartu Pelajar (langsung Hadir) / koreksi manual
- Absen guru: daftar wajah (sekali) → verifikasi lokasi (GPS) → verifikasi wajah (kedip + cocokkan) → hasil; rekap absen per bulan
- Wali: beranda sementara (kartu pelajar). Fitur wali lengkap di tahap 2.

## Menjalankan

```bash
cd mobile
flutter pub get

# Emulator Android + server Laravel lokal (php artisan serve):
flutter run --dart-define=API_URL=http://10.0.2.2:8000/api/v1

# HP asli di jaringan yang sama dengan laptop (ganti IP):
flutter run --dart-define=API_URL=http://192.168.1.10:8000/api/v1

# Rilis (wajib HTTPS):
flutter build apk --release --dart-define=API_URL=https://DOMAIN-SIAKAD/api/v1
```

Build debug Android mengizinkan HTTP (untuk server lokal); build rilis hanya HTTPS.

iOS: butuh macOS + Xcode. Target minimum iOS 15.5 (syarat ML Kit), sudah diatur di `ios/Podfile`.

## Notifikasi (konseling, pengumuman, surat izin, tugas)

Riwayat notifikasi (ikon lonceng di beranda) langsung jalan. Notifikasi **push** di bilah
notifikasi HP butuh proyek Firebase (gratis):

1. Buka https://console.firebase.google.com → **Add project** → nama mis. `MyDarfik`.
2. **Add app → Android**, package name `id.sch.darulfikri.mydarfik` → unduh
   `google-services.json` → taruh di `mobile/android/app/google-services.json` (tidak masuk git),
   lalu build ulang aplikasi.
3. **Project settings → Service accounts → Generate new private key** → simpan file JSON-nya
   di luar repo. Di server isi `FIREBASE_CREDENTIALS`:
   - lokal: path file, mis. `FIREBASE_CREDENTIALS=/home/saya/kunci/mydarfik-firebase.json`
   - Railway: tempel seluruh isi JSON sebagai nilai variabel.
4. Jalankan antrean di server (`php artisan queue:work`; di Docker sudah otomatis lewat supervisord)
   dan scheduler (`php artisan schedule:work`) untuk pengingat tugas H-1 pukul 18.00.

Tanpa langkah 2–3 aplikasi tetap jalan; notifikasi hanya tampil di riwayat lonceng.

## Pengenalan wajah (di HP)

- `google_mlkit_face_detection`: deteksi wajah dan kedipan (liveness).
- `tflite_flutter` + `assets/models/mobilefacenet.tflite`: wajah → embedding 192 angka.
  Model dari [FaceRecognitionAuth](https://github.com/MCarlomagno/FaceRecognitionAuth) (lisensi BSD-3-Clause).
- Foto tidak dikirim ke server. Server menyimpan template (embedding) terenkripsi dan
  menerima skor + koordinat GPS, lalu memeriksa ulang jarak, skor minimal, jam, dan hari sekolah.
- Ambang kecocokan: `GURU_MIN_SKOR_WAJAH` di `.env` server (default 85).
  Konversi skor ada di `lib/features/face/face_service.dart` (`_cosNol`, `_cosPenuh`) —
  **perlu dikalibrasi dengan uji coba di HP guru** sebelum dipakai resmi.

Keterbatasan: karena pencocokan di HP, aplikasi yang dimodifikasi bisa mengirim skor palsu.
Server tetap memvalidasi lokasi dan menolak GPS palsu yang terdeteksi Android.

## Struktur

```
lib/
  core/       konfigurasi, tema (token Figma), klien API, sesi login, format tanggal
  widgets/    komponen bersama (kartu, tombol, chip status, kartu identitas)
  features/   splash, auth, guru (presensi, sholat, absen), wali, face
```
