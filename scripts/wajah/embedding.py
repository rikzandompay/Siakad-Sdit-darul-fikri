#!/usr/bin/env python3
"""Embedding wajah dari foto profil guru (acuan presensi), dihitung di server.

Pemakaian:
    python embedding.py FOTO                      -> {"embedding": [...192 angka], "mata": [[x,y],[x,y]], "skor_deteksi": 0.97}
    python embedding.py FOTO --mata x1,y1,x2,y2   -> lewati deteksi (untuk uji paritas dengan aplikasi)
    python embedding.py FOTO --mata ... --potongan  -> keluarkan hasil potong 112x112 (tanpa model)

Gagal yang wajar (tidak ada wajah, lebih dari satu wajah, terlalu kecil) dicetak sebagai
{"error": kode, "pesan": teks} dengan kode keluar 0. Kode keluar 1 = kesalahan tak terduga.

Pemotongan wajah HARUS sama dengan aplikasi (mobile/lib/features/face/face_service.dart,
potongWajah): dua mata diurutkan menurut x, wajah diluruskan pada garis mata, sisi potong
3.0 x jarak mata, pusat 0.4 x jarak mata di bawah titik tengah mata, sampel bilinear
448x448 (tepi direplikasi), rata-rata blok 4x4 -> 112x112, lalu (x - 127.5) / 128.
"""
import argparse
import json
import math
import os
import sys

import cv2
import numpy as np

DIR = os.path.dirname(os.path.abspath(__file__))
MODEL_DETEKSI = os.path.join(DIR, 'models', 'face_detection_yunet_2023mar.onnx')
# Model yang sama persis dengan aplikasi (deploy membawa seluruh repo, termasuk mobile/).
MODEL_WAJAH = os.path.join(DIR, '..', '..', 'mobile', 'assets', 'models', 'mobilefacenet.tflite')

SISI_MATA = 3.0      # sisi potong / jarak mata
TURUN_MATA = 0.4     # pusat potong di bawah titik tengah mata (x jarak mata)
ANTARA = 448         # ukuran sampel sebelum diperkecil
UKURAN = 112         # input MobileFaceNet
MIN_JARAK_MATA = 20  # piksel
SKOR_DETEKSI = 0.8
MAKS_SISI_DETEKSI = 640


class GagalWajah(Exception):
    def __init__(self, kode, pesan):
        super().__init__(pesan)
        self.kode = kode
        self.pesan = pesan


def potong_wajah(rgb, mata_a, mata_b):
    """Potongan wajah 112x112x3 float32 yang sudah dinormalisasi (x - 127.5) / 128."""
    kiri, kanan = sorted([tuple(map(float, mata_a)), tuple(map(float, mata_b))], key=lambda p: p[0])
    dx, dy = kanan[0] - kiri[0], kanan[1] - kiri[1]
    jarak = math.hypot(dx, dy)
    if jarak < 1e-6:
        raise GagalWajah('mata_tidak_valid', 'Posisi mata tidak valid.')
    cos, sin = dx / jarak, dy / jarak
    tengah_x, tengah_y = (kiri[0] + kanan[0]) / 2, (kiri[1] + kanan[1]) / 2
    # Pusat potong: turun sepanjang sumbu vertikal wajah (sudah diputar mengikuti garis mata).
    cx = tengah_x - sin * TURUN_MATA * jarak
    cy = tengah_y + cos * TURUN_MATA * jarak
    skala = SISI_MATA * jarak / ANTARA

    idx = (np.arange(ANTARA, dtype=np.float64) - (ANTARA - 1) / 2) * skala
    fx, fy = np.meshgrid(idx, idx)  # fx berubah per kolom, fy per baris
    sx = cx + cos * fx - sin * fy
    sy = cy + sin * fx + cos * fy

    h, w = rgb.shape[:2]
    sx = np.clip(sx, 0, w - 1)
    sy = np.clip(sy, 0, h - 1)
    x0 = np.floor(sx).astype(np.int64)
    y0 = np.floor(sy).astype(np.int64)
    x1 = np.minimum(x0 + 1, w - 1)
    y1 = np.minimum(y0 + 1, h - 1)
    ax = (sx - x0)[..., None]
    ay = (sy - y0)[..., None]
    src = rgb.astype(np.float64)
    atas = src[y0, x0] * (1 - ax) + src[y0, x1] * ax
    bawah = src[y1, x0] * (1 - ax) + src[y1, x1] * ax
    sampel = atas * (1 - ay) + bawah * ay

    blok = ANTARA // UKURAN
    kecil = sampel.reshape(UKURAN, blok, UKURAN, blok, 3).mean(axis=(1, 3))
    return ((kecil - 127.5) / 128.0).astype(np.float32)


def deteksi_mata(bgr):
    h, w = bgr.shape[:2]
    skala = min(1.0, MAKS_SISI_DETEKSI / max(h, w))
    kecil = cv2.resize(bgr, (round(w * skala), round(h * skala)), interpolation=cv2.INTER_AREA) if skala < 1 else bgr
    detektor = cv2.FaceDetectorYN.create(MODEL_DETEKSI, '', (kecil.shape[1], kecil.shape[0]), SKOR_DETEKSI, 0.3, 50)
    _, wajah = detektor.detect(kecil)
    if wajah is None or len(wajah) == 0:
        raise GagalWajah('tidak_ada_wajah', 'Wajah tidak terlihat pada foto profil. Ajukan foto yang jelas, menghadap depan, dan cukup terang.')
    wajah = sorted(wajah, key=lambda f: f[2] * f[3], reverse=True)
    if len(wajah) > 1 and wajah[1][2] * wajah[1][3] * 4 > wajah[0][2] * wajah[0][3]:
        raise GagalWajah('lebih_dari_satu_wajah', 'Foto profil berisi lebih dari satu wajah. Ajukan foto yang hanya berisi wajah Anda.')
    f = wajah[0]
    mata = [(f[4] / skala, f[5] / skala), (f[6] / skala, f[7] / skala)]
    if math.dist(*mata) < MIN_JARAK_MATA:
        raise GagalWajah('wajah_terlalu_kecil', 'Wajah pada foto profil terlalu kecil. Ajukan foto yang lebih dekat.')
    return mata, float(f[14])


def embedding(potongan):
    try:
        from tflite_runtime.interpreter import Interpreter
    except ImportError:  # paket pengganti resmi tflite-runtime
        from ai_edge_litert.interpreter import Interpreter
    model = Interpreter(model_path=MODEL_WAJAH, num_threads=1)
    model.allocate_tensors()
    model.set_tensor(model.get_input_details()[0]['index'], potongan[None, ...])
    model.invoke()
    v = model.get_tensor(model.get_output_details()[0]['index'])[0].astype(np.float64)
    n = np.linalg.norm(v)
    return (v / n if n > 0 else v).tolist()


def main():
    p = argparse.ArgumentParser()
    p.add_argument('foto')
    p.add_argument('--mata', help='x1,y1,x2,y2 (lewati deteksi)')
    p.add_argument('--potongan', action='store_true', help='keluarkan potongan 112x112, bukan embedding')
    a = p.parse_args()

    try:
        bgr = cv2.imread(a.foto, cv2.IMREAD_COLOR)  # orientasi EXIF ikut diterapkan
        if bgr is None:
            raise GagalWajah('foto_tidak_terbaca', 'Foto profil tidak dapat dibaca.')
        if a.mata:
            x1, y1, x2, y2 = map(float, a.mata.split(','))
            mata, skor = [(x1, y1), (x2, y2)], None
        else:
            mata, skor = deteksi_mata(bgr)
        potongan = potong_wajah(cv2.cvtColor(bgr, cv2.COLOR_BGR2RGB), *mata)
        if a.potongan:
            print(json.dumps({'potongan': [round(float(x), 6) for x in potongan.reshape(-1)]}))
            return 0
        hasil = {'embedding': [round(x, 7) for x in embedding(potongan)], 'mata': [[round(x, 2), round(y, 2)] for x, y in mata]}
        if skor is not None:
            hasil['skor_deteksi'] = round(skor, 4)
        print(json.dumps(hasil))
        return 0
    except GagalWajah as e:
        print(json.dumps({'error': e.kode, 'pesan': e.pesan}))
        return 0


if __name__ == '__main__':
    sys.exit(main())
