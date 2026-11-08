<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\NilaiRapotController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\PresensiSholatController;
use App\Http\Controllers\RekapPresensiGuruController;
use App\Http\Controllers\SiswaController;
use App\Support\Sekolah;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor CSV/PDF untuk aplikasi: memakai laporan yang sama dengan web, tetapi
 * dikirim sebagai berkas (bukan halaman cetak) agar langsung bisa disimpan di HP.
 */
class EksporController extends Controller
{
    /** jenis => [controller, method csv, method pdf, orientasi pdf] */
    private const JENIS = [
        'absensi' => [PresensiController::class, 'exportRekapCsv', 'exportRekapPdf', 'landscape'],
        'sholat' => [PresensiSholatController::class, 'exportRekapCsv', 'exportRekapPdf', 'portrait'],
        'nilai' => [NilaiRapotController::class, 'exportRekapCsv', 'exportRekapPdf', 'landscape'],
        'guru-presensi' => [RekapPresensiGuruController::class, 'exportCsv', 'exportPdf', 'portrait'],
        'data-guru' => [GuruController::class, 'exportCsv', 'exportPdf', 'portrait'],
        'data-siswa' => [SiswaController::class, 'exportCsv', 'exportPdf', 'landscape'],
    ];

    public function __invoke(Request $request, string $jenis, string $format)
    {
        abort_unless(isset(self::JENIS[$jenis]) && in_array($format, ['csv', 'pdf'], true), 404, 'Jenis ekspor tidak dikenal.');
        [$kelas, $metodeCsv, $metodePdf, $orientasi] = self::JENIS[$jenis];

        $hasil = app()->call([app($kelas), $format === 'csv' ? $metodeCsv : $metodePdf]);
        $nama = $jenis.'-'.Sekolah::now()->format('Y-m-d').'.'.$format;

        return $format === 'csv' ? $this->csv($hasil, $nama) : $this->pdf($hasil, $nama, $orientasi);
    }

    private function csv(mixed $hasil, string $nama): Response
    {
        if ($hasil instanceof StreamedResponse) {
            ob_start();
            $hasil->sendContent();
            $isi = (string) ob_get_clean();
        } else {
            $isi = (string) $hasil->getContent();
        }

        return response($isi, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$nama.'"',
        ]);
    }

    private function pdf(mixed $hasil, string $nama, string $orientasi): Response
    {
        $html = $hasil instanceof View ? $hasil->render() : (string) $hasil->getContent();
        // Halaman web memanggil dialog cetak dan memuat logo lewat URL; dompdf butuh berkas lokal.
        $html = str_replace(['onload="window.print()"', asset('Lgo.png')], ['', public_path('Lgo.png')], $html);

        $isi = Pdf::loadHTML($html)->setPaper('a4', $orientasi)->output();

        return response($isi, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nama.'"',
        ]);
    }
}
