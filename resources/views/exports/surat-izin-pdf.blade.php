<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Izin — {{ $nama_anak }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 22mm 25mm 22mm 25mm;
        }

        body {
            font-family: 'Times New Roman', Times, Georgia, serif;
            font-size: 12pt;
            color: #111827;
            line-height: 1.55;
            margin: 0;
            padding: 0;
        }

        .kop {
            text-align: center;
            border-bottom: 3px double #111827;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }

        .kop .nomor {
            font-size: 10pt;
            letter-spacing: .3px;
        }

        .tanggal {
            text-align: right;
            margin-bottom: 18px;
        }

        .tujuan {
            margin-bottom: 18px;
            line-height: 1.45;
        }

        .paragraf {
            text-align: justify;
            margin-bottom: 12px;
        }

        .tabel-identitas {
            width: 100%;
            margin: 8px 0 14px 0;
            border-collapse: collapse;
        }

        .tabel-identitas td {
            padding: 2px 0;
            vertical-align: top;
        }

        .tabel-identitas .label {
            width: 165px;
        }

        .tabel-identitas .pemisah {
            width: 16px;
            text-align: center;
        }

        .penutup {
            margin-top: 16px;
            margin-bottom: 24px;
        }

        .tanda-tangan {
            margin-top: 20px;
            line-height: 1.4;
        }

        .tanda-tangan .ruang-ttd {
            height: 60px;
        }

        .tanda-tangan .nama-ttd {
            font-weight: bold;
            text-decoration: underline;
        }

        .tanda-tangan .keterangan-ttd {
            font-size: 11pt;
            color: #374151;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    {{-- Surat orang tua: tanpa kop sekolah & tanpa tanda tangan kepala sekolah --}}
    <div class="kop">
        <div style="font-weight:bold;font-size:14pt;letter-spacing:.5px;">SURAT IZIN KEPERLUAN</div>
        @if(! empty($nomor_surat))
            <div class="nomor">Nomor: {{ $nomor_surat }}</div>
        @endif
    </div>

    <div class="tanggal">
        {{ $kota }}, {{ $tanggal_surat }}
    </div>

    <div class="tujuan">
        Kepada Yth.<br>
        <strong>{{ $nama_wali_kelas }}</strong><br>
        Wali Kelas {{ $kelas }}<br>
        {{ $nama_sekolah }}<br>
        di Tempat
    </div>

    <div class="paragraf">Dengan hormat,</div>

    <div class="paragraf">Saya yang bertanda tangan di bawah ini:</div>

    <table class="tabel-identitas">
        <tr>
            <td class="label">Nama</td>
            <td class="pemisah">:</td>
            <td>{{ $nama_orang_tua }}</td>
        </tr>
        <tr>
            <td class="label">Orang tua/wali dari</td>
            <td class="pemisah">:</td>
            <td>{{ $nama_anak }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td>
            <td class="pemisah">:</td>
            <td>{{ $kelas }}</td>
        </tr>
    </table>

    <div class="paragraf">
        dengan surat ini bermaksud memberitahukan bahwa anak kami, <strong>{{ $nama_anak }}</strong>, tidak dapat
        mengikuti kegiatan belajar mengajar di sekolah karena {{ $alasan_teks }}, terhitung mulai tanggal
        {{ $tanggal_mulai }} selama {{ $jumlah_hari }} hari.
    </div>

    @if(! empty($kontak))
        <div class="paragraf">
            Apabila Bapak/Ibu memerlukan informasi lebih lanjut, kami dapat dihubungi melalui {{ $kontak }}.
        </div>
    @endif

    <div class="paragraf penutup">
        Demikian surat pemberitahuan ini kami sampaikan. Atas perhatian dan izin yang Bapak/Ibu berikan, kami ucapkan
        terima kasih.
    </div>

    <div class="tanda-tangan">
        Hormat kami,<br>
        <div class="ruang-ttd"></div>
        <div class="nama-ttd">{{ $nama_orang_tua }}</div>
        <div class="keterangan-ttd">Orang Tua/Wali {{ $nama_anak }}</div>
    </div>
</body>

</html>
