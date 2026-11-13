import 'dart:convert';

import 'package:dio/dio.dart';

import 'config.dart';

/// Kesalahan dari API dalam bentuk yang siap ditampilkan.
class ApiException implements Exception {
  ApiException(this.message, {this.status, this.kode, this.data = const {}});

  final String message;
  final int? status;

  /// Kode khusus dari server, mis. `di_luar_area`, `wajah_tidak_cocok`, `sudah_absen`.
  final String? kode;
  final Map<String, dynamic> data;

  bool get unauthorized => status == 401;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient({String? baseUrl})
    : _dio = Dio(
        BaseOptions(
          baseUrl: baseUrl ?? AppConfig.apiUrl,
          connectTimeout: const Duration(seconds: 45),
          receiveTimeout: const Duration(seconds: 45),
          sendTimeout: const Duration(seconds: 60),
          headers: {'Accept': 'application/json'},
        ),
      );

  final Dio _dio;
  String? _token;

  String get baseUrl => _dio.options.baseUrl;
  set baseUrl(String v) => _dio.options.baseUrl = v;

  /// Rapikan input pengguna: tambah http:// bila kosong, buang garis miring akhir, tambah /api/v1 bila belum ada.
  static String normalisasi(String input) {
    var u = input.trim();
    if (u.isEmpty) return u;
    if (!u.startsWith('http://') && !u.startsWith('https://')) u = 'http://$u';
    u = u.replaceAll(RegExp(r'/+$'), '');
    if (!u.endsWith('/api/v1')) u = '$u/api/v1';
    return u;
  }

  /// Dipanggil saat server menolak token (kedaluwarsa / dicabut).
  void Function()? onUnauthorized;

  set token(String? value) => _token = value;

  /// [batas] memperpendek waktu tunggu (mis. saat aplikasi dibuka) agar layar tidak menggantung.
  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, dynamic>? query,
    Duration? batas,
  }) {
    final f = _send(
      () => _dio.get(path, queryParameters: query, options: _opts()),
    );
    if (batas == null) return f;
    return f.timeout(
      batas,
      onTimeout: () => throw ApiException(
        'Server tidak merespons. Periksa koneksi internet Anda lalu coba lagi.',
      ),
    );
  }

  Future<Map<String, dynamic>> post(
    String path, [
    Map<String, dynamic>? body,
  ]) => _send(() => _dio.post(path, data: body ?? {}, options: _opts()));

  Future<Map<String, dynamic>> put(String path, [Map<String, dynamic>? body]) =>
      _send(() => _dio.put(path, data: body ?? {}, options: _opts()));

  Future<Map<String, dynamic>> delete(
    String path, [
    Map<String, dynamic>? body,
  ]) => _send(() => _dio.delete(path, data: body, options: _opts()));

  /// Kirim form multipart (mis. foto surat izin). [file] = path lokal, dikirim sebagai [fileField].
  Future<Map<String, dynamic>> upload(
    String path,
    Map<String, dynamic> fields, {
    String? file,
    String fileField = 'lampiran',
  }) async {
    final form = FormData.fromMap({
      ...fields,
      if (file != null) fileField: await MultipartFile.fromFile(file),
    });
    return _send(() => _dio.post(path, data: form, options: _opts()));
  }

  /// Unduh berkas (CSV/PDF) dari server sebagai byte, lengkap dengan token login.
  Future<List<int>> bytes(String path, {Map<String, dynamic>? query}) async {
    try {
      final res = await _dio.get<List<int>>(
        path,
        queryParameters: query,
        options: _opts().copyWith(
          responseType: ResponseType.bytes,
          headers: {'Accept': '*/*', ...authHeaders},
          receiveTimeout: const Duration(minutes: 3),
        ),
      );
      return res.data ?? const [];
    } on DioException catch (e) {
      // Galat dari server datang sebagai byte JSON; ubah dulu agar pesannya terbaca.
      final r = e.response;
      if (r != null && r.data is List<int>) {
        try {
          final json = jsonDecode(utf8.decode(r.data as List<int>));
          if (json is Map<String, dynamic>) {
            throw ApiException(
              json['message'] as String? ?? 'Gagal mengunduh berkas.',
              status: r.statusCode,
            );
          }
        } on FormatException {
          // abaikan, pakai pesan umum di bawah
        }
      }
      throw _toApiException(e);
    }
  }

  /// URL lengkap + header untuk memuat gambar terlindungi (mis. lampiran izin).
  String url(String path) => '${_dio.options.baseUrl}$path';
  Map<String, String> get authHeaders => {
    if (_token != null) 'Authorization': 'Bearer $_token',
  };

  Options _opts() =>
      Options(headers: {if (_token != null) 'Authorization': 'Bearer $_token'});

  Future<Map<String, dynamic>> _send(Future<Response> Function() call) async {
    try {
      final res = await call();
      final data = res.data;
      return data is Map<String, dynamic> ? data : {'data': data};
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  ApiException _toApiException(DioException e) {
    final res = e.response;
    if (res == null) {
      final msg = switch (e.type) {
        DioExceptionType.connectionTimeout ||
        DioExceptionType.receiveTimeout ||
        DioExceptionType.sendTimeout => 'Server tidak merespons. Periksa koneksi internet Anda lalu coba lagi.',
        _ => 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda lalu coba lagi.',
      };
      return ApiException(msg);
    }

    final body = res.data is Map<String, dynamic>
        ? res.data as Map<String, dynamic>
        : <String, dynamic>{};
    var message =
        body['message'] as String? ?? 'Terjadi kesalahan (${res.statusCode}).';
    // Ambil pesan validasi pertama agar lebih spesifik.
    final errors = body['errors'];
    if (errors is Map && errors.isNotEmpty) {
      final first = errors.values.first;
      if (first is List && first.isNotEmpty) message = first.first.toString();
    }
    if (res.statusCode == 401) {
      message = 'Sesi berakhir. Silakan masuk kembali.';
      onUnauthorized?.call();
    }
    return ApiException(
      message,
      status: res.statusCode,
      kode: body['kode'] as String?,
      data: body,
    );
  }
}
