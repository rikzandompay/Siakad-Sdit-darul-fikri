<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Support\Sekolah;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class Controller
{
    protected function putSafeCsv($stream, array $row): void
    {
        // ponytail: prefix formula triggers with single quote; upgrade to dedicated Exporter if XLSX needed
        fputcsv($stream, array_map(
            fn ($v) => is_string($v) && $v !== '-' && ! is_numeric($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v,
            $row
        ));
    }

    /**
     * Kop CSV seragam: identitas yayasan/sekolah, judul laporan, lalu baris info berlabel.
     * Baris info ditulis sebagai [label, ':', nilai]; array kosong menjadi baris pemisah.
     *
     * @param  array<int, array{0: string, 1: string, 2: string}|array{}>  $info
     */
    protected function putKopCsv($stream, string $judul, array $info = []): void
    {
        fputcsv($stream, ['YAYASAN PENDIDIKAN DARUL FIKRI']);
        fputcsv($stream, ['SD IT DARUL FIKRI']);
        fputcsv($stream, [$judul]);
        fputcsv($stream, []);

        foreach ($info as $baris) {
            $this->putSafeCsv($stream, $baris);
        }

        fputcsv($stream, []);
    }

    /**
     * Kirim berkas CSV ber-BOM UTF-8 agar karakter Indonesia terbaca rapi di Excel.
     *
     * @param  callable(resource): void  $tulis
     */
    protected function streamCsv(string $filename, callable $tulis): StreamedResponse
    {
        return response()->stream(function () use ($tulis) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            $tulis($file);
            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Rentang tanggal untuk filter rekap: hari_ini | minggu_ini | bulan_ini | semester_ini.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    protected function periodeRange(string $rentang, ?string $tanggal = null): array
    {
        $now = $tanggal ? Carbon::parse($tanggal, Sekolah::timezone()) : Sekolah::now();

        return match ($rentang) {
            'minggu_ini' => ['start' => $now->copy()->startOfWeek(), 'end' => $now->copy()->endOfWeek()],
            'bulan_ini' => ['start' => $now->copy()->startOfMonth(), 'end' => $now->copy()->endOfMonth()],
            'semester_ini' => [
                'start' => $now->month >= 7 ? Carbon::create($now->year, 7, 1, 0, 0, 0, Sekolah::timezone()) : Carbon::create($now->year, 1, 1, 0, 0, 0, Sekolah::timezone()),
                'end' => $now->month >= 7 ? Carbon::create($now->year, 12, 31, 0, 0, 0, Sekolah::timezone()) : Carbon::create($now->year, 6, 30, 0, 0, 0, Sekolah::timezone()),
            ],
            default => ['start' => $now->copy()->startOfDay(), 'end' => $now->copy()->endOfDay()],
        };
    }

    protected function periodeLabel(string $rentang, ?string $tanggal = null): string
    {
        $now = $tanggal ? Carbon::parse($tanggal, Sekolah::timezone()) : Sekolah::now();

        return match ($rentang) {
            'minggu_ini' => 'Minggu '.$now->copy()->startOfWeek()->translatedFormat('d M').' - '.$now->copy()->endOfWeek()->translatedFormat('d M Y'),
            'bulan_ini' => $now->translatedFormat('F Y'),
            'semester_ini' => ($now->month >= 7 ? 'Semester Ganjil ' : 'Semester Genap ').$now->year,
            default => $now->translatedFormat('d F Y'),
        };
    }

    protected function accessibleKelasIds(): array
    {
        $guruId = auth()->id();

        return array_values(array_unique(array_merge(
            Kelas::where('wali_kelas_id', $guruId)->pluck('id')->all(),
            JadwalPelajaran::where('guru_id', $guruId)->pluck('kelas_id')->all()
        )));
    }
}
