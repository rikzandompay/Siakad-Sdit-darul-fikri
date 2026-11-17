import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/format.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';

/// Pengajuan izin siswa + riwayat.
class IzinWaliScreen extends StatefulWidget {
  const IzinWaliScreen({super.key});

  @override
  State<IzinWaliScreen> createState() => _IzinWaliScreenState();
}

class _IzinWaliScreenState extends State<IzinWaliScreen> {
  String _jenis = 'Sakit';
  DateTime? _mulai;
  DateTime? _selesai;
  final _alasan = TextEditingController();
  XFile? _foto;
  bool _kirim = false;
  Key _riwayat = UniqueKey();

  @override
  void dispose() {
    _alasan.dispose();
    super.dispose();
  }

  String _ymd(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<DateTime?> _pilihTanggal(DateTime? awal) => showDatePicker(
    context: context,
    initialDate: awal ?? DateTime.now(),
    firstDate: DateTime.now().subtract(const Duration(days: 7)),
    lastDate: DateTime.now().add(const Duration(days: 30)),
  );

  Future<void> _ambilFoto() async {
    final sumber = await showModalBottomSheet<ImageSource>(
      context: context,
      showDragHandle: true,
      backgroundColor: AppColors.surface,
      builder: (c) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: const Text('Ambil foto'),
              onTap: () => Navigator.pop(c, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: const Text('Pilih dari galeri'),
              onTap: () => Navigator.pop(c, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (sumber == null) return;
    final f = await ImagePicker().pickImage(
      source: sumber,
      maxWidth: 1600,
      imageQuality: 75,
    );
    if (f != null) setState(() => _foto = f);
  }

  Future<void> _ajukan() async {
    if (_mulai == null || _alasan.text.trim().isEmpty) {
      showMessage(context, 'Isi tanggal mulai dan alasan.', error: true);
      return;
    }
    final anak = context.read<Session>().anak!;
    setState(() => _kirim = true);
    try {
      final res = await context.read<ApiClient>().upload('/wali/izin', {
        'siswa_id': anak['id'],
        'jenis': _jenis,
        'tanggal_mulai': _ymd(_mulai!),
        'tanggal_selesai': _ymd(_selesai ?? _mulai!),
        'alasan': _alasan.text.trim(),
      }, file: _foto?.path);
      if (!mounted) return;
      await context.push(
        '/wali/izin/terkirim',
        extra: {'jenis': _jenis, 'anak': res['anak'], 'kelas': res['kelas']},
      );
      setState(() {
        _mulai = _selesai = null;
        _foto = null;
        _alasan.clear();
        _riwayat = UniqueKey();
      });
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _kirim = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final anak = context.watch<Session>().anak!;
    final api = context.read<ApiClient>();
    Widget tanggal(String label, DateTime? v, ValueChanged<DateTime> set) =>
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: AppText.overline.copyWith(fontSize: 10)),
              const SizedBox(height: 6),
              OutlinedButton.icon(
                onPressed: () async {
                  final t = await _pilihTanggal(v);
                  if (t != null) setState(() => set(t));
                },
                icon: const Icon(Icons.event_outlined, size: 18),
                label: Text(
                  v == null ? 'Pilih tanggal' : Fmt.tanggalPendek(v),
                  overflow: TextOverflow.ellipsis,
                ),
                style: OutlinedButton.styleFrom(
                  minimumSize: const Size.fromHeight(48),
                  alignment: Alignment.centerLeft,
                  backgroundColor: AppColors.field,
                  side: const BorderSide(color: AppColors.line),
                ),
              ),
            ],
          ),
        );
    return AppPage(
      title: 'Pengajuan Izin Siswa',
      subtitle: 'Kirim Surat Sakit / Izin Mandiri',
      bottom: PrimaryButton(
        label: 'Kirim Surat Izin ke Wali Kelas',
        loading: _kirim,
        onPressed: _ajukan,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text('JENIS IZIN', style: AppText.overline.copyWith(fontSize: 10)),
          const SizedBox(height: 6),
          PilihanChips<String>(
            pilihan: const {'Sakit': 'Sakit', 'Izin': 'Izin Keperluan'},
            nilai: _jenis,
            onChanged: (v) => setState(() => _jenis = v),
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              tanggal('MULAI TANGGAL', _mulai, (t) {
                _mulai = t;
                if (_selesai != null && _selesai!.isBefore(t)) _selesai = t;
              }),
              const SizedBox(width: 10),
              tanggal(
                'SAMPAI TANGGAL',
                _selesai,
                (t) => _selesai = (_mulai != null && t.isBefore(_mulai!))
                    ? _mulai
                    : t,
              ),
            ],
          ),
          const SizedBox(height: 14),
          Text(
            'ALASAN / KETERANGAN',
            style: AppText.overline.copyWith(fontSize: 10),
          ),
          const SizedBox(height: 6),
          TextField(
            controller: _alasan,
            minLines: 3,
            maxLines: 5,
            maxLength: 1000,
            decoration: InputDecoration(
              hintText: _jenis == 'Sakit'
                  ? 'Contoh: Ananda demam sejak tadi malam dan disarankan istirahat.'
                  : 'Contoh: Acara keluarga di luar kota.',
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'UNGGAH SURAT DOKTER / BUKTI (FOTO)',
            style: AppText.overline.copyWith(fontSize: 10),
          ),
          const SizedBox(height: 6),
          AppCard(
            onTap: _ambilFoto,
            borderColor: AppColors.brand200,
            child: Column(
              children: [
                Icon(
                  _foto == null
                      ? Icons.photo_camera_outlined
                      : Icons.check_circle,
                  color: _foto == null ? AppColors.brand700 : AppColors.ok600,
                  size: 32,
                ),
                const SizedBox(height: 6),
                Text(
                  _foto == null
                      ? 'Ketuk untuk Ambil Foto Surat'
                      : 'Foto terlampir · ketuk untuk ganti',
                  style: AppText.label.copyWith(color: AppColors.brand700),
                ),
                Text('Opsional · maksimal 2 MB', style: AppText.caption),
              ],
            ),
          ),
          const SizedBox(height: 20),
          Text('Riwayat Izin ${anak['nama']}', style: AppText.heading),
          const SizedBox(height: 10),
          FutureBuilder<Map<String, dynamic>>(
            key: _riwayat,
            future: api.get('/wali/izin', query: {'siswa_id': anak['id']}),
            builder: (context, s) {
              if (!s.hasData)
                return const Padding(
                  padding: EdgeInsets.all(16),
                  child: Center(child: CircularProgressIndicator()),
                );
              final list = (s.data!['data'] as List).cast<Map>();
              if (list.isEmpty)
                return Text(
                  'Belum ada pengajuan izin.',
                  style: AppText.bodySmall,
                );
              return Column(
                children: [
                  for (final i in list)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: AppCard(
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    '${i['jenis']} · ${Fmt.tanggalPendek(Fmt.parse(i['tanggal_mulai']))}${i['tanggal_mulai'] == i['tanggal_selesai'] ? '' : ' – ${Fmt.tanggalPendek(Fmt.parse(i['tanggal_selesai']))}'}',
                                    style: AppText.label,
                                  ),
                                  Text(
                                    i['catatan_peninjau'] ?? i['alasan'],
                                    style: AppText.caption,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ],
                              ),
                            ),
                            StatusChip(
                              i['status'],
                              tone: i['status'] == 'Disetujui'
                                  ? ChipTone.ok
                                  : (i['status'] == 'Ditolak'
                                        ? ChipTone.alpa
                                        : ChipTone.gold),
                            ),
                          ],
                        ),
                      ),
                    ),
                ],
              );
            },
          ),
        ],
      ),
    );
  }
}

class IzinTerkirimScreen extends StatelessWidget {
  const IzinTerkirimScreen({super.key, required this.data});
  final Map<String, dynamic> data;

  @override
  Widget build(BuildContext context) {
    return AppPage(
      bottom: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          PrimaryButton(
            label: 'Kembali ke Beranda',
            onPressed: () => context.go('/wali'),
          ),
          const SizedBox(height: 10),
          SecondaryButton(
            label: 'Ajukan Izin Lagi',
            onPressed: () => context.pop(),
          ),
        ],
      ),
      child: Column(
        children: [
          ResultHero(
            icon: Icons.check_rounded,
            title: 'Izin Terkirim',
            subtitle: '${data['anak']} (${data['kelas'] ?? '-'})',
          ),
          InfoTable([
            ('Jenis', Text(data['jenis'], style: AppText.label)),
            (
              'Status',
              Text(
                'Menunggu konfirmasi',
                style: AppText.label.copyWith(color: AppColors.ok700),
              ),
            ),
            (
              'Tujuan',
              Text('Wali ${data['kelas'] ?? 'kelas'}', style: AppText.label),
            ),
          ]),
        ],
      ),
    );
  }
}
