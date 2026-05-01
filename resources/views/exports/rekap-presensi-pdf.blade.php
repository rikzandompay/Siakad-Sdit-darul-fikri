<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Presensi {{ $selectedKelas->nama_kelas }} - SDIT Darul Fikri</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double #004532; padding-bottom: 15px; }
        .header h1 { font-size: 18px; color: #004532; }
        .header h2 { font-size: 14px; color: #555; margin-top: 4px; }
        .header p { font-size: 10px; color: #999; margin-top: 6px; }
        .info { margin-bottom: 15px; }
        .info span { display: inline-block; margin-right: 30px; font-size: 11px; }
        .info strong { color: #004532; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #004532; color: white; padding: 8px 6px; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; text-align: center; }
        tr:nth-child(even) td { background: #f9fafb; }
        td:nth-child(2), td:nth-child(3) { text-align: left; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #999; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>SDIT DARUL FIKRI</h1>
        <h2>Rekapitulasi Kehadiran Siswa - {{ $selectedKelas->nama_kelas }}</h2>
        @if($selectedPelajaran)
            <p>Mata Pelajaran: <strong>{{ $selectedPelajaran->nama_pelajaran }}</strong></p>
        @endif
        <p>Periode: <strong>{{ \Carbon\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y') }}</strong></p>
    </div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Hadir</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpa</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rekapData as $data)
            @php
                $summary = $data['summary'];
                $total = $summary['H'] + $summary['S'] + $summary['I'] + $summary['A'];
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="text-align:left">{{ $data['siswa']->nis }}</td>
                <td style="text-align:left"><strong>{{ $data['siswa']->nama_siswa }}</strong></td>
                <td>{{ $summary['H'] }}</td>
                <td>{{ $summary['S'] }}</td>
                <td>{{ $summary['I'] }}</td>
                <td>{{ $summary['A'] }}</td>
                <td><strong>{{ $total }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        <p>Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB | SDIT Darul Fikri &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
