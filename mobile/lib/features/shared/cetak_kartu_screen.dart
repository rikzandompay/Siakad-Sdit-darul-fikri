import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../core/unduh.dart';
import '../../widgets/common.dart';
import '../../widgets/kartu_identitas.dart';
import '../../widgets/kartu_pelajar_desain.dart';

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
                leading: const Icon(
                  Icons.camera_alt_outlined,
                  color: AppColors.brand700,
                ),
                title: const Text('Ambil dari Kamera'),
                onTap: () => Navigator.pop(c, ImageSource.camera),
              ),
              ListTile(
                leading: const Icon(
                  Icons.photo_library_outlined,
                  color: AppColors.brand700,
                ),
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
      final file = await ImagePicker().pickImage(
        source: sumber,
        maxWidth: 600,
        maxHeight: 800,
        imageQuality: 75,
      );
      if (file == null) return;

      setState(() => _uploading = true);

      String pesan = 'Foto profil berhasil diunggah dan terpasang di kartu!';
      if (session.peran == Peran.guru) {
        final res = await api.upload(
          '/guru/profil/foto',
          {},
          file: file.path,
          fileField: 'foto',
        );
        pesan = res['message'] as String? ?? pesan;
      } else {
        final a = session.anak;
        if (a == null) return;
        await api.upload(
          '/wali/profil/foto',
          {'siswa_id': a['id']},
          file: file.path,
          fileField: 'foto',
        );
      }

      await session.refreshMe();
      if (mounted) {
        messenger.showSnackBar(SnackBar(content: Text(pesan)));
      }
    } catch (e) {
      if (mounted) {
        messenger.showSnackBar(
          SnackBar(
            content: Text('Gagal mengunggah foto: $e'),
            backgroundColor: AppColors.alpa,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  /// Wali: unduh kartu anak sebagai PDF A4 (ukuran kartu asli) lalu simpan ke folder Download.
  /// Unduh kartu sebagai PDF A4 (ukuran kartu asli) lalu simpan ke folder Download.
  /// Guru: kartu guru miliknya. Wali: kartu pelajar anak yang sedang dipilih.
  Future<void> _unduhPdf() async {
    final session = context.read<Session>();
    if (session.peran == Peran.guru) {
      await unduhLaporan(
        context,
        jenis: 'kartu-guru',
        format: 'pdf',
        alamatApi: '/guru/kartu',
      );
      return;
    }
    final a = session.anak;
    if (a == null) return;
    await unduhLaporan(
      context,
      jenis: 'kartu-pelajar',
      format: 'pdf',
      alamatApi: '/wali/kartu',
      query: {'siswa_id': a['id']},
    );
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
                const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              else
                TextButton.icon(
                  onPressed: _pilihDanUploadFoto,
                  icon: const Icon(Icons.add_a_photo_outlined, size: 16),
                  label: const Text(
                    'Ganti Foto',
                    style: TextStyle(fontSize: 12),
                  ),
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
            KartuPelajarDepan(
              nama: a['nama'] ?? '-',
              nis: a['nis'] ?? '-',
              kelas: a['kelas_singkat'] ?? a['kelas'] ?? '-',
              alamat: a['alamat'] ?? '-',
              inisial: a['inisial'] ?? '',
              fotoUrl: a['foto_url'],
              tahunAjaran: akun['tahun_ajaran'],
              qr: a['qr'],
              onTapFoto: _pilihDanUploadFoto,
            ),
          const SizedBox(height: 20),
          Text('Sisi Belakang', style: AppText.heading),
          const SizedBox(height: 8),
          if (guru)
            _KartuBelakangGuru(akun: akun)
          else if (a != null)
            KartuPelajarBelakang(kepalaSekolah: a['kepala_sekolah']),
          const SizedBox(height: 24),
          PrimaryButton(
            label: 'Unduh PDF Kartu (siap cetak)',
            onPressed: _unduhPdf,
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
            child: Row(
              children: [
                Image.asset('assets/images/logo.png', height: 34),
                const SizedBox(width: 10),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'KETENTUAN KARTU',
                      style: AppText.overline.copyWith(
                        color: AppColors.brand700,
                      ),
                    ),
                    Text('SDIT Darul Fikri', style: AppText.title),
                  ],
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 6, 14, 14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '1. Kartu identitas resmi pendidik / tenaga kependidikan.',
                  style: AppText.bodySmall,
                ),
                const SizedBox(height: 4),
                Text(
                  '2. Digunakan untuk presensi dan verifikasi administrasi.',
                  style: AppText.bodySmall,
                ),
                const SizedBox(height: 4),
                Text(
                  '3. Wajib dijaga dengan baik dan tidak dipindahtangankan.',
                  style: AppText.bodySmall,
                ),
              ],
            ),
          ),
          Container(
            color: AppColors.brand700,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
            alignment: Alignment.center,
            child: Text(
              'Jl. Sungai Durian Laut, Kec. Sungai Raya, Kab. Kubu Raya',
              style: AppText.caption.copyWith(
                color: Colors.white,
                fontSize: 10,
              ),
              textAlign: TextAlign.center,
            ),
          ),
        ],
      ),
    );
  }
}
