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
///
/// Izin Sakit: tanggal mulai–selesai, alasan, dan foto surat dokter (bebas diajukan).
/// Izin Keperluan: formulir surat (nama, anak, kelas, wali kelas, kota, tanggal surat,
/// mulai izin, jumlah hari, alasan, kontak) + pratinjau surat, maksimal 3x per semester
/// (hanya pengajuan yang disetujui yang dihitung).
class IzinWaliScreen extends StatefulWidget {
  const IzinWaliScreen({super.key});

  @override
  State<IzinWaliScreen> createState() => _IzinWaliScreenState();
}

class _IzinWaliScreenState extends State<IzinWaliScreen> {
  String _jenis = 'Sakit';

  // Izin sakit
  DateTime? _mulai;
  DateTime? _selesai;
  final _alasan = TextEditingController();
  XFile? _foto;

  // Izin keperluan (surat)
  final _namaLengkap = TextEditingController();
  final _kota = TextEditingController();
  final _kontak = TextEditingController();
  final _alasanKeperluan = TextEditingController();
  DateTime? _mulaiKeperluan;
  DateTime? _selesaiKeperluan;
  String? _alasanPilihan;
  bool _prefill = false;

  bool _kirim = false;
  Key _riwayat = UniqueKey();

  @override
  void dispose() {
    _alasan.dispose();
    _namaLengkap.dispose();
    _kota.dispose();
    _kontak.dispose();
    _alasanKeperluan.dispose();
    super.dispose();
  }

  /// Isi form surat dari profil wali/anak saat layar pertama dibuka.
  void _siapkanFormSurat() {
    final akun = context.read<Session>().akun;
    final anak = context.read<Session>().anak!;
    _namaLengkap.text = (akun['nama'] ?? '').toString();
    _kota.text = 'Kab. Kubu Raya';
    _kontak.text = ((akun['no_hp'] ?? anak['no_hp_wali']) ?? '').toString();
    // _siapkanFormSurat dipanggil dari build(), jadi listener dipasang setelah frame selesai.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _namaLengkap.addListener(_tandaiBerubah);
      _kota.addListener(_tandaiBerubah);
      _kontak.addListener(_tandaiBerubah);
      _alasanKeperluan.addListener(_tandaiBerubah);
    });
  }

  /// Isian teks memengaruhi kelengkapan surat (tombol & pratinjau), jadi layar digambar ulang.
  void _tandaiBerubah() => setState(() {});

  /// Jumlah hari ikut rentang tanggal mulai–selesai izin (maksimal 30 hari).
  int get _jumlahHari => _mulaiKeperluan == null || _selesaiKeperluan == null
      ? 1
      : _selesaiKeperluan!.difference(_mulaiKeperluan!).inDays + 1;

  /// Pratinjau hanya tampil bila semua isian surat sudah lengkap.
  bool get _suratLengkap =>
      _namaLengkap.text.trim().isNotEmpty &&
      _kota.text.trim().isNotEmpty &&
      _mulaiKeperluan != null &&
      _selesaiKeperluan != null &&
      _alasanPilihan != null &&
      _kontak.text.trim().isNotEmpty;

  String _ymd(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<DateTime?> _pilihTanggal(
    DateTime? awal, {
    DateTime? pertama,
    DateTime? terakhir,
  }) => showDatePicker(
    context: context,
    initialDate: awal ?? DateTime.now(),
    firstDate: pertama ?? DateTime.now().subtract(const Duration(days: 7)),
    lastDate: terakhir ?? DateTime.now().add(const Duration(days: 30)),
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

  /// Teks alasan yang dikirim: pilihan + keterangan bebas bila ada.
  String _alasanSurat() {
    final bebas = _alasanKeperluan.text.trim();
    if (_alasanPilihan == 'Lainnya' || _alasanPilihan == null) {
      return bebas.isEmpty ? 'Keperluan Mendesak' : bebas;
    }
    return bebas.isEmpty ? _alasanPilihan! : '$_alasanPilihan ($bebas)';
  }

  Future<void> _ajukan() async {
    final anak = context.read<Session>().anak!;
    if (_jenis == 'Sakit') {
      if (_mulai == null || _alasan.text.trim().isEmpty) {
        showMessage(context, 'Isi tanggal mulai dan alasan.', error: true);
        return;
      }
    } else {
      if (_mulaiKeperluan == null || _selesaiKeperluan == null) {
        showMessage(
          context,
          'Pilih tanggal mulai dan sampai izin.',
          error: true,
        );
        return;
      }
      if (_alasanPilihan == null && _alasanKeperluan.text.trim().isEmpty) {
        showMessage(context, 'Pilih alasan izin.', error: true);
        return;
      }
    }

    setState(() => _kirim = true);
    try {
      final surat = _jenis == 'Izin';
      final res = await context.read<ApiClient>().upload('/wali/izin', {
        'siswa_id': anak['id'],
        'jenis': _jenis,
        'tanggal_mulai': _ymd(surat ? _mulaiKeperluan! : _mulai!),
        'tanggal_selesai': _ymd(
          surat ? _selesaiKeperluan! : (_selesai ?? _mulai!),
        ),
        'alasan': surat ? _alasanSurat() : _alasan.text.trim(),
        if (surat) ...{
          'nama_lengkap': _namaLengkap.text.trim(),
          'kota': _kota.text.trim(),
          'tanggal_surat': _ymd(DateTime.now()),
          'jumlah_hari': _jumlahHari,
          'kontak': _kontak.text.trim(),
        },
      }, file: _foto?.path);
      if (!mounted) return;
      await context.push(
        '/wali/izin/terkirim',
        extra: {
          'jenis': _jenis,
          'anak': res['anak'],
          'kelas': res['kelas'],
          'nomor_surat': (res['izin'] as Map?)?['nomor_surat'],
        },
      );
      setState(() {
        _mulai = _selesai = null;
        _foto = null;
        _alasan.clear();
        _mulaiKeperluan = null;
        _selesaiKeperluan = null;
        _alasanPilihan = null;
        _alasanKeperluan.clear();
        _riwayat = UniqueKey();
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.status == 422 && e.data['sisa_kuota'] == 0) {
        await showDialog(
          context: context,
          builder: (c) => AlertDialog(
            icon: const Icon(Icons.info_outline, color: AppColors.brand700),
            title: const Text('Kuota izin keperluan habis'),
            content: Text(e.message),
            actions: [
              TextButton(
                onPressed: () {
                  Navigator.pop(c);
                  setState(() => _jenis = 'Sakit');
                },
                child: const Text('Ajukan Izin Sakit'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(c),
                child: const Text('Tutup'),
              ),
            ],
          ),
        );
      } else {
        showMessage(context, e.message, error: true);
      }
    } finally {
      if (mounted) setState(() => _kirim = false);
    }
  }

  Widget _label(String teks) =>
      Text(teks, style: AppText.overline.copyWith(fontSize: 10));

  Widget _tombolTanggal(
    String label,
    DateTime? v,
    ValueChanged<DateTime> set, {
    DateTime? pertama,
    DateTime? terakhir,
    bool lebar = true,
  }) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      _label(label),
      const SizedBox(height: 6),
      OutlinedButton.icon(
        onPressed: () async {
          final t = await _pilihTanggal(
            v,
            pertama: pertama,
            terakhir: terakhir,
          );
          if (t != null) setState(() => set(t));
        },
        icon: const Icon(Icons.event_outlined, size: 18),
        label: Text(
          v == null ? 'Pilih tanggal' : Fmt.tanggalPendek(v),
          overflow: TextOverflow.ellipsis,
        ),
        style: OutlinedButton.styleFrom(
          minimumSize: Size.fromHeight(lebar ? 48 : 48),
          alignment: Alignment.centerLeft,
          backgroundColor: AppColors.field,
          side: const BorderSide(color: AppColors.line),
        ),
      ),
    ],
  );

  @override
  Widget build(BuildContext context) {
    if (!_prefill) {
      _prefill = true;
      _siapkanFormSurat();
    }
    final anak = context.watch<Session>().anak!;
    final api = context.read<ApiClient>();
    final surat = _jenis == 'Izin';

    return AppPage(
      title: 'Pengajuan Izin Siswa',
      subtitle: 'Kirim Surat Sakit / Izin Mandiri',
      bottom: PrimaryButton(
        label: surat
            ? 'Kirim Surat Izin ke Wali Kelas'
            : 'Kirim Pengajuan Izin Sakit',
        loading: _kirim,
        onPressed: _ajukan,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _label('JENIS IZIN'),
          const SizedBox(height: 6),
          PilihanChips<String>(
            pilihan: const {'Sakit': 'Sakit', 'Izin': 'Izin Keperluan'},
            nilai: _jenis,
            onChanged: (v) => setState(() => _jenis = v),
          ),
          const SizedBox(height: 14),
          if (!surat) ..._formSakit(anak),
          if (surat) ..._formSurat(anak, api),
          const SizedBox(height: 24),
          Text('Riwayat Izin ${anak['nama']}', style: AppText.heading),
          const SizedBox(height: 10),
          _riwayatIzin(api, anak),
        ],
      ),
    );
  }

  List<Widget> _formSakit(Map<String, dynamic> anak) => [
    Row(
      children: [
        Expanded(
          child: _tombolTanggal('MULAI TANGGAL', _mulai, (t) {
            _mulai = t;
            if (_selesai != null && _selesai!.isBefore(t)) _selesai = t;
          }),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _tombolTanggal(
            'SAMPAI TANGGAL',
            _selesai,
            (t) =>
                _selesai = (_mulai != null && t.isBefore(_mulai!)) ? _mulai : t,
          ),
        ),
      ],
    ),
    const SizedBox(height: 14),
    _label('ALASAN / KETERANGAN'),
    const SizedBox(height: 6),
    TextField(
      controller: _alasan,
      minLines: 3,
      maxLines: 5,
      maxLength: 1000,
      decoration: const InputDecoration(
        hintText:
            'Contoh: Ananda demam sejak tadi malam dan disarankan istirahat.',
      ),
    ),
    const SizedBox(height: 8),
    _label('UNGGAH SURAT DOKTER / BUKTI (FOTO)'),
    const SizedBox(height: 6),
    AppCard(
      onTap: _ambilFoto,
      borderColor: AppColors.brand200,
      child: Column(
        children: [
          Icon(
            _foto == null ? Icons.photo_camera_outlined : Icons.check_circle,
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
  ];

  List<Widget> _formSurat(Map<String, dynamic> anak, ApiClient api) => [
    _label('DETAIL SURAT'),
    const SizedBox(height: 6),
    FutureBuilder<Map<String, dynamic>>(
      future: api.get('/wali/izin'),
      builder: (context, s) {
        final kuota = s.data?['kuota_izin'] as Map?;
        if (kuota == null) return const SizedBox.shrink();
        final sisa = kuota['sisa'] ?? 0;
        return Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: Row(
            children: [
              Icon(
                sisa == 0 ? Icons.block : Icons.info_outline,
                size: 18,
                color: sisa == 0 ? AppColors.alpa : AppColors.brand700,
              ),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  sisa == 0
                      ? 'Kuota izin keperluan semester ini habis (${kuota['maksimal']}x). Izin sakit tetap bisa diajukan.'
                      : 'Sisa izin keperluan semester ini: $sisa/${kuota['maksimal']} (yang sudah disetujui).',
                  style: AppText.caption.copyWith(
                    color: sisa == 0 ? AppColors.alpa : AppColors.inkMuted,
                  ),
                ),
              ),
            ],
          ),
        );
      },
    ),
    TextField(
      controller: _namaLengkap,
      textCapitalization: TextCapitalization.words,
      decoration: const InputDecoration(labelText: 'Nama lengkap orang tua *'),
    ),
    const SizedBox(height: 10),
    InputDecorator(
      decoration: const InputDecoration(labelText: 'Nama anak / kelas'),
      child: Text(
        '${anak['nama']} · ${anak['kelas'] ?? '-'} · ${anak['wali_kelas'] ?? '-'}',
        style: AppText.body,
      ),
    ),
    const SizedBox(height: 10),
    TextField(
      controller: _kota,
      textCapitalization: TextCapitalization.words,
      decoration: const InputDecoration(labelText: 'Kota'),
    ),
    const SizedBox(height: 14),
    Row(
      children: [
        Expanded(
          child: _tombolTanggal('TANGGAL MULAI IZIN *', _mulaiKeperluan, (t) {
            _mulaiKeperluan = t;
            if (_selesaiKeperluan != null && _selesaiKeperluan!.isBefore(t)) {
              _selesaiKeperluan = t;
            }
          }, lebar: false),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _tombolTanggal(
            'SAMPAI TANGGAL IZIN *',
            _selesaiKeperluan,
            (t) => _selesaiKeperluan =
                (_mulaiKeperluan != null && t.isBefore(_mulaiKeperluan!))
                ? _mulaiKeperluan
                : t,
            terakhir: (_mulaiKeperluan ?? DateTime.now()).add(
              const Duration(days: 30),
            ),
            pertama: _mulaiKeperluan ?? DateTime.now(),
            lebar: false,
          ),
        ),
      ],
    ),
    if (_mulaiKeperluan != null && _selesaiKeperluan != null)
      Padding(
        padding: const EdgeInsets.only(top: 8),
        child: Text(
          'Lama izin: $_jumlahHari hari',
          style: AppText.caption.copyWith(color: AppColors.brand700),
        ),
      ),
    const SizedBox(height: 14),
    _label('ALASAN IZIN *'),
    const SizedBox(height: 6),
    PilihanChips<String>(
      pilihan: const {
        'Sakit Keluarga': 'Sakit Keluarga',
        'Acara Keluarga': 'Acara Keluarga',
        'Keperluan Dinas': 'Keperluan Dinas',
        'Urusan Penting': 'Urusan Penting',
        'Lainnya': 'Lainnya',
      },
      nilai: _alasanPilihan ?? '',
      onChanged: (v) => setState(() => _alasanPilihan = v),
    ),
    const SizedBox(height: 8),
    TextField(
      controller: _alasanKeperluan,
      minLines: 2,
      maxLines: 4,
      maxLength: 500,
      decoration: const InputDecoration(
        hintText:
            'Keterangan tambahan (opsional), mis. acara keluarga di luar kota',
      ),
    ),
    _label('KONTAK ORANG TUA'),
    const SizedBox(height: 6),
    TextField(
      controller: _kontak,
      keyboardType: TextInputType.phone,
      decoration: const InputDecoration(hintText: 'Nomor HP / Email'),
    ),
    if (_suratLengkap)
      _PratinjauSurat(
        key: ValueKey(
          '${_namaLengkap.text}|${_kota.text}|${_kontak.text}|'
          '${_ymd(_mulaiKeperluan!)}|$_jumlahHari|$_alasanPilihan|${_alasanSurat()}',
        ),
        api: api,
        siswaId: anak['id'],
        nama: _namaLengkap.text,
        kota: _kota.text,
        tanggalMulai: _mulaiKeperluan,
        jumlahHari: _jumlahHari,
        alasan: _alasanSurat(),
        kontak: _kontak.text,
      )
    else
      AppCard(
        color: AppColors.field,
        child: Row(
          children: [
            const Icon(
              Icons.description_outlined,
              size: 18,
              color: AppColors.inkMuted,
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                'Pratinjau surat muncul setelah semua isian di atas terisi.',
                style: AppText.caption,
              ),
            ),
          ],
        ),
      ),
  ];

  Widget _riwayatIzin(
    ApiClient api,
    Map<String, dynamic> anak,
  ) => FutureBuilder<Map<String, dynamic>>(
    key: _riwayat,
    future: api.get('/wali/izin', query: {'siswa_id': anak['id']}),
    builder: (context, s) {
      if (!s.hasData) {
        return const Padding(
          padding: EdgeInsets.all(16),
          child: Center(child: CircularProgressIndicator()),
        );
      }
      final list = (s.data!['data'] as List).cast<Map>();
      if (list.isEmpty) {
        return Text('Belum ada pengajuan izin.', style: AppText.bodySmall);
      }
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
                          if (i['nomor_surat'] != null)
                            Text(
                              'No. ${i['nomor_surat']}',
                              style: AppText.caption,
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
  );
}

/// Pratinjau surat dari server (teks sudah diformat sama seperti PDF).
class _PratinjauSurat extends StatelessWidget {
  const _PratinjauSurat({
    super.key,
    required this.api,
    required this.siswaId,
    required this.nama,
    required this.kota,
    required this.tanggalMulai,
    required this.jumlahHari,
    required this.alasan,
    required this.kontak,
  });

  final ApiClient api;
  final Object? siswaId;
  final String nama;
  final String kota;
  final DateTime? tanggalMulai;
  final int jumlahHari;
  final String alasan;
  final String kontak;

  String _ymd(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: api.post('/wali/izin/pratinjau-surat', {
        'siswa_id': siswaId,
        'nama_lengkap': nama,
        'kota': kota,
        if (tanggalMulai != null) 'tanggal_mulai': _ymd(tanggalMulai!),
        'jumlah_hari': jumlahHari,
        'alasan': alasan,
        'kontak': kontak,
      }),
      builder: (context, s) {
        final d = s.data?['data'] as Map?;
        Widget baris(String kiri, String kanan) => Padding(
          padding: const EdgeInsets.only(bottom: 2),
          child: Text.rich(
            TextSpan(
              children: [
                TextSpan(
                  text: '$kiri ',
                  style: AppText.bodySmall.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
                TextSpan(text: kanan, style: AppText.bodySmall),
              ],
            ),
          ),
        );
        return AppCard(
          color: AppColors.field,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  const Icon(Icons.description_outlined, size: 18),
                  const SizedBox(width: 6),
                  Text('Pratinjau surat', style: AppText.label),
                ],
              ),
              const SizedBox(height: 10),
              if (d == null)
                Text('Menyusun pratinjau…', style: AppText.caption)
              else ...[
                Text(
                  '${d['kota']}, ${d['tanggal_surat']}',
                  style: AppText.bodySmall,
                ),
                const SizedBox(height: 8),
                Text('Kepada Yth.', style: AppText.bodySmall),
                Text(
                  '${d['nama_wali_kelas']} · Wali Kelas ${d['kelas']}',
                  style: AppText.bodySmall.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  '${d['nama_sekolah']}, di Tempat',
                  style: AppText.bodySmall,
                ),
                const SizedBox(height: 8),
                Text(
                  'Dengan hormat, saya ${d['nama_orang_tua']}, orang tua/wali dari ${d['nama_anak']} (Kelas ${d['kelas']}), memberitahukan bahwa anak kami tidak dapat mengikuti kegiatan belajar mengajar karena ${d['alasan_teks']}, terhitung mulai tanggal ${d['tanggal_mulai']} selama ${d['jumlah_hari']} hari.',
                  style: AppText.bodySmall,
                ),
                if (d['kontak'] != null && '${d['kontak']}'.isNotEmpty)
                  baris('Kontak:', '${d['kontak']}'),
                const SizedBox(height: 8),
                Text('Hormat kami,', style: AppText.bodySmall),
                Text(
                  '${d['nama_orang_tua']}',
                  style: AppText.bodySmall.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ],
          ),
        );
      },
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
            if (data['nomor_surat'] != null)
              (
                'No. Surat',
                Text('${data['nomor_surat']}', style: AppText.label),
              ),
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
