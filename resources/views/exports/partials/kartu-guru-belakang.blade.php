{{-- Kartu Guru, sisi belakang. Variabel: $aset, $kepalaSekolah. --}}
@php
    $ketentuan = [
        'Kartu ini adalah tanda pengenal resmi pendidik dan tenaga kependidikan SDIT Darul Fikri.',
        'Digunakan untuk presensi dan verifikasi administrasi sekolah.',
        'Wajib dijaga dengan baik dan tidak boleh dipindahtangankan kepada orang lain.',
        'Bagi yang menemukan kartu ini, mohon dikembalikan ke alamat SDIT Darul Fikri.',
    ];
@endphp
<div class="kartu">
    <img class="hias-tr" src="{{ $aset['swoosh_tr'] }}" alt="">
    <img class="hias-bl kecil" src="{{ $aset['swoosh_bl'] }}" alt="">
    <img class="watermark" src="{{ $aset['watermark'] }}" alt="">
    <img class="lambang" src="{{ $aset['logo'] }}" alt="">
    <div class="jenis">KARTU GURU</div>
    <div class="judul">SDIT Darul Fikri</div>

    <div class="tt-judul">KETENTUAN KARTU:</div>
    <div class="tt-daftar">
        <table>
            @foreach($ketentuan as $i => $teks)
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
