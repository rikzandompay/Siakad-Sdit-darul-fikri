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
            font-family: Inter, Arial, Helvetica, sans-serif;
            color: #0f2a44;
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

        /* Ukuran kartu ID standar (CR80): 85,6 x 54 mm */
        .card {
            width: 85.6mm;
            height: 54mm;
            border-radius: 3.5mm;
            overflow: hidden;
            background: linear-gradient(160deg, #ffffff 0%, #e6f1fb 100%);
            border: 0.3mm solid #c7dcef;
            position: relative;
            box-shadow: 0 4px 14px rgba(15, 42, 68, .12);
        }

        .card-head {
            background: #1e7fc4;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 2.5mm;
            padding: 2mm 3mm;
            height: 11mm;
        }

        .card-head img { width: 7mm; height: 7mm; object-fit: contain; background: #fff; border-radius: 50%; padding: .5mm; }
        .card-head .t1 { font-size: 7.5pt; font-weight: 700; letter-spacing: .02em; line-height: 1.1; }
        .card-head .t2 { font-size: 5.5pt; opacity: .9; }

        .card-body { display: flex; gap: 3mm; padding: 2.5mm 3mm; }

        .avatar {
            width: 18mm;
            height: 23mm;
            border-radius: 2mm;
            background: #d5e7f7;
            color: #1e7fc4;
            font-weight: 700;
            font-size: 14pt;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .fields { font-size: 6.5pt; line-height: 1.45; min-width: 0; }
        .fields .name { font-size: 8.5pt; font-weight: 700; margin-bottom: .8mm; line-height: 1.15; }
        .fields .label { color: #5b7590; display: inline-block; width: 15mm; }

        .qr { width: 26mm; height: 26mm; flex-shrink: 0; background: #fff; border-radius: 1.5mm; padding: 1mm; }
        .qr svg { width: 100%; height: 100%; display: block; }
        .qr-label { font-size: 5.5pt; font-weight: 700; color: #1e7fc4; text-align: center; margin-top: .6mm; letter-spacing: .08em; }

        .card-foot {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            font-size: 5pt;
            color: #5b7590;
            padding: 1.2mm 3mm;
            border-top: 0.2mm solid #c7dcef;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar, .flash { display: none; }
            .card { box-shadow: none; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
        {{-- Depan --}}
        <div class="card">
            <div class="card-head">
                <img src="{{ asset('Lgo.png') }}" alt="">
                <div>
                    <div class="t1">KARTU PELAJAR</div>
                    <div class="t2">SD IT Darul Fikri</div>
                </div>
            </div>
            <div class="card-body">
                <div class="avatar" style="overflow: hidden;">
                    @if($siswa->foto)
                        <img src="{{ $siswa->fotoUrl() }}" alt="" style="width: 100%; height: 100%; object-fit: cover; border-radius: 2mm;">
                    @else
                        {{ $siswa->initials }}
                    @endif
                </div>
                <div class="fields">
                    <div class="name">{{ $siswa->nama_siswa }}</div>
                    <div><span class="label">NIS</span>{{ $siswa->nis }}</div>
                    <div><span class="label">Kelas</span>{{ $siswa->kelas?->nama_kelas ?? '-' }}</div>
                    <div><span class="label">Alamat</span>{{ $siswa->alamat ?? '-' }}</div>
                </div>
            </div>
            <div class="card-foot">Jl. Sungai Durian Laut, Kec. Sungai Raya, Kab. Kubu Raya</div>
        </div>

        {{-- Belakang --}}
        <div class="card">
            <div class="card-head">
                <img src="{{ asset('Lgo.png') }}" alt="">
                <div>
                    <div class="t1">MyDarfik</div>
                    <div class="t2">Scan QR untuk presensi sholat</div>
                </div>
            </div>
            <div class="card-body">
                <div>
                    <div class="qr">{!! $qrSvg !!}</div>
                    <div class="qr-label">QR SHOLAT</div>
                </div>
                <div class="fields">
                    <div class="name">Data Wali</div>
                    <div><span class="label">Nama</span>{{ $siswa->nama_wali ?? '-' }}</div>
                    <div><span class="label">No. HP</span>{{ $siswa->no_hp_wali ?? '-' }}</div>
                    <div><span class="label">Alamat</span>{{ $siswa->alamat ?? '-' }}</div>
                </div>
            </div>
            <div class="card-foot">Jika menemukan kartu ini, harap kembalikan ke SD IT Darul Fikri.</div>
        </div>
    </div>
</body>

</html>
