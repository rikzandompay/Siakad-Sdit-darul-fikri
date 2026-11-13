{{-- Gaya Kartu Pelajar (desain baru). Seluruh elemen diposisikan absolut dalam milimeter pada kartu
     CR80 85,6 x 54 mm; sama dengan tata letak di aplikasi (mobile/lib/widgets/kartu_pelajar_desain.dart).
     Dipakai halaman web dan PDF (dompdf): tanpa flexbox, gradient, atau shadow di sini. --}}
.kartu { position: relative; width: 85mm; height: 53.4mm; overflow: hidden; background-color: #EAF2FC; border: 0.3mm solid #9FB0C8; border-radius: 4.2mm; color: #14284B; }
.kartu div, .kartu img { position: absolute; }
.kartu .tebal { font-weight: bold; }

.kartu .hias-tr { right: 0; top: 0; width: 40mm; }
.kartu .hias-bl { left: 0; bottom: 6mm; width: 30mm; }
.kartu .hias-bl.kecil { width: 13mm; }
.kartu .lambang { left: 4.2mm; top: 4mm; width: 7.73mm; height: 9.8mm; }
.kartu .jenis { left: 15.3mm; top: 4.2mm; font-size: 6.3pt; font-weight: bold; letter-spacing: 0.35mm; color: #1B8A9A; white-space: nowrap; }
.kartu .judul { left: 15.3mm; top: 7.2mm; font-size: 14.5pt; font-weight: bold; white-space: nowrap; }

.kartu .foto { left: 5.5mm; top: 17.5mm; width: 17.5mm; height: 22.7mm; overflow: hidden; background-color: #D4E6FA; border: 0.35mm solid #14284B; border-radius: 2.2mm; text-align: center; }
.kartu .foto img { left: 0; top: 0; width: 17.5mm; height: 22.7mm; object-fit: cover; }
.kartu .foto .ini { left: 0; top: 7.4mm; width: 17.5mm; font-size: 15pt; font-weight: bold; color: #1E7FC4; }
.kartu .foto .cap { left: 0; top: 13mm; width: 17.5mm; font-size: 5.3pt; letter-spacing: 0.15mm; color: #1E7FC4; }

.kartu .nama { left: 27mm; top: 17.4mm; width: 34.5mm; font-weight: bold; white-space: nowrap; overflow: hidden; }
.kartu .ikon { left: 27mm; width: 3.2mm; }
.kartu .lbl { left: 31.2mm; font-size: 6pt; font-weight: bold; letter-spacing: 0.15mm; white-space: nowrap; }
.kartu .val { font-size: 8pt; color: #111827; line-height: 1.2; width: 30.5mm; height: 3.6mm; overflow: hidden; white-space: nowrap; }
.kartu .val.dua { height: 7.2mm; white-space: normal; }

.kartu .qr-kotak { left: 62.8mm; top: 17.5mm; width: 19.2mm; height: 24.5mm; background-color: #FFFFFF; border: 0.25mm solid #E5E7EB; border-radius: 2.6mm; }
.kartu .qr-kotak img { left: 1.5mm; top: 1.4mm; width: 16.2mm; height: 16.2mm; }
.kartu .qr-kotak .qr-label { left: 0; top: 18.9mm; width: 19.2mm; text-align: center; font-size: 5.4pt; font-weight: bold; letter-spacing: 0.1mm; }

.kartu .kaki { left: 0; bottom: 0; width: 85mm; height: 6mm; background-color: #0C5392; color: #FFFFFF; font-size: 6.9pt; }
.kartu .kaki .kiri { left: 4.5mm; top: 1.75mm; white-space: nowrap; }
.kartu .kaki .kanan { right: 4.5mm; top: 1.75mm; white-space: nowrap; }

.kartu .watermark { left: 29mm; top: 11.5mm; width: 27mm; }
.kartu .tt-judul { left: 4.6mm; top: 15.6mm; font-size: 7.2pt; font-weight: bold; white-space: nowrap; }
.kartu .tt-daftar { left: 4.6mm; top: 19mm; width: 50.5mm; font-size: 5.3pt; line-height: 1.22; }
.kartu .tt-daftar table { border-collapse: collapse; width: 50.5mm; }
.kartu .tt-daftar td { padding: 0 0 0.5mm 0; vertical-align: top; }
.kartu .tt-daftar td.no { width: 3.4mm; }
.kartu .tengah { left: 56mm; width: 27mm; text-align: center; }
.kartu .mengetahui { top: 16.2mm; font-size: 5.5pt; line-height: 1.25; }
.kartu .ttd { left: 58.5mm; top: 22.5mm; width: 22.5mm; }
.kartu .garis { left: 57.5mm; width: 24mm; background-color: #14284B; }
.kartu .kepsek-nama { top: 36.8mm; font-size: 6.3pt; font-weight: bold; }
.kartu .kepsek-jabatan { top: 40.6mm; font-size: 5.8pt; }
