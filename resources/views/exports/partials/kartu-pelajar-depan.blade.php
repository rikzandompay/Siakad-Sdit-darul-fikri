{{-- Sisi depan. Variabel: $siswa, $aset (jalur gambar), $foto (URL / data-URI / null), $qrSrc, $tahunAjaran. --}}
@php
    $kelasSingkat = $siswa->kelas ? \App\Http\Controllers\Api\V1\Guru\PresensiKelasController::singkatan($siswa->kelas->nama_kelas) : '-';
    $alamat = $siswa->alamat ?: '-';
    // Nama panjang diperkecil agar muat satu baris (lebar 34,5 mm).
    $namaTampil = \Illuminate\Support\Str::limit($siswa->nama_siswa, 30);
    $ukuranNama = max(5.8, min(10.2, 160 / max(1, mb_strlen($namaTampil))));
@endphp
<div class="kartu">
    <img class="hias-tr" src="{{ $aset['swoosh_tr'] }}" alt="">
    <img class="hias-bl" src="{{ $aset['swoosh_bl'] }}" alt="">
    <img class="lambang" src="{{ $aset['logo'] }}" alt="">
    <div class="jenis">KARTU PELAJAR</div>
    <div class="judul">SDIT Darul Fikri</div>

    <div class="foto">
        @if($foto)
            <img src="{{ $foto }}" alt="">
        @else
            <div class="ini">{{ $siswa->initials }}</div>
            <div class="cap">FOTO 3×4</div>
        @endif
    </div>

    <div class="nama" style="font-size: {{ number_format($ukuranNama, 1, '.', '') }}pt;">{{ $namaTampil }}</div>

    <img class="ikon" style="top: 22.8mm;" src="{{ $aset['ikon_nis'] }}" alt="">
    <div class="lbl" style="top: 22.6mm;">NIS</div>
    <div class="val" style="left: 31.2mm; top: 25.3mm;">{{ $siswa->nis }}</div>

    <img class="ikon" style="top: 29.6mm;" src="{{ $aset['ikon_kelas'] }}" alt="">
    <div class="lbl" style="top: 29.4mm;">KELAS</div>
    <div class="val" style="left: 31.2mm; top: 32.1mm;">{{ $kelasSingkat }}</div>

    <img class="ikon" style="top: 36.4mm;" src="{{ $aset['ikon_alamat'] }}" alt="">
    <div class="lbl" style="top: 36.2mm;">ALAMAT</div>
    <div class="val dua" style="left: 31.2mm; top: 38.9mm;">{{ \Illuminate\Support\Str::limit($alamat, 60) }}</div>

    <div class="qr-kotak">
        <img src="{{ $qrSrc }}" alt="">
        <div class="qr-label">QR SHOLAT</div>
    </div>

    <div class="kaki">
        <div class="kiri">Tahun Ajaran {{ $tahunAjaran ?? '-' }}</div>
        <div class="kanan">MyDarfik</div>
    </div>
</div>
