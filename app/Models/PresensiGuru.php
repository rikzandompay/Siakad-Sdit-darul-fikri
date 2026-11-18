<?php

namespace App\Models;

use App\Support\Sekolah;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Absen guru dari aplikasi MyDarfik (lokasi + wajah). Satu baris per guru per hari.
 */
class PresensiGuru extends Model
{
    public const HADIR = 'Hadir';

    public const TIDAK_HADIR = 'Tidak Hadir';

    public const STATUS = [self::HADIR, self::TIDAK_HADIR];

    protected $table = 'presensi_guru';

    protected $fillable = [
        'guru_id', 'tanggal', 'status', 'jam_masuk', 'jam_pulang',
        'masuk_latitude', 'masuk_longitude', 'masuk_jarak_meter', 'masuk_skor_wajah',
        'pulang_latitude', 'pulang_longitude', 'pulang_jarak_meter', 'pulang_skor_wajah',
        'sumber', 'keterangan', 'koreksi_mandiri',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'masuk_skor_wajah' => 'float',
            'pulang_skor_wajah' => 'float',
            'koreksi_mandiri' => 'boolean',
        ];
    }

    /** Simpan tanggal sebagai Y-m-d agar cocok dengan unique key di semua database. */
    protected function tanggal(): Attribute
    {
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    /**
     * Nilai kolom untuk koreksi manual oleh admin. Koreksi atas absen milik sendiri tetap boleh
     * (tidak ada admin lain), tetapi selalu ditandai agar terlihat di rekap dan ekspor.
     */
    /**
     * Keterangan lokasi presensi untuk rekap: "Di lokasi SD IT Darul Fikri" bila
     * titik absen masuk berada di dalam radius sekolah, "Di luar lokasi presensi"
     * bila di luar. Null untuk absen manual (tanpa titik lokasi).
     *
     * @return array{di_lokasi: bool, teks: string}|null
     */
    public function keteranganLokasi(): ?array
    {
        if ($this->masuk_jarak_meter === null) {
            return null;
        }

        $jarak = (int) $this->masuk_jarak_meter;
        $radius = PengaturanSekolah::radiusMeter();

        return $jarak <= $radius
            ? ['di_lokasi' => true, 'teks' => 'Di lokasi '.Sekolah::NAMA, 'jarak' => $jarak]
            : ['di_lokasi' => false, 'teks' => 'Di luar lokasi presensi', 'jarak' => $jarak];
    }

    public static function dataKoreksi(int $guruId, User $oleh, string $keterangan): array
    {
        $mandiri = $guruId === $oleh->id;

        return [
            'sumber' => 'admin',
            'koreksi_mandiri' => $mandiri,
            'keterangan' => ($mandiri ? '[Koreksi mandiri] ' : '').$keterangan.' (diubah oleh '.($oleh->nama_lengkap ?? $oleh->name).')',
        ];
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /** Terlambat jika jam masuk melewati jam masuk acuan sekolah. */
    public function getTerlambatAttribute(): bool
    {
        return $this->jam_masuk !== null
            && substr($this->jam_masuk, 0, 5) > PengaturanSekolah::jamMasuk();
    }
}
