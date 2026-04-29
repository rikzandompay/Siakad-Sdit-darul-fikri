<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai Siswa - {{ $selectedKelas->nama_kelas }}</title>
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
        th { background: #004532; color: white; padding: 8px 6px; font-size: 10px; text-transform: uppercase; border: 1px solid #004532; }
        td { padding: 6px; border: 1px solid #e5e7eb; text-align: center; }
        td:nth-child(2), td:nth-child(3) { text-align: left; }
        tr:nth-child(even) td { background: #f9fafb; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #999; }
        .text-red { color: #991b1b; }
        .bg-gray { background: #e5e7eb !important; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>SDIT DARUL FIKRI</h1>
        <h2>Rekapitulasi Nilai Siswa</h2>
        <p>Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
    </div>
    <div class="info">
        <span><strong>Kelas:</strong> {{ $selectedKelas->nama_kelas }}</span>
        <span><strong>Tahun Ajaran:</strong> {{ $tahunAjaranAktif->nama_semester }}</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                @foreach($mapelList as $m)
                    <th>{{ substr($m->nama_pelajaran, 0, 15) }}{{ strlen($m->nama_pelajaran) > 15 ? '...' : '' }}</th>
                @endforeach
                <th style="background-color: #065f46;">Rata-rata</th>
            </tr>
        </thead>
        <tbody>
            @foreach($siswaList as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $s->nis }}</td>
                    <td><strong>{{ $s->nama_siswa }}</strong></td>
                    @foreach($mapelList as $m)
                        @php
                            $nilai = $nilaiMap[$s->id][$m->id] ?? null;
                        @endphp
                        <td>
                            @if($nilai !== null)
                                <span class="{{ $nilai < 75 ? 'text-red' : '' }}">{{ number_format($nilai, 1) }}</span>
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                    @php
                        $rataRata = $rataRataSiswa[$s->id] ?? 0;
                    @endphp
                    <td style="font-weight: bold; background: #f0fdf4;">{{ $rataRata > 0 ? number_format($rataRata, 1) : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        <p>SDIT Darul Fikri &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
