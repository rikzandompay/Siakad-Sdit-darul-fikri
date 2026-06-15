<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai Siswa - {{ $selectedKelas->nama_kelas }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 1.5cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
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
            font-size: 9pt;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 4px 6px;
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

        .text-red {
            color: #991b1b;
        }

        .bg-gray {
            background: #e5e7eb !important;
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
            padding-bottom: 60px;
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
        <h2>LEMBAGA YAYASAN PENDIDIKAN DARUL FIKRI</h2>
        <p>Alamat: Jl. Sungai Durian Laut Kec.Sungai Raya Kabupaten Kubu Raya, Provinsi Kalimantan Barat, Kode Pos
            78391<br>Telp:
            (021) 1234567 | Email:
            info@sditdarulfikri.sch.id</p>
    </div>

    <div class="title">REKAPITULASI NILAI SISWA</div>

    <table class="info-table">
        <tr>
            <td>Kelas</td>
            <td>:</td>
            <td>{{ $selectedKelas->nama_kelas }}</td>
            <td>Tahun Ajaran</td>
            <td>:</td>
            <td>{{ $tahunAjaranAktif->nama_semester }}</td>
        </tr>
        <tr>
            <td>Tanggal Cetak</td>
            <td>:</td>
            <td>{{ now()->translatedFormat('d F Y') }}</td>
            <td>Total Siswa</td>
            <td>:</td>
            <td>{{ $siswaList->count() }} Orang</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%">No</th>
                <th style="width: 8%">NIS</th>
                <th style="width: 15%">Nama Siswa</th>
                @foreach ($mapelList as $m)
                    <th>{{ substr($m->nama_pelajaran, 0, 15) }}{{ strlen($m->nama_pelajaran) > 15 ? '...' : '' }}</th>
                @endforeach
                <th style="background-color: #065f46; color: white;">Rata-rata</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($siswaList as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="text-left">{{ $s->nis }}</td>
                    <td class="text-left"><strong>{{ $s->nama_siswa }}</strong></td>
                    @foreach ($mapelList as $m)
                        @php
                            $nilai = $nilaiMap[$s->id][$m->id] ?? null;
                        @endphp
                        <td>
                            @if ($nilai !== null)
                                <span
                                    class="{{ $nilai < 75 ? 'text-red' : '' }}">{{ number_format($nilai, 1) }}</span>
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                    @php
                        $rataRata = $rataRataSiswa[$s->id] ?? 0;
                    @endphp
                    <td style="font-weight: bold; background: #f0fdf4;">
                        {{ $rataRata > 0 ? number_format($rataRata, 1) : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature">
        <table>
            <tr>
                <td>
                    Mengetahui,<br>Kepala Sekolah
                    <br><br><br><br><br>
                    <br><b>Dewi Quraisyinta<br>
                        <span class="signature-name">_______________________</span><br>
                        NIP. .........................
                </td>
                <td>
                    ............, {{ now()->translatedFormat('d F Y') }}<br>Wali Kelas
                    <br><br><br><br><br>
                    <span
                        class="signature-name">{{ $selectedKelas->waliKelas->nama_lengkap ?? '_______________________' }}</span><br>
                    NIP. .........................
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
