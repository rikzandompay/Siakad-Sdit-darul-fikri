<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Surat Izin — {{ $nama_anak }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 25mm 25mm 25mm 25mm;
        }

        body {
            font-family: 'Times New Roman', Times, Georgia, serif;
            font-size: 12pt;
            color: #111827;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .header-tanggal {
            text-align: left;
            margin-bottom: 22px;
        }

        .tujuan {
            margin-bottom: 22px;
            line-height: 1.45;
        }

        .salam-pembuka {
            margin-bottom: 16px;
        }

        .paragraf {
            text-align: justify;
            margin-bottom: 14px;
            text-indent: 0;
        }

        .tabel-identitas {
            width: 100%;
            margin: 10px 0 16px 0;
            border-collapse: collapse;
        }

        .tabel-identitas td {
            padding: 2.5px 0;
            vertical-align: top;
            font-size: 12pt;
        }

        .tabel-identitas .label {
            width: 170px;
        }

        .tabel-identitas .pemisah {
            width: 18px;
            text-align: center;
        }

        .tabel-identitas .nilai {
            font-weight: normal;
        }

        .penutup {
            margin-top: 18px;
            margin-bottom: 26px;
        }

        .tanda-tangan {
            margin-top: 24px;
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
    <div class="header-tanggal">
        {{ $kota }}, {{ $tanggal_surat }}
    </div>

    <div class="tujuan">
        Kepada Yth.<br>
        <strong>{{ $nama_wali_kelas }}</strong><br>
        Wali Kelas {{ $kelas }}<br>
        {{ $nama_sekolah }}<br>
        di Tempat
    </div>

    <div class="salam-pembuka">
        Dengan hormat,
    </div>

    <div class="paragraf">
        Saya yang bertanda tangan di bawah ini:
    </div>

    <table class="tabel-identitas">
        <tr>
            <td class="label">Nama</td>
            <td class="pemisah">:</td>
            <td class="nilai">{{ $nama_orang_tua }}</td>
        </tr>
        <tr>
            <td class="label">Orang tua/wali dari</td>
            <td class="pemisah">:</td>
            <td class="nilai">{{ $nama_anak }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td>
            <td class="pemisah">:</td>
            <td class="nilai">{{ $kelas }}</td>
        </tr>
    </table>

    <div class="paragraf">
        dengan surat ini bermaksud memberitahukan bahwa anak kami, <strong>{{ $nama_anak }}</strong>, tidak dapat mengikuti kegiatan belajar mengajar di sekolah karena {{ $alasan_teks }}, terhitung mulai tanggal {{ $tanggal_mulai }} selama {{ $jumlah_hari }} hari.
    </div>

    @if(!empty($kontak))
    <div class="paragraf">
        Apabila Bapak/Ibu memerlukan informasi lebih lanjut, kami dapat dihubungi melalui {{ $kontak }}.
    </div>
    @endif

    <div class="paragraf penutup">
        Demikian surat pemberitahuan ini kami sampaikan. Atas perhatian dan izin yang Bapak/Ibu berikan, kami ucapkan terima kasih.
    </div>

    <div class="tanda-tangan">
        Hormat kami,<br>
        <div class="ruang-ttd"></div>
        <div class="nama-ttd">{{ $nama_orang_tua }}</div>
        <div class="keterangan-ttd">Orang Tua/Wali {{ $nama_anak }}</div>
    </div>
</body>

</html>
