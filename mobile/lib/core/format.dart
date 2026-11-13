import 'package:intl/intl.dart';

/// Format tanggal & waktu bahasa Indonesia.
class Fmt {
  static DateTime parse(String ymd) => DateTime.parse(ymd);

  /// Senin, 9 Nov 2026
  static String tanggalPanjang(DateTime d) =>
      DateFormat('EEEE, d MMM y', 'id_ID').format(d);

  /// Sen, 3 Nov
  static String tanggalPendek(DateTime d) =>
      DateFormat('EEE, d MMM', 'id_ID').format(d);

  /// November 2026
  static String bulan(DateTime d) => DateFormat('MMMM y', 'id_ID').format(d);

  static String jarak(num meter) => meter >= 1000
      ? '${(meter / 1000).toStringAsFixed(1).replaceAll('.', ',')} km'
      : '${meter.round()} m';
}
