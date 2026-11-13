import 'dart:async';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

/// Percakapan konseling. [base] = '/wali/konseling' atau '/guru/konseling'.
/// [sayaPengirim] = 'wali' atau 'guru' untuk menentukan gelembung kanan.
class ChatScreen extends StatefulWidget {
  const ChatScreen({
    super.key,
    required this.base,
    required this.id,
    required this.sayaPengirim,
  });
  final String base;
  final int id;
  final String sayaPengirim;

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  Map<String, dynamic>? _d;
  String? _error;
  final _isi = TextEditingController();
  final _scroll = ScrollController();
  bool _kirim = false;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _load();
    // Perbarui otomatis tiap 15 detik selama layar terbuka.
    _timer = Timer.periodic(
      const Duration(seconds: 15),
      (_) => _load(diam: true),
    );
  }

  @override
  void dispose() {
    _timer?.cancel();
    _isi.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _load({bool diam = false}) async {
    try {
      final d = await context.read<ApiClient>().get(
        '${widget.base}/${widget.id}',
      );
      if (!mounted) return;
      final baru =
          (d['pesan'] as List).length !=
          ((_d?['pesan'] as List?)?.length ?? -1);
      setState(() => _d = d);
      if (baru)
        WidgetsBinding.instance.addPostFrameCallback(
          (_) => _scroll.hasClients
              ? _scroll.jumpTo(_scroll.position.maxScrollExtent)
              : null,
        );
    } on ApiException catch (e) {
      if (!diam && mounted) setState(() => _error = e.message);
    }
  }

  String? _foto;

  Future<void> _pilihFoto() async {
    final f = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      maxWidth: 1600,
      imageQuality: 80,
    );
    if (f != null) setState(() => _foto = f.path);
  }

  Future<void> _kirimPesan() async {
    final teks = _isi.text.trim();
    if (teks.isEmpty && _foto == null) return;
    setState(() => _kirim = true);
    try {
      final api = context.read<ApiClient>();
      if (_foto != null) {
        await api.upload(
          '${widget.base}/${widget.id}',
          {if (teks.isNotEmpty) 'isi': teks},
          file: _foto,
          fileField: 'foto',
        );
      } else {
        await api.post('${widget.base}/${widget.id}', {'isi': teks});
      }
      _isi.clear();
      _foto = null;
      await _load();
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _kirim = false);
    }
  }

  /// Pesan milik saya: wali = semua pesan wali; guru = hanya yang ditulis akun ini.
  bool _milik(Map<String, dynamic> m) {
    if (m['pengirim'] != widget.sayaPengirim) return false;
    if (widget.sayaPengirim == 'wali') return true;
    return m['guru_id'] == context.read<Session>().akun['id'];
  }

  Future<void> _menuPesan(Map<String, dynamic> m) async {
    final aksi = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      backgroundColor: AppColors.surface,
      builder: (c) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.edit_outlined),
              title: const Text('Ubah pesan'),
              onTap: () => Navigator.pop(c, 'ubah'),
            ),
            ListTile(
              leading: const Icon(Icons.delete_outline, color: AppColors.alpa),
              title: const Text(
                'Hapus pesan',
                style: TextStyle(color: AppColors.alpa),
              ),
              onTap: () => Navigator.pop(c, 'hapus'),
            ),
          ],
        ),
      ),
    );
    if (aksi == null || !mounted) return;
    final api = context.read<ApiClient>();
    final path = '${widget.base}/${widget.id}/pesan/${m['id']}';
    try {
      if (aksi == 'hapus') {
        final ok = await showDialog<bool>(
          context: context,
          builder: (c) => AlertDialog(
            title: const Text('Hapus pesan?'),
            content: const Text('Pesan hilang untuk kedua pihak.'),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(c, false),
                child: const Text('Batal'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(c, true),
                child: const Text(
                  'Hapus',
                  style: TextStyle(color: AppColors.alpa),
                ),
              ),
            ],
          ),
        );
        if (ok != true) return;
        await api.delete(path);
      } else {
        final ctrl = TextEditingController(text: m['isi'] ?? '');
        final baru = await showDialog<String>(
          context: context,
          builder: (c) => AlertDialog(
            title: const Text('Ubah pesan'),
            content: TextField(
              controller: ctrl,
              minLines: 2,
              maxLines: 6,
              maxLength: 2000,
              autofocus: true,
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(c),
                child: const Text('Batal'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(c, ctrl.text.trim()),
                child: const Text('Simpan'),
              ),
            ],
          ),
        );
        ctrl.dispose();
        if (baru == null) return;
        await api.put(path, {'isi': baru});
      }
      await _load();
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = _d;
    final judul = d == null
        ? 'Konseling'
        : (widget.sayaPengirim == 'wali'
              ? (d['pengampu'] ?? 'Guru Pengampu')
              : 'Wali ${d['siswa']}');
    final sub = d == null ? null : '${d['topik']} · ${d['kelas'] ?? ''}';
    return AppPage(
      title: judul,
      subtitle: sub,
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (_foto != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  const Icon(Icons.image_outlined, color: AppColors.brand700),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text('Foto siap dikirim', style: AppText.bodySmall),
                  ),
                  TextButton(
                    onPressed: () => setState(() => _foto = null),
                    child: const Text('Batal'),
                  ),
                ],
              ),
            ),
          Row(
            children: [
              IconButton(
                tooltip: 'Lampirkan foto',
                onPressed: _kirim ? null : _pilihFoto,
                icon: const Icon(Icons.image_outlined),
                style: IconButton.styleFrom(minimumSize: const Size(48, 48)),
              ),
              Expanded(
                child: TextField(
                  controller: _isi,
                  minLines: 1,
                  maxLines: 4,
                  maxLength: 2000,
                  textCapitalization: TextCapitalization.sentences,
                  decoration: InputDecoration(
                    hintText: 'Tulis pesan…',
                    counterText: '',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(24),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              IconButton.filled(
                tooltip: 'Kirim',
                onPressed: _kirim ? null : _kirimPesan,
                style: IconButton.styleFrom(
                  backgroundColor: AppColors.brand700,
                  minimumSize: const Size(48, 48),
                ),
                icon: _kirim
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.send_rounded, color: Colors.white),
              ),
            ],
          ),
        ],
      ),
      child: d == null
          ? (_error == null
                ? const Center(child: CircularProgressIndicator())
                : ErrorPanel(message: _error!, onRetry: _load))
          : ListView.builder(
              controller: _scroll,
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
              itemCount: (d['pesan'] as List).length,
              itemBuilder: (context, i) {
                final m = Map<String, dynamic>.from((d['pesan'] as List)[i]);
                final saya = m['pengirim'] == widget.sayaPengirim;
                final milik = _milik(m);
                final waktu = DateTime.parse(m['waktu']).toLocal();
                final sebelum = i == 0
                    ? null
                    : DateTime.parse((d['pesan'] as List)[i - 1]['waktu'])
                          .toLocal();
                final hariBaru =
                    sebelum == null || !DateUtils.isSameDay(sebelum, waktu);
                return Column(
                  children: [
                    if (hariBaru)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.line,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            DateUtils.isSameDay(waktu, DateTime.now())
                                ? 'Hari ini'
                                : DateFormat('d MMM y', 'id_ID').format(waktu),
                            style: AppText.caption,
                          ),
                        ),
                      ),
                    Align(
                      alignment: saya
                          ? Alignment.centerRight
                          : Alignment.centerLeft,
                      child: GestureDetector(
                        onLongPress: milik ? () => _menuPesan(m) : null,
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 10),
                          constraints: BoxConstraints(
                            maxWidth: MediaQuery.of(context).size.width * 0.78,
                          ),
                          padding: const EdgeInsets.fromLTRB(14, 10, 14, 8),
                          decoration: BoxDecoration(
                            color: saya
                                ? AppColors.brand700
                                : AppColors.surface,
                            border: saya
                                ? null
                                : Border.all(color: AppColors.line),
                            borderRadius: BorderRadius.circular(16).copyWith(
                              bottomRight: saya
                                  ? const Radius.circular(4)
                                  : null,
                              bottomLeft: saya
                                  ? null
                                  : const Radius.circular(4),
                            ),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (!saya)
                                Text(
                                  m['nama'] ?? '',
                                  style: AppText.captionBold.copyWith(
                                    color: AppColors.brand700,
                                  ),
                                ),
                              if (m['ada_foto'] == true)
                                Padding(
                                  padding: const EdgeInsets.only(bottom: 6),
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(10),
                                    child: Image.network(
                                      context.read<ApiClient>().url(
                                        '${widget.base}/${widget.id}/pesan/${m['id']}/foto',
                                      ),
                                      headers: context
                                          .read<ApiClient>()
                                          .authHeaders,
                                      width: 220,
                                      fit: BoxFit.cover,
                                      errorBuilder: (_, _, _) => const SizedBox(
                                        width: 220,
                                        height: 80,
                                        child: Center(
                                          child: Text(
                                            'Foto tidak dapat dimuat',
                                          ),
                                        ),
                                      ),
                                      loadingBuilder: (_, child, p) => p == null
                                          ? child
                                          : const SizedBox(
                                              width: 220,
                                              height: 120,
                                              child: Center(
                                                child:
                                                    CircularProgressIndicator(
                                                      strokeWidth: 2,
                                                    ),
                                              ),
                                            ),
                                    ),
                                  ),
                                ),
                              if ((m['isi'] as String?)?.isNotEmpty ?? false)
                                Text(
                                  m['isi'],
                                  style: AppText.body.copyWith(
                                    color: saya ? Colors.white : AppColors.ink,
                                  ),
                                ),
                              const SizedBox(height: 4),
                              Align(
                                alignment: Alignment.bottomRight,
                                child: Text(
                                  '${m['diedit'] == true ? 'diedit · ' : ''}${DateFormat('HH:mm').format(waktu)}',
                                  style: AppText.caption.copyWith(
                                    fontSize: 11,
                                    color: saya
                                        ? AppColors.brand100
                                        : AppColors.inkFaint,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ],
                );
              },
            ),
    );
  }
}
