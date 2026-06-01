<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Pelajaran - {{ $kelas->nama_kelas }} - SD IT Darul Fikri</title>
    <style>
        @page { size: A4; margin: 2cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; color: #000; line-height: 1.5; }
        .kop-surat { text-align: center; border-bottom: 3px double #000; margin-bottom: 20px; padding-bottom: 10px; }
        .kop-surat h1 { font-size: 18pt; margin: 0; text-transform: uppercase; font-weight: bold; }
        .kop-surat h2 { font-size: 14pt; margin: 5px 0 0; }
        .kop-surat p { font-size: 10pt; margin: 5px 0 0; }
        .title { text-align: center; font-size: 14pt; font-weight: bold; margin-bottom: 20px; text-decoration: underline; text-transform: uppercase; }
        .info-table { width: 100%; margin-bottom: 20px; font-size: 11pt; }
        .info-table td { padding: 2px 5px; vertical-align: top; }
        .info-table td:first-child { width: 120px; font-weight: bold; }
        .info-table td:nth-child(2) { width: 10px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 11pt; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 6px 8px; text-align: center; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; vertical-align: middle; }
        .data-table td.text-left { text-align: left; }
        .day-header { font-weight: bold; background-color: #e6f0ed; }
        .signature { width: 100%; margin-top: 40px; page-break-inside: avoid; font-size: 11pt; }
        .signature table { width: 100%; text-align: center; }
        .signature td { width: 50%; padding-bottom: 60px; }
        .signature-name { font-weight: bold; text-decoration: underline; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="kop-surat">
        <h1>SD IT DARUL FIKRI</h1>
        <h2>YAYASAN PENDIDIKAN DARUL FIKRI</h2>
        <p>Alamat: Jl. Raya Pendidikan No. 1, Kota, Provinsi, Kode Pos 12345<br>Telp: (021) 1234567 | Email: info@sditdarulfikri.sch.id</p>
    </div>

    <div class="title">LAPORAN JADWAL PELAJARAN</div>

    <table class="info-table">
        <tr>
            <td>Kelas</td><td>:</td><td>{{ $kelas->nama_kelas }}</td>
            <td>Tahun Ajaran</td><td>:</td><td>{{ date('Y') }}/{{ date('Y')+1 }}</td>
        </tr>
        <tr>
            <td>Wali Kelas</td><td>:</td><td>{{ $kelas->waliKelas->nama_lengkap ?? '-' }}</td>
            <td>Tanggal Cetak</td><td>:</td><td>{{ now()->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <table class="data-table">
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
                        @if ($j->hari !== $currentHari)
                            {{ $j->hari }}
                            @php $currentHari = $j->hari; @endphp
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->jam_selesai)->format('H:i') }}</td>
                    <td class="text-left"><strong>{{ $j->mataPelajaran->nama_pelajaran ?? '-' }}</strong></td>
                    <td class="text-left">{{ $j->guru->nama_lengkap ?? ($j->guru->name ?? '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Belum ada jadwal pelajaran.</td>
                </tr>
            @endforelse
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
                    ............, {{ now()->translatedFormat('d F Y') }}<br>Wali Kelas
                    <br><br><br><br><br>
                    <span class="signature-name">{{ $kelas->waliKelas->nama_lengkap ?? '_______________________' }}</span><br>
                    NIP. .........................
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
