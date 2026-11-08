import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/api_client.dart';
import '../../core/config.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/kartu_identitas.dart';

/// Halaman Cetak Kartu: menampilkan pratinjau kartu depan & belakang siap cetak.
class CetakKartuScreen extends StatefulWidget {
  const CetakKartuScreen({super.key});

  @override
  State<CetakKartuScreen> createState() => _CetakKartuScreenState();
}

class _CetakKartuScreenState extends State<CetakKartuScreen> {
  bool _uploading = false;

  Future<void> _pilihDanUploadFoto() async {
    final session = context.read<Session>();
    final api = context.read<ApiClient>();
    final messenger = ScaffoldMessenger.of(context);

    final sumber = await showModalBottomSheet<ImageSource>(
      context: context,
      showDragHandle: true,
      backgroundColor: AppColors.surface,
      builder: (c) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text('Pilih Sumber Foto', style: AppText.heading),
              const SizedBox(height: 12),
              ListTile(
                leading: const Icon(Icons.camera_alt_outlined, color: AppColors.brand700),
                title: const Text('Ambil dari Kamera'),
                onTap: () => Navigator.pop(c, ImageSource.camera),
              ),
              ListTile(
                leading: const Icon(Icons.photo_library_outlined, color: AppColors.brand700),
                title: const Text('Pilih dari Galeri'),
                onTap: () => Navigator.pop(c, ImageSource.gallery),
              ),
            ],
          ),
        ),
      ),
    );

    if (sumber == null) return;

    try {
      final file = await ImagePicker().pickImage(source: sumber, maxWidth: 600, maxHeight: 800, imageQuality: 75);
      if (file == null) return;

      setState(() => _uploading = true);

      if (session.peran == Peran.guru) {
        await api.upload('/guru/profil/foto', {}, file: file.path, fileField: 'foto');
      } else {
        final a = session.anak;
        if (a == null) return;
        await api.upload('/wali/profil/foto', {'siswa_id': a['id']}, file: file.path, fileField: 'foto');
      }

      await session.refreshMe();
      if (mounted) {
        messenger.showSnackBar(
          const SnackBar(content: Text('Foto profil berhasil diunggah dan terpasang di kartu!')),
        );
      }
    } catch (e) {
      if (mounted) {
        messenger.showSnackBar(
          SnackBar(content: Text('Gagal mengunggah foto: $e'), backgroundColor: AppColors.alpa),
        );
      }
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  Future<void> _bukaCetakWeb() async {
    final session = context.read<Session>();
    final a = session.anak;
    if (a != null && session.isAdmin) {
      final uri = Uri.parse('${AppConfig.webUrl}/siswa/${a['id']}/kartu');
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
        return;
      }
    }
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pratinjau kartu siap cetak. Gunakan tangkapan layar atau cetak via sistem.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<Session>();
    final guru = session.peran == Peran.guru;
    final akun = session.akun;
    final a = session.anak;

    return AppPage(
      title: 'Cetak Kartu',
      subtitle: guru ? 'Kartu Identitas Guru' : 'Kartu Pelajar Siswa',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Expanded(child: Text('Sisi Depan', style: AppText.heading)),
              if (_uploading)
                const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
              else
                TextButton.icon(
                  onPressed: _pilihDanUploadFoto,
                  icon: const Icon(Icons.add_a_photo_outlined, size: 16),
                  label: const Text('Ganti Foto', style: TextStyle(fontSize: 12)),
                ),
            ],
          ),
          const SizedBox(height: 8),
          if (guru)
            KartuIdentitas(
              jenis: 'KARTU GURU',
              nama: akun['nama'] ?? '-',
              inisial: akun['inisial'] ?? '',
              fotoUrl: akun['foto_url'],
              onTapFoto: _pilihDanUploadFoto,
              fields: [
                ('NIP', akun['nip'] ?? '-'),
                ('Jabatan', akun['jabatan'] ?? 'Guru'),
              ],
              tahunAjaran: akun['tahun_ajaran'],
              footerKanan: 'Kartu Identitas Guru',
            )
          else if (a != null)
            KartuIdentitas(
              jenis: 'KARTU PELAJAR',
              nama: a['nama'] ?? '-',
              inisial: a['inisial'] ?? '',
              fotoUrl: a['foto_url'],
              onTapFoto: _pilihDanUploadFoto,
              fields: [
                ('NIS', a['nis'] ?? '-'),
                ('Kelas', a['kelas_singkat'] ?? a['kelas'] ?? '-'),
                ('Alamat', a['alamat'] ?? '-'),
              ],
              tahunAjaran: akun['tahun_ajaran'],
              footerKanan: 'SDIT Darul Fikri',
            ),
          const SizedBox(height: 20),
          Text('Sisi Belakang', style: AppText.heading),
          const SizedBox(height: 8),
          if (guru)
            _KartuBelakangGuru(akun: akun)
          else if (a != null)
            _KartuBelakangSiswa(a: a, akun: akun),
          const SizedBox(height: 24),
          PrimaryButton(
            label: 'Cetak / Buka Tampilan Cetak',
            onPressed: _bukaCetakWeb,
          ),
          const SizedBox(height: 10),
          SecondaryButton(
            label: 'Unggah / Ubah Foto Kartu',
            onPressed: _pilihDanUploadFoto,
          ),
        ],
      ),
    );
  }
}

class _KartuBelakangSiswa extends StatelessWidget {
  const _KartuBelakangSiswa({required this.a, required this.akun});
  final Map<String, dynamic> a;
  final Map<String, dynamic> akun;

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.brand200),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.white, AppColors.brand50, AppColors.brand100],
        ),
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 4),
            child: Row(children: [
              Image.asset('assets/images/logo.png', height: 34),
              const SizedBox(width: 10),
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('MYDARFIK', style: AppText.overline.copyWith(color: AppColors.brand700)),
                Text('Scan QR Sholat', style: AppText.title),
              ]),
            ]),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (a['qr'] != null)
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: AppColors.line),
                    ),
                    child: Column(
                      children: [
                        QrImageView(data: a['qr'], size: 76, padding: EdgeInsets.zero),
                        const SizedBox(height: 4),
                        Text('QR SHOLAT', style: AppText.overline.copyWith(fontSize: 8)),
                      ],
                    ),
                  ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Data Wali', style: AppText.heading),
                      const SizedBox(height: 6),
                      Text('Nama: ${a['nama_wali'] ?? akun['nama'] ?? '-'}', style: AppText.bodySmall),
                      const SizedBox(height: 4),
                      Text('No. HP: ${a['no_hp_wali'] ?? akun['no_hp'] ?? '-'}', style: AppText.bodySmall),
                      const SizedBox(height: 4),
                      Text('Alamat: ${a['alamat'] ?? '-'}', style: AppText.bodySmall, maxLines: 2, overflow: TextOverflow.ellipsis),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Container(
            color: AppColors.brand700,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
            alignment: Alignment.center,
            child: Text(
              'Jika menemukan kartu ini, harap kembalikan ke SDIT Darul Fikri.',
              style: AppText.caption.copyWith(color: Colors.white, fontSize: 10),
              textAlign: TextAlign.center,
            ),
          ),
        ],
      ),
    );
  }
}

class _KartuBelakangGuru extends StatelessWidget {
  const _KartuBelakangGuru({required this.akun});
  final Map<String, dynamic> akun;

  @override
  Widget build(BuildContext context) {
    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.brand200),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.white, AppColors.brand50, AppColors.brand100],
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 6),
            child: Row(children: [
              Image.asset('assets/images/logo.png', height: 34),
              const SizedBox(width: 10),
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('KETENTUAN KARTU', style: AppText.overline.copyWith(color: AppColors.brand700)),
                Text('SDIT Darul Fikri', style: AppText.title),
              ]),
            ]),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 6, 14, 14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('1. Kartu identitas resmi pendidik / tenaga kependidikan.', style: AppText.bodySmall),
                const SizedBox(height: 4),
                Text('2. Digunakan untuk presensi dan verifikasi administrasi.', style: AppText.bodySmall),
                const SizedBox(height: 4),
                Text('3. Wajib dijaga dengan baik dan tidak dipindahtangankan.', style: AppText.bodySmall),
              ],
            ),
          ),
          Container(
            color: AppColors.brand700,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
            alignment: Alignment.center,
            child: Text(
              'Jl. Sungai Durian Laut, Kec. Sungai Raya, Kab. Kubu Raya',
              style: AppText.caption.copyWith(color: Colors.white, fontSize: 10),
              textAlign: TextAlign.center,
            ),
          ),
        ],
      ),
    );
  }
}

