<?php

namespace App\Http\Controllers;

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
     * Rentang tanggal untuk filter rekap: hari_ini | minggu_ini | bulan_ini | semester_ini.
     *
     * @return array{start: \Carbon\Carbon, end: \Carbon\Carbon}
     */
    protected function periodeRange(string $rentang, ?string $tanggal = null): array
    {
        $now = $tanggal ? \Carbon\Carbon::parse($tanggal) : \Carbon\Carbon::today();

        return match ($rentang) {
            'minggu_ini' => ['start' => $now->copy()->startOfWeek(), 'end' => $now->copy()->endOfWeek()],
            'bulan_ini' => ['start' => $now->copy()->startOfMonth(), 'end' => $now->copy()->endOfMonth()],
            'semester_ini' => [
                'start' => $now->month >= 7 ? \Carbon\Carbon::create($now->year, 7, 1) : \Carbon\Carbon::create($now->year, 1, 1),
                'end' => $now->month >= 7 ? \Carbon\Carbon::create($now->year, 12, 31) : \Carbon\Carbon::create($now->year, 6, 30),
            ],
            default => ['start' => $now->copy()->startOfDay(), 'end' => $now->copy()->endOfDay()],
        };
    }

    protected function periodeLabel(string $rentang, ?string $tanggal = null): string
    {
        $now = $tanggal ? \Carbon\Carbon::parse($tanggal) : \Carbon\Carbon::today();

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
            \App\Models\Kelas::where('wali_kelas_id', $guruId)->pluck('id')->all(),
            \App\Models\JadwalPelajaran::where('guru_id', $guruId)->pluck('kelas_id')->all()
        )));
    }
}
