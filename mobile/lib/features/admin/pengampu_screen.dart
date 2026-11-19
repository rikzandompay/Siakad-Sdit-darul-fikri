import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/api_client.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

class PengampuScreen extends StatefulWidget {
  const PengampuScreen({super.key});
  @override
  State<PengampuScreen> createState() => _PengampuScreenState();
}

class _PengampuScreenState extends State<PengampuScreen> {
  Map<String, dynamic>? _d;
  final Map<int, int?> _pilihan = {};
  String? _error;
  bool _simpan = false;

  @override
  void initState() {
    super.initState();
    context
        .read<ApiClient>()
        .get('/admin/konseling-pengampu')
        .then((d) {
          if (!mounted) return;
          setState(() {
            _d = d;
            for (final k in (d['kelas'] as List)) {
              _pilihan[k['id'] as int] = k['pengampu_id'] as int?;
            }
          });
        })
        .catchError((e) {
          if (mounted) setState(() => _error = e.toString());
        });
  }

  Future<void> _kirim() async {
    setState(() => _simpan = true);
    try {
      await context.read<ApiClient>().put('/admin/konseling-pengampu', {
        'pengampu': {for (final e in _pilihan.entries) '${e.key}': e.value},
      });
      if (mounted) {
        showMessage(context, 'Pengampu konseling disimpan.');
        context.pop();
      }
    } on ApiException catch (e) {
      if (mounted) showMessage(context, e.message, error: true);
    } finally {
      if (mounted) setState(() => _simpan = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = _d;
    final guru = {
      for (final g in (d?['guru'] as List? ?? []))
        g['id'] as int: g['nama'] as String,
    };
    return AppPage(
      title: 'Pengampu Konseling',
      subtitle: 'Guru yang membalas konseling wali per kelas',
      scroll: false,
      padding: EdgeInsets.zero,
      bottom: d == null
          ? null
          : PrimaryButton(
              label: 'Simpan',
              loading: _simpan,
              onPressed: _simpan ? null : _kirim,
            ),
      child: _error != null
          ? ErrorPanel(message: _error!)
          : d == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
              children: [
                for (final k in (d['kelas'] as List).cast<Map>())
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: AppCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(k['nama'], style: AppText.label),
                          Text(
                            '${k['jumlah_siswa']} siswa · Wali kelas ${k['wali_kelas'] ?? 'belum diatur'}',
                            style: AppText.caption,
                          ),
                          const SizedBox(height: 10),
                          DropdownButtonFormField<int?>(
                            initialValue: guru.containsKey(_pilihan[k['id']])
                                ? _pilihan[k['id']]
                                : null,
                            isExpanded: true,
                            hint: const Text('Belum ada pengampu'),
                            items: [
                              const DropdownMenuItem<int?>(
                                value: null,
                                child: Text('Belum ada pengampu'),
                              ),
                              for (final e in guru.entries)
                                DropdownMenuItem<int?>(
                                  value: e.key,
                                  child: Text(
                                    e.value,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                            ],
                            onChanged: (v) =>
                                setState(() => _pilihan[k['id'] as int] = v),
                            decoration: const InputDecoration(
                              contentPadding: EdgeInsets.symmetric(
                                horizontal: 14,
                                vertical: 12,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
    );
  }
}

