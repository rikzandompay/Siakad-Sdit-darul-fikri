import 'package:dio/dio.dart';
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
      await context.read<Session>().login(peran: _peran, username: _user.text, password: _pass.text, ingat: _ingat);
      if (mounted) context.go(_peran == Peran.wali ? '/wali' : '/guru');
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _showServerDialog() async {
    final session = context.read<Session>();
    final messenger = ScaffoldMessenger.of(context);
    final ctrl = TextEditingController(text: session.api.baseUrl);
    await showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Pengaturan Server API'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Ubah alamat server backend agar HP dapat terhubung ke laptop atau server live.',
                style: TextStyle(fontSize: 13, color: AppColors.inkMuted),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: ctrl,
                decoration: const InputDecoration(
                  labelText: 'URL API Base',
                  hintText: 'http://10.61.248.45:8000/api/v1',
                  border: OutlineInputBorder(),
                ),
              ),
              const SizedBox(height: 12),
              const Text('Preset Cepat:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
              const SizedBox(height: 6),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  ActionChip(
                    avatar: const Icon(Icons.cloud_done, size: 14, color: AppColors.ok700),
                    label: const Text('Online Tunnel (Bisa 4G/Semua Wi-Fi)', style: TextStyle(fontSize: 11)),
                    onPressed: () => ctrl.text = 'https://drunk-cutting-fix-doll.trycloudflare.com/api/v1',
                  ),
                  ActionChip(
                    avatar: const Icon(Icons.wifi, size: 14),
                    label: const Text('Wi-Fi Laptop (Lokal)', style: TextStyle(fontSize: 11)),
                    onPressed: () => ctrl.text = 'http://10.61.248.45:8000/api/v1',
                  ),
                  ActionChip(
                    avatar: const Icon(Icons.android, size: 14),
                    label: const Text('Emulator (10.0.2.2)', style: TextStyle(fontSize: 11)),
                    onPressed: () => ctrl.text = 'http://10.0.2.2:8000/api/v1',
                  ),
                ],
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () async {
              final newUrl = ctrl.text.trim();
              if (newUrl.isNotEmpty) {
                await session.setCustomBaseUrl(newUrl);
                if (ctx.mounted) Navigator.pop(ctx);
                if (mounted) {
                  messenger.showSnackBar(
                    SnackBar(content: Text('Server diubah ke: $newUrl')),
                  );
                }
              }
            },
            child: const Text('Simpan'),
          ),
        ],
      ),
    );
  }

  Future<void> _aturServer() async {
    final api = context.read<ApiClient>();
    final ctrl = TextEditingController(text: api.baseUrl);
    String? hasil;
    bool ok = false;
    bool uji = false;
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: AppColors.surface,
      builder: (c) => StatefulBuilder(
        builder: (c, set) => Padding(
          padding: EdgeInsets.fromLTRB(20, 0, 20, 20 + MediaQuery.of(c).viewInsets.bottom),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Text('Alamat Server', style: AppText.title),
            const SizedBox(height: 4),
            Text('Isi alamat server SIAKAD, mis. 192.168.1.10:8000 (jaringan sekolah) atau alamat domain.', style: AppText.bodySmall),
            const SizedBox(height: 12),
            TextField(controller: ctrl, keyboardType: TextInputType.url, autocorrect: false, decoration: const InputDecoration(hintText: 'http://192.168.1.10:8000')),
            const SizedBox(height: 10),
            if (hasil != null)
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: ok ? AppColors.ok100 : AppColors.alpaTint, borderRadius: BorderRadius.circular(10)),
                child: Text(hasil!, style: AppText.bodySmall.copyWith(color: ok ? AppColors.ok700 : AppColors.alpa)),
              ),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(
                child: SecondaryButton(
                  label: uji ? 'Menguji…' : 'Tes koneksi',
                  onPressed: uji
                      ? null
                      : () async {
                          final url = ApiClient.normalisasi(ctrl.text);
                          set(() {
                            uji = true;
                            hasil = null;
                          });
                          try {
                            final dio = Dio(BaseOptions(baseUrl: url, connectTimeout: const Duration(seconds: 6), receiveTimeout: const Duration(seconds: 6)));
                            final r = await dio.get('/ping');
                            ok = r.statusCode == 200;
                            hasil = ok ? 'Terhubung ke $url' : 'Server menjawab, tetapi kode ${r.statusCode}.';
                          } on DioException catch (e) {
                            ok = false;
                            final u = e.requestOptions.uri;
                            hasil = e.response != null
                                ? 'Server ${u.host}:${u.port} menjawab kode ${e.response!.statusCode}. Alamat atau versi server mungkin salah.'
                                : switch (e.type) {
                                    DioExceptionType.connectionTimeout => 'Tidak ada jawaban dari ${u.host}:${u.port}. HP dan server harus satu Wi-Fi (bukan data seluler), dan router tidak boleh memblokir antar-perangkat.',
                                    DioExceptionType.connectionError => 'Tidak dapat terhubung ke ${u.host}:${u.port}. Cek alamat/port dan pastikan server berjalan (php artisan serve --host=0.0.0.0).',
                                    _ => 'Gagal: ${e.type.name}',
                                  };
                          }
                          set(() => uji = false);
                        },
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: PrimaryButton(
                  label: 'Simpan',
                  onPressed: () async {
                    final url = ApiClient.normalisasi(ctrl.text);
                    if (url.isEmpty) return;
                    try {
                      await context.read<Session>().setCustomBaseUrl(url);
                    } catch (_) {
                      api.baseUrl = url;
                    }
                    if (c.mounted) Navigator.pop(c);
                  },
                ),
              ),
            ]),
          ]),
        ),
      ),
    );
    ctrl.dispose();
    if (mounted) setState(() => _error = null);
  }

  @override
  Widget build(BuildContext context) {
    final session = context.watch<Session>();
    final guru = _peran == Peran.guru;
    return DecoratedBox(
      decoration: const BoxDecoration(gradient: AppColors.pageGradient),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        appBar: AppBar(
          backgroundColor: Colors.transparent,
          elevation: 0,
          actions: [
            IconButton(
              tooltip: 'Pengaturan Server API',
              icon: const Icon(Icons.dns_outlined, color: AppColors.brand700),
              onPressed: _showServerDialog,
            ),
          ],
        ),
        body: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
            child: Column(
              children: [
                Image.asset('assets/images/logo.png', height: 96),
                const SizedBox(height: 20),
                Text('SDIT Darul Fikri', style: AppText.title.copyWith(fontSize: 28)),
                const SizedBox(height: 6),
                Text('SISTEM INFORMASI AKADEMIK', style: AppText.overline.copyWith(letterSpacing: 2)),
                const SizedBox(height: 24),
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(color: AppColors.surface, borderRadius: BorderRadius.circular(24), boxShadow: [BoxShadow(color: AppColors.brand700.withValues(alpha: 0.08), blurRadius: 24, offset: const Offset(0, 8))]),
                  child: Form(
                    key: _form,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text('Selamat Datang!', style: AppText.title),
                        const SizedBox(height: 4),
                        Text('Silakan login untuk mengakses sistem', style: AppText.bodySmall),
                        const SizedBox(height: 20),
                        _PeranTabs(value: _peran, onChanged: (p) => setState(() {
                              _peran = p;
                              _error = null;
                              _pass.clear();
                            })),
                        const SizedBox(height: 20),
                        Text(guru ? 'USERNAME / NIP' : 'NIS SISWA', style: AppText.overline),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: _user,
                          keyboardType: guru ? TextInputType.text : TextInputType.number,
                          textInputAction: TextInputAction.next,
                          autofillHints: const [AutofillHints.username],
                          decoration: InputDecoration(hintText: guru ? 'Masukkan username atau NIP' : 'Masukkan NIS anak', prefixIcon: const Icon(Icons.person_outline)),
                          validator: (v) => (v ?? '').trim().isEmpty ? (guru ? 'Isi username atau NIP' : 'Isi NIS siswa') : null,
                        ),
                        const SizedBox(height: 16),
                        Text(guru ? 'KATA SANDI' : 'NAMA LENGKAP WALI', style: AppText.overline),
                        const SizedBox(height: 8),
                        TextFormField(
                          controller: _pass,
                          obscureText: guru && !_lihat,
                          textCapitalization: guru ? TextCapitalization.none : TextCapitalization.words,
                          autofillHints: guru ? const [AutofillHints.password] : null,
                          onFieldSubmitted: (_) => _masuk(),
                          decoration: InputDecoration(
                            hintText: guru ? 'Masukkan kata sandi' : 'Sesuai data di sekolah',
                            prefixIcon: Icon(guru ? Icons.lock_outline : Icons.badge_outlined),
                            suffixIcon: guru
                                ? IconButton(
                                    tooltip: _lihat ? 'Sembunyikan kata sandi' : 'Lihat kata sandi',
                                    icon: Icon(_lihat ? Icons.visibility_off_outlined : Icons.visibility_outlined),
                                    onPressed: () => setState(() => _lihat = !_lihat),
                                  )
                                : null,
                          ),
                          validator: (v) => (v ?? '').trim().isEmpty ? (guru ? 'Isi kata sandi' : 'Isi nama lengkap wali') : null,
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
                            decoration: BoxDecoration(color: AppColors.alpaTint, borderRadius: BorderRadius.circular(10)),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(_error!, style: AppText.bodySmall.copyWith(color: AppColors.alpa)),
                                const SizedBox(height: 8),
                                InkWell(
                                  onTap: _showServerDialog,
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      const Icon(Icons.settings, size: 14, color: AppColors.alpa),
                                      const SizedBox(width: 4),
                                      Text(
                                        'Ganti / Periksa Alamat Server',
                                        style: AppText.captionBold.copyWith(
                                          color: AppColors.alpa,
                                          decoration: TextDecoration.underline,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 12),
                        ],
                        const SizedBox(height: 8),
                        PrimaryButton(label: 'Masuk ke Sistem', loading: _loading, onPressed: _masuk),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                InkWell(
                  onTap: _showServerDialog,
                  borderRadius: BorderRadius.circular(20),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: AppColors.brand200),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.04),
                          blurRadius: 4,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.dns_outlined, size: 15, color: AppColors.brand700),
                        const SizedBox(width: 6),
                        Text(
                          'Server: ${session.api.baseUrl.replaceFirst(RegExp(r'^https?://'), '').replaceFirst('/api/v1', '')}',
                          style: AppText.caption.copyWith(color: AppColors.brand700, fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(width: 4),
                        const Icon(Icons.arrow_drop_down, size: 16, color: AppColors.brand700),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text('Butuh bantuan login? Hubungi Tata Usaha Sekolah', style: AppText.bodySmall, textAlign: TextAlign.center),
                TextButton.icon(
                  onPressed: _aturServer,
                  icon: const Icon(Icons.dns_outlined, size: 18),
                  label: const Text('Atur alamat server'),
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
                boxShadow: aktif ? [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 6, offset: const Offset(0, 2))] : null,
              ),
              child: Text(label, style: AppText.label.copyWith(color: aktif ? AppColors.brand700 : AppColors.inkMuted)),
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(color: AppColors.field, borderRadius: BorderRadius.circular(12)),
      child: Row(children: [tab(Peran.guru, 'Guru & Staf'), tab(Peran.wali, 'Wali Murid (Ortu)')]),
    );
  }
}
