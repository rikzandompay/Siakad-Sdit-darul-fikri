<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'nip',
        'nama_lengkap',
        'username',
        'no_hp',
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

    public function jadwalPelajaran()
    {
        return $this->hasMany(JadwalPelajaran::class, 'guru_id');
    }
}
