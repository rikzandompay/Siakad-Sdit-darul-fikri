import 'dart:io';

import 'package:file_saver/file_saver.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:provider/provider.dart';

import '../widgets/common.dart';
import 'api_client.dart';

const _kanal = MethodChannel('mydarfik/unduh');

/// Ambil laporan dari server lalu simpan ke HP.
/// Android: folder Download publik (lewat MediaStore). Platform lain / bila gagal: folder dokumen aplikasi.
/// [jenis] = nama laporan di API (absensi, sholat, nilai, guru-presensi, data-guru, data-siswa).
/// [alamatApi] mengganti alamat bawaan `/admin/ekspor/...` (mis. `/wali/kartu`); [jenis] tetap dipakai untuk nama berkas.
/// [namaBerkas] mengganti seluruh nama berkas (tanpa timestamp), [keterangan] menggantikan "Laporan" di pesan progres.
Future<void> unduhLaporan(
  BuildContext context, {
  required String jenis,
  required String format,
  String? alamatApi,
  Map<String, Object?> query = const {},
  String? namaBerkas,
  String keterangan = 'Laporan',
}) async {
  final api = context.read<ApiClient>();
  final messenger = ScaffoldMessenger.of(context);
  messenger
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        duration: const Duration(minutes: 1),
        content: Text('Menyiapkan $keterangan ${format.toUpperCase()}...'),
      ),
    );
  try {
    final data = Uint8List.fromList(
      await api.bytes(
        alamatApi ?? '/admin/ekspor/$jenis/$format',
        query: {
          for (final e in query.entries)
            if (e.value != null) e.key: '${e.value}',
        },
      ),
    );
    final stamp = DateTime.now()
        .toIso8601String()
        .substring(0, 19)
        .replaceAll(':', '')
        .replaceAll('T', '-');
    final nama = namaBerkas != null
        ? '$namaBerkas.$format'
        : 'darfik-$jenis-$stamp.$format';
    final mime = format == 'pdf' ? 'application/pdf' : 'text/csv';

    String lokasi;
    Future<void> Function() buka;
    String? uriAndroid;
    if (Platform.isAndroid) {
      try {
        uriAndroid = await _kanal.invokeMethod<String>('simpan', {
          'nama': nama,
          'mime': mime,
          'data': data,
        });
      } on PlatformException {
        uriAndroid = null; // mis. Android 7-9 tanpa izin penyimpanan: pakai cadangan di bawah.
      }
    }
    if (uriAndroid != null) {
      lokasi = 'folder Download';
      buka = () =>
          _kanal.invokeMethod('buka', {'uri': uriAndroid, 'mime': mime});
    } else {
      final path = await FileSaver.instance.saveFile(
        name: nama.replaceAll('.$format', ''),
        bytes: data,
        fileExtension: format,
        mimeType: format == 'pdf' ? MimeType.pdf : MimeType.csv,
      );
      if (!File(path).existsSync()) {
        throw const FileSystemException('Berkas tidak tersimpan');
      }
      lokasi = 'penyimpanan aplikasi';
      buka = () => OpenFilex.open(path);
    }

    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text('Tersimpan di $lokasi: $nama'),
          action: SnackBarAction(label: 'Buka', onPressed: () => buka()),
          duration: const Duration(seconds: 8),
        ),
      );
  } on ApiException catch (e) {
    messenger.hideCurrentSnackBar();
    if (context.mounted) {
      showMessage(context, e.message, error: true);
    }
  } catch (_) {
    messenger.hideCurrentSnackBar();
    if (context.mounted) {
      showMessage(
        context,
        'Berkas tidak dapat disimpan. Periksa ruang kosong HP lalu coba lagi.',
        error: true,
      );
    }
  }
}

/// Tampilkan surat PDF tanpa menyimpannya ke folder Download.
/// Berkas hanya ditaruh di cache aplikasi (dibersihkan sistem kapan saja),
/// lalu dibuka dengan aplikasi pembaca PDF bawaan HP.
Future<void> pratinjauPdf(
  BuildContext context, {
  required String alamatApi,
  required String namaBerkas,
  String keterangan = 'Surat',
}) async {
  final api = context.read<ApiClient>();
  final messenger = ScaffoldMessenger.of(context);
  messenger
    ..hideCurrentSnackBar()
    ..showSnackBar(
      SnackBar(
        duration: const Duration(minutes: 1),
        content: Text('Menyiapkan pratinjau $keterangan...'),
      ),
    );
  try {
    final data = Uint8List.fromList(await api.bytes(alamatApi));
    final nama = namaBerkas.toLowerCase().endsWith('.pdf')
        ? namaBerkas
        : '$namaBerkas.pdf';
    final folder = await getApplicationCacheDirectory();
    final berkas = File('${folder.path}/pratinjau/$nama');
    await berkas.parent.create(recursive: true);
    await berkas.writeAsBytes(data, flush: true);

    messenger.hideCurrentSnackBar();
    final hasil = await OpenFilex.open(berkas.path, type: 'application/pdf');
    if (hasil.type != ResultType.done) {
      messenger.showSnackBar(
        const SnackBar(
          content: Text(
            'Tidak ada aplikasi pembaca PDF di HP ini. Pasang aplikasi seperti Google Drive atau Adobe Acrobat.',
          ),
        ),
      );
    }
  } on ApiException catch (e) {
    messenger.hideCurrentSnackBar();
    if (context.mounted) {
      showMessage(context, e.message, error: true);
    }
  } catch (_) {
    messenger.hideCurrentSnackBar();
    if (context.mounted) {
      showMessage(context, 'Surat tidak dapat dibuka. Coba lagi.', error: true);
    }
  }
}
