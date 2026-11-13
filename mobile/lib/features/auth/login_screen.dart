import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _form = GlobalKey<FormState>();
  final _user = TextEditingController();
  final _pass = TextEditingController();
  Peran _peran = Peran.guru;
  bool _ingat = true;
  bool _lihat = false;
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _user.dispose();
    _pass.dispose();
    super.dispose();
  }

  Future<void> _masuk() async {
    if (!_form.currentState!.validate()) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await context.read<Session>().login(
        peran: _peran,
        username: _user.text,
        password: _pass.text,
        ingat: _ingat,
      );
      if (mounted) context.go(_peran == Peran.wali ? '/wali' : '/guru');
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final guru = _peran == Peran.guru;
    return DecoratedBox(
      decoration: const BoxDecoration(gradient: AppColors.pageGradient),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        body: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(16, 48, 16, 24),
            child: Column(
              children: [
                Image.asset('assets/images/logo.png', height: 96),
                const SizedBox(height: 20),
                Text(
                  'SDIT Darul Fikri',
                  style: AppText.title.copyWith(fontSize: 28),
                ),
                const SizedBox(height: 6),
                Text(
                  'SISTEM INFORMASI AKADEMIK',
                  style: AppText.overline.copyWith(letterSpacing: 2),
                ),
                const SizedBox(height: 24),
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.brand700.withValues(alpha: 0.08),
                        blurRadius: 24,
                        offset: const Offset(0, 8),
                      ),
                    ],
                  ),
                  child: Form(
                    key: _form,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text('Selamat Datang!', style: AppText.title),
                        const SizedBox(height: 4),
                        Text(
                          'Silakan login untuk mengakses sistem',
                          style: AppText.bodySmall,
                        ),
                        const SizedBox(height: 20),
                        _PeranTabs(
                          value: _peran,
                          onChanged: (p) => setState(() {
                            _peran = p;
                            _error = null;
                            _pass.clear();
                          }),
                        ),
                        const SizedBox(height: 20),
                        Text(
                          guru ? 'USERNAME / NIP' : 'NIS SISWA',
                          style: AppText.overline,
                        ),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: _user,
                          keyboardType: guru
                              ? TextInputType.text
                              : TextInputType.number,
                          textInputAction: TextInputAction.next,
                          autofillHints: const [AutofillHints.username],
                          decoration: InputDecoration(
                            hintText: guru
                                ? 'Masukkan username atau NIP'
                                : 'Masukkan NIS anak',
                            prefixIcon: const Icon(Icons.person_outline),
                          ),
                          validator: (v) => (v ?? '').trim().isEmpty
                              ? (guru
                                    ? 'Isi username atau NIP'
                                    : 'Isi NIS siswa')
                              : null,
                        ),
                        const SizedBox(height: 16),
                        Text('KATA SANDI', style: AppText.overline),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: _pass,
                          obscureText: !_lihat,
                          keyboardType: TextInputType.text,
                          autofillHints: guru
                              ? const [AutofillHints.password]
                              : null,
                          onFieldSubmitted: (_) => _masuk(),
                          decoration: InputDecoration(
                            hintText: guru
                                ? 'Masukkan kata sandi'
                                : 'Tanggal lahir (YY-MM-DD) atau sandi baru',
                            prefixIcon: Icon(Icons.lock_outline),
                            suffixIcon: IconButton(
                              tooltip: _lihat
                                  ? 'Sembunyikan kata sandi'
                                  : 'Lihat kata sandi',
                              icon: Icon(
                                _lihat
                                    ? Icons.visibility_off_outlined
                                    : Icons.visibility_outlined,
                              ),
                              onPressed: () => setState(() => _lihat = !_lihat),
                            ),
                          ),
                          validator: (v) => (v ?? '').trim().isEmpty
                              ? (guru
                                    ? 'Isi kata sandi'
                                    : 'Isi kata sandi atau tanggal lahir siswa')
                              : null,
                        ),
                        const SizedBox(height: 8),
                        CheckboxListTile(
                          value: _ingat,
                          onChanged: (v) => setState(() => _ingat = v ?? false),
                          title: Text('Ingat saya', style: AppText.body),
                          controlAffinity: ListTileControlAffinity.leading,
                          contentPadding: EdgeInsets.zero,
                          dense: true,
                        ),
                        if (_error != null) ...[
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: AppColors.alpaTint,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _error!,
                                  style: AppText.bodySmall.copyWith(
                                    color: AppColors.alpa,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 12),
                        ],
                        const SizedBox(height: 8),
                        PrimaryButton(
                          label: 'Masuk ke Sistem',
                          loading: _loading,
                          onPressed: _masuk,
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'Butuh bantuan login? Hubungi Tata Usaha Sekolah',
                  style: AppText.bodySmall,
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _PeranTabs extends StatelessWidget {
  const _PeranTabs({required this.value, required this.onChanged});
  final Peran value;
  final ValueChanged<Peran> onChanged;

  @override
  Widget build(BuildContext context) {
    Widget tab(Peran p, String label) {
      final aktif = value == p;
      return Expanded(
        child: Semantics(
          selected: aktif,
          button: true,
          child: GestureDetector(
            onTap: () => onChanged(p),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              height: 44,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: aktif ? AppColors.surface : Colors.transparent,
                borderRadius: BorderRadius.circular(10),
                boxShadow: aktif
                    ? [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.06),
                          blurRadius: 6,
                          offset: const Offset(0, 2),
                        ),
                      ]
                    : null,
              ),
              child: Text(
                label,
                style: AppText.label.copyWith(
                  color: aktif ? AppColors.brand700 : AppColors.inkMuted,
                ),
              ),
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: AppColors.field,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          tab(Peran.guru, 'Guru & Staf'),
          tab(Peran.wali, 'Wali Murid (Ortu)'),
        ],
      ),
    );
  }
}
