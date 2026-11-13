import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';

import 'core/api_client.dart';
import 'core/session.dart';
import 'core/theme.dart';
import 'router.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID');
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
    ),
  );

  final api = ApiClient();
  try {
    // Versi lama membolehkan alamat server diganti di layar login; buang sisanya
    // agar HP yang pernah diatur ke laptop tidak tetap memakai alamat itu.
    const storage = FlutterSecureStorage();
    await storage.delete(key: 'api_url');
    await storage.delete(key: 'custom_api_url');
  } catch (_) {}
  final session = Session(api);
  await session.notifikasi.init();
  runApp(MyDarfikApp(session: session));
}

class MyDarfikApp extends StatefulWidget {
  const MyDarfikApp({super.key, required this.session});
  final Session session;

  @override
  State<MyDarfikApp> createState() => _MyDarfikAppState();
}

class _MyDarfikAppState extends State<MyDarfikApp> {
  late final router = buildRouter(widget.session);

  @override
  void initState() {
    super.initState();
    // Notifikasi diketuk saat aplikasi berjalan: buka layar terkait di atas layar sekarang.
    widget.session.notifikasi.onBuka = (rute) {
      if (widget.session.loggedIn) router.push(rute);
    };
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: widget.session),
        ChangeNotifierProvider.value(value: widget.session.notifikasi),
        Provider.value(value: widget.session.api),
      ],
      child: MaterialApp.router(
        title: 'MyDarfik',
        debugShowCheckedModeBanner: false,
        theme: buildTheme(),
        routerConfig: router,
      ),
    );
  }
}
