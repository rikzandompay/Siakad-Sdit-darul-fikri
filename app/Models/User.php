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
        'foto_pengajuan',
        'foto_pengajuan_pada',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'foto_pengajuan_pada' => 'datetime',
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

    /** URL foto yang menunggu persetujuan (hanya untuk admin dan pemiliknya). */
    public function fotoPengajuanUrl(): ?string
    {
        return $this->foto_pengajuan ? route('foto.guru.pengajuan', ['guru' => $this->id, 'v' => \App\Support\FotoPrivat::versi($this->foto_pengajuan)]) : null;
    }

    /**
     * Foto profil adalah acuan identitas wajah. Guru tidak bisa langsung menggantinya:
     * fotonya menunggu persetujuan admin. Foto admin langsung berlaku.
     *
     * @return bool true bila foto langsung aktif, false bila menunggu persetujuan
     */
    public function unggahFoto(\Illuminate\Http\UploadedFile $file): bool
    {
        if ($this->isAdmin()) {
            $this->simpanFoto($file);

            return true;
        }

        $path = \App\Support\FotoPrivat::simpan($file, 'foto_guru_pengajuan', $this->foto_pengajuan);
        $this->update(['foto_pengajuan' => $path, 'foto_pengajuan_pada' => now()]);

        $admin = static::where('status', 'Aktif')->get()->filter->isAdmin();
        \Illuminate\Support\Facades\Notification::send($admin, new \App\Notifications\FotoProfilDiajukan($this));

        return false;
    }

    /** Foto pengajuan menjadi foto profil. Acuan berubah, jadi wajah harus didaftarkan ulang. */
    public function setujuiFotoPengajuan(): void
    {
        $lama = $this->foto_profil;
        $this->update(['foto_profil' => $this->foto_pengajuan, 'foto_pengajuan' => null, 'foto_pengajuan_pada' => null]);
        \App\Support\FotoPrivat::hapus($lama);
        $this->hapusWajah();
        $this->notify(new \App\Notifications\FotoProfilDitinjau(true));
    }

    public function tolakFotoPengajuan(?string $catatan = null): void
    {
        $this->batalkanFotoPengajuan();
        $this->notify(new \App\Notifications\FotoProfilDitinjau(false, $catatan));
    }

    public function batalkanFotoPengajuan(): void
    {
        \App\Support\FotoPrivat::hapus($this->foto_pengajuan);
        $this->update(['foto_pengajuan' => null, 'foto_pengajuan_pada' => null]);
    }

    /** Simpan foto profil aktif di disk privat dan hapus yang lama. Wajah harus didaftarkan ulang. */
    public function simpanFoto(\Illuminate\Http\UploadedFile $file): string
    {
        $path = \App\Support\FotoPrivat::simpan($file, 'foto_guru', $this->foto_profil);
        $this->update(['foto_profil' => $path]);
        $this->hapusWajah();

        return $path;
    }

    public function hapusFoto(): void
    {
        \App\Support\FotoPrivat::hapus($this->foto_profil);
        $this->update(['foto_profil' => null]);
        $this->hapusWajah();
    }

    /** Hapus data wajah guru ini beserta tanda "mirip" yang menunjuk kepadanya. */
    public function hapusWajah(): void
    {
        WajahGuru::where('guru_id', $this->id)->delete();
        WajahGuru::where('mirip_guru_id', $this->id)->update(['mirip_guru_id' => null, 'mirip_skor' => null, 'mirip_pada' => null]);
    }

    public function isAdmin(): bool
    {
        // ponytail: config-driven admin list avoids remote DB migration; upgrade to role column when schema changes
        return in_array(strtolower((string) $this->username), array_map('strtolower', config('admin.usernames', ['admin'])), true);
    }
}
