<?php

namespace App\Support;

use App\Models\PengajuanIzin;
use App\Models\PengaturanSekolah;
use App\Models\Siswa;
use App\Models\Wali;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SuratIzin
{
    public const ALASAN_PILIHAN = [
        'Sakit' => 'sedang sakit',
        'Acara Keluarga' => 'ada acara keluarga yang harus dihadiri',
        'Urusan Keluarga' => 'ada urusan keluarga',
        'Keperluan Mendesak' => 'ada keperluan mendesak',
    ];

    public static function formatTanggal(mixed $tgl): string
    {
        if (! $tgl) {
            return Sekolah::now()->translatedFormat('d F Y');
        }

        try {
            $parsed = $tgl instanceof Carbon ? $tgl : Carbon::parse($tgl);

            return $parsed->locale('id')->translatedFormat('d F Y');
        } catch (\Throwable) {
            return (string) $tgl;
        }
    }

    public static function formatAlasanKalimat(string $alasan): string
    {
        $trim = trim($alasan);
        if ($trim === '') {
            return 'ada keperluan yang tidak dapat ditinggalkan';
        }

        foreach (self::ALASAN_PILIHAN as $kunci => $kalimat) {
            if (strcasecmp($trim, $kunci) === 0) {
                return $kalimat;
            }
        }

        $lower = strtolower($trim);
        if (in_array($lower, ['sakit', 'demam', 'flu', 'batuk', 'pusing', 'cacat'], true) || str_starts_with($lower, 'sakit ')) {
            return 'sedang '.$lower;
        }

        if (preg_match('/^(karena|ada|sedang)\s+/i', $trim)) {
            return $trim;
        }

        return 'ada '.lcfirst($trim);
    }

    public static function dataSurat(PengajuanIzin $izin, array $override = []): array
    {
        $siswa = $izin->siswa;
        $wali = $izin->wali;
        $kelas = $siswa?->kelas;
        $waliKelas = $kelas?->waliKelas;

        $namaWaliKelas = $override['nama_wali_kelas'] ?? $waliKelas?->nama_lengkap ?? $waliKelas?->name ?? 'Wali Kelas';
        $namaSekolah = $override['nama_sekolah'] ?? PengaturanSekolah::ambil('nama_sekolah', 'SD IT Darul Fikri');
        $kota = $override['kota'] ?? PengaturanSekolah::ambil('kota', 'Kab. Kubu Raya');
        $tanggalSurat = self::formatTanggal($override['tanggal_surat'] ?? $izin->created_at ?? Sekolah::now());
        $tanggalMulai = self::formatTanggal($override['tanggal_mulai'] ?? $izin->tanggal_mulai);
        $jumlahHari = (int) ($override['jumlah_hari'] ?? $izin->jumlahHari());
        $alasan = (string) ($override['alasan'] ?? $izin->alasan);
        $namaOrangTua = $override['nama_orang_tua'] ?? $override['nama_lengkap'] ?? $wali?->nama ?? $siswa?->nama_wali ?? 'Orang Tua / Wali';
        $namaAnak = $override['nama_anak'] ?? $siswa?->nama_siswa ?? '-';
        $namaKelas = $override['kelas'] ?? $kelas?->nama_kelas ?? '-';
        $kontak = $override['kontak'] ?? $override['no_hp'] ?? $wali?->no_hp ?? $siswa?->no_hp_wali ?? null;

        return [
            'kota' => $kota,
            'tanggal_surat' => $tanggalSurat,
            'nama_wali_kelas' => $namaWaliKelas,
            'kelas' => $namaKelas,
            'nama_sekolah' => $namaSekolah,
            'nama_orang_tua' => $namaOrangTua,
            'nama_anak' => $namaAnak,
            'alasan' => $alasan,
            'alasan_teks' => self::formatAlasanKalimat($alasan),
            'tanggal_mulai' => $tanggalMulai,
            'jumlah_hari' => max(1, $jumlahHari),
            'kontak' => $kontak,
        ];
    }

    public static function dataSuratKustom(array $input, ?Siswa $siswa = null, ?Wali $wali = null): array
    {
        $kelas = $siswa?->kelas;
        $waliKelas = $kelas?->waliKelas;

        $namaWaliKelas = $input['nama_wali_kelas'] ?? $waliKelas?->nama_lengkap ?? $waliKelas?->name ?? 'Wali Kelas';
        $namaSekolah = $input['nama_sekolah'] ?? PengaturanSekolah::ambil('nama_sekolah', 'SD IT Darul Fikri');
        $kota = $input['kota'] ?? PengaturanSekolah::ambil('kota', 'Kab. Kubu Raya');
        $tanggalSurat = self::formatTanggal($input['tanggal_surat'] ?? Sekolah::now());
        $tanggalMulai = self::formatTanggal($input['tanggal_mulai'] ?? Sekolah::now());
        $jumlahHari = isset($input['jumlah_hari']) ? (int) $input['jumlah_hari'] : 1;
        $alasan = (string) ($input['alasan'] ?? 'Acara Keluarga');
        $namaOrangTua = $input['nama_orang_tua'] ?? $input['nama_lengkap'] ?? $wali?->nama ?? $siswa?->nama_wali ?? 'Orang Tua / Wali';
        $namaAnak = $input['nama_anak'] ?? $siswa?->nama_siswa ?? '-';
        $namaKelas = $input['kelas'] ?? $kelas?->nama_kelas ?? '-';
        $kontak = $input['kontak'] ?? $input['no_hp'] ?? $wali?->no_hp ?? $siswa?->no_hp_wali ?? null;

        return [
            'kota' => $kota,
            'tanggal_surat' => $tanggalSurat,
            'nama_wali_kelas' => $namaWaliKelas,
            'kelas' => $namaKelas,
            'nama_sekolah' => $namaSekolah,
            'nama_orang_tua' => $namaOrangTua,
            'nama_anak' => $namaAnak,
            'alasan' => $alasan,
            'alasan_teks' => self::formatAlasanKalimat($alasan),
            'tanggal_mulai' => $tanggalMulai,
            'jumlah_hari' => max(1, $jumlahHari),
            'kontak' => $kontak,
        ];
    }

    public static function buatPdf(array $data): string
    {
        return Pdf::loadView('exports.surat-izin-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->output();
    }
}
