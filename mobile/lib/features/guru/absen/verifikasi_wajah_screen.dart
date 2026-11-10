import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/session.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';
import '../../face/face_capture.dart';
import '../../face/face_service.dart';

/// Langkah 2: liveness + ambil embedding wajah, lalu kirim ke server (server yang mencocokkan).
class VerifikasiWajahScreen extends StatefulWidget {
  const VerifikasiWajahScreen({super.key, required this.args});
  final Map<String, dynamic> args;

  @override
  State<VerifikasiWajahScreen> createState() => _VerifikasiWajahScreenState();
}

class _VerifikasiWajahScreenState extends State<VerifikasiWajahScreen> {
  static const _maksPercobaan = 3;
  int _percobaan = 0;
  Key _captureKey = UniqueKey();
  String? _error;

  void _keHasil(Map<String, dynamic> hasil) {
    if (mounted) context.pushReplacement('/guru/absen/hasil', extra: {...hasil, 'aksi': widget.args['aksi']});
  }

  Future<void> _selesai(List<String> foto) async {
    final api = context.read<ApiClient>();
    final fotoUrl = context.read<Session>().akun['foto_url'] as String?;
    try {
      final emb = await FaceService.instance.embeddingDariFoto(foto.first);
      _percobaan++;

      List<double>? fotoEmb;
      if (fotoUrl != null) {
        try {
          fotoEmb = await FaceService.instance.embeddingDariFotoProfil(await api.bytes(fotoUrl));
        } catch (_) {}
      }

      final res = await api.post('/guru/absen/${widget.args['aksi']}', {
        'latitude': widget.args['latitude'],
        'longitude': widget.args['longitude'],
        'akurasi': widget.args['akurasi'],
        'mock': widget.args['mock'] ?? false,
        'embedding': emb,
        'foto_embedding': ?fotoEmb,
        'liveness': true,
      });
      _keHasil({'jenis': 'berhasil', 'hari_ini': res['hari_ini'], 'skor_wajah': res['skor_wajah'], 'jarak_meter': widget.args['jarak_meter']});
    } on FaceException catch (e) {
      _ulang(e.message);
    } on ApiException catch (e) {
      if (e.kode == 'wajah_tidak_cocok') {
        if (_percobaan >= _maksPercobaan) {
          _keHasil({'jenis': 'wajah_tidak_cocok', 'skor_wajah': e.data['skor_wajah'], 'min_skor_wajah': e.data['min_skor_wajah'], 'percobaan': _percobaan, 'habis': true});
        } else {
          _keHasil({'jenis': 'wajah_tidak_cocok', 'skor_wajah': e.data['skor_wajah'], 'min_skor_wajah': e.data['min_skor_wajah'], 'percobaan': _percobaan, 'args': widget.args});
        }
      } else {
        _keHasil({'jenis': e.kode ?? 'error', 'pesan': e.message, 'data': e.data});
      }
    }
  }

  void _ulang(String pesan) => setState(() {
        _error = pesan;
        _captureKey = UniqueKey();
      });

  @override
  Widget build(BuildContext context) {
    final didalam = widget.args['di_dalam_area'] != false;
    final namaSekolah = widget.args['lokasi_nama'] as String? ?? 'SDIT Darul Fikri, Sidoarjo';
    final pesanLokasi = widget.args['lokasi_pesan'] as String? ?? (didalam ? null : 'Lokasi tidak sesuai (Di luar jangkauan)');
    final lokasi = LokasiStatus(
      sukses: didalam,
      nama: namaSekolah,
      pesan: pesanLokasi,
      persen: 100,
    );

    return AppPage(
      title: 'Verifikasi Wajah',
      subtitle: 'Langkah 2 dari 2',
      bottom: SecondaryButton(label: 'Batalkan', color: AppColors.inkMuted, onPressed: () => context.pop()),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        if (_error != null)
          Padding(padding: const EdgeInsets.only(bottom: 12), child: Text(_error!, textAlign: TextAlign.center)),
        FaceCapture(key: _captureKey, onSelesai: _selesai, onGagal: _ulang, lokasi: lokasi),
      ]),
    );
  }
}
