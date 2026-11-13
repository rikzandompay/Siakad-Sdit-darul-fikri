<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Presensi Guru {{ $periodeLabel }} - SDIT Darul Fikri</title>
    <style>
        @page { size: A4 {{ $selectedGuru ? 'landscape' : 'portrait' }}; margin: 1.6cm; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; color: #000; line-height: 1.4; }
        .kop { border-bottom: 3px double #000; margin-bottom: 18px; padding-bottom: 8px; }
        .kop table { width: 100%; border-collapse: collapse; }
        .kop td { border: none; padding: 0; }
        .title { text-align: center; font-size: 13pt; font-weight: bold; margin-bottom: 14px; text-decoration: underline; }
        .info td { padding: 2px 6px; }
        .info td:first-child { font-weight: bold; width: 140px; }
        .data { width: 100%; border-collapse: collapse; margin: 12px 0 28px; font-size: 10.5pt; }
        .data th, .data td { border: 1px solid #000; padding: 5px 7px; text-align: center; }
        .data th { background: #f2f2f2; }
        .data td.l { text-align: left; }
        .sign { width: 100%; text-align: center; page-break-inside: avoid; }
        .sign td { width: 50%; vertical-align: top; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style>
</head>

<body onload="window.print()">
    <div class="kop">
        <table>
            <tr>
                <td style="width: 15%; text-align: center;"><img src="{{ asset('Lgo.png') }}" alt="Logo" style="width: 76px;"></td>
                <td style="width: 70%; text-align: center;">
                    <div style="font-size: 17pt; font-weight: bold;">SD IT DARUL FIKRI</div>
                    <div style="font-size: 13pt;">YAYASAN PENDIDIKAN DARUL FIKRI</div>
                    <div style="font-size: 8pt;">Jl. Sungai Durian Laut Kec. Sungai Raya Kabupaten Kubu Raya, Kalimantan Barat 78391</div>
                </td>
                <td style="width: 15%;"></td>
            </tr>
        </table>
    </div>

    <div class="title">REKAPITULASI PRESENSI GURU</div>

    <table class="info">
        <tr><td>Periode</td><td>:</td><td>{{ $periodeLabel }}</td></tr>
        @if($selectedGuru)
            <tr><td>Nama Guru</td><td>:</td><td>{{ $selectedGuru->nama_lengkap ?? $selectedGuru->name }}</td></tr>
            <tr><td>NIP</td><td>:</td><td>{{ $selectedGuru->nip ?? '-' }}</td></tr>
        @endif
        <tr><td>Jam masuk acuan</td><td>:</td><td>{{ $jamMasuk }}</td></tr>
    </table>

    <table class="data">
        @if($selectedGuru)
            <thead>
                <tr><th>No</th><th>Tanggal</th><th>Status</th><th>Masuk</th><th>Pulang</th><th>Terlambat</th><th>Jarak Masuk</th><th>Wajah</th><th>Keterangan</th></tr>
            </thead>
            <tbody>
                @forelse($detail as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="l">{{ $row->tanggal->translatedFormat('D, d M Y') }}</td>
                        <td>{{ $row->status }}</td>
                        <td>{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-' }}</td>
                        <td>{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-' }}</td>
                        <td>{{ $row->terlambat ? 'Ya' : '-' }}</td>
                        <td>{{ $row->masuk_jarak_meter !== null ? $row->masuk_jarak_meter.' m' : '-' }}</td>
                        <td>{{ $row->masuk_skor_wajah !== null ? $row->masuk_skor_wajah.'%' : '-' }}</td>
                        <td class="l">@if($insidenPerHari->has($row->tanggal->toDateString()))<b>[Lokasi palsu {{ $insidenPerHari->get($row->tanggal->toDateString()) }}]</b> @endif{{ $row->keterangan ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9">Belum ada data absen pada periode ini.</td></tr>
                @endforelse
            </tbody>
        @else
            <thead>
                <tr><th style="width:5%">No</th><th style="width:18%">NIP</th><th>Nama Guru</th><th style="width:10%">Hadir</th><th style="width:11%">Terlambat</th><th style="width:12%">Tidak Hadir</th><th style="width:10%">% Hadir</th></tr>
            </thead>
            <tbody>
                @forelse($ringkasan as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="l">{{ $row['guru']->nip ?? '-' }}</td>
                        <td class="l">{{ $row['guru']->nama_lengkap ?? $row['guru']->name }}@if($row['lokasi_palsu'] > 0)<br><b>Lokasi palsu: {{ $row['lokasi_palsu'] }} hari</b>@endif @if($row['koreksi_mandiri'] > 0)<br><b>Koreksi mandiri: {{ $row['koreksi_mandiri'] }}×</b>@endif</td>
                        <td>{{ $row['hadir'] }}</td>
                        <td>{{ $row['terlambat'] }}</td>
                        <td>{{ $row['tidak_hadir'] }}</td>
                        <td>{{ $row['persen'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Belum ada guru aktif.</td></tr>
                @endforelse
            </tbody>
        @endif
    </table>

    @if($selectedGuru && $insidenDetail->isNotEmpty())
        <p style="margin-top:10px;font-size:10px;"><b>Catatan lokasi palsu (permanen):</b>
            @foreach($insidenDetail as $ins){{ $ins->tanggal->format('d/m/Y') }} {{ substr($ins->jam, 0, 5) }} ({{ $ins->tahap }}{{ $ins->jarak_meter !== null ? ', ±'.$ins->jarak_meter.' m' : '' }})@if(! $loop->last); @endif @endforeach
        </p>
    @endif

    <table class="sign">
        <tr>
            <td style="padding-bottom: 64px;">&nbsp;</td>
            <td style="padding-bottom: 64px;">............, {{ now()->translatedFormat('d F Y') }}<br>Kepala Sekolah</td>
        </tr>
        <tr>
            <td></td>
            <td><b>{{ Auth::user()->nama_lengkap ?? '_______________________' }}</b><br>NIP. .........................</td>
        </tr>
    </table>
</body>

</html>
