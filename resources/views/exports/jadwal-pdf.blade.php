<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Pelajaran - {{ $kelas->nama_kelas }} - SDIT Darul Fikri</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px double #004532; padding-bottom: 15px; }
        .header h1 { font-size: 20px; color: #004532; }
        .header h2 { font-size: 16px; color: #555; margin-top: 4px; }
        .header p { font-size: 11px; color: #999; margin-top: 6px; }
        .info { margin-bottom: 15px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #004532; color: white; padding: 10px 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .footer { margin-top: 30px; text-align: right; font-size: 11px; color: #999; }
        .day-header { font-weight: bold; color: #004532; background: #e6f0ed; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>SDIT DARUL FIKRI</h1>
        <h2>Laporan Jadwal Pelajaran</h2>
        <p>Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
    </div>
    
    <div class="info">
        <strong>Kelas:</strong> {{ $kelas->nama_kelas }} <br>
        <strong>Wali Kelas:</strong> {{ $kelas->waliKelas->nama_lengkap ?? '-' }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Hari</th>
                <th style="width: 20%;">Waktu</th>
                <th style="width: 35%;">Mata Pelajaran</th>
                <th style="width: 30%;">Guru Pengampu</th>
            </tr>
        </thead>
        <tbody>
            @php $currentHari = ''; @endphp
            @forelse($jadwal as $j)
            <tr>
                <td class="{{ $j->hari !== $currentHari ? 'day-header' : '' }}">
                    @if($j->hari !== $currentHari)
                        {{ $j->hari }}
                        @php $currentHari = $j->hari; @endphp
                    @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->jam_selesai)->format('H:i') }}</td>
                <td><strong>{{ $j->mataPelajaran->nama_pelajaran ?? '-' }}</strong></td>
                <td>{{ $j->guru->nama_lengkap ?? $j->guru->name ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; padding: 20px; color: #777;">Belum ada jadwal pelajaran</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="footer">
        <p>SDIT Darul Fikri &copy; {{ date('Y') }}</p>
    </div>
</body>
</html>
