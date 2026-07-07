<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Siswa - SDIT Darul Fikri</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000;
            line-height: 1.5;
        }

        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }

        .kop-surat h1 {
            font-size: 18pt;
            margin: 0;
            text-transform: uppercase;
            font-weight: bold;
        }

        .kop-surat h2 {
            font-size: 14pt;
            margin: 5px 0 0;
        }

        .kop-surat p {
            font-size: 10pt;
            margin: 5px 0 0;
        }

        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 20px;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 11pt;
        }

        .info-table td {
            padding: 2px 5px;
            vertical-align: top;
        }

        .info-table td:first-child {
            width: 120px;
            font-weight: bold;
        }

        .info-table td:nth-child(2) {
            width: 10px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 10pt;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
        }

        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            vertical-align: middle;
        }

        .data-table td.text-left {
            text-align: left;
        }

        .signature {
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid;
            font-size: 11pt;
        }

        .signature table {
            width: 100%;
            text-align: center;
        }

        .signature td {
            width: 50%;
            vertical-align: top;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="kop-surat">
        <h1>SD IT DARUL FIKRI</h1>
        <h2>YAYASAN PENDIDIKAN DARUL FIKRI</h2>
        <p>Alamat: Jl. Sungai Durian Laut Kec.Sungai Raya Kabupaten Kubu Raya, Provinsi Kalimantan Barat, Kode Pos
            78391<br>Telp:
            (021) 1234567 | Email:
            info@sditdarulfikri.sch.id</p>
    </div>

    <div class="title">LAPORAN DATA SISWA</div>

    <table class="info-table">
        <tr>
            <td>Tanggal Cetak</td>
            <td>:</td>
            <td>{{ now()->translatedFormat('d F Y') }}</td>
            <td>Total Siswa</td>
            <td>:</td>
            <td>{{ $data->count() }} Orang</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%">No</th>
                <th style="width: 8%">NIS</th>
                <th style="width: 10%">NISN</th>
                <th style="width: 15%">Nama Siswa</th>
                <th style="width: 10%">Tgl Lahir</th>
                <th style="width: 15%">Alamat</th>
                <th style="width: 3%">L/P</th>
                <th style="width: 5%">Kelas</th>
                <th style="width: 12%">Nama Wali</th>
                <th style="width: 12%">No HP</th>
                <th style="width: 7%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $s->nis }}</td>
                    <td>{{ $s->nisn }}</td>
                    <td class="text-left"><strong>{{ $s->nama_siswa }}</strong></td>
                    <td>{{ $s->tanggal_lahir }}</td>
                    <td class="text-left">{{ $s->alamat ?? '-' }}</td>
                    <td>{{ $s->jenis_kelamin }}</td>
                    <td>{{ $s->kelas->nama_kelas ?? '-' }}</td>
                    <td class="text-left">{{ $s->nama_wali ?? '-' }}</td>
                    <td>{{ $s->no_hp_wali ?? '-' }}</td>
                    <td>{{ $s->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">Tidak ada data siswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature">
        <table>
            <tr>
                <td style="padding-bottom: 70px;">
                    Mengetahui,<br>Kepala Sekolah
                </td>
                <td style="padding-bottom: 70px;">
                    ............, {{ now()->translatedFormat('d F Y') }}<br>Admin Akademik
                </td>
            </tr>
            <tr>
                <td>
                    <b>Dewi Quraisyinta</b><br>
                    <span class="signature-name">_______________________</span><br>
                    NIP. .........................
                </td>
                <td>
                    <b>{{ Auth::user()->name ?? '_______________________' }}</b><br>
                    <span class="signature-name">_______________________</span><br>
                    NIP. .........................
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
