<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pengumuman — {{ $pengumuman->judul }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 22mm 18mm 22mm;
        }

        body {
            font-family: 'Times New Roman', Times, Georgia, serif;
            font-size: 11.5pt;
            color: #111827;
            line-height: 1.5;
            margin: 0;
        }

        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            margin-bottom: 22px;
            padding-bottom: 10px;
        }

        .kop-tabel {
            width: 100%;
            border-collapse: collapse;
        }

        .kop-tabel td {
            vertical-align: middle;
        }

        .kop-logo {
            width: 15%;
            text-align: center;
        }

        .kop-logo img {
            width: 80px;
        }

        .kop-teks {
            width: 70%;
            text-align: center;
        }

        .kop-nama {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kop-yayasan {
            font-size: 13pt;
            margin-top: 3px;
        }

        .kop-alamat {
            font-size: 8pt;
            margin-top: 4px;
        }

        .kop-sisi {
            width: 15%;
        }

        .judul-surat {
            text-align: center;
            margin-bottom: 4px;
        }

        .judul-surat .judul {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 6px;
            text-transform: uppercase;
            text-decoration: underline;
            text-decoration-thickness: 2px;
        }

        .judul-surat .nomor {
            font-size: 10.5pt;
        }

        .tentang {
            text-align: center;
            margin: 14px 0 4px;
        }

        .kegiatan {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12.5pt;
            margin-bottom: 16px;
        }

        .paragraf {
            text-align: justify;
            margin-bottom: 10px;
        }

        .tabel-jadwal {
            border-collapse: collapse;
            margin: 8px 0 12px 0;
        }

        .tabel-jadwal td {
            padding: 1.5px 0;
            vertical-align: top;
        }

        .tabel-jadwal .label {
            width: 110px;
        }

        .tabel-jadwal .pemisah {
            width: 14px;
            text-align: center;
        }

        .tanda-tangan {
            margin-top: 28px;
            width: 100%;
        }

        .tanda-tangan table {
            width: 100%;
            border-collapse: collapse;
        }

        .tanda-tangan td {
            width: 50%;
            vertical-align: top;
        }

        .tanda-tangan .kolom-ttd {
            text-align: center;
        }

        .tanda-tangan .ruang-ttd {
            height: 62px;
        }

        .tanda-tangan .ruang-ttd img {
            height: 60px;
        }

        .tanda-tangan .nama {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    @include('exports.partials.kop-surat', ['logo' => $logo])

    <div class="judul-surat">
        <div class="judul">Pengumuman</div>
        @if($pengumuman->nomor_surat)
            <div class="nomor">Nomor: {{ $pengumuman->nomor_surat }}</div>
        @endif
    </div>

    <div class="tentang">Tentang</div>
    <div class="kegiatan">{{ $pengumuman->judul }}</div>

    <div class="paragraf">{!! nl2br(e($pengumuman->isi)) !!}</div>

    @if($pengumuman->punyaJadwal())
        <div class="paragraf">Adapun jadwal kegiatan sebagai berikut:</div>
        <table class="tabel-jadwal">
            @if($pengumuman->jadwal_hari_tanggal)
                <tr>
                    <td class="label">Hari/Tanggal</td>
                    <td class="pemisah">:</td>
                    <td>{{ $pengumuman->jadwal_hari_tanggal }}</td>
                </tr>
            @endif
            @if($pengumuman->jadwal_waktu)
                <tr>
                    <td class="label">Waktu</td>
                    <td class="pemisah">:</td>
                    <td>{{ $pengumuman->jadwal_waktu }}</td>
                </tr>
            @endif
            @if($pengumuman->jadwal_tempat)
                <tr>
                    <td class="label">Tempat</td>
                    <td class="pemisah">:</td>
                    <td>{{ $pengumuman->jadwal_tempat }}</td>
                </tr>
            @endif
        </table>
    @endif

    <div class="paragraf">Demikian pengumuman ini kami sampaikan. Atas perhatiannya kami mengucapkan terima kasih.</div>

    <div class="tanda-tangan">
        <table>
            <tr>
                <td></td>
                <td class="kolom-ttd">
                    @if($pengumuman->tempat)
                        {{ $pengumuman->tempat }}, {{ ($pengumuman->tanggal_surat ?? $pengumuman->created_at)->translatedFormat('d F Y') }}
                    @else
                        {{ ($pengumuman->tanggal_surat ?? $pengumuman->created_at)->translatedFormat('d F Y') }}
                    @endif
                    <br>
                    Kepala SD IT Darul Fikri
                    <div class="ruang-ttd">@if($ttd)<img src="{{ $ttd }}" alt="Tanda tangan">@endif</div>
                    <div class="nama">{{ $namaKepala }}</div>
                    @if($nip)
                        <div>NIP. {{ $nip }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
