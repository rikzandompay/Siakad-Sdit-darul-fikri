<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Panduan pengguna (PDF) sesuai peran akun yang login: wali murid, guru, atau kepala sekolah. */
class PanduanController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $akun = $request->user();
        $berkas = match (true) {
            ! $akun instanceof User => 'Panduan-MyDarfik-Wali-Murid.pdf',
            $akun->isAdmin() => 'Panduan-MyDarfik-Kepala-Sekolah.pdf',
            default => 'Panduan-MyDarfik-Guru.pdf',
        };
        $jalur = resource_path('panduan/'.$berkas);
        abort_unless(is_file($jalur), 404, 'Panduan belum tersedia. Hubungi Tata Usaha.');

        return response()->download($jalur, $berkas, ['Content-Type' => 'application/pdf']);
    }
}
