import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import 'panduan_screen.dart';

/// Lembar "Ubah Kata Sandi" dari menu profil (guru dan wali).
Future<void> bukaUbahSandi(BuildContext context) => showModalBottomSheet(
  context: context,
  isScrollControlled: true,
  showDragHandle: true,
  backgroundColor: AppColors.surface,
  builder: (c) => Padding(
    padding: EdgeInsets.fromLTRB(
      20,
      0,
      20,
      20 + MediaQuery.of(c).viewInsets.bottom,
    ),
    child: SingleChildScrollView(
      child: FormUbahSandi(onBerhasil: () => Navigator.pop(c)),
    ),
  ),
);

/// Halaman wajib ganti kata sandi: tampil otomatis selama akun masih memakai sandi bawaan
/// (lihat redirect di router). Tidak bisa dilewati; hanya bisa ganti sandi atau keluar.
class GantiSandiWajibScreen extends StatelessWidget {
  const GantiSandiWajibScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final wali = context.watch<Session>().peran == Peran.wali;
    return PopScope(
      canPop: false,
      // Tanpa judul AppPage: halaman ini tidak punya tombol kembali.
      child: AppPage(
        padding: const EdgeInsets.fromLTRB(16, 24, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('Ganti Kata Sandi', style: AppText.title),
            Text('Wajib sebelum memakai aplikasi', style: AppText.bodySmall),
            const SizedBox(height: 16),
            AppCard(
              color: AppColors.brand50,
              borderColor: AppColors.brand200,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.shield_outlined, color: AppColors.brand700),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      wali
                          ? 'Akun Anda masih memakai kata sandi bawaan (tanggal lahir ananda) yang mudah ditebak. Buat kata sandi baru agar data ananda aman.'
                          : 'Akun Anda masih memakai kata sandi bawaan dari sekolah. Buat kata sandi baru yang hanya Anda ketahui.',
                      style: AppText.body,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),
            AppCard(child: FormUbahSandi(wajib: true, onBerhasil: () {})),
            const SizedBox(height: 16),
            TextButton(
              onPressed: () => context.read<Session>().logout(),
              child: const Text('Keluar'),
            ),
            const SizedBox(height: 8),
            const Center(child: TeksVersi()),
          ],
        ),
      ),
    );
  }
}

/// Formulir sandi lama + baru. Endpoint dipilih dari peran akun.
/// Setelah berhasil, profil dimuat ulang sehingga tanda `wajib_ganti_sandi` hilang.
class FormUbahSandi extends StatefulWidget {
  const FormUbahSandi({
    super.key,
    required this.onBerhasil,
    this.wajib = false,
  });

  final VoidCallback onBerhasil;
  final bool wajib;

  @override
  State<FormUbahSandi> createState() => _FormUbahSandiState();
}

class _FormUbahSandiState extends State<FormUbahSandi> {
  final _lama = TextEditingController();
  final _baru = TextEditingController();
  final _ulang = TextEditingController();
  bool _loading = false;
  bool _lihat = false;
  String? _error;

  @override
  void dispose() {
    _lama.dispose();
    _baru.dispose();
    _ulang.dispose();
    super.dispose();
  }

  Future<void> _simpan() async {
    final session = context.read<Session>();
    final wali = session.peran == Peran.wali;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await context.read<ApiClient>().post(
        wali ? '/wali/ubah-sandi' : '/guru/ubah-sandi',
        {
          'sandi_lama': _lama.text,
          'sandi_baru': _baru.text,
          'sandi_baru_confirmation': _ulang.text,
        },
      );
      await session.refreshMe();
      if (!mounted) return;
      widget.onBerhasil();
      showMessage(
        context,
        'Kata sandi diperbarui. Perangkat lain otomatis keluar.',
      );
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final wali = context.watch<Session>().peran == Peran.wali;
    // Wali yang masih bawaan: sandi lama = tanggal lahir ananda.
    final lamaTanggal = wali && widget.wajib;
    InputDecoration dekor(String label, {String? hint}) => InputDecoration(
      labelText: label,
      hintText: hint,
      suffixIcon: IconButton(
        tooltip: _lihat ? 'Sembunyikan' : 'Lihat',
        icon: Icon(
          _lihat ? Icons.visibility_off_outlined : Icons.visibility_outlined,
        ),
        onPressed: () => setState(() => _lihat = !_lihat),
      ),
    );

    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          widget.wajib ? 'Buat Kata Sandi Baru' : 'Ubah Kata Sandi',
          style: AppText.title,
        ),
        const SizedBox(height: 4),
        Text(
          'Minimal 8 karakter, berisi huruf besar, huruf kecil, dan angka.',
          style: AppText.bodySmall,
        ),
        const SizedBox(height: 16),
        TextField(
          controller: _lama,
          obscureText: !_lihat,
          keyboardType: TextInputType.text,
          decoration: dekor(
            lamaTanggal
                ? 'Tanggal lahir ananda (sandi lama)'
                : 'Kata sandi lama',
            hint: lamaTanggal ? 'YY-MM-DD, contoh 18-07-05' : null,
          ),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _baru,
          obscureText: !_lihat,
          decoration: dekor('Kata sandi baru'),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _ulang,
          obscureText: !_lihat,
          decoration: dekor('Ulangi kata sandi baru'),
        ),
        if (_error != null)
          Padding(
            padding: const EdgeInsets.only(top: 12),
            child: Text(
              _error!,
              style: AppText.bodySmall.copyWith(color: AppColors.alpa),
            ),
          ),
        const SizedBox(height: 16),
        PrimaryButton(label: 'Simpan', loading: _loading, onPressed: _simpan),
      ],
    );
  }
}
