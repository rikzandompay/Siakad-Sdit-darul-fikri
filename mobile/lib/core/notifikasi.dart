import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

import 'api_client.dart';

/// Notifikasi aplikasi: push FCM (bila Firebase sudah dikonfigurasi lewat google-services.json)
/// dan jumlah notifikasi belum dibaca untuk badge lonceng.
///
/// Tanpa Firebase, push dilewati tetapi riwayat notifikasi di server tetap bisa dibuka.
class LayananNotifikasi extends ChangeNotifier {
  LayananNotifikasi(this.api);

  final ApiClient api;

  /// Jumlah notifikasi belum dibaca (badge lonceng).
  int belumDibaca = 0;

  /// Dipasang oleh aplikasi: membuka rute go_router saat notifikasi diketuk.
  void Function(String rute)? onBuka;

  static const _kanal = AndroidNotificationChannel(
    'mydarfik',
    'Notifikasi MyDarfik',
    description: 'Konseling, pengumuman, surat izin, dan tugas',
    importance: Importance.high,
  );

  final _lokal = FlutterLocalNotificationsPlugin();
  bool _firebaseSiap = false;
  bool _terdaftar = false;
  String? _token;
  String? _ruteTertunda;
  StreamSubscription<String>? _subToken;

  /// Dipanggil sekali saat aplikasi dibuka, sebelum login dipulihkan.
  Future<void> init() async {
    try {
      await Firebase.initializeApp();
      _firebaseSiap = true;
    } catch (e) {
      // google-services.json belum dipasang: aplikasi tetap jalan tanpa push.
      debugPrint('Firebase tidak aktif, notifikasi push dimatikan: $e');
      return;
    }

    await _lokal.initialize(
      settings: const InitializationSettings(
        android: AndroidInitializationSettings('@mipmap/ic_launcher'),
      ),
      onDidReceiveNotificationResponse: (r) {
        if (r.payload != null && r.payload!.isNotEmpty) _buka(r.payload!);
      },
    );
    await _lokal
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.createNotificationChannel(_kanal);

    // Aplikasi sedang terbuka: FCM tidak menampilkan notifikasi sendiri, jadi tampilkan lewat notifikasi lokal.
    FirebaseMessaging.onMessage.listen((m) {
      final n = m.notification;
      if (n != null) {
        _lokal.show(
          id: m.hashCode,
          title: n.title,
          body: n.body,
          payload: m.data['rute'] as String?,
          notificationDetails: NotificationDetails(
            android: AndroidNotificationDetails(
              _kanal.id,
              _kanal.name,
              channelDescription: _kanal.description,
              importance: Importance.high,
              priority: Priority.high,
              tag: m.notification?.android?.tag,
            ),
          ),
        );
      }
      muatJumlah();
    });

    // Notifikasi diketuk saat aplikasi di latar belakang / tertutup.
    FirebaseMessaging.onMessageOpenedApp.listen(
      (m) => _buka(m.data['rute'] as String?),
    );
    final awal = await FirebaseMessaging.instance.getInitialMessage();
    if (awal != null) _ruteTertunda = awal.data['rute'] as String?;
  }

  /// Setelah login / sesi dipulihkan: kirim token perangkat ke server dan muat jumlah belum dibaca.
  Future<void> daftarkan() async {
    unawaited(muatJumlah());
    if (!_firebaseSiap || _terdaftar) return;
    _terdaftar = true;
    try {
      await FirebaseMessaging.instance.requestPermission();
      // Server mengirim lewat FCM HTTP v1 dengan target registration token, jadi tetap memakai
      // getToken/onTokenRefresh (register() menghasilkan Installation ID untuk API lain).
      // ignore: deprecated_member_use
      _token = await FirebaseMessaging.instance.getToken();
      if (_token != null)
        await api.post('/perangkat', {
          'token': _token,
          'platform': defaultTargetPlatform == TargetPlatform.iOS
              ? 'ios'
              : 'android',
        });
      // ignore: deprecated_member_use
      _subToken ??= FirebaseMessaging.instance.onTokenRefresh.listen((t) async {
        _token = t;
        if (_terdaftar) {
          try {
            await api.post('/perangkat', {'token': t});
          } catch (_) {}
        }
      });
    } catch (e) {
      _terdaftar = false;
      debugPrint('Gagal mendaftarkan perangkat untuk notifikasi: $e');
    }
  }

  /// Sebelum logout: lepaskan token agar HP ini tidak lagi menerima notifikasi akun tersebut.
  Future<void> lepas() async {
    if (_terdaftar && _token != null) {
      try {
        await api.delete('/perangkat', {'token': _token});
      } catch (_) {}
    }
    reset();
  }

  /// Sesi berakhir (logout / token ditolak server). Di server, perangkat ikut terhapus bersama token login.
  void reset() {
    _terdaftar = false;
    belumDibaca = 0;
    notifyListeners();
  }

  Future<void> muatJumlah() async {
    try {
      final res = await api.get('/notifikasi');
      setJumlah((res['belum_dibaca'] as num?)?.toInt() ?? 0);
    } catch (_) {}
  }

  void setJumlah(int n) {
    if (n == belumDibaca) return;
    belumDibaca = n;
    notifyListeners();
  }

  /// Rute dari notifikasi yang membuka aplikasi saat tertutup; diambil splash setelah login dipulihkan.
  String? ambilRuteTertunda() {
    final r = _ruteTertunda;
    _ruteTertunda = null;
    return r;
  }

  void _buka(String? rute) {
    if (rute == null || rute.isEmpty) return;
    if (onBuka == null) {
      _ruteTertunda = rute;
      return;
    }
    onBuka!(rute);
  }
}
