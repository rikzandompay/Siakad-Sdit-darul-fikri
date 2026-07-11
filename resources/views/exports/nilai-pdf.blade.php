<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nilai {{ $mapel->nama_pelajaran }} - {{ $kelas->nama_kelas }}</title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            color: #000;
            line-height: 1.4;
        }

        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            margin-bottom: 15px;
            padding-bottom: 8px;
        }

        .kop-surat h1 {
            font-size: 16pt;
            margin: 0;
            text-transform: uppercase;
            font-weight: bold;
        }

        .kop-surat h2 {
            font-size: 12pt;
            margin: 3px 0 0;
        }

        .kop-surat p {
            font-size: 9pt;
            margin: 3px 0 0;
        }

        .title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 15px;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 10pt;
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
            margin-bottom: 20px;
            font-size: 8pt;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 3px 4px;
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

        .highlight {
            background: #f0fdf4;
            font-weight: bold;
        }

        .signature {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
            font-size: 10pt;
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
        <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
            <tr>
                <td style="width: 15%; text-align: center; vertical-align: middle; border: none; padding: 0;">
                    <img src="{{ asset('Lgo.png') }}" alt="Logo" style="width: 80px;">
                </td>
                <td style="width: 70%; text-align: center; border: none; padding: 0;">
                    <h1 style="font-size: 18pt; margin: 0; text-transform: uppercase; font-weight: bold;">SD IT DARUL FIKRI</h1>
                    <h2 style="font-size: 14pt; margin: 5px 0 0;">YAYASAN PENDIDIKAN DARUL FIKRI</h2>
                    <p style="font-size: 8pt; margin: 4px 0 0;">Alamat: Jl. Sungai Durian Laut Kec.Sungai Raya Kabupaten Kubu Raya, Provinsi Kalimantan Barat, Kode Pos 78391<br>Telp: (021) 1234567 | Email: info@sditdarulfikri.sch.id</p>
                </td>
                <td style="width: 15%; border: none; padding: 0;"></td>
            </tr>
        </table>
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
                <th rowspan="2" style="width: 3%">No</th>
                <th rowspan="2" style="width: 8%">NIS</th>
                <th rowspan="2" style="width: 12%">Nama Siswa</th>
                <th colspan="6" style="background: #d1fae5;">BAB 1</th>
                <th colspan="6" style="background: #d1fae5;">BAB 2</th>
                <th colspan="6" style="background: #d1fae5;">BAB 3</th>
                <th colspan="6" style="background: #d1fae5;">BAB 4</th>
                <th rowspan="2" style="background: #065f46; color: white;">Formatif (50%)</th>
                <th rowspan="2" style="background: #7c3aed; color: white;">SAS (30%)</th>
                <th rowspan="2" style="background: #d97706; color: white;">Kehadiran</th>
                <th colspan="2" style="background: #fee2e2;">Pengurang</th>
                <th rowspan="2" style="background: #1f2937; color: white;">Nilai Rapor</th>
                <th rowspan="2" style="background: #f3f4f6;">Predikat</th>
            </tr>
            <tr>
                @for ($bab = 1; $bab <= 4; $bab++)
                    <th style="font-size: 7pt;">TP1</th>
                    <th style="font-size: 7pt;">TP2</th>
                    <th style="font-size: 7pt;">TP3</th>
                    <th style="font-size: 7pt;">TP4</th>
                    <th style="font-size: 7pt;">UH</th>
                    <th style="background: #a7f3d0; font-size: 7pt;">Rata²</th>
                @endfor
                <th style="font-size: 7pt;">-</th>
                <th style="font-size: 7pt;">-</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($siswaList as $i => $s)
                @php
                    $n = $nilaiData[$s->id] ?? null;
                    $formatifData = $n ? $n->formatif_data : \App\Models\NilaiRapot::getDefaultFormatifData();
                    if (is_string($formatifData)) {
                        $formatifData = json_decode($formatifData, true);
                    }
                    $formatifData = $formatifData ?: \App\Models\NilaiRapot::getDefaultFormatifData();
                    $babAverages = $n ? $n->getBabAverages() : [];
                    $formatifTotal = $n ? $n->getFormatifTotal() : 0;
                    $nilaiRapor = $n ? $n->nilai_rapor : 0;
                    $predikat = \App\Models\NilaiRapot::getPredikat($nilaiRapor);
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="text-left">{{ $s->nis }}</td>
                    <td class="text-left"><strong>{{ $s->nama_siswa }}</strong></td>
                    @for ($bab = 1; $bab <= 4; $bab++)
                        @php $babKey = "bab{$bab}"; @endphp
                        <td>{{ $formatifData[$babKey]['tp1'] ?? 0 }}</td>
                        <td>{{ $formatifData[$babKey]['tp2'] ?? 0 }}</td>
                        <td>{{ $formatifData[$babKey]['tp3'] ?? 0 }}</td>
                        <td>{{ $formatifData[$babKey]['tp4'] ?? 0 }}</td>
                        <td>{{ $formatifData[$babKey]['uh'] ?? 0 }}</td>
                        <td class="highlight">{{ number_format($babAverages[$babKey] ?? 0, 1) }}</td>
                    @endfor
                    <td class="highlight" style="background: #d1fae5;">{{ number_format($formatifTotal, 1) }}</td>
                    <td>{{ $n ? number_format($n->sas, 1) : '0.0' }}</td>
                    <td>{{ $n ? number_format($n->kehadiran, 1) : '0.0' }}</td>
                    <td>{{ $n ? number_format($n->pengurang_tidaktelat, 1) : '0.0' }}</td>
                    <td>{{ $n ? number_format($n->pengurang_menyontek, 1) : '0.0' }}</td>
                    <td class="highlight" style="background: #e5e7eb;">{{ number_format($nilaiRapor, 1) }}</td>
                    <td class="grade-{{ strtolower($predikat) }}">{{ $predikat }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature">
        <table>
            <tr>
                <td style="padding-bottom: 70px;">
                    Mengetahui,<br>Kepala Sekolah
                </td>
                <td style="padding-bottom: 70px;">
                    {{ now()->translatedFormat('d F Y') }}<br>Wali Kelas
                </td>
            </tr>
            <tr>
                <td>
                    <b>Dewi Quraisyinta</b><br>
                    <span class="signature-name">_______________________</span><br>
                    NIP. .........................
                </td>
                <td>
                    <b>{{ $kelas->waliKelas->nama_lengkap ?? '_______________________' }}</b><br>
                    <span class="signature-name">_______________________</span><br>
                    NIP. .........................
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
