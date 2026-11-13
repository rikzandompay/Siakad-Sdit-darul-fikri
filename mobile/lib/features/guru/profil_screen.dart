import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/kartu_identitas.dart';
import '../shared/panduan_screen.dart';
import '../shared/ubah_sandi.dart';

class ProfilScreen extends StatelessWidget {
  const ProfilScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final session = context.watch<Session>();
    final akun = session.akun;
    final wali = (akun['wali_kelas'] as List?)?.join(', ');

    return AppPage(
      title: 'Profil',
      subtitle: 'Akun guru MyDarfik',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Expanded(child: Text('Kartu Guru', style: AppText.heading)),
              Text(
                'TA ${(akun['tahun_ajaran'] ?? '-').toString().split(' ').first}',
                style: AppText.caption,
              ),
            ],
          ),
          const SizedBox(height: 8),
          KartuIdentitas(
            jenis: 'KARTU GURU',
            nama: akun['nama'] ?? '-',
            inisial: akun['inisial'] ?? '',
            fotoUrl: akun['foto_url'],
            onTapFoto: () => _uploadFoto(context),
            fields: [
              ('NIP', akun['nip'] ?? '-'),
              ('Jabatan', akun['jabatan'] ?? 'Guru'),
            ],
            tahunAjaran: akun['tahun_ajaran'],
            footerKanan: 'Kartu Identitas Guru',
          ),
          if (akun['foto_menunggu'] == true) ...[
            const SizedBox(height: 8),
            Text(
              'Foto baru menunggu persetujuan kepala sekolah.',
              style: AppText.caption.copyWith(color: const Color(0xFF7A5512)),
            ),
          ],
          const SizedBox(height: 16),
          InfoTable([
            (
              'Semester',
              Text(
                akun['tahun_ajaran'] != null
                    ? 'Semester ${akun['tahun_ajaran']}'
                    : '-',
                style: AppText.label,
              ),
            ),
            (
              'Wali kelas',
              Text(
                wali?.isNotEmpty == true ? wali! : '-',
                style: AppText.label,
              ),
            ),
            (
              'Sekolah',
              Text(akun['sekolah'] ?? 'SDIT Darul Fikri', style: AppText.label),
            ),
          ]),
          const SizedBox(height: 16),
          AppCard(
            padding: const EdgeInsets.symmetric(vertical: 4),
            child: Column(
              children: [
                MenuRow(
                  icon: 'kartu',
                  label: 'Cetak Kartu Guru',
                  onTap: () => context.push('/guru/profil/cetak-kartu'),
                ),
                const Divider(height: 1, indent: 64, color: AppColors.line),
                MenuRow(
                  icon: 'foto',
                  label: 'Unggah / Ganti Foto Profil',
                  onTap: () => _uploadFoto(context),
                ),
                const Divider(height: 1, indent: 64, color: AppColors.line),
                MenuRow(
                  icon: 'sandi',
                  label: 'Ubah Kata Sandi',
                  onTap: () => bukaUbahSandi(context),
                ),
                const Divider(height: 1, indent: 64, color: AppColors.line),
                MenuRow(
                  icon: 'panduan',
                  label: 'Panduan Penggunaan',
                  onTap: () => context.push('/guru/profil/panduan'),
                ),
                const Divider(height: 1, indent: 64, color: AppColors.line),
                MenuRow(
                  icon: 'bantuan',
                  label: 'Bantuan Tata Usaha',
                  onTap: () => showMessage(
                    context,
                    'Hubungi Tata Usaha SDIT Darul Fikri untuk bantuan akun.',
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          AlertButton(
            label: 'Keluar',
            icon: Icons.logout_rounded,
            onPressed: () async {
              final ok = await showDialog<bool>(
                context: context,
                builder: (c) => AlertDialog(
                  title: const Text('Keluar dari MyDarfik?'),
                  content: const Text(
                    'Anda perlu login lagi untuk memakai aplikasi.',
                  ),
                  actions: [
                    TextButton(
                      onPressed: () => c.pop(false),
                      child: const Text('Batal'),
                    ),
                    TextButton(
                      onPressed: () => c.pop(true),
                      child: const Text(
                        'Keluar',
                        style: TextStyle(color: AppColors.alpa),
                      ),
                    ),
                  ],
                ),
              );
              if (ok == true && context.mounted)
                await context.read<Session>().logout();
            },
          ),
          const SizedBox(height: 16),
          const Center(child: TeksVersi()),
        ],
      ),
    );
  }

  Future<void> _uploadFoto(BuildContext context) async {
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
              Text('Pilih Sumber Foto Profil', style: AppText.heading),
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

      messenger.showSnackBar(
        const SnackBar(content: Text('Mengunggah foto profil...')),
      );
      final res = await api.upload(
        '/guru/profil/foto',
        {},
        file: file.path,
        fileField: 'foto',
      );
      await session.refreshMe();
      messenger.showSnackBar(
        SnackBar(content: Text(res['message'] as String? ?? 'Foto terkirim.')),
      );
    } catch (e) {
      messenger.showSnackBar(
        SnackBar(
          content: Text('Gagal mengunggah foto: $e'),
          backgroundColor: AppColors.alpa,
        ),
      );
    }
  }
}
