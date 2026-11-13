{{-- Sisi belakang. Variabel: $aset (jalur gambar), $kepalaSekolah. --}}
@php
    $tataTertib = [
        'Kartu ini adalah tanda pengenal resmi siswa SDIT Darul Fikri.',
        'Wajib dibawa setiap hari dan dikenakan selama berada di lingkungan sekolah.',
        'Kartu tidak boleh dicoret-coret, rusak, atau dipindahtangankan kepada orang lain.',
        'Apabila kartu ini hilang atau rusak, segera lapor ke pihak sekolah.',
        'Bagi yang menemukan kartu ini, mohon dikembalikan ke alamat SDIT Darul Fikri.',
    ];
@endphp
<div class="kartu">
    <img class="hias-tr" src="{{ $aset['swoosh_tr'] }}" alt="">
    <img class="hias-bl kecil" src="{{ $aset['swoosh_bl'] }}" alt="">
    <img class="watermark" src="{{ $aset['watermark'] }}" alt="">
    <img class="lambang" src="{{ $aset['logo'] }}" alt="">
    <div class="jenis">KARTU PELAJAR</div>
    <div class="judul">SDIT Darul Fikri</div>

    <div class="tt-judul">TATA TERTIB &amp; KETENTUAN:</div>
    <div class="tt-daftar">
        <table>
            @foreach($tataTertib as $i => $teks)
                <tr><td class="no">{{ $i + 1 }}.</td><td>{{ $teks }}</td></tr>
            @endforeach
        </table>
    </div>

    <div class="tengah mengetahui">Mengetahui,<br>Kepala SDIT Darul Fikri</div>
    <img class="ttd" src="{{ $aset['ttd'] }}" alt="">
    <div class="garis" style="top: 36.2mm; height: 0.3mm;"></div>
    <div class="tengah kepsek-nama">{{ $kepalaSekolah ?: '-' }}</div>
    <div class="garis" style="top: 40mm; height: 0.25mm;"></div>
    <div class="tengah kepsek-jabatan">Kepala Sekolah</div>

    <div class="kaki">
        <div class="kiri">Alamat: Sui Durian Laut, Kubu Raya</div>
        <div class="kanan">MyDarfik</div>
    </div>
</div>
