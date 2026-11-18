{{-- Kop surat untuk ekspor PDF (dompdf). Variabel: $yayasan (nama yayasan di baris kedua kop). --}}
@props(['yayasan' => 'YAYASAN PENDIDIKAN DARUL FIKRI'])

<div class="kop-surat">
    <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
        <tr>
            <td style="width: 15%; text-align: center; vertical-align: middle; border: none; padding: 0;">
                <img src="{{ asset('Lgo.png') }}" alt="Logo" style="width: 80px;">
            </td>
            <td style="width: 70%; text-align: center; border: none; padding: 0;">
                <h1 style="font-size: 18pt; margin: 0; text-transform: uppercase; font-weight: bold;">SD IT DARUL FIKRI</h1>
                <h2 style="font-size: 14pt; margin: 5px 0 0;">{{ $yayasan }}</h2>
                <p style="font-size: 8pt; margin: 4px 0 0;">Alamat: Jl. Sungai Durian Laut Kec.Sungai Raya Kabupaten Kubu Raya, Provinsi Kalimantan Barat, Kode Pos 78391<br>Telp: (021) 1234567 | Email: info@sditdarulfikri.sch.id</p>
            </td>
            <td style="width: 15%; border: none; padding: 0;"></td>
        </tr>
    </table>
</div>
