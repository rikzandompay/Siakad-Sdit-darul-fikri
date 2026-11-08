import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api_client.dart';

enum Peran { guru, wali }

/// Status login: token disimpan di penyimpanan aman perangkat (Keychain / Keystore).
class Session extends ChangeNotifier {
  Session(this.api) {
    api.onUnauthorized = () => _clear();
  }

  final ApiClient api;
  static const _storage = FlutterSecureStorage();

  bool ready = false;
  String? _token;
  Peran? peran;
  Map<String, dynamic> akun = {};

  bool get loggedIn => _token != null;
  bool get isAdmin => peran == Peran.guru && akun['is_admin'] == true;

  /// Wali: anak yang sedang dipantau (bila punya lebih dari satu).
  int? _anakId;
  List<Map<String, dynamic>> get daftarAnak => ((akun['anak'] as List?) ?? []).map((e) => Map<String, dynamic>.from(e as Map)).toList();
  Map<String, dynamic>? get anak {
    final semua = daftarAnak;
    if (semua.isEmpty) return null;
    return semua.firstWhere((a) => a['id'] == _anakId, orElse: () => semua.first);
  }

  void pilihAnak(int id) {
    _anakId = id;
    notifyListeners();
  }

  /// Satu-satunya tempat menyimpan alamat server pilihan pengguna (kunci `api_url`, dibaca main.dart).
  Future<void> setCustomBaseUrl(String url) async {
    final clean = ApiClient.normalisasi(url);
    if (clean.isEmpty) return;
    api.baseUrl = clean;
    await _storage.write(key: 'api_url', value: clean);
    await _storage.delete(key: 'custom_api_url');
    notifyListeners();
  }

  Future<void> restore() async {
    try {
      // Kunci lama dari versi sebelumnya: pindahkan ke `api_url` lalu hapus.
      final lama = await _storage.read(key: 'custom_api_url');
      if (lama != null && lama.trim().isNotEmpty) await setCustomBaseUrl(lama);
      _token = await _storage.read(key: 'token');
      final p = await _storage.read(key: 'peran');
      peran = p == null ? null : Peran.values.byName(p);
      api.token = _token;

      // Pulihkan cache akun bila tersedia
      final rawAkun = await _storage.read(key: 'akun');
      if (rawAkun != null && rawAkun.isNotEmpty) {
        try {
          akun = Map<String, dynamic>.from(jsonDecode(rawAkun) as Map);
        } catch (_) {}
      }

      if (_token != null) {
        try {
          final me = await api.get('/auth/me', batas: const Duration(seconds: 6));
          akun = Map<String, dynamic>.from(me['akun'] as Map);
          await _storage.write(key: 'akun', value: jsonEncode(akun));
        } on ApiException catch (e) {
          if (e.unauthorized) {
            await _clear(notify: false);
          } else if (akun.isEmpty) {
            // Bila server tidak terjangkau dan tidak ada cache data profil,
            // bersihkan sesi agar tidak tersangkut di layar kosong tanpa data.
            await _clear(notify: false);
          }
        }
      }
    } catch (_) {
      await _clear(notify: false);
    }
    ready = true;
    notifyListeners();
  }

  Future<void> refreshMe() async {
    if (_token != null) {
      final me = await api.get('/auth/me');
      akun = Map<String, dynamic>.from(me['akun'] as Map);
      await _storage.write(key: 'akun', value: jsonEncode(akun));
      notifyListeners();
    }
  }

  Future<void> login({required Peran peran, required String username, required String password, required bool ingat}) async {
    final res = await api.post('/auth/login', {
      'peran': peran.name,
      'username': username.trim(),
      'password': password,
      'device_name': 'MyDarfik ${defaultTargetPlatform.name}',
    });
    _token = res['token'] as String;
    this.peran = peran;
    akun = Map<String, dynamic>.from(res['akun'] as Map);
    api.token = _token;
    if (ingat) {
      await _storage.write(key: 'token', value: _token);
      await _storage.write(key: 'peran', value: peran.name);
      await _storage.write(key: 'akun', value: jsonEncode(akun));
    } else {
      await _storage.delete(key: 'token');
      await _storage.delete(key: 'peran');
      await _storage.delete(key: 'akun');
    }
    notifyListeners();
  }

  Future<void> refreshAkun() async => refreshMe();

  Future<void> logout() async {
    try {
      await api.post('/auth/logout');
    } catch (_) {
      // Tetap keluar walau server tidak terjangkau.
    }
    await _clear();
  }

  Future<void> _clear({bool notify = true}) async {
    _token = null;
    peran = null;
    akun = {};
    api.token = null;
    await _storage.delete(key: 'token');
    await _storage.delete(key: 'peran');
    await _storage.delete(key: 'akun');
    if (notify) notifyListeners();
  }
}
