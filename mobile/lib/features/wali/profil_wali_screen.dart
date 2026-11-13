import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/pilihan.dart';
import '../shared/panduan_screen.dart';
import '../shared/ubah_sandi.dart';
import 'wali_home_screen.dart';

class ProfilWaliScreen extends StatelessWidget {
  const ProfilWaliScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final session = context.watch<Session>();
    final a = session.anak!;
    Widget data(String judul, List<(String, String?)> rows) => AppCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SectionLabel(judul),
          for (var i = 0; i < rows.length; i++)
            Container(
              padding: const EdgeInsets.symmetric(vertical: 10),
              decoration: BoxDecoration(
                border: i == rows.length - 1
                    ? null
                    : const Border(bottom: BorderSide(color: AppColors.line)),
              ),
              child: Row(
                children: [
                  SizedBox(
                    width: 100,
                    child: Text(rows[i].$1, style: AppText.bodySmall),
                  ),
                  Expanded(
                    child: Text(rows[i].$2 ?? '-', style: AppText.label),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
    return AppPage(
      title: 'Profil',
      subtitle: 'Data ananda dan wali',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text('Kartu Pelajar', style: AppText.heading),
          const SizedBox(height: 8),
          KartuPelajar(
            a: a,
            tahunAjaran: session.akun['tahun_ajaran'],
            onTapFoto: () => _uploadFoto(context),
          ),
          const SizedBox(height: 12),
          data('Data Ananda', [
            ('Tgl Lahir', a['tanggal_lahir']),
            ('L/P', a['jenis_kelamin']),
            ('Kelas', a['kelas']),
            ('Alamat', a['alamat']),
          ]),
          const SizedBox(height: 12),
          data('Data Wali', [
            ('Nama Wali', a['nama_wali'] ?? session.akun['nama']),
            ('No HP', a['no_hp_wali'] ?? session.akun['no_hp']),
          ]),
          const SizedBox(height: 16),
          AppCard(
            padding: const EdgeInsets.symmetric(vertical: 4),
            child: Column(
              children: [
                MenuRow(
                  icon: 'kartu',
                  label: 'Cetak Kartu Pelajar',
                  onTap: () => context.push('/wali/profil/cetak-kartu'),
                ),
                const Divider(height: 1, indent: 64, color: AppColors.line),
                MenuRow(
                  icon: 'foto',
                  label: 'Unggah / Ubah Foto Ananda',
                  onTap: () => _uploadFoto(context),
                ),
                const Divider(height: 1, indent: 64, color: AppColors.line),
                MenuRow(
                  icon: 'profil',
                  label: 'Ubah Data',
                  onTap: () => context.push('/wali/profil/ubah'),
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
                  onTap: () => context.push('/wali/profil/panduan'),
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
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(c, false),
                      child: const Text('Batal'),
                    ),
                    TextButton(
                      onPressed: () => Navigator.pop(c, true),
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
    final a = session.anak;
    if (a == null) return;

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
              Text('Pilih Sumber Foto Ananda', style: AppText.heading),
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
        const SnackBar(content: Text('Mengunggah foto ananda...')),
      );
      await api.upload(
        '/wali/profil/foto',
        {'siswa_id': a['id']},
        file: file.path,
        fileField: 'foto',
      );
      await session.refreshMe();
      messenger.showSnackBar(
        const SnackBar(
          content: Text(
            'Foto ananda berhasil diunggah dan terpasang di kartu pelajar!',
          ),
        ),
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

class UbahDataScreen extends StatefulWidget {
  const UbahDataScreen({super.key});

  @override
  State<UbahDataScreen> createState() => _UbahDataScreenState();
}

class _UbahDataScreenState extends State<UbahDataScreen> {
  late final Map<String, dynamic> a = context.read<Session>().anak!;
  late String _jk = a['jenis_kelamin'] ?? 'L';
  late final _alamat = TextEditingController(text: a['alamat']);
  late final _wali = TextEditingController(text: a['nama_wali']);
  late final _hp = TextEditingController(text: a['no_hp_wali']);
  bool _simpan = false;

  @override
  void dispose() {
    _alamat.dispose();
    _wali.dispose();
    _hp.dispose();
    super.dispose();
  }

  Future<void> _kirim() async {
    final namaBaru = _wali.text.trim();
    setState(() => _simpan = true);
    try {
      await context.read<ApiClient>().put('/wali/siswa/${a['id']}', {
        'jenis_kelamin': _jk,
        'alamat': _alamat.text.trim(),
        'nama_wali': namaBaru,
        'no_hp_wali': _hp.text.trim(),
      });
      if (!mounted) return;
      await context.read<Session>().refreshAkun();
      if (!mounted) return;
      showMessage(context, 'Data tersimpan.');
      context.pop();
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _simpan = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    Widget label(String t) => Padding(
      padding: const EdgeInsets.only(top: 14, bottom: 6),
      child: Text(t, style: AppText.overline.copyWith(fontSize: 10)),
    );
    return AppPage(
      title: 'Ubah Data',
      subtitle: 'Perbarui data ananda dan wali',
      bottom: PrimaryButton(
        label: 'Simpan Perubahan',
        loading: _simpan,
        onPressed: _kirim,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          label('TANGGAL LAHIR'),
          TextField(
            enabled: false,
            controller: TextEditingController(text: a['tanggal_lahir'] ?? '-'),
            decoration: const InputDecoration(
              suffixIcon: Icon(Icons.lock_outline),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Tanggal lahir dipakai untuk login dan hanya bisa diubah sekolah.',
            style: AppText.caption,
          ),
          label('JENIS KELAMIN'),
          PilihanChips<String>(
            pilihan: const {'L': 'Laki-laki', 'P': 'Perempuan'},
            nilai: _jk,
            onChanged: (v) => setState(() => _jk = v),
          ),
          label('ALAMAT'),
          TextField(
            controller: _alamat,
            maxLength: 100,
            decoration: const InputDecoration(counterText: ''),
          ),
          label('NAMA WALI'),
          TextField(
            controller: _wali,
            maxLength: 100,
            textCapitalization: TextCapitalization.words,
            decoration: const InputDecoration(counterText: ''),
          ),
          label('NO HP'),
          TextField(
            controller: _hp,
            maxLength: 20,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(counterText: ''),
          ),
          label('KELAS'),
          TextField(
            enabled: false,
            controller: TextEditingController(text: a['kelas']),
            decoration: const InputDecoration(
              suffixIcon: Icon(Icons.lock_outline),
            ),
          ),
          const SizedBox(height: 4),
          Text('Kelas diatur oleh sekolah.', style: AppText.caption),
        ],
      ),
    );
  }
}
