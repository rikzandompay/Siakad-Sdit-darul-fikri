import 'dart:io';

import 'package:file_saver/file_saver.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:open_filex/open_filex.dart';
import 'package:provider/provider.dart';

import '../widgets/common.dart';
import 'api_client.dart';

const _kanal = MethodChannel('mydarfik/unduh');

/// Ambil laporan dari server lalu simpan ke HP.
/// Android: folder Download publik (lewat MediaStore). Platform lain / bila gagal: folder dokumen aplikasi.
/// [jenis] = nama laporan di API (absensi, sholat, nilai, guru-presensi, data-guru, data-siswa).
Future<void> unduhLaporan(BuildContext context, {required String jenis, required String format, Map<String, Object?> query = const {}}) async {
  final api = context.read<ApiClient>();
  final messenger = ScaffoldMessenger.of(context);
  messenger
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(duration: const Duration(minutes: 1), content: Text('Menyiapkan ${format.toUpperCase()}...')));
  try {
    final data = Uint8List.fromList(await api.bytes('/admin/ekspor/$jenis/$format', query: {for (final e in query.entries) if (e.value != null) e.key: '${e.value}'}));
    final stamp = DateTime.now().toIso8601String().substring(0, 19).replaceAll(':', '').replaceAll('T', '-');
    final nama = 'darfik-$jenis-$stamp.$format';
    final mime = format == 'pdf' ? 'application/pdf' : 'text/csv';

    String lokasi;
    Future<void> Function() buka;
    String? uriAndroid;
    if (Platform.isAndroid) {
      try {
        uriAndroid = await _kanal.invokeMethod<String>('simpan', {'nama': nama, 'mime': mime, 'data': data});
      } on PlatformException {
        uriAndroid = null; // mis. Android 7-9 tanpa izin penyimpanan: pakai cadangan di bawah.
      }
    }
    if (uriAndroid != null) {
      lokasi = 'folder Download';
      buka = () => _kanal.invokeMethod('buka', {'uri': uriAndroid, 'mime': mime});
    } else {
      final path = await FileSaver.instance.saveFile(name: nama.replaceAll('.$format', ''), bytes: data, fileExtension: format, mimeType: format == 'pdf' ? MimeType.pdf : MimeType.csv);
      if (!File(path).existsSync()) throw const FileSystemException('Berkas tidak tersimpan');
      lokasi = 'penyimpanan aplikasi';
      buka = () => OpenFilex.open(path);
    }

    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text('Tersimpan di $lokasi: $nama'),
        action: SnackBarAction(label: 'Buka', onPressed: () => buka()),
        duration: const Duration(seconds: 8),
      ));
  } on ApiException catch (e) {
    messenger.hideCurrentSnackBar();
    if (context.mounted) showMessage(context, e.message, error: true);
  } catch (_) {
    messenger.hideCurrentSnackBar();
    if (context.mounted) showMessage(context, 'Berkas tidak dapat disimpan. Periksa ruang kosong HP lalu coba lagi.', error: true);
  }
}
