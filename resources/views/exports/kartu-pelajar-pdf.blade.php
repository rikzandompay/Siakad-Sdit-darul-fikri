<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kartu Pelajar {{ $siswa->nama_siswa }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }

        body { font-family: Helvetica, Arial, sans-serif; color: #111827; margin: 0; }
        .judul-halaman { font-size: 11pt; font-weight: bold; margin: 0 0 1mm; }
        .petunjuk { font-size: 8pt; color: #475569; margin: 0 0 6mm; line-height: 1.4; }

        table.lembar { border-collapse: collapse; }
        table.lembar td { padding: 0; vertical-align: top; }
        /* Garis potong putus-putus di luar kartu. */
        .potong { border: 0.2mm dashed #94a3b8; padding: 1mm; }

        @include('exports.partials.kartu-pelajar-gaya')
    </style>
</head>

<body>
    <p class="judul-halaman">Kartu Pelajar — {{ $siswa->nama_siswa }}</p>
    <p class="petunjuk">
        Cetak pada kertas A4 dengan skala <b>100%</b> (jangan "fit to page" / "sesuaikan halaman"),
        lebih baik di kertas art paper/foto. Potong mengikuti garis putus-putus, lalu laminating.
    </p>

    <table class="lembar">
        <tr>
            <td class="potong">@include('exports.partials.kartu-pelajar-depan')</td>
            <td style="width: 3mm;"></td>
            <td class="potong">@include('exports.partials.kartu-pelajar-belakang')</td>
        </tr>
    </table>
</body>

</html>
