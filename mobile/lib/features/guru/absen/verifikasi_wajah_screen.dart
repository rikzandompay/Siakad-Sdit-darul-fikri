import 'dart:async';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/api_client.dart';
import '../../../core/format.dart';
import '../../../core/perangkat.dart';
import '../../../core/theme.dart';
import '../../../widgets/common.dart';
import '../../face/face_capture.dart';
import '../../face/face_service.dart';

/// Presensi guru dalam satu layar: kamera (cek kehidupan + wajah) dan GPS berjalan bersamaan.
/// Lokasi dilacak terus selama kamera terbuka; hasilnya tampil di layar yang sama (bingkai hijau
/// berhasil, merah wajah tidak sesuai) tanpa halaman verifikasi lokasi atau halaman gagal.
/// Pencocokan wajah dan pemeriksaan lokasi tetap diputuskan server.
class VerifikasiWajahScreen extends StatefulWidget {
  const VerifikasiWajahScreen({super.key, required this.aksi});
  final String aksi; // masuk | pulang

  @override
  State<VerifikasiWajahScreen> createState() => _VerifikasiWajahScreenState();
}

enum _Lokasi { mencari, kurangAkurat, diDalam, diLuar, palsu, gagal }

class _VerifikasiWajahScreenState extends State<VerifikasiWajahScreen> {
  static const _maksPercobaan = 3;

  // ── Lokasi ──
  StreamSubscription<Position>? _sub;
  _Lokasi _lokasi = _Lokasi.mencari;
  Position? _posisiSah; // pembacaan terakhir yang akurat dan sudah dicek server
  int? _jarak;
  double? _akurasi;
  int _maksAkurasi = 50;
  String? _pesanLokasi;
  bool _palsu = false;
  bool _cekBerjalan = false;
  DateTime? _cekTerakhir;
  String _namaSekolah = 'sekolah';
  DateTime? _dalamPada; // terakhir kali pembacaan GPS dinyatakan di dalam area

  // ── Wajah & hasil ──
  Key _captureKey = UniqueKey();
  HasilWajah _hasilWajah = HasilWajah.belum;
  String? _pesanProses;
  int _percobaan = 0;
  bool _selesai = false;
  Map<String, dynamic>? _berhasil;
  String? _pesanGagal;
  bool _bisaUlang = false;

  /// Wajah mirip guru lain: ambil lebih banyak foto untuk memastikan (bukan langsung gagal).
  bool _tambahan = false;

  @override
  void initState() {
    super.initState();
    _mulaiLokasi();
  }

  @override
  void dispose() {
    _sub?.cancel();
    super.dispose();
  }

  // ── Pelacakan lokasi ─────────────────────────────────────────────

  Future<void> _mulaiLokasi() async {
    try {
      if (!await Geolocator.isLocationServiceEnabled())
        return _lokasiGagal(
          'GPS tidak aktif. Nyalakan lokasi di HP lalu coba lagi.',
        );
      var izin = await Geolocator.checkPermission();
      if (izin == LocationPermission.denied)
        izin = await Geolocator.requestPermission();
      if (izin == LocationPermission.denied ||
          izin == LocationPermission.deniedForever) {
        return _lokasiGagal(
          'Izin lokasi ditolak. Aktifkan izin lokasi untuk MyDarfik di pengaturan HP.',
        );
      }
      // Tanda lokasi palsu hanya menempel pada sebagian pembacaan: periksa juga posisi tersimpan.
      if ((await Geolocator.getLastKnownPosition())?.isMocked ?? false)
        _palsu = true;
      if (_palsu) {
        await _laporPalsu();
        return;
      }
      _sub =
          Geolocator.getPositionStream(
            locationSettings: const LocationSettings(
              accuracy: LocationAccuracy.best,
              distanceFilter: 0,
            ),
          ).listen(
            _posisiBaru,
            onError: (_) => _lokasiGagal(
              'Lokasi tidak dapat dibaca. Pastikan GPS aktif dan coba di tempat terbuka.',
            ),
          );
    } catch (_) {
      _lokasiGagal(
        'Lokasi tidak dapat dibaca. Pastikan GPS aktif dan coba di tempat terbuka.',
      );
    }
  }

  void _posisiBaru(Position p) {
    if (!mounted || _selesai) return;
    if (p.isMocked) {
      _palsu = true;
      _laporPalsu();
      return;
    }
    setState(() => _akurasi = p.accuracy);
    if (p.accuracy > _maksAkurasi) {
      if (_posisiSah == null) setState(() => _lokasi = _Lokasi.kurangAkurat);
      return;
    }
    // Cek ke server paling sering tiap 4 detik.
    final baru =
        _cekTerakhir == null ||
        DateTime.now().difference(_cekTerakhir!) > const Duration(seconds: 4);
    if (!_cekBerjalan && baru) _cekServer(p);
  }

  Future<void> _cekServer(Position p) async {
    _cekBerjalan = true;
    _cekTerakhir = DateTime.now();
    try {
      final res = await context.read<ApiClient>().post(
        '/guru/absen/cek-lokasi',
        {
          'latitude': p.latitude,
          'longitude': p.longitude,
          'akurasi': p.accuracy,
          'mock': false,
        },
      );
      if (!mounted || _palsu) return;
      final dalam = res['di_dalam_area'] == true;
      // Satu pembacaan meleset (umum di dalam gedung) tidak langsung membatalkan posisi di dalam
      // area yang baru saja terbaca; server memeriksa ulang titik itu saat absen dikirim.
      final masihDalam =
          !dalam &&
          _dalamPada != null &&
          DateTime.now().difference(_dalamPada!) < const Duration(seconds: 60);
      setState(() {
        _namaSekolah =
            (res['sekolah'] as Map?)?['nama'] as String? ?? _namaSekolah;
        _maksAkurasi =
            (res['maks_akurasi_meter'] as num?)?.toInt() ?? _maksAkurasi;
        if (masihDalam) return;
        _posisiSah = p;
        _jarak = (res['jarak_meter'] as num?)?.round();
        if (dalam) _dalamPada = DateTime.now();
        _lokasi = dalam ? _Lokasi.diDalam : _Lokasi.diLuar;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.kode == 'lokasi_palsu' || e.kode == 'lokasi_palsu_diblokir') {
        _tandaiPalsu(e.message);
      } else if (e.status != null && e.status! < 500 && e.status != 429) {
        _lokasiGagal(e.message);
      }
      // Gangguan jaringan/server: biarkan pembacaan berikutnya mencoba lagi.
    } finally {
      _cekBerjalan = false;
    }
  }

  /// Lokasi palsu: kirim ke server (ditolak, absen hari ini diblokir, kepala sekolah diberi tahu).
  Future<void> _laporPalsu() async {
    final api = context.read<ApiClient>();
    _sub?.cancel();
    if (_lokasi == _Lokasi.palsu) return;
    setState(() => _lokasi = _Lokasi.palsu);
    var pesan =
        'Terdeteksi aplikasi lokasi palsu. Absen ditolak dan kepala sekolah sudah diberi tahu.';
    try {
      final p = await Geolocator.getLastKnownPosition();
      await api.post('/guru/absen/cek-lokasi', {
        'latitude': p?.latitude ?? 0,
        'longitude': p?.longitude ?? 0,
        'akurasi': p?.accuracy ?? 0,
        'mock': true,
      });
    } on ApiException catch (e) {
      if (e.kode == 'lokasi_palsu' || e.kode == 'lokasi_palsu_diblokir')
        pesan = e.message;
    } catch (_) {}
    _tandaiPalsu(pesan);
  }

  void _tandaiPalsu(String pesan) {
    _sub?.cancel();
    if (!mounted) return;
    setState(() {
      _palsu = true;
      _lokasi = _Lokasi.palsu;
      _pesanLokasi = pesan;
      _gagal(pesan, ulang: false);
    });
  }

  void _lokasiGagal(String pesan) {
    if (!mounted) return;
    setState(() {
      _lokasi = _Lokasi.gagal;
      _pesanLokasi = pesan;
    });
  }

  /// Tunggu sampai lokasi punya hasil pasti (maks 30 detik) sebelum absen dikirim.
  Future<void> _tungguLokasi() async {
    final batas = DateTime.now().add(const Duration(seconds: 30));
    while (mounted &&
        (_lokasi == _Lokasi.mencari || _lokasi == _Lokasi.kurangAkurat) &&
        DateTime.now().isBefore(batas)) {
      await Future.delayed(const Duration(milliseconds: 300));
    }
  }

  // ── Wajah dan pengiriman absen ───────────────────────────────────

  Future<void> _fotoSiap(List<String> foto) async {
    final api = context.read<ApiClient>();
    try {
      final embeddings = <List<double>>[];
      for (final path in foto) {
        try {
          embeddings.add(await FaceService.instance.embeddingDariFoto(path));
        } catch (_) {}
      }
      if (embeddings.isEmpty)
        throw FaceException(
          'Wajah tidak terdeteksi di foto. Coba lagi dengan wajah di dalam bingkai.',
        );
      if (_tambahan && embeddings.length < 3)
        throw FaceException(
          'Wajah kurang jelas di foto verifikasi tambahan. Tahan HP tetap diam lalu coba lagi.',
        );

      // Semua tangkapan dikirim; server merata-ratakannya lalu membandingkan dengan acuan dari
      // foto profil (acuan dibuat dan disimpan di server, tidak pernah dikirim ke HP).
      final emb = embeddings.first;
      final lainnya = embeddings.skip(1).toList();

      if (_lokasi == _Lokasi.mencari || _lokasi == _Lokasi.kurangAkurat) {
        setState(() => _pesanProses = 'Menunggu lokasi GPS…');
        await _tungguLokasi();
        if (!mounted) return;
      }
      switch (_lokasi) {
        case _Lokasi.palsu:
          return;
        case _Lokasi.diLuar:
          return setState(
            () => _gagal(
              'Anda berada di luar area sekolah (${Fmt.jarak(_jarak ?? 0)}). Dekati sekolah lalu coba lagi.',
            ),
          );
        case _Lokasi.gagal:
          return setState(
            () => _gagal(_pesanLokasi ?? 'Lokasi tidak dapat dibaca.'),
          );
        case _Lokasi.mencari || _Lokasi.kurangAkurat:
          return setState(
            () => _gagal(
              _akurasi == null
                  ? 'Lokasi GPS belum terbaca. Coba di tempat terbuka lalu ulangi.'
                  : 'Sinyal GPS kurang akurat (±${_akurasi!.round()} m, maksimal $_maksAkurasi m). Coba di tempat terbuka lalu ulangi.',
            ),
          );
        case _Lokasi.diDalam:
          break;
      }

      setState(() => _pesanProses = 'Mencocokkan wajah…');
      _percobaan++;
      final p = _posisiSah!;
      final perangkat = await Perangkat.ambil();
      final res = await api.post('/guru/absen/${widget.aksi}', {
        'latitude': p.latitude,
        'longitude': p.longitude,
        'akurasi': p.accuracy,
        'mock': _palsu,
        'embedding': emb,
        if (lainnya.length >= 2) 'embeddings': lainnya,
        // Versi cara memotong wajah; harus sama dengan pembuat acuan di server.
        'pipeline_wajah': 2,
        'liveness': true,
        if (_tambahan) 'verifikasi_tambahan': true,
        // Satu guru satu perangkat: server menolak absen dari HP yang belum disetujui.
        'perangkat_id': perangkat.id,
        'perangkat_nama': perangkat.nama,
        'perangkat_platform': perangkat.platform,
        'perangkat_versi_os': perangkat.versiOs,
      });
      if (!mounted) return;
      _sub?.cancel();
      setState(() {
        _selesai = true;
        _hasilWajah = HasilWajah.cocok;
        _berhasil = res;
      });
    } on FaceException catch (e) {
      if (mounted) setState(() => _gagal(e.message));
    } on ApiException catch (e) {
      if (!mounted) return;
      final skor = (e.data['skor_wajah'] as num?)?.round();
      final min = (e.data['min_skor_wajah'] as num?)?.round();
      setState(() {
        switch (e.kode) {
          case 'verifikasi_tambahan' when !_tambahan:
            // Bukan kegagalan: ambil ulang beberapa foto untuk memastikan wajah ini milik akun ini.
            _percobaan--;
            _tambahan = true;
            _pesanProses = null;
            _captureKey = UniqueKey();
          case 'wajah_tidak_cocok':
            _hasilWajah = HasilWajah.tidakCocok;
            _gagal(
              skor == null || min == null
                  ? e.message
                  : 'Wajah tidak cocok dengan foto profil ($skor%, minimal $min%). Pastikan wajah terang, menghadap kamera, tanpa masker atau kacamata hitam.',
            );
          case 'wajah_mirip_guru_lain' || 'wajah_kembar_terdeteksi':
            _hasilWajah = HasilWajah.tidakCocok;
            _gagal(e.message);
          case 'di_luar_area':
            _lokasi = _Lokasi.diLuar;
            _jarak = (e.data['jarak_meter'] as num?)?.round() ?? _jarak;
            _gagal(e.message);
          case 'lokasi_palsu' || 'lokasi_palsu_diblokir':
            _tandaiPalsu(e.message);
          case 'sudah_absen' ||
              'lewat_batas' ||
              'belum_masuk' ||
              'perangkat_menunggu' ||
              'perangkat_ditolak' ||
              'perangkat_lain':
            _gagal(e.message, ulang: false);
          default:
            _gagal(e.message);
        }
      });
    }
  }

  /// Tampilkan kegagalan di layar yang sama. [ulang] = boleh coba lagi (dibatasi untuk wajah).
  void _gagal(String pesan, {bool ulang = true}) {
    final habis =
        _hasilWajah == HasilWajah.tidakCocok && _percobaan >= _maksPercobaan;
    _selesai = true;
    _pesanGagal = habis
        ? '$pesan\n\nPercobaan sudah $_percobaan kali. Hubungi kepala sekolah atau Tata Usaha untuk absen manual.'
        : pesan;
    _bisaUlang = ulang && !habis && !_palsu;
  }

  void _cobaLagi() {
    setState(() {
      _selesai = false;
      _hasilWajah = HasilWajah.belum;
      _pesanGagal = null;
      _pesanProses = null;
      _captureKey = UniqueKey();
    });
    if (_lokasi == _Lokasi.gagal) {
      setState(() => _lokasi = _Lokasi.mencari);
      _sub?.cancel();
      _mulaiLokasi();
    }
  }

  // ── Tampilan ─────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final judul = widget.aksi == 'pulang'
        ? 'Presensi Pulang'
        : 'Presensi Masuk';
    final berhasil = _berhasil != null;

    return AppPage(
      title: judul,
      subtitle: 'Wajah dan lokasi diperiksa bersamaan',
      bottom: berhasil
          ? Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                PrimaryButton(
                  label: 'Selesai',
                  icon: Icons.check_rounded,
                  onPressed: () => context.pop(),
                ),
                const SizedBox(height: 8),
                TertiaryButton(
                  label: 'Lihat riwayat presensi',
                  onPressed: () => context.pushReplacement('/guru/absen/rekap'),
                ),
              ],
            )
          : _pesanGagal != null
          ? Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (_bisaUlang) ...[
                  WarningButton(
                    label: 'Coba Lagi',
                    icon: Icons.refresh_rounded,
                    onPressed: _cobaLagi,
                  ),
                  const SizedBox(height: 10),
                ],
                SecondaryButton(
                  label: 'Kembali',
                  color: AppColors.inkMuted,
                  onPressed: () => context.pop(),
                ),
              ],
            )
          : SecondaryButton(
              label: 'Batalkan',
              color: AppColors.inkMuted,
              onPressed: () => context.pop(),
            ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          FaceCapture(
            key: _captureKey,
            jumlahFoto: _tambahan ? 5 : 3,
            onSelesai: _fotoSiap,
            onGagal: (pesan) => setState(() => _gagal(pesan)),
            hasil: _hasilWajah,
            pesanProses: _pesanProses,
          ),
          const SizedBox(height: 12),
          if (_tambahan && !berhasil && _pesanGagal == null) ...[
            _kartuTambahan(),
            const SizedBox(height: 12),
          ],
          if (berhasil)
            _kartuBerhasil()
          else if (_pesanGagal != null)
            _kartuGagal(),
          if (!berhasil) ...[
            if (_pesanGagal != null) const SizedBox(height: 12),
            _kartuLokasi(),
          ],
        ],
      ),
    );
  }

  Widget _kartuBerhasil() {
    final h = Map<String, dynamic>.from(_berhasil!['hari_ini'] as Map);
    final masuk = widget.aksi != 'pulang';
    final jam = masuk ? h['jam_masuk'] : h['jam_pulang'];
    final telat = masuk && h['terlambat'] == true;
    return Semantics(
      liveRegion: true,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Presensi ${masuk ? 'masuk' : 'pulang'} berhasil',
            style: AppText.heading.copyWith(color: AppColors.ok700),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 10),
          InfoTable([
            (
              'Waktu ${masuk ? 'masuk' : 'pulang'}',
              Text(
                masuk
                    ? '$jam · ${telat ? 'Terlambat' : 'Tepat waktu'}'
                    : '$jam',
                style: AppText.label.copyWith(
                  color: telat ? const Color(0xFF8A6417) : AppColors.ok700,
                ),
              ),
            ),
            (
              'Lokasi',
              Text(
                'Di area $_namaSekolah (${Fmt.jarak(_jarak ?? 0)})',
                style: AppText.label,
              ),
            ),
            (
              'Kecocokan wajah',
              Text(
                '${(_berhasil!['skor_wajah'] as num?)?.round() ?? '-'}%',
                style: AppText.label,
              ),
            ),
            ('Status', Text(h['status'] ?? 'Hadir', style: AppText.label)),
          ]),
        ],
      ),
    );
  }

  Widget _kartuTambahan() => Semantics(
    liveRegion: true,
    child: Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.brand50,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.brand200),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.verified_user_outlined, color: AppColors.brand700),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              'Verifikasi tambahan: wajah Anda mirip dengan guru lain. Kedipkan mata sekali lagi; beberapa foto diambil untuk memastikan ini memang Anda.',
              style: AppText.bodySmall.copyWith(color: AppColors.brand800),
            ),
          ),
        ],
      ),
    ),
  );

  Widget _kartuGagal() => Semantics(
    liveRegion: true,
    child: Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.alpaTint,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.error_outline_rounded, color: AppColors.alpa),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              _pesanGagal!,
              style: AppText.bodySmall.copyWith(color: const Color(0xFF7F1D1D)),
            ),
          ),
        ],
      ),
    ),
  );

  Widget _kartuLokasi() {
    final (warna, ikon, status, keterangan) = switch (_lokasi) {
      _Lokasi.mencari => (
        AppColors.brand700,
        Icons.my_location_rounded,
        'Mencari lokasi…',
        'Membaca GPS HP',
      ),
      _Lokasi.kurangAkurat => (
        AppColors.gold,
        Icons.gps_not_fixed_rounded,
        'Sinyal GPS lemah',
        'Akurasi ±${_akurasi?.round() ?? '-'} m, menunggu di bawah $_maksAkurasi m',
      ),
      _Lokasi.diDalam => (
        AppColors.ok600,
        Icons.location_on_rounded,
        'Di area $_namaSekolah',
        '${Fmt.jarak(_jarak ?? 0)} dari titik sekolah · akurasi ±${_akurasi?.round() ?? '-'} m',
      ),
      _Lokasi.diLuar => (
        AppColors.alpa,
        Icons.location_off_rounded,
        'Di luar area sekolah',
        '${Fmt.jarak(_jarak ?? 0)} dari $_namaSekolah · akurasi ±${_akurasi?.round() ?? '-'} m',
      ),
      _Lokasi.palsu => (
        AppColors.alpa,
        Icons.wrong_location_rounded,
        'Lokasi palsu terdeteksi',
        'Absen lewat aplikasi diblokir hari ini',
      ),
      _Lokasi.gagal => (
        AppColors.alpa,
        Icons.location_disabled_rounded,
        'Lokasi tidak terbaca',
        _pesanLokasi ?? '',
      ),
    };
    final memuat =
        _lokasi == _Lokasi.mencari || _lokasi == _Lokasi.kurangAkurat;
    return Semantics(
      liveRegion: true,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.line),
        ),
        child: Row(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: warna.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: memuat
                  ? Padding(
                      padding: const EdgeInsets.all(11),
                      child: CircularProgressIndicator(
                        strokeWidth: 2.5,
                        color: warna,
                      ),
                    )
                  : Icon(ikon, color: warna, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Lokasi · $status',
                    style: AppText.label.copyWith(
                      color: memuat ? AppColors.ink : warna,
                    ),
                  ),
                  if (keterangan.isNotEmpty)
                    Text(keterangan, style: AppText.caption),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
