<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Siswa - SDIT Darul Fikri</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            font-size: 10px;
            /* Reduced font size to fit more columns */
            color: #333;
        }

        @page {
            size: landscape;
            margin: 1cm;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #004532;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 18px;
            color: #004532;
        }

        .header h2 {
            font-size: 14px;
            color: #555;
            margin-top: 4px;
        }

        .header p {
            font-size: 10px;
            color: #999;
            margin-top: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background: #004532;
            color: white;
            padding: 8px 6px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:nth-child(even) td {
            background: #f9fafb;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #999;
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
    <div class="header">
        <h1>SD IT DARUL FIKRI</h1>
        <h2>Laporan Data Siswa</h2>
        <p>Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Tanggal Lahir</th>
                <th>Alamat</th>
                <th>Gender</th>
                <th>Kelas</th>
                <th>Nama Wali</th>
                <th>No HP</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $i => $s)
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
                    <td>{{ $s->nis }}</td>
                    <td>{{ $s->nisn }}</td>
                    <td><strong>{{ $s->nama_siswa }}</strong></td>
                    <td>{{ $s->tanggal_lahir }}</td>
                    <td>{{ $s->alamat ?? '-' }}</td>
                    <td style="text-align:center">{{ $s->jenis_kelamin }}</td>
                    <td>{{ $s->kelas->nama_kelas ?? '-' }}</td>
                    <td>{{ $s->nama_wali ?? '-' }}</td>
                    <td>{{ $s->no_hp_wali ?? '-' }}</td>
                    <td style="text-align:center">{{ $s->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">
        <p>Total: {{ $data->count() }} siswa | SDIT Darul Fikri &copy; {{ date('Y') }}</p>
    </div>
</body>

</html>
