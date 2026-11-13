{{-- Kartu Guru, sisi depan. Variabel: $guru, $jabatan, $aset, $foto (data-URI / null), $tahunAjaran. --}}
@php
    $namaTampil = \Illuminate\Support\Str::limit($guru->nama_lengkap ?? $guru->name, 40);
    $ukuranNama = max(5.8, min(10.2, 230 / max(1, mb_strlen($namaTampil))));
    $inisial = \App\Support\Sekolah::inisial($guru->nama_lengkap ?? $guru->name);
@endphp
<div class="kartu">
    <img class="hias-tr" src="{{ $aset['swoosh_tr'] }}" alt="">
    <img class="hias-bl" src="{{ $aset['swoosh_bl'] }}" alt="">
    <img class="lambang" src="{{ $aset['logo'] }}" alt="">
    <div class="jenis">KARTU GURU</div>
    <div class="judul">SDIT Darul Fikri</div>

    <div class="foto">
        @if($foto)
            <img src="{{ $foto }}" alt="">
        @else
            <div class="ini">{{ $inisial }}</div>
            <div class="cap">FOTO 3×4</div>
        @endif
    </div>

    <div class="nama" style="width: 52mm; font-size: {{ number_format($ukuranNama, 1, '.', '') }}pt;">{{ $namaTampil }}</div>

    <img class="ikon" style="top: 22.8mm;" src="{{ $aset['ikon_nis'] }}" alt="">
    <div class="lbl" style="top: 22.6mm;">NIP</div>
    <div class="val" style="left: 31.2mm; top: 25.3mm; width: 48mm;">{{ $guru->nip ?: '-' }}</div>

    <img class="ikon" style="top: 29.6mm;" src="{{ $aset['ikon_kelas'] }}" alt="">
    <div class="lbl" style="top: 29.4mm;">JABATAN</div>
    <div class="val" style="left: 31.2mm; top: 32.1mm; width: 48mm;">{{ $jabatan }}</div>

    <div class="kaki">
        <div class="kiri">Tahun Ajaran {{ $tahunAjaran ?? '-' }}</div>
        <div class="kanan">MyDarfik</div>
    </div>
</div>
