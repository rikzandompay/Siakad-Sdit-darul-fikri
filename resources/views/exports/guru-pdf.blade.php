<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Guru - SDIT Darul Fikri</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            color: #000;
            line-height: 1.25;
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
            font-size: 8pt;
            margin: 4px 0 0;
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
            table-layout: fixed;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 7.2pt;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            word-break: break-word;
            overflow-wrap: anywhere;
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
            margin-top: 24px;
            page-break-inside: avoid;
            font-size: 9pt;
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

    <div class="title">LAPORAN DATA GURU</div>

    <table class="info-table">
        <tr>
            <td>Tanggal Cetak</td>
            <td>:</td>
            <td>{{ now()->translatedFormat('d F Y') }}</td>
            <td>Total Guru</td>
            <td>:</td>
            <td>{{ $data->count() }} Orang</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%">No</th>
                <th style="width: 12%">NIP</th>
                <th style="width: 25%">Nama Lengkap</th>
                <th style="width: 5%">Gender</th>
                <th style="width: 15%">Username</th>
                <th style="width: 20%">Email</th>
                <th style="width: 10%">No HP</th>
                <th style="width: 10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $i => $g)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $g->nip ?? '-' }}</td>
                    <td class="text-left"><strong>{{ $g->nama_lengkap ?? $g->name }}</strong></td>
                    <td>{{ $g->jenis_kelamin ?? '-' }}</td>
                    <td>{{ $g->username ?? '-' }}</td>
                    <td class="text-left">{{ $g->email }}</td>
                    <td>{{ $g->no_hp ?? '-' }}</td>
                    <td>{{ $g->status ?? 'Aktif' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Tidak ada data guru.</td>
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
