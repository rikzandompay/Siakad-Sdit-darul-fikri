import 'dart:io';

import 'package:device_info_plus/device_info_plus.dart';

/// Identitas perangkat untuk absen.
///
/// Server hanya mengizinkan satu perangkat per guru. ID diambil dari sistem
/// (ANDROID_ID / identifierForVendor), bukan dibuat acak, supaya tidak berubah
/// setiap kali aplikasi dipasang ulang dan tidak bisa dipalsukan dengan mudah.
class Perangkat {
  const Perangkat({
    required this.id,
    required this.nama,
    required this.platform,
    required this.versiOs,
  });

  final String id;
  final String nama;
  final String platform;
  final String versiOs;

  static Perangkat? _cache;

  /// Ambil identitas perangkat (di-cache selama proses aplikasi hidup).
  static Future<Perangkat> ambil() async {
    if (_cache != null) return _cache!;

    final info = DeviceInfoPlugin();

    if (Platform.isAndroid) {
      final a = await info.androidInfo;
      // ANDROID_ID unik per aplikasi + perangkat, bertahan setelah reinstall.
      return _cache = Perangkat(
        id: a.id,
        nama: '${a.manufacturer} ${a.model}'.trim(),
        platform: 'android',
        versiOs: 'Android ${a.version.release}',
      );
    }

    if (Platform.isIOS) {
      final i = await info.iosInfo;
      return _cache = Perangkat(
        id: i.identifierForVendor ?? 'ios-tanpa-id',
        nama: i.utsname.machine,
        platform: 'ios',
        versiOs: 'iOS ${i.systemVersion}',
      );
    }

    // Platform lain (mis. desktop saat pengembangan): pakai gabungan nama mesin.
    final d = await info.deviceInfo;
    final data = d.data;
    final id =
        (data['identifierForVendor'] ??
                data['id'] ??
                data['machine'] ??
                'perangkat-tidak-dikenal')
            .toString();
    return _cache = Perangkat(
      id: id,
      nama: (data['model'] ?? 'Perangkat').toString(),
      platform: Platform.operatingSystem,
      versiOs: (data['version'] ?? '').toString(),
    );
  }

  /// Payload tambahan untuk request absen.
  Future<Map<String, dynamic>> kePayload() async {
    final p = await Perangkat.ambil();
    return {
      'perangkat_id': p.id,
      'perangkat_nama': p.nama,
      'perangkat_platform': p.platform,
      'perangkat_versi_os': p.versiOs,
    };
  }
}
