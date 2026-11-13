import 'package:go_router/go_router.dart';

import 'core/session.dart';
import 'features/auth/login_screen.dart';
import 'features/guru/absen/absen_screen.dart';
import 'features/guru/absen/rekap_absen_screen.dart';
import 'features/guru/absen/verifikasi_wajah_screen.dart';
import 'features/guru/dashboard_screen.dart';
import 'features/guru/presensi/pilih_kelas_screen.dart';
import 'features/guru/presensi/pilih_mapel_screen.dart';
import 'features/guru/presensi/presensi_kelas_screen.dart';
import 'features/guru/presensi/presensi_tersimpan_screen.dart';
import 'features/guru/profil_screen.dart';
import 'features/shared/panduan_screen.dart';
import 'features/shared/ubah_sandi.dart';
import 'features/guru/sholat/pilih_jenis_screen.dart';
import 'features/guru/sholat/pilih_kelas_sholat_screen.dart';
import 'features/guru/sholat/presensi_sholat_screen.dart';
import 'features/guru/sholat/scan_qr_screen.dart';
import 'features/guru/sholat/sholat_tersimpan_screen.dart';
import 'features/splash/splash_screen.dart';
import 'features/admin/admin_data_screens.dart';
import 'features/admin/foto_pengajuan_screen.dart';
import 'features/admin/kelola_screens.dart';
import 'features/admin/rekap_screens.dart';
import 'features/guru/lainnya/izin_guru_screen.dart';
import 'features/guru/lainnya/jadwal_mengajar_screen.dart';
import 'features/guru/lainnya/konseling_guru_screen.dart';
import 'features/guru/lainnya/penilaian_screen.dart';
import 'features/guru/lainnya/tugas_screen.dart';
import 'features/shared/cetak_kartu_screen.dart';
import 'features/shared/chat_screen.dart';
import 'features/shared/notifikasi_screen.dart';
import 'features/shared/pengumuman_screen.dart';
import 'features/wali/izin_wali_screen.dart';
import 'features/wali/jadwal_guru_screen.dart';
import 'features/wali/jadwal_tas_screen.dart';
import 'features/wali/kehadiran_screen.dart';
import 'features/wali/konseling_wali_screen.dart';
import 'features/wali/profil_wali_screen.dart';
import 'features/admin/kelola_sekolah_screens.dart';
import 'features/wali/rapor_screens.dart';
import 'features/wali/tugas_wali_screen.dart';
import 'features/wali/wali_home_screen.dart';

GoRouter buildRouter(Session session) {
  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: session,
    redirect: (context, state) {
      final loc = state.matchedLocation;
      if (loc == '/splash') return null;
      if (!session.ready) return '/splash';
      if (!session.loggedIn) return loc == '/login' ? null : '/login';
      final home = session.peran == Peran.wali ? '/wali' : '/guru';
      // Masih memakai sandi bawaan: wajib ganti dulu sebelum halaman lain.
      final wajibGanti = session.akun['wajib_ganti_sandi'] == true;
      if (wajibGanti) return loc == '/ganti-sandi' ? null : '/ganti-sandi';
      if (loc == '/ganti-sandi') return home;
      if (loc == '/login' || loc == '/') return home;
      // Cegah wali membuka halaman guru dan sebaliknya.
      if (session.peran == Peran.wali && loc.startsWith('/guru')) return home;
      if (session.peran == Peran.guru && loc.startsWith('/wali')) return home;
      if (loc.startsWith('/admin') && !session.isAdmin) return home;
      return null;
    },
    routes: [
      GoRoute(path: '/splash', builder: (_, _) => const SplashScreen()),
      GoRoute(path: '/login', builder: (_, _) => const LoginScreen()),
      GoRoute(
        path: '/ganti-sandi',
        builder: (_, _) => const GantiSandiWajibScreen(),
      ),
      GoRoute(
        path: '/wali',
        builder: (_, _) => const WaliHomeScreen(),
        routes: [
          GoRoute(
            path: 'kehadiran',
            builder: (_, _) => const KehadiranScreen(),
          ),
          GoRoute(path: 'jadwal', builder: (_, _) => const JadwalTasScreen()),
          GoRoute(
            path: 'pengumuman',
            builder: (_, _) =>
                const PengumumanScreen(endpoint: '/wali/pengumuman'),
          ),
          GoRoute(path: 'tugas', builder: (_, _) => const TugasWaliScreen()),
          GoRoute(path: 'izin', builder: (_, _) => const IzinWaliScreen()),
          GoRoute(
            path: 'izin/terkirim',
            builder: (_, s) =>
                IzinTerkirimScreen(data: s.extra as Map<String, dynamic>),
          ),
          GoRoute(path: 'rapor', builder: (_, _) => const RaporKelasScreen()),
          GoRoute(
            path: 'rapor/semester',
            builder: (_, s) =>
                RaporSemesterScreen(tahun: s.extra as Map<String, dynamic>),
          ),
          GoRoute(
            path: 'rapor/pilihan',
            builder: (_, s) =>
                RaporPilihanScreen(data: s.extra as Map<String, dynamic>),
          ),
          GoRoute(
            path: 'rapor/grafik',
            builder: (_, s) =>
                RaporGrafikScreen(tahun: s.extra as Map<String, dynamic>),
          ),
          GoRoute(
            path: 'rapor/nilai/:id',
            builder: (_, s) => RaporNilaiScreen(
              tahunAjaranId: int.parse(s.pathParameters['id']!),
              kelas: s.extra as String?,
            ),
          ),
          GoRoute(path: 'guru', builder: (_, _) => const JadwalGuruScreen()),
          GoRoute(
            path: 'guru/:id',
            builder: (_, s) => JadwalMengajarScreen(
              endpoint: '/wali/guru/${s.pathParameters['id']}/jadwal',
            ),
          ),
          GoRoute(
            path: 'konseling',
            builder: (_, _) => const KonselingWaliScreen(),
          ),
          GoRoute(
            path: 'konseling/:id',
            builder: (_, s) => ChatScreen(
              base: '/wali/konseling',
              id: int.parse(s.pathParameters['id']!),
              sayaPengirim: 'wali',
            ),
          ),
          GoRoute(
            path: 'notifikasi',
            builder: (_, _) => const NotifikasiScreen(),
          ),
          GoRoute(path: 'profil', builder: (_, _) => const ProfilWaliScreen()),
          GoRoute(
            path: 'profil/ubah',
            builder: (_, _) => const UbahDataScreen(),
          ),
          GoRoute(
            path: 'profil/cetak-kartu',
            builder: (_, _) => const CetakKartuScreen(),
          ),
          GoRoute(
            path: 'profil/panduan',
            builder: (_, _) => const PanduanScreen(),
          ),
        ],
      ),
      GoRoute(path: '/admin/guru', builder: (_, _) => const DataGuruScreen()),
      GoRoute(path: '/admin/siswa', builder: (_, _) => const DataSiswaScreen()),
      GoRoute(
        path: '/admin/kelas',
        builder: (_, _) => const KelasMapelScreen(),
      ),
      GoRoute(
        path: '/admin/pengumuman',
        builder: (_, _) => const PengumumanAdminScreen(),
      ),
      GoRoute(
        path: '/admin/rekap-absensi',
        builder: (_, _) => const RekapAbsensiScreen(),
      ),
      // Wali kelas (juga kepala sekolah yang menjadi wali kelas) membuka rekap kelasnya dari beranda guru.
      GoRoute(
        path: '/guru/rekap-kelas',
        builder: (_, _) => const RekapAbsensiScreen(),
      ),
      GoRoute(
        path: '/guru/rekap-sholat',
        builder: (_, _) => const RekapSholatScreen(),
      ),
      GoRoute(
        path: '/guru/rekap-nilai',
        builder: (_, _) => const RekapNilaiScreen(),
      ),
      GoRoute(
        path: '/admin/rekap-sholat',
        builder: (_, _) => const RekapSholatScreen(),
      ),
      GoRoute(
        path: '/admin/rekap-nilai',
        builder: (_, _) => const RekapNilaiScreen(),
      ),
      GoRoute(
        path: '/admin/rekap-guru',
        builder: (_, _) => const RekapGuruScreen(),
      ),
      GoRoute(
        path: '/admin/tahun-ajaran',
        builder: (_, _) => const TahunAjaranScreen(),
      ),
      GoRoute(path: '/admin/mapel', builder: (_, _) => const MapelScreen()),
      GoRoute(
        path: '/admin/pengaturan-sekolah',
        builder: (_, _) => const PengaturanSekolahScreen(),
      ),
      GoRoute(
        path: '/admin/pengampu',
        builder: (_, _) => const PengampuScreen(),
      ),
      GoRoute(path: '/admin/wali', builder: (_, _) => const AkunWaliScreen()),
      GoRoute(
        path: '/admin/foto-pengajuan',
        builder: (_, _) => const FotoPengajuanScreen(),
      ),
      GoRoute(
        path: '/admin/guru/:id/absen',
        builder: (_, s) => KoreksiGuruScreen(
          guruId: int.parse(s.pathParameters['id']!),
          nama: s.extra as String?,
        ),
      ),
      GoRoute(
        path: '/guru',
        builder: (_, _) => const GuruDashboardScreen(),
        routes: [
          GoRoute(
            path: 'notifikasi',
            builder: (_, _) => const NotifikasiScreen(),
          ),
          GoRoute(path: 'profil', builder: (_, _) => const ProfilScreen()),
          GoRoute(
            path: 'profil/cetak-kartu',
            builder: (_, _) => const CetakKartuScreen(),
          ),
          GoRoute(
            path: 'profil/panduan',
            builder: (_, _) => const PanduanScreen(),
          ),
          // Presensi kelas
          GoRoute(
            path: 'presensi',
            builder: (_, _) => const PilihKelasScreen(),
          ),
          GoRoute(
            path: 'presensi/kelas/:kelasId',
            builder: (_, s) => PilihMapelScreen(
              kelasId: int.parse(s.pathParameters['kelasId']!),
            ),
          ),
          GoRoute(
            path: 'presensi/jadwal/:jadwalId',
            builder: (_, s) => PresensiKelasScreen(
              jadwalId: int.parse(s.pathParameters['jadwalId']!),
            ),
          ),
          GoRoute(
            path: 'presensi/tersimpan',
            builder: (_, s) =>
                PresensiTersimpanScreen(data: s.extra as Map<String, dynamic>),
          ),
          // Presensi sholat
          GoRoute(
            path: 'sholat',
            builder: (_, _) => const PilihKelasSholatScreen(),
          ),
          GoRoute(
            path: 'sholat/:kelasId',
            builder: (_, s) => PilihJenisScreen(
              kelasId: int.parse(s.pathParameters['kelasId']!),
            ),
          ),
          GoRoute(
            path: 'sholat/:kelasId/:jenis',
            builder: (_, s) => PresensiSholatScreen(
              kelasId: int.parse(s.pathParameters['kelasId']!),
              jenis: s.pathParameters['jenis']!,
            ),
          ),
          GoRoute(
            path: 'sholat/:kelasId/:jenis/scan',
            builder: (_, s) => ScanQrScreen(
              kelasId: int.parse(s.pathParameters['kelasId']!),
              jenis: s.pathParameters['jenis']!,
            ),
          ),
          GoRoute(
            path: 'sholat-tersimpan',
            builder: (_, s) =>
                SholatTersimpanScreen(data: s.extra as Map<String, dynamic>),
          ),
          // Absen guru
          GoRoute(path: 'absen', builder: (_, _) => const AbsenScreen()),
          GoRoute(
            path: 'absen/wajah',
            builder: (_, s) => VerifikasiWajahScreen(aksi: s.extra as String),
          ),
          GoRoute(
            path: 'absen/rekap',
            builder: (_, _) => const RekapAbsenScreen(),
          ),
          GoRoute(path: 'tugas', builder: (_, _) => const TugasScreen()),
          GoRoute(
            path: 'tugas/:id',
            builder: (_, s) =>
                PenilaianScreen(tugasId: int.parse(s.pathParameters['id']!)),
          ),
          GoRoute(path: 'izin', builder: (_, _) => const IzinGuruScreen()),
          GoRoute(
            path: 'konseling',
            builder: (_, _) => const KonselingGuruScreen(),
          ),
          GoRoute(
            path: 'konseling/:id',
            builder: (_, s) => ChatScreen(
              base: '/guru/konseling',
              id: int.parse(s.pathParameters['id']!),
              sayaPengirim: 'guru',
            ),
          ),
          GoRoute(
            path: 'pengumuman',
            builder: (_, _) =>
                const PengumumanScreen(endpoint: '/guru/pengumuman'),
          ),
          GoRoute(
            path: 'jadwal',
            builder: (_, _) =>
                const JadwalMengajarScreen(endpoint: '/guru/jadwal'),
          ),
        ],
      ),
    ],
  );
}
