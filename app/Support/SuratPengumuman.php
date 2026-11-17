<?php

namespace App\Support;

use App\Models\Pengumuman;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Surat pengumuman resmi: kop sekolah, nomor surat, dan tanda tangan kepala sekolah.
 * Dipakai halaman web admin maupun aplikasi MyDarfik.
 */
class SuratPengumuman
{
    public static function buatPdf(Pengumuman $pengumuman): string
    {
        $ttd = public_path('images/kartu/kartu_ttd.png');
        $pembuat = $pengumuman->pembuat;

        return Pdf::loadView('exports.pengumuman-pdf', [
            'pengumuman' => $pengumuman,
            'logo' => public_path('Lgo.png'),
            'ttd' => file_exists($ttd) ? $ttd : null,
            'namaKepala' => Sekolah::kepalaSekolahLengkap() ?: (string) ($pembuat?->nama_lengkap ?? $pembuat?->name ?? ''),
            'nip' => Sekolah::nipKepalaSekolah(),
        ])->setPaper('a4', 'portrait')->output();
    }
}
