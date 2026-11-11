import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mydarfik/features/face/face_capture.dart';

/// Frame kamera depan Android umum: 1280x720 mentah, diputar 270 -> tegak 720x1280.
/// Dengan BoxFit.cover pada area 0.82 x 1, pusat bingkai oval (0.41, 0.44) jatuh di
/// sekitar titik (360, 587) gambar tegak, dan lebar oval ~446 px.
void main() {
  const mentah = Size(1280, 720);
  String? cek(Rect kotak, {int jumlah = 1, double y = 0}) =>
      cekPosisiWajah(jumlahWajah: jumlah, kotak: kotak, ukuranMentah: mentah, diputar: true, eulerY: y);

  test('wajah di tengah bingkai dengan ukuran pas diterima', () {
    expect(cek(Rect.fromCenter(center: const Offset(360, 587), width: 330, height: 400)), isNull);
  });

  test('tidak ada wajah atau lebih dari satu wajah', () {
    expect(cekPosisiWajah(jumlahWajah: 0, ukuranMentah: mentah, diputar: true), 'Wajah tidak ditemukan');
    expect(cek(Rect.fromCenter(center: const Offset(360, 587), width: 330, height: 400), jumlah: 2), 'Terlihat lebih dari satu wajah');
  });

  test('hanya dahi di tepi bawah layar (seperti tangkapan layar pengguna) ditolak', () {
    expect(cek(Rect.fromCenter(center: const Offset(360, 1150), width: 330, height: 260)), 'Masukkan wajah ke dalam bingkai');
  });

  test('wajah bergeser ke samping ditolak, memperhitungkan efek cermin', () {
    expect(cek(Rect.fromCenter(center: const Offset(160, 587), width: 330, height: 400)), 'Masukkan wajah ke dalam bingkai');
    expect(cek(Rect.fromCenter(center: const Offset(560, 587), width: 330, height: 400)), 'Masukkan wajah ke dalam bingkai');
  });

  test('terlalu jauh, terlalu dekat, dan menoleh', () {
    expect(cek(Rect.fromCenter(center: const Offset(360, 587), width: 150, height: 180)), 'Dekatkan wajah ke kamera');
    expect(cek(Rect.fromCenter(center: const Offset(360, 587), width: 700, height: 800)), 'Jauhkan sedikit wajah dari kamera');
    expect(cek(Rect.fromCenter(center: const Offset(360, 587), width: 330, height: 400), y: 35), 'Hadapkan wajah lurus ke kamera');
  });
}
