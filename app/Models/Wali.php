<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

/**
 * Akun wali murid untuk aplikasi MyDarfik. Dibuat dan disinkronkan OTOMATIS dari data siswa:
 * - username  = NIS siswa
 * - "kata sandi" = nama lengkap wali (kolom nama_wali) — dibandingkan tanpa peduli huruf
 *   besar/kecil, spasi ganda, dan tanda baca; disimpan ter-hash.
 * - status    = Aktif selama siswa berstatus Aktif.
 * Tidak ada pembuatan akun manual: tambah/ubah/hapus siswa sudah cukup.
 */
class Wali extends Authenticatable
{
    use HasApiTokens;

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

    /** "Siti  Aminah, S.Pd." => "siti aminah spd" */
    public static function normalisasiNama(?string $nama): string
    {
        $nama = mb_strtolower((string) $nama);
        $nama = preg_replace('/[^\p{L}\p{N}\s]/u', '', $nama);

        return trim(preg_replace('/\s+/u', ' ', $nama));
    }

    /** Apakah nama yang diketik wali cocok dengan "kata sandi" akun ini. */
    public function cocokNama(?string $nama): bool
    {
        $norm = static::normalisasiNama($nama);

        return $norm !== '' && Hash::check($norm, $this->password);
    }

    public static function untukSiswa(Siswa $siswa): ?self
    {
        return static::whereHas('siswa', fn ($q) => $q->whereKey($siswa->id))->first();
    }

    /**
     * Buat/perbarui/hapus akun wali sesuai data siswa. Dipanggil otomatis saat siswa disimpan.
     * Tanpa nama wali, akun tidak ada (tidak ada "kata sandi" yang bisa dipakai).
     */
    public static function sinkronDariSiswa(Siswa $siswa): ?self
    {
        $wali = static::untukSiswa($siswa);
        $norm = static::normalisasiNama($siswa->nama_wali);

        if ($norm === '') {
            $wali?->delete();

            return null;
        }

        $data = [
            'nama' => trim($siswa->nama_wali),
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
