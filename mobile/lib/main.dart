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
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(statusBarColor: Colors.transparent, statusBarIconBrightness: Brightness.dark));

  final api = ApiClient();
  try {
    // Alamat server yang diatur pengguna di layar login menimpa bawaan build.
    final tersimpan = await const FlutterSecureStorage().read(key: 'api_url');
    if (tersimpan != null && tersimpan.isNotEmpty) api.baseUrl = tersimpan;
  } catch (_) {}
  final session = Session(api);
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
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider.value(value: widget.session),
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
