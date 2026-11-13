import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../core/api_client.dart';

/// Kartu Pelajar desain baru (depan & belakang). Seluruh tata letak dalam milimeter pada kartu
/// CR80 85,6 × 54 mm, lalu diskalakan ke lebar yang tersedia, sehingga tampilan di HP sama
/// dengan hasil cetak PDF/web (lihat resources/views/exports/partials/kartu-pelajar-*.blade.php).
class _Warna {
  static const navy = Color(0xFF14284B);
  static const teal = Color(0xFF1B8A9A);
  static const biru = Color(0xFF0C5392);
  static const ink = Color(0xFF111827);
  static const foto = Color(0xFFD4E6FA);
}

const _lebarMm = 85.6;
const _tinggiMm = 54.0;
const _tinggiKakiMm = 6.0;

/// Kerangka kartu: latar gradien, hiasan sudut, kaki biru. [anak] digambar di antaranya.
class _Kerangka extends StatelessWidget {
  const _Kerangka({
    required this.kiriKaki,
    required this.kananKaki,
    this.lebarHiasBl = 30,
    required this.bangun,
  });

  final String kiriKaki;
  final String kananKaki;

  /// Lebar hiasan sudut kiri-bawah (mm); dikecilkan di sisi belakang agar tidak menutupi tata tertib.
  final double lebarHiasBl;
  final List<Widget> Function(
    double Function(double mm) mm,
    TextStyle Function(double pt, FontWeight w, Color c, {double? spasi}) gaya,
  )
  bangun;

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: _lebarMm / _tinggiMm,
      child: LayoutBuilder(
        builder: (context, box) {
          final s = box.maxWidth / _lebarMm;
          double mm(double v) => v * s;
          TextStyle gaya(double pt, FontWeight w, Color c, {double? spasi}) =>
              GoogleFonts.inter(
                fontSize: pt * 0.3528 * s,
                fontWeight: w,
                color: c,
                height: 1.15,
                letterSpacing: spasi == null ? null : spasi * s,
              );
          return Container(
            clipBehavior: Clip.antiAlias,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(mm(4.2)),
              border: Border.all(
                color: const Color(0xFF9FB0C8),
                width: mm(0.3),
              ),
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Colors.white, Color(0xFFEAF2FC), Color(0xFFD8E7F8)],
              ),
            ),
            child: Stack(
              children: [
                Positioned(
                  right: 0,
                  top: 0,
                  width: mm(40),
                  child: Image.asset('assets/images/kartu_swoosh_tr.png'),
                ),
                Positioned(
                  left: 0,
                  bottom: mm(_tinggiKakiMm),
                  width: mm(lebarHiasBl),
                  child: Image.asset('assets/images/kartu_swoosh_bl.png'),
                ),
                ...bangun(mm, gaya),
                Positioned(
                  left: 0,
                  right: 0,
                  bottom: 0,
                  height: mm(_tinggiKakiMm),
                  child: Container(
                    color: _Warna.biru,
                    padding: EdgeInsets.symmetric(horizontal: mm(4.5)),
                    child: Row(
                      children: [
                        Expanded(
                          child: Text(
                            kiriKaki,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: gaya(6.9, FontWeight.w500, Colors.white),
                          ),
                        ),
                        Text(
                          kananKaki,
                          style: gaya(6.9, FontWeight.w500, Colors.white),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

/// Lambang + "KARTU PELAJAR / SDIT Darul Fikri" (sama di kedua sisi).
List<Widget> _kop(
  double Function(double) mm,
  TextStyle Function(double, FontWeight, Color, {double? spasi}) gaya,
) => [
  Positioned(
    left: mm(4.2),
    top: mm(4.0),
    height: mm(9.8),
    child: Image.asset('assets/images/logo.png'),
  ),
  Positioned(
    left: mm(15.3),
    top: mm(4.2),
    child: Text(
      'KARTU PELAJAR',
      style: gaya(6.3, FontWeight.w600, _Warna.teal, spasi: 0.35),
    ),
  ),
  Positioned(
    left: mm(15.3),
    top: mm(7.2),
    child: Text(
      'SDIT Darul Fikri',
      style: gaya(14.5, FontWeight.w800, _Warna.navy),
    ),
  ),
];

class KartuPelajarDepan extends StatelessWidget {
  const KartuPelajarDepan({
    super.key,
    required this.nama,
    required this.nis,
    required this.kelas,
    required this.alamat,
    required this.inisial,
    required this.tahunAjaran,
    this.fotoUrl,
    this.qr,
    this.onTapFoto,
    this.onTapQr,
  });

  final String nama;
  final String nis;
  final String kelas;
  final String alamat;
  final String inisial;
  final String? tahunAjaran;
  final String? fotoUrl;
  final String? qr;
  final VoidCallback? onTapFoto;
  final VoidCallback? onTapQr;

  @override
  Widget build(BuildContext context) {
    final adaFoto = fotoUrl != null && fotoUrl!.trim().isNotEmpty;
    return _Kerangka(
      kiriKaki: 'Tahun Ajaran ${tahunAjaran ?? '-'}',
      kananKaki: 'MyDarfik',
      bangun: (mm, gaya) {
        Widget baris(
          double y,
          String ikon,
          String label,
          String nilai, {
          double kiriNilai = 31.2,
          int maks = 1,
        }) => Positioned(
          left: 0,
          right: 0,
          top: mm(y),
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Positioned(
                left: mm(27),
                top: mm(0.2),
                width: mm(3.2),
                child: Image.asset('assets/images/kartu_ikon_$ikon.png'),
              ),
              Padding(
                padding: EdgeInsets.only(left: mm(31.2)),
                child: Text(
                  label,
                  style: gaya(6.0, FontWeight.w700, _Warna.navy, spasi: 0.15),
                ),
              ),
              Padding(
                padding: EdgeInsets.only(
                  left: mm(kiriNilai),
                  top: mm(2.7),
                  right: mm(23),
                ),
                child: Text(
                  nilai,
                  maxLines: maks,
                  overflow: TextOverflow.ellipsis,
                  style: gaya(8.0, FontWeight.w500, _Warna.ink),
                ),
              ),
            ],
          ),
        );

        return [
          ..._kop(mm, gaya),
          // Foto 3x4
          Positioned(
            left: mm(5.5),
            top: mm(17.5),
            width: mm(17.5),
            height: mm(22.7),
            child: GestureDetector(
              onTap: onTapFoto,
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                  Container(
                    clipBehavior: Clip.antiAlias,
                    decoration: BoxDecoration(
                      color: _Warna.foto,
                      borderRadius: BorderRadius.circular(mm(2.2)),
                      border: Border.all(color: _Warna.navy, width: mm(0.35)),
                    ),
                    alignment: Alignment.center,
                    width: double.infinity,
                    height: double.infinity,
                    child: adaFoto
                        ? Image.network(
                            fotoUrl!,
                            // Foto disajikan lewat rute yang butuh login.
                            headers: context.read<ApiClient>().authHeaders,
                            width: double.infinity,
                            height: double.infinity,
                            fit: BoxFit.cover,
                            errorBuilder: (_, _, _) => _FotoKosong(
                              inisial: inisial,
                              mm: mm,
                              gaya: gaya,
                            ),
                          )
                        : _FotoKosong(inisial: inisial, mm: mm, gaya: gaya),
                  ),
                  if (onTapFoto != null)
                    Positioned(
                      right: -mm(1.3),
                      bottom: -mm(1.3),
                      child: Container(
                        width: mm(5),
                        height: mm(5),
                        decoration: const BoxDecoration(
                          color: Color(0xFF1E7FC4),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(
                          Icons.camera_alt,
                          size: mm(2.9),
                          color: Colors.white,
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
          // Nama (menyusut bila panjang)
          Positioned(
            left: mm(27),
            top: mm(17.4),
            width: mm(34.5),
            child: FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerLeft,
              child: Text(
                nama,
                maxLines: 1,
                style: gaya(10.2, FontWeight.w800, _Warna.navy),
              ),
            ),
          ),
          baris(22.6, 'nis', 'NIS', nis),
          baris(29.4, 'kelas', 'KELAS', kelas),
          baris(36.2, 'alamat', 'ALAMAT', alamat, maks: 2),
          if (qr != null)
            Positioned(
              left: mm(62.8),
              top: mm(17.5),
              width: mm(19.2),
              height: mm(24.5),
              child: GestureDetector(
                onTap: onTapQr,
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(mm(2.6)),
                    border: Border.all(
                      color: const Color(0xFFE5E7EB),
                      width: mm(0.25),
                    ),
                  ),
                  padding: EdgeInsets.fromLTRB(mm(1.5), mm(1.4), mm(1.5), 0),
                  child: Column(
                    children: [
                      QrImageView(
                        data: qr!,
                        size: mm(16.2),
                        padding: EdgeInsets.zero,
                      ),
                      SizedBox(height: mm(1.6)),
                      Text(
                        'QR SHOLAT',
                        style: gaya(
                          5.4,
                          FontWeight.w700,
                          _Warna.navy,
                          spasi: 0.1,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
        ];
      },
    );
  }
}

class _FotoKosong extends StatelessWidget {
  const _FotoKosong({
    required this.inisial,
    required this.mm,
    required this.gaya,
  });
  final String inisial;
  final double Function(double) mm;
  final TextStyle Function(double, FontWeight, Color, {double? spasi}) gaya;

  @override
  Widget build(BuildContext context) => Column(
    mainAxisSize: MainAxisSize.min,
    children: [
      Text(inisial, style: gaya(15, FontWeight.w800, const Color(0xFF1E7FC4))),
      SizedBox(height: mm(0.8)),
      Text(
        'FOTO 3×4',
        style: gaya(5.3, FontWeight.w500, const Color(0xFF1E7FC4), spasi: 0.15),
      ),
    ],
  );
}

const _tataTertib = [
  'Kartu ini adalah tanda pengenal resmi siswa SDIT Darul Fikri.',
  'Wajib dibawa setiap hari dan dikenakan selama berada di lingkungan sekolah.',
  'Kartu tidak boleh dicoret-coret, rusak, atau dipindahtangankan kepada orang lain.',
  'Apabila kartu ini hilang atau rusak, segera lapor ke pihak sekolah.',
  'Bagi yang menemukan kartu ini, mohon dikembalikan ke alamat SDIT Darul Fikri.',
];

class KartuPelajarBelakang extends StatelessWidget {
  const KartuPelajarBelakang({super.key, this.kepalaSekolah});

  /// Nama kepala sekolah di kolom tanda tangan (dari server).
  final String? kepalaSekolah;

  @override
  Widget build(BuildContext context) {
    return _Kerangka(
      kiriKaki: 'Alamat: Sui Durian Laut, Kubu Raya',
      kananKaki: 'MyDarfik',
      lebarHiasBl: 13,
      bangun: (mm, gaya) {
        Widget tengah(double y, String teks, TextStyle st) => Positioned(
          left: mm(56),
          width: mm(27),
          top: mm(y),
          child: Text(teks, textAlign: TextAlign.center, style: st),
        );
        return [
          Positioned(
            left: mm(29),
            top: mm(11.5),
            width: mm(27),
            child: Image.asset('assets/images/kartu_watermark.png'),
          ),
          ..._kop(mm, gaya),
          Positioned(
            left: mm(4.6),
            top: mm(15.6),
            child: Text(
              'TATA TERTIB & KETENTUAN:',
              style: gaya(7.2, FontWeight.w800, _Warna.navy),
            ),
          ),
          Positioned(
            left: mm(4.6),
            top: mm(19.0),
            width: mm(50.5),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                for (final (i, t) in _tataTertib.indexed)
                  Padding(
                    padding: EdgeInsets.only(bottom: mm(0.5)),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SizedBox(
                          width: mm(3.4),
                          child: Text(
                            '${i + 1}.',
                            style: gaya(5.3, FontWeight.w500, _Warna.navy),
                          ),
                        ),
                        Expanded(
                          child: Text(
                            t,
                            style: gaya(5.3, FontWeight.w500, _Warna.navy),
                          ),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
          ),
          tengah(
            16.2,
            'Mengetahui,\nKepala SDIT Darul Fikri',
            gaya(5.5, FontWeight.w500, _Warna.navy),
          ),
          Positioned(
            left: mm(58.5),
            top: mm(22.5),
            width: mm(22.5),
            child: Image.asset('assets/images/kartu_ttd.png'),
          ),
          Positioned(
            left: mm(57.5),
            width: mm(24),
            top: mm(36.2),
            child: Container(height: mm(0.3), color: _Warna.navy),
          ),
          tengah(
            36.8,
            kepalaSekolah ?? '-',
            gaya(6.3, FontWeight.w800, _Warna.navy),
          ),
          Positioned(
            left: mm(57.5),
            width: mm(24),
            top: mm(40),
            child: Container(height: mm(0.25), color: _Warna.navy),
          ),
          tengah(
            40.6,
            'Kepala Sekolah',
            gaya(5.8, FontWeight.w500, _Warna.navy),
          ),
        ];
      },
    );
  }
}
