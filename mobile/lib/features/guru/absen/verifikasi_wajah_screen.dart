import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../widgets/common.dart';
import '../../face/face_capture.dart';
import '../../face/face_service.dart';

/// Langkah 2: liveness + cocokkan wajah dengan template, lalu kirim absen ke server.
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
  List<double>? _template;
  String? _error;

  @override
  void initState() {
    super.initState();
    context.read<ApiClient>().get('/guru/wajah').then((res) {
      final t = res['template'] as List?;
      if (t == null) {
        _keHasil({'jenis': 'error', 'pesan': 'Wajah belum terdaftar. Daftarkan wajah terlebih dahulu.'});
      } else {
        setState(() => _template = t.map((e) => (e as num).toDouble()).toList());
      }
    }).catchError((e) {
      if (mounted) setState(() => _error = e.toString());
    });
  }

  void _keHasil(Map<String, dynamic> hasil) {
    if (mounted) context.pushReplacement('/guru/absen/hasil', extra: {...hasil, 'aksi': widget.args['aksi']});
  }

  Future<void> _selesai(List<String> foto) async {
    final api = context.read<ApiClient>();
    try {
      final emb = await FaceService.instance.embeddingDariFoto(foto.first);
      final skor = FaceService.skor(emb, _template!);
      _percobaan++;
      final res = await api.post('/guru/absen/${widget.args['aksi']}', {
        'latitude': widget.args['latitude'],
        'longitude': widget.args['longitude'],
        'akurasi': widget.args['akurasi'],
        'skor_wajah': skor,
        'liveness': true,
      });
      _keHasil({'jenis': 'berhasil', 'hari_ini': res['hari_ini'], 'skor_wajah': skor, 'jarak_meter': widget.args['jarak_meter']});
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
    return AppPage(
      title: 'Verifikasi Wajah',
      subtitle: 'Langkah 2 dari 2',
      bottom: SecondaryButton(label: 'Batalkan', onPressed: () => context.pop()),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        if (_error != null)
          Padding(padding: const EdgeInsets.only(bottom: 12), child: Text(_error!, textAlign: TextAlign.center)),
        if (_template == null)
          const Padding(padding: EdgeInsets.only(top: 80), child: Center(child: CircularProgressIndicator()))
        else
          FaceCapture(key: _captureKey, onSelesai: _selesai, onGagal: _ulang),
      ]),
    );
  }
}
