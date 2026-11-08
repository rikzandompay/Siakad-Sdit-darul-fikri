<?php

namespace App\Models;

use App\Models\Concerns\MenerimaNotifikasi;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

/**
 * Akun wali murid untuk aplikasi MyDarfik. Dibuat dan disinkronkan OTOMATIS dari data siswa:
 * - username  = NIS siswa
 * - kata sandi = tanggal lahir siswa format YY-MM-DD (mis. 16-01-25); disimpan ter-hash.
 *   Pemisah "-", "/", "." dan tahun 4 digit juga diterima.
 * - status    = Aktif selama siswa berstatus Aktif.
 * Tidak ada pembuatan akun manual: tambah/ubah/hapus siswa sudah cukup.
 */
class Wali extends Authenticatable
{
    use HasApiTokens, MenerimaNotifikasi;

    protected $table = 'wali';

    protected $fillable = ['nama', 'username', 'password', 'no_hp', 'status', 'terakhir_login_pada'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'terakhir_login_pada' => 'datetime'];
    }

    public function siswa()
    {
        return $this->belongsToMany(Siswa::class, 'siswa_wali', 'wali_id', 'siswa_id');
    }

    public function isAktif(): bool
    {
        return $this->status === 'Aktif';
    }

    /** "16-1-25", "2016/01/25", "16.01.25" => "16-01-25"; tidak valid => "". */
    public static function normalisasiTanggal(?string $tanggal): string
    {
        if (! preg_match('/^\s*(\d{2}|\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})\s*$/', (string) $tanggal, $m)) {
            return '';
        }
        [$y, $bulan, $hari] = [(int) $m[1], (int) $m[2], (int) $m[3]];

        return checkdate($bulan, $hari, $y < 100 ? 2000 + $y : $y) ? sprintf('%02d-%02d-%02d', $y % 100, $bulan, $hari) : '';
    }

    /** Kata sandi akun dari tanggal lahir siswa (kolom date). */
    public static function sandiDariSiswa(Siswa $siswa): string
    {
        return $siswa->tanggal_lahir ? static::normalisasiTanggal(substr((string) $siswa->tanggal_lahir, 0, 10)) : '';
    }

    /** Apakah tanggal lahir yang diketik wali cocok dengan kata sandi akun ini. */
    public function cocokSandi(?string $tanggal): bool
    {
        $norm = static::normalisasiTanggal($tanggal);

        return $norm !== '' && Hash::check($norm, $this->password);
    }

    public static function untukSiswa(Siswa $siswa): ?self
    {
        return static::whereHas('siswa', fn ($q) => $q->whereKey($siswa->id))->first();
    }

    /**
     * Buat/perbarui/hapus akun wali sesuai data siswa. Dipanggil otomatis saat siswa disimpan.
     * Tanpa tanggal lahir, akun tidak ada (tidak ada kata sandi yang bisa dipakai).
     */
    public static function sinkronDariSiswa(Siswa $siswa): ?self
    {
        $wali = static::untukSiswa($siswa);
        $norm = static::sandiDariSiswa($siswa);

        if ($norm === '') {
            $wali?->delete();

            return null;
        }

        $data = [
            'nama' => trim((string) $siswa->nama_wali) ?: 'Wali '.$siswa->nama_siswa,
            'username' => (string) $siswa->nis,
            'no_hp' => $siswa->no_hp_wali,
            'status' => $siswa->status === 'Aktif' ? 'Aktif' : 'Nonaktif',
        ];

        if (! $wali) {
            // Username (NIS) bisa sempat dipakai akun lama yang tak terhubung ke siswa mana pun.
            static::where('username', $data['username'])->whereDoesntHave('siswa')->delete();
            $wali = static::create($data + ['password' => $norm]);
            $wali->siswa()->attach($siswa->id);

            return $wali;
        }

        $wali->fill($data);
        if (! Hash::check($norm, $wali->password)) {
            $wali->password = $norm;
            // Kata sandi berubah: keluarkan semua perangkat yang memakai sandi lama.
            $wali->tokens()->delete();
        }
        if ($wali->isDirty('status') && $data['status'] !== 'Aktif') {
            $wali->tokens()->delete();
        }
        $wali->save();

        return $wali;
    }

    /** Dipanggil sebelum siswa dihapus: akun ikut hilang jika tidak ada anak lain. */
    public static function lepasSiswa(Siswa $siswa): void
    {
        $wali = static::untukSiswa($siswa);
        if ($wali && $wali->siswa()->count() <= 1) {
            $wali->delete();
        }
    }

    /** Sinkronkan semua siswa (data lama sebelum fitur ini ada). @return int jumlah akun terbuat/diperbarui */
    public static function sinkronSemua(): int
    {
        $jumlah = 0;
        Siswa::orderBy('id')->chunkById(200, function ($daftar) use (&$jumlah) {
            foreach ($daftar as $siswa) {
                if (static::sinkronDariSiswa($siswa)) {
                    $jumlah++;
                }
            }
        });

        return $jumlah;
    }
}
