import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/kartu_identitas.dart';

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
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Row(children: [
          Expanded(child: Text('Kartu Guru', style: AppText.heading)),
          Text('TA ${(akun['tahun_ajaran'] ?? '-').toString().split(' ').first}', style: AppText.caption),
        ]),
        const SizedBox(height: 8),
        KartuIdentitas(
          jenis: 'KARTU GURU',
          nama: akun['nama'] ?? '-',
          inisial: akun['inisial'] ?? '',
          fotoUrl: akun['foto_url'],
          onTapFoto: () => _uploadFoto(context),
          fields: [('NIP', akun['nip'] ?? '-'), ('Jabatan', akun['jabatan'] ?? 'Guru')],
          tahunAjaran: akun['tahun_ajaran'],
          footerKanan: 'Kartu Identitas Guru',
        ),
        const SizedBox(height: 16),
        InfoTable([
          ('Semester', Text(akun['tahun_ajaran'] != null ? 'Semester ${akun['tahun_ajaran']}' : '-', style: AppText.label)),
          ('Wali kelas', Text(wali?.isNotEmpty == true ? wali! : '-', style: AppText.label)),
          ('Sekolah', Text(akun['sekolah'] ?? 'SDIT Darul Fikri', style: AppText.label)),
        ]),
        const SizedBox(height: 16),
        AppCard(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Column(children: [
            _MenuRow(icon: 'presensi', label: 'Cetak Kartu Guru', onTap: () => context.push('/guru/profil/cetak-kartu')),
            const Divider(height: 1, indent: 64, color: AppColors.line),
            _MenuRow(icon: 'absen', label: 'Unggah / Ganti Foto Profil', onTap: () => _uploadFoto(context)),
            const Divider(height: 1, indent: 64, color: AppColors.line),
            _MenuRow(icon: 'lock', label: 'Ubah Kata Sandi', onTap: () => _ubahSandi(context)),
            const Divider(height: 1, indent: 64, color: AppColors.line),
            _MenuRow(icon: 'calendar', label: 'Jadwal Mengajar', onTap: () => context.push('/guru/jadwal')),
            const Divider(height: 1, indent: 64, color: AppColors.line),
            _MenuRow(icon: 'rekap_absen', label: 'Rekap Absen Saya', onTap: () => context.push('/guru/absen/rekap')),
            const Divider(height: 1, indent: 64, color: AppColors.line),
            _MenuRow(icon: 'mega', label: 'Bantuan Tata Usaha', onTap: () => showMessage(context, 'Hubungi Tata Usaha SDIT Darul Fikri untuk bantuan akun.')),
          ]),
        ),
        const SizedBox(height: 16),
        SecondaryButton(
          label: 'Keluar',
          color: AppColors.alpa,
          onPressed: () async {
            final ok = await showDialog<bool>(
              context: context,
              builder: (c) => AlertDialog(
                title: const Text('Keluar dari MyDarfik?'),
                content: const Text('Anda perlu login lagi untuk memakai aplikasi.'),
                actions: [
                  TextButton(onPressed: () => c.pop(false), child: const Text('Batal')),
                  TextButton(onPressed: () => c.pop(true), child: const Text('Keluar', style: TextStyle(color: AppColors.alpa))),
                ],
              ),
            );
            if (ok == true && context.mounted) await context.read<Session>().logout();
          },
        ),
      ]),
    );
  }

  Future<void> _ubahSandi(BuildContext context) async {
    await showModalBottomSheet(context: context, isScrollControlled: true, showDragHandle: true, backgroundColor: AppColors.surface, builder: (_) => const _UbahSandiSheet());
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

      messenger.showSnackBar(const SnackBar(content: Text('Mengunggah foto profil...')));
      await api.upload('/guru/profil/foto', {}, file: file.path, fileField: 'foto');
      await session.refreshMe();
      messenger.showSnackBar(const SnackBar(content: Text('Foto profil berhasil diunggah!')));
    } catch (e) {
      messenger.showSnackBar(SnackBar(content: Text('Gagal mengunggah foto: $e'), backgroundColor: AppColors.alpa));
    }
  }
}

class _MenuRow extends StatelessWidget {
  const _MenuRow({required this.icon, required this.label, required this.onTap});
  final String icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => ListTile(
        onTap: onTap,
        leading: Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(color: AppColors.brand50, borderRadius: BorderRadius.circular(10)),
          alignment: Alignment.center,
          child: Image.asset('assets/icons/$icon.png', width: 22),
        ),
        title: Text(label, style: AppText.label),
        trailing: const Icon(Icons.chevron_right, color: AppColors.inkMuted),
      );
}

class _UbahSandiSheet extends StatefulWidget {
  const _UbahSandiSheet();
  @override
  State<_UbahSandiSheet> createState() => _UbahSandiSheetState();
}

class _UbahSandiSheetState extends State<_UbahSandiSheet> {
  final _lama = TextEditingController();
  final _baru = TextEditingController();
  final _ulang = TextEditingController();
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _lama.dispose();
    _baru.dispose();
    _ulang.dispose();
    super.dispose();
  }

  Future<void> _simpan() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await context.read<ApiClient>().post('/guru/ubah-sandi', {'sandi_lama': _lama.text, 'sandi_baru': _baru.text, 'sandi_baru_confirmation': _ulang.text});
      if (!mounted) return;
      Navigator.pop(context);
      showMessage(context, 'Kata sandi diperbarui. Perangkat lain otomatis keluar.');
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.fromLTRB(20, 0, 20, 20 + MediaQuery.of(context).viewInsets.bottom),
      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Text('Ubah Kata Sandi', style: AppText.title),
        const SizedBox(height: 4),
        Text('Minimal 8 karakter, berisi huruf besar, huruf kecil, dan angka.', style: AppText.bodySmall),
        const SizedBox(height: 16),
        TextField(controller: _lama, obscureText: true, decoration: const InputDecoration(labelText: 'Kata sandi lama')),
        const SizedBox(height: 12),
        TextField(controller: _baru, obscureText: true, decoration: const InputDecoration(labelText: 'Kata sandi baru')),
        const SizedBox(height: 12),
        TextField(controller: _ulang, obscureText: true, decoration: const InputDecoration(labelText: 'Ulangi kata sandi baru')),
        if (_error != null) Padding(padding: const EdgeInsets.only(top: 12), child: Text(_error!, style: AppText.bodySmall.copyWith(color: AppColors.alpa))),
        const SizedBox(height: 16),
        PrimaryButton(label: 'Simpan', loading: _loading, onPressed: _simpan),
      ]),
    );
  }
}
