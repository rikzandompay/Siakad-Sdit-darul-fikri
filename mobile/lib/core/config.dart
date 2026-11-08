/// Konfigurasi build. Alamat API bisa diganti saat build:
///   flutter run --dart-define=API_URL=https://siakad.sditdarulfikri.sch.id/api/v1
class AppConfig {
  static const apiUrl = String.fromEnvironment(
    'API_URL',
    // Server produksi SIAKAD. Untuk uji lokal: --dart-define=API_URL=http://IP-LAPTOP:8000/api/v1
    defaultValue: 'https://siakad-darul-fikri.web.id/api/v1',
  );

  /// Alamat web SIAKAD (untuk ekspor CSV/PDF yang dibuka di browser).
  static String get webUrl => apiUrl.replaceFirst(RegExp(r'/api/v1/?$'), '');

  static const namaAplikasi = 'MyDarfik';
  static const namaSekolah = 'SDIT Darul Fikri';
  static const versi = '1.0.0';
}
