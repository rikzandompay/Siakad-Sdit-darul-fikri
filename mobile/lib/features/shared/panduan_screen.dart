import 'package:flutter/material.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:provider/provider.dart';

import '../../core/config.dart';
import '../../core/unduh.dart';
import '../../core/session.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

/// Satu topik panduan: judul, ikon, dan langkah-langkahnya.
typedef _Topik = (String judul, IconData ikon, List<String> langkah);

const _umum = <_Topik>[
  (
    'Server tidak merespons',
    Icons.wifi_off_rounded,
    [
      'Pastikan HP tersambung ke internet atau ke Wi-Fi sekolah.',
      'Di layar masuk, ketuk "Atur alamat server", isi alamat dari Tata Usaha, lalu tekan "Tes koneksi".',
      'Bila tetap gagal, hubungi Tata Usaha SDIT Darul Fikri.',
    ],
  ),
];

const _guru = <_Topik>[
  (
    'Absen masuk & pulang',
    Icons.fingerprint_rounded,
    [
      'Buka menu "Absen Saya" di beranda.',
      'Pertama kali: daftarkan wajah dengan mengikuti petunjuk kamera.',
      'Aktifkan GPS dan pastikan berada di area sekolah, lalu tekan "Absen Masuk".',
      'Arahkan wajah ke kamera sampai terverifikasi. Lakukan hal yang sama untuk "Absen Pulang".',
      'Riwayat dapat dilihat di Profil → "Rekap Absen Saya".',
    ],
  ),
  (
    'Presensi kelas (wali kelas)',
    Icons.fact_check_outlined,
    [
      'Buka menu "Presensi", pilih kelas lalu mata pelajaran/jadwal.',
      'Tandai setiap siswa: Hadir, Izin, Sakit, atau Alpa.',
      'Tekan "Simpan". Presensi bisa dibuka lagi untuk diperbaiki pada hari yang sama.',
    ],
  ),
  (
    'Presensi sholat (scan QR)',
    Icons.qr_code_scanner_rounded,
    [
      'Buka menu "Sholat", pilih kelas dan waktu sholat.',
      'Ketuk "Scan QR" lalu arahkan kamera ke QR di kartu pelajar siswa.',
      'Siswa tanpa kartu dapat ditandai manual dari daftar, lalu tekan "Simpan".',
    ],
  ),
  (
    'Bagi tugas & beri nilai',
    Icons.assignment_outlined,
    [
      'Buka menu "Bagi Tugas", pilih kelas dan mata pelajaran, isi judul serta tenggat.',
      'Wali murid akan menerima pemberitahuan tugas baru.',
      'Untuk menilai, buka tugas lalu isi nilai setiap siswa dan simpan.',
    ],
  ),
  (
    'Surat izin',
    Icons.mail_outline_rounded,
    [
      'Menu "Surat Izin" menampilkan pengajuan izin dari wali murid.',
      'Buka pengajuan, periksa lampiran, lalu pilih "Setujui" atau "Tolak".',
      'Guru juga dapat mengajukan izin tidak hadir dari menu yang sama.',
    ],
  ),
  (
    'Konseling dengan wali murid',
    Icons.forum_outlined,
    [
      'Menu "Konseling" berisi percakapan dari wali murid kelas yang Anda ampu.',
      'Buka percakapan untuk membaca dan membalas pesan.',
    ],
  ),
  (
    'Kartu guru, foto & kata sandi',
    Icons.badge_outlined,
    [
      'Profil → "Cetak Kartu Guru" untuk melihat kartu identitas.',
      'Profil → "Unggah / Ganti Foto Profil". Foto baru menunggu persetujuan kepala sekolah dan dipakai sebagai acuan wajah saat absen.',
      'Profil → "Ubah Kata Sandi" untuk mengganti kata sandi.',
    ],
  ),
];

const _kepsek = <_Topik>[
  (
    'Tab Sekolah (kepala sekolah)',
    Icons.dashboard_outlined,
    [
      'Tab "Sekolah" di beranda menampilkan ringkasan kehadiran siswa dan guru hari ini.',
      'Absen pribadi tetap tersedia di kartu "Absen Saya" bagian atas.',
      'Tab "Mengajar Saya" berisi menu guru seperti presensi, tugas, dan jadwal.',
    ],
  ),
  (
    'Rekap & unduh laporan',
    Icons.download_rounded,
    [
      'Buka menu rekap (absensi, sholat, nilai, atau presensi guru) di tab Sekolah.',
      'Pilih kelas dan periode, lalu ketuk "CSV" atau "PDF".',
      'Berkas tersimpan di folder Download HP; ketuk "Buka" untuk melihatnya.',
    ],
  ),
  (
    'Kelola data & pengumuman',
    Icons.manage_accounts_outlined,
    [
      'Data Guru, Data Siswa, dan Kelas & Mapel dapat dikelola dari tab Sekolah.',
      'Setujui foto profil guru yang diajukan agar menjadi acuan wajah.',
      'Buat pengumuman untuk guru atau wali murid dari "Info Sekolah".',
    ],
  ),
];

const _wali = <_Topik>[
  (
    'Masuk ke aplikasi',
    Icons.login_rounded,
    [
      'Pilih tab "Wali Murid (Ortu)".',
      'Isi NIS siswa dan tanggal lahir siswa dengan format TT-BB-HH (tahun-bulan-hari), contoh lahir 16 Januari 2018 ditulis 18-01-16 (atau 2018-01-16).',
      'Centang "Ingat saya" agar tidak perlu masuk ulang.',
    ],
  ),
  (
    'Kehadiran & sholat ananda',
    Icons.event_available_outlined,
    [
      'Beranda menampilkan kehadiran dan presensi sholat ananda hari ini.',
      'Menu "Kehadiran" berisi rekap per bulan.',
    ],
  ),
  (
    'Jadwal, tugas & rapor',
    Icons.menu_book_outlined,
    [
      'Menu "Jadwal" menampilkan pelajaran dan perlengkapan yang perlu dibawa besok.',
      'Menu "Tugas" berisi tugas dari guru beserta tenggat dan nilainya.',
      'Menu "Rapor" menampilkan nilai dan grafik perkembangan.',
    ],
  ),
  (
    'Mengajukan izin',
    Icons.mail_outline_rounded,
    [
      'Buka menu "Izin", pilih tanggal dan jenis (sakit/izin), tulis keterangan.',
      'Lampirkan foto surat bila ada, lalu kirim. Status persetujuan tampil di daftar izin.',
    ],
  ),
  (
    'Konseling dengan guru',
    Icons.forum_outlined,
    [
      'Buka menu "Konseling", lalu tekan "Mulai Konseling".',
      'Tulis topik dan pesan. Balasan guru muncul di "Riwayat Konseling".',
    ],
  ),
  (
    'Cetak kartu pelajar',
    Icons.badge_outlined,
    [
      'Profil → "Cetak Kartu Pelajar", lalu "Unduh PDF Kartu".',
      'PDF tersimpan di folder Download. Cetak di kertas A4 dengan skala 100% (jangan "sesuaikan halaman").',
      'Potong mengikuti garis putus-putus, lalu laminating. QR di belakang dipakai untuk presensi sholat.',
    ],
  ),
  (
    'Foto & data ananda',
    Icons.edit_outlined,
    [
      'Profil → "Unggah / Ubah Foto Ananda" untuk foto kartu.',
      'Profil → "Ubah Data" untuk memperbarui nomor HP atau alamat.',
    ],
  ),
];

/// Halaman Panduan Penggunaan: isi menyesuaikan peran akun (guru, kepala sekolah, wali).
class PanduanScreen extends StatelessWidget {
  const PanduanScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final session = context.watch<Session>();
    final guru = session.peran == Peran.guru;
    final topik = [
      if (guru && session.isAdmin) ..._kepsek,
      if (guru) ..._guru else ..._wali,
      ..._umum,
    ];

    return AppPage(
      title: 'Panduan Penggunaan',
      subtitle: guru
          ? (session.isAdmin ? 'Kepala Sekolah & Guru' : 'Guru')
          : 'Wali Murid',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          PrimaryButton(
            label: 'Unduh Panduan Lengkap (PDF)',
            icon: Icons.picture_as_pdf_outlined,
            onPressed: () => unduhLaporan(
              context,
              jenis: 'panduan',
              format: 'pdf',
              alamatApi: '/panduan',
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'PDF berisi tangkapan layar bernomor sesuai peran akun Anda. Atau ketuk topik di bawah untuk ringkasannya.',
            style: AppText.bodySmall,
          ),
          const SizedBox(height: 12),
          AppCard(
            padding: const EdgeInsets.symmetric(vertical: 4),
            child: Column(
              children: [
                for (final (i, t) in topik.indexed) ...[
                  if (i > 0)
                    const Divider(height: 1, indent: 64, color: AppColors.line),
                  _TopikTile(topik: t),
                ],
              ],
            ),
          ),
          const SizedBox(height: 20),
          const Center(child: TeksVersi()),
        ],
      ),
    );
  }
}

class _TopikTile extends StatelessWidget {
  const _TopikTile({required this.topik});
  final _Topik topik;

  @override
  Widget build(BuildContext context) {
    final (judul, ikon, langkah) = topik;
    return Theme(
      // Hilangkan garis bawaan ExpansionTile agar menyatu dengan kartu.
      data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
      child: ExpansionTile(
        leading: Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: AppColors.brand50,
            borderRadius: BorderRadius.circular(10),
          ),
          alignment: Alignment.center,
          child: Icon(ikon, size: 22, color: AppColors.brand700),
        ),
        title: Text(judul, style: AppText.label),
        childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 14),
        expandedCrossAxisAlignment: CrossAxisAlignment.start,
        children: [
          for (final (i, l) in langkah.indexed)
            Padding(
              padding: const EdgeInsets.only(top: 6),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 20,
                    height: 20,
                    margin: const EdgeInsets.only(right: 10, top: 1),
                    decoration: const BoxDecoration(
                      color: AppColors.brand700,
                      shape: BoxShape.circle,
                    ),
                    alignment: Alignment.center,
                    child: Text(
                      '${i + 1}',
                      style: AppText.caption.copyWith(
                        color: Colors.white,
                        fontSize: 11,
                      ),
                    ),
                  ),
                  Expanded(child: Text(l, style: AppText.body)),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

/// "MyDarfik v1.0.0 (build 1)" dibaca dari APK yang terpasang; cadangan: [AppConfig.versi].
class TeksVersi extends StatelessWidget {
  const TeksVersi({super.key});

  static final Future<PackageInfo?> _info = PackageInfo.fromPlatform()
      .then<PackageInfo?>((i) => i, onError: (_) => null);

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<PackageInfo?>(
      future: _info,
      builder: (context, snap) {
        final i = snap.data;
        final versi = i == null
            ? AppConfig.versi
            : '${i.version} (build ${i.buildNumber})';
        return Text(
          '${AppConfig.namaAplikasi} v$versi · ${AppConfig.namaSekolah}',
          style: AppText.caption,
          textAlign: TextAlign.center,
        );
      },
    );
  }
}
