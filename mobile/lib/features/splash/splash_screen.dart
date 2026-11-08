import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/config.dart';
import '../../core/session.dart';
import '../../core/theme.dart';

/// Splash: logo membesar, judul & tagline naik, lalu ke login/beranda (±3,5 detik).
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 1400))..forward();

  @override
  void initState() {
    super.initState();
    final session = context.read<Session>();
    Future.wait([session.restore(), Future.delayed(const Duration(milliseconds: 3500))]).then((_) {
      if (!mounted) return;
      context.go(!session.loggedIn ? '/login' : (session.peran == Peran.wali ? '/wali' : '/guru'));
      // Aplikasi dibuka dari ketukan notifikasi: tampilkan layar terkait di atas beranda.
      final rute = session.notifikasi.ambilRuteTertunda();
      if (session.loggedIn && rute != null) context.push(rute);
    });
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final logo = CurvedAnimation(parent: _c, curve: const Interval(0, 0.6, curve: Curves.easeOutBack));
    final teks = CurvedAnimation(parent: _c, curve: const Interval(0.45, 1, curve: Curves.easeOut));
    return DecoratedBox(
      decoration: const BoxDecoration(gradient: AppColors.pageGradient),
      child: Scaffold(
        backgroundColor: Colors.transparent,
        body: SafeArea(
          child: Column(
            children: [
              const Spacer(flex: 3),
              ScaleTransition(scale: Tween(begin: 0.6, end: 1.0).animate(logo), child: FadeTransition(opacity: logo, child: Image.asset('assets/images/logo.png', height: 96))),
              const SizedBox(height: 24),
              FadeTransition(
                opacity: teks,
                child: SlideTransition(
                  position: Tween(begin: const Offset(0, 0.4), end: Offset.zero).animate(teks),
                  child: Column(children: [
                    Text('MyDarfik', style: AppText.title.copyWith(fontSize: 30)),
                    const SizedBox(height: 6),
                    Text('SISTEM INFORMASI AKADEMIK', style: AppText.overline.copyWith(color: AppColors.brand700, letterSpacing: 2)),
                  ]),
                ),
              ),
              const Spacer(flex: 3),
              const _Dots(),
              const SizedBox(height: 48),
              Text('Versi ${AppConfig.versi}', style: AppText.caption.copyWith(color: AppColors.inkFaint)),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }
}

class _Dots extends StatefulWidget {
  const _Dots();
  @override
  State<_Dots> createState() => _DotsState();
}

class _DotsState extends State<_Dots> with SingleTickerProviderStateMixin {
  late final _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 900))..repeat();

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _c,
      builder: (_, _) {
        final aktif = (_c.value * 3).floor();
        return Row(mainAxisAlignment: MainAxisAlignment.center, children: [
          for (var i = 0; i < 3; i++)
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 4),
              width: 8,
              height: 8,
              decoration: BoxDecoration(shape: BoxShape.circle, color: i == aktif ? AppColors.brand700 : AppColors.brand200),
            ),
        ]);
      },
    );
  }
}
