# Acuan wajah presensi (server)

`embedding.py` membuat acuan wajah guru dari foto profil yang sudah disetujui kepala sekolah.
Dipanggil oleh job `App\Jobs\BuatAcuanWajah` (lewat `App\Support\PengenalWajahPython`) setiap kali
foto profil disetujui/diganti atau data wajah direset. HP tidak pernah mengirim atau menerima acuan.

- Deteksi wajah + posisi mata: OpenCV YuNet (`models/face_detection_yunet_2023mar.onnx`,
  dari [opencv_zoo](https://github.com/opencv/opencv_zoo), lisensi Apache-2.0).
- Embedding: `mobile/assets/models/mobilefacenet.tflite`, model yang sama dengan aplikasi.
- Pemotongan wajah harus identik dengan `FaceService.potongWajah` di aplikasi; dijaga oleh
  `mobile/test/potong_wajah_test.dart` (golden dari `embedding.py --potongan`). Bila salah satu
  diubah, naikkan `PIPELINE_WAJAH` di `AbsenController` dan `pipeline_wajah` di aplikasi.

## Pemasangan di VPS (sekali)

```bash
sudo apt-get install -y python3-venv
sudo python3 -m venv /opt/siakad-wajah
sudo /opt/siakad-wajah/bin/pip install numpy==1.26.4 opencv-python-headless==4.10.0.84 tflite-runtime==2.14.0
# .env: GURU_WAJAH_PYTHON=/opt/siakad-wajah/bin/python
sudo -u www-data php artisan wajah:buat-acuan --semua   # buat ulang acuan semua guru
```

Satu foto butuh sekitar 100 MB RAM dan kurang dari 1 detik, hanya saat job berjalan.
