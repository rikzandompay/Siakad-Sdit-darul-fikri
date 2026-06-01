<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nilai {{ $mapel->nama_pelajaran }} - {{ $kelas->nama_kelas }}</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
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
            font-size: 11pt;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 6px 8px;
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

        .grade-a {
            background: #d1fae5 !important;
            color: #065f46;
            font-weight: bold;
        }

        .grade-b {
            background: #fef3c7 !important;
            color: #92400e;
            font-weight: bold;
        }

        .grade-c {
            background: #ffedd5 !important;
            color: #9a3412;
            font-weight: bold;
        }

        .grade-d {
            background: #fee2e2 !important;
            color: #991b1b;
            font-weight: bold;
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
        <h2>YAYASAN PENDIDIKAN DARUL FIKRI</h2>
        <p>Alamat: Jl. Sungai Durian Laut Kec.Sungai Raya Kabupaten Kubu Raya, Provinsi Kalimantan Barat, Kode Pos
            78391<br>Telp:
            (021) 1234567 | Email:
            info@sditdarulfikri.sch.id</p>
    </div>

    <div class="title">LAPORAN NILAI RAPOT</div>

    <table class="info-table">
        <tr>
            <td>Kelas</td>
            <td>:</td>
            <td>{{ $kelas->nama_kelas }}</td>
            <td>Tahun Ajaran</td>
            <td>:</td>
            <td>{{ $tahunAjaran->nama_semester }}</td>
        </tr>
        <tr>
            <td>Mata Pelajaran</td>
            <td>:</td>
            <td>{{ $mapel->nama_pelajaran }}</td>
            <td>Tanggal Cetak</td>
            <td>:</td>
            <td>{{ now()->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 15%">NIS</th>
                <th style="width: 30%">Nama Siswa</th>
                <th style="width: 10%">Tugas (30%)</th>
                <th style="width: 10%">UTS (30%)</th>
                <th style="width: 10%">UAS (40%)</th>
                <th style="width: 10%">Nilai Akhir</th>
                <th style="width: 10%">Predikat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($siswaList as $i => $s)
                @php
                    $n = $nilaiData[$s->id] ?? null;
                    $na = $n ? $n->nilai_akhir : 0;
                    $predikat = \App\Models\NilaiRapot::getPredikat($na);
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="text-left">{{ $s->nis }}</td>
                    <td class="text-left"><strong>{{ $s->nama_siswa }}</strong></td>
                    <td>{{ $n ? number_format($n->nilai_tugas, 1) : '0.0' }}</td>
                    <td>{{ $n ? number_format($n->nilai_uts, 1) : '0.0' }}</td>
                    <td>{{ $n ? number_format($n->nilai_uas, 1) : '0.0' }}</td>
                    <td><strong>{{ number_format($na, 1) }}</strong></td>
                    <td class="grade-{{ strtolower($predikat) }}">{{ $predikat }}</td>
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
                    <span class="signature-name">_______________________</span><br>
                    NIP. .........................
                </td>
                <td>
                    Mengetahui,<br>Kepala Sekolah
                    <br><br><br><br><br>
                    <br><b>Dewi Quraisyinta<br>
                        <span class="signature-name">_______________________</span><br>
                        NIP. .........................
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
