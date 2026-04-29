<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nilai {{ $mapel->nama_pelajaran }} - {{ $kelas->nama_kelas }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double #004532; padding-bottom: 15px; }
        .header h1 { font-size: 18px; color: #004532; }
        .header h2 { font-size: 14px; color: #555; margin-top: 4px; }
        .header p { font-size: 10px; color: #999; margin-top: 6px; }
        .info { margin-bottom: 15px; }
        .info span { display: inline-block; margin-right: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #004532; color: white; padding: 8px 6px; font-size: 10px; text-transform: uppercase; }
        td { padding: 6px; border-bottom: 1px solid #e5e7eb; text-align: center; }
        td:nth-child(2), td:nth-child(3) { text-align: left; }
        tr:nth-child(even) td { background: #f9fafb; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #999; }
        .grade-a { background: #d1fae5 !important; color: #065f46; font-weight: bold; }
        .grade-b { background: #fef3c7 !important; color: #92400e; font-weight: bold; }
        .grade-c { background: #ffedd5 !important; color: #9a3412; font-weight: bold; }
        .grade-d { background: #fee2e2 !important; color: #991b1b; font-weight: bold; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>SDIT DARUL FIKRI</h1>
        <h2>Laporan Nilai Rapot</h2>
        <p>Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
    </div>
    <div class="info">
        <span><strong>Kelas:</strong> {{ $kelas->nama_kelas }}</span>
        <span><strong>Mata Pelajaran:</strong> {{ $mapel->nama_pelajaran }}</span>
        <span><strong>Tahun Ajaran:</strong> {{ $tahunAjaran->nama_semester }}</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Tugas (30%)</th>
                <th>UTS (30%)</th>
                <th>UAS (40%)</th>
                <th>Nilai Akhir</th>
                <th>Predikat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($siswaList as $i => $s)
                @php
                    $n = $nilaiData[$s->id] ?? null;
                    $na = $n ? $n->nilai_akhir : 0;
                    $predikat = \App\Models\NilaiRapot::getPredikat($na);
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td style="text-align:left">{{ $s->nis }}</td>
                    <td style="text-align:left"><strong>{{ $s->nama_siswa }}</strong></td>
                    <td>{{ $n ? number_format($n->nilai_tugas, 1) : '0.0' }}</td>
                    <td>{{ $n ? number_format($n->nilai_uts, 1) : '0.0' }}</td>
                    <td>{{ $n ? number_format($n->nilai_uas, 1) : '0.0' }}</td>
                    <td><strong>{{ number_format($na, 1) }}</strong></td>
                    <td class="grade-{{ strtolower($predikat) }}">{{ $predikat }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        <p>Formula: (Tugas × 30%) + (UTS × 30%) + (UAS × 40%) | SDIT Darul Fikri &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
