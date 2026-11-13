<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Pelajar {{ $siswa->nama_siswa }} - SDIT Darul Fikri</title>
    <style>
        @page { size: A4; margin: 15mm; }

        * { box-sizing: border-box; }

        body {
            font-family: Inter, system-ui, Arial, Helvetica, sans-serif;
            color: #111827;
            background: #f1f5f9;
            margin: 0;
            padding: 24px 16px;
        }

        .toolbar {
            max-width: 720px;
            margin: 0 auto 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .toolbar button {
            border: 1px solid #cbd5e1;
            background: #fff;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .toolbar .primary { background: #1e7fc4; border-color: #1e7fc4; color: #fff; }
        .toolbar .note { font-size: 12px; color: #475569; }
        .flash { max-width: 720px; margin: 0 auto 16px; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 8px; padding: 10px 14px; font-size: 13px; }

        .sheet {
            display: flex;
            flex-wrap: wrap;
            gap: 10mm;
            justify-content: center;
        }

        /* Gaya kartu bersama (sama dengan PDF) + gradient seperti di aplikasi. */
        @include('exports.partials.kartu-pelajar-gaya')
        .kartu { background-image: linear-gradient(135deg, #ffffff 0%, #eff7fd 50%, #dceefa 100%); box-shadow: 0 4px 14px rgba(15, 42, 68, .12); }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar, .flash { display: none; }
            .kartu { box-shadow: none; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .kartu * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>

<body>
    @if(session('success'))
        <div class="flash">{{ session('success') }}</div>
    @endif

    <div class="toolbar">
        <button class="primary" type="button" onclick="window.print()">Cetak Kartu</button>
        <form method="POST" action="{{ route('siswa.qr.regenerate', $siswa) }}"
              onsubmit="return confirm('Buat QR baru? Kartu lama tidak bisa dipakai untuk presensi sholat lagi.')">
            @csrf
            <button type="submit">Ganti QR</button>
        </form>
        <span class="note">Ganti QR hanya jika kartu hilang atau disalahgunakan.</span>
    </div>

    <div class="sheet">
        @include('exports.partials.kartu-pelajar-depan')
        @include('exports.partials.kartu-pelajar-belakang')
    </div>
</body>

</html>
