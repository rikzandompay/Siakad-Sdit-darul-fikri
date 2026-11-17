<?php

namespace App\Support;

use App\Models\PengajuanIzin;
use App\Models\PengaturanSekolah;
use App\Models\Siswa;
use App\Models\Wali;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

/**
 * Surat izin keperluan dari orang tua/wali siswa kepada wali kelas. Isinya surat
 * orang tua (tanpa kop sekolah dan tanpa tanda tangan kepala sekolah) beserta
 * nomor surat dari buku nomor surat sekolah.
 */
class SuratIzin
{
    public const ALASAN_PILIHAN = [
        'Sakit Keluarga' => 'ada anggota keluarga yang sakit',
        'Acara Keluarga' => 'ada acara keluarga yang harus dihadiri',
        'Keperluan Dinas' => 'ada keperluan dinas',
        'Urusan Penting' => 'ada urusan penting yang tidak dapat ditinggalkan',
    ];

    public static function formatTanggal(mixed $tgl): string
    {
        if (! $tgl) {
            return Sekolah::now()->translatedFormat('d F Y');
        }

        try {
            return Carbon::parse($tgl)->locale('id')->translatedFormat('d F Y');
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

        return preg_match('/^(karena|ada|sedang)\s+/i', $trim) ? $trim : 'ada '.lcfirst($trim);
    }

    /** Data surat dari pengajuan izin, dengan data wali kelas & sekolah saat ini. */
    public static function dataSurat(PengajuanIzin $izin): array
    {
        $siswa = $izin->siswa;
        $kelas = $siswa?->kelas;
        $waliKelas = $kelas?->waliKelas;

        return [
            'nomor_surat' => $izin->nomor_surat,
            'kota' => $izin->kota ?: PengaturanSekolah::ambil('kota', 'Kab. Kubu Raya'),
            'tanggal_surat' => self::formatTanggal($izin->tanggal_surat ?? $izin->created_at),
            'nama_wali_kelas' => $waliKelas?->nama_lengkap ?? $waliKelas?->name ?? '-',
            'kelas' => $kelas?->nama_kelas ?? '-',
            'nama_sekolah' => 'SDIT Darul Fikri',
            'nama_orang_tua' => $izin->nama_orang_tua ?: ($izin->wali?->nama ?? $siswa?->nama_wali ?? 'Orang Tua/Wali'),
            'nama_anak' => $siswa?->nama_siswa ?? '-',
            'alasan_teks' => self::formatAlasanKalimat((string) $izin->alasan),
            'tanggal_mulai' => self::formatTanggal($izin->tanggal_mulai),
            'jumlah_hari' => max(1, $izin->jumlahHari()),
            'kontak' => $izin->kontak ?: ($izin->wali?->no_hp ?? $siswa?->no_hp_wali),
        ];
    }

    /** Pratinjau surat di aplikasi sebelum dikirim (belum punya nomor surat). */
    public static function dataPratinjau(array $input, ?Siswa $siswa = null, ?Wali $wali = null): array
    {
        $kelas = $siswa?->kelas;

        return [
            'nomor_surat' => null,
            'kota' => $input['kota'] ?? PengaturanSekolah::ambil('kota', 'Kab. Kubu Raya'),
            'tanggal_surat' => self::formatTanggal($input['tanggal_surat'] ?? Sekolah::now()),
            'nama_wali_kelas' => $kelas?->waliKelas?->nama_lengkap ?? '-',
            'kelas' => $kelas?->nama_kelas ?? '-',
            'nama_sekolah' => 'SDIT Darul Fikri',
            'nama_orang_tua' => $input['nama_lengkap'] ?? $wali?->nama ?? $siswa?->nama_wali ?? 'Orang Tua/Wali',
            'nama_anak' => $siswa?->nama_siswa ?? ($input['nama_anak'] ?? '-'),
            'alasan_teks' => self::formatAlasanKalimat((string) ($input['alasan'] ?? '')),
            'tanggal_mulai' => self::formatTanggal($input['tanggal_mulai'] ?? Sekolah::now()),
            'jumlah_hari' => max(1, (int) ($input['jumlah_hari'] ?? 1)),
            'kontak' => $input['kontak'] ?? $wali?->no_hp ?? $siswa?->no_hp_wali,
        ];
    }

    public static function buatPdf(array $data): string
    {
        return Pdf::loadView('exports.surat-izin-pdf', $data)->setPaper('a4', 'portrait')->output();
    }
}
