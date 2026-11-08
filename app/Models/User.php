<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Concerns\MenerimaNotifikasi;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, MenerimaNotifikasi;

    protected $fillable = [
        'name',
        'email',
        'password',
        'nip',
        'nama_lengkap',
        'jenis_kelamin',
        'username',
        'no_hp',
        'status',
        'foto_profil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function kelasWali()
    {
        return $this->hasMany(Kelas::class, 'wali_kelas_id');
    }

    public function wajah()
    {
        return $this->hasOne(WajahGuru::class, 'guru_id');
    }

    public function jadwalPelajaran()
    {
        return $this->hasMany(JadwalPelajaran::class, 'guru_id');
    }

    /** URL foto profil (rute terlindungi login), null bila belum ada. Dipakai web, kartu, dan aplikasi. */
    public function fotoUrl(): ?string
    {
        return $this->foto_profil ? route('foto.guru', ['guru' => $this->id, 'v' => \App\Support\FotoPrivat::versi($this->foto_profil)]) : null;
    }

    /** Simpan foto baru di disk privat dan hapus yang lama. Mengembalikan path tersimpan. */
    public function simpanFoto(\Illuminate\Http\UploadedFile $file): string
    {
        $path = \App\Support\FotoPrivat::simpan($file, 'foto_guru', $this->foto_profil);
        $this->update(['foto_profil' => $path]);

        return $path;
    }

    public function hapusFoto(): void
    {
        \App\Support\FotoPrivat::hapus($this->foto_profil);
        $this->update(['foto_profil' => null]);
    }

    public function isAdmin(): bool
    {
        // ponytail: config-driven admin list avoids remote DB migration; upgrade to role column when schema changes
        return in_array(strtolower((string) $this->username), array_map('strtolower', config('admin.usernames', ['admin'])), true);
    }
}
