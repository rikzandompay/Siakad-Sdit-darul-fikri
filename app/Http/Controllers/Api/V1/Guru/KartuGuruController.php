<?php

namespace App\Http\Controllers\Api\V1\Guru;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\FotoPrivat;
use App\Support\Sekolah;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Kartu Guru (depan & belakang, ukuran asli CR80) sebagai PDF A4 siap cetak untuk guru yang login. */
class KartuGuruController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $guru = $request->user();
        $isi = Pdf::loadView('exports.kartu-guru-pdf', [
            'guru' => $guru,
            'jabatan' => $guru->isAdmin() ? 'Kepala Sekolah' : 'Guru',
            'aset' => Siswa::asetKartu(pdf: true),
            'foto' => FotoPrivat::dataUri($guru->foto_profil),
            'tahunAjaran' => TahunAjaran::where('status_aktif', 'Y')->value('nama_semester'),
            'kepalaSekolah' => Sekolah::kepalaSekolah(),
        ])->setPaper('a4', 'portrait')->output();

        return response($isi, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="kartu-guru-'.($guru->nip ?: $guru->username).'.pdf"',
        ]);
    }
}
