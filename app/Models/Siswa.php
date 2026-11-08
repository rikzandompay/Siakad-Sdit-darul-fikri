<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * App\Models\Siswa
 *
 * @property int $id
 * @property string $nis
 * @property string $nisn
 * @property string $nama_siswa
 * @property string $tanggal_lahir
 * @property string|null $alamat
 * @property string $jenis_kelamin
 * @property int $kelas_id
 * @property string|null $nama_wali
 * @property string|null $no_hp_wali
 * @property string $status
 * @property string|null $qr_token
 * @property-read Kelas $kelas
 */
class Siswa extends Model
{
    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'nisn',
        'nama_siswa',
        'tanggal_lahir',
        'alamat',
        'jenis_kelamin',
        'kelas_id',
        'nama_wali',
        'no_hp_wali',
        'status',
        'foto',
    ];

    /** Token QR berfungsi seperti kartu presensi; jangan ikut ter-serialisasi ke HTML/JSON. */
    protected $hidden = ['qr_token'];

    /** Awalan isi QR Kartu Pelajar, dibaca aplikasi MyDarfik saat scan sholat. */
    public const QR_PREFIX = 'MYDARFIK:SISWA:';

    protected static function booted(): void
    {
        // Akun wali di aplikasi mengikuti data siswa (NIS + nama wali).
        // Kegagalan sinkron (mis. migrasi belum jalan) dilaporkan, tapi tidak boleh menggagalkan simpan siswa.
        static::saved(function (Siswa $siswa) {
            try {
                Wali::sinkronDariSiswa($siswa);
            } catch (QueryException $e) {
                report($e);
            }
        });
        static::deleting(function (Siswa $siswa) {
            try {
                Wali::lepasSiswa($siswa);
            } catch (QueryException $e) {
                report($e);
            }
        });

        static::creating(function (Siswa $siswa) {
            if (empty($siswa->qr_token)) {
                $siswa->qr_token = static::generateQrToken();
            }
        });
    }

    public static function generateQrToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('qr_token', $token)->exists());

        return $token;
    }

    /** Ganti QR (misalnya kartu hilang); QR lama langsung tidak berlaku. */
    public function regenerateQrToken(): void
    {
        $this->qr_token = static::generateQrToken();
        $this->save();
    }

    public function getQrPayloadAttribute(): string
    {
        return self::QR_PREFIX.$this->qr_token;
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function wali()
    {
        return $this->belongsToMany(Wali::class, 'siswa_wali', 'siswa_id', 'wali_id');
    }

    public function presensiSholat()
    {
        return $this->hasMany(PresensiSholat::class, 'siswa_id');
    }

    public function presensi()
    {
        return $this->hasMany(Presensi::class, 'siswa_id');
    }

    public function nilaiRapot()
    {
        return $this->hasMany(NilaiRapot::class, 'siswa_id');
    }

    /** URL foto siswa (rute terlindungi login), null bila belum ada. */
    public function fotoUrl(): ?string
    {
        return $this->foto ? route('foto.siswa', ['siswa' => $this->id, 'v' => \App\Support\FotoPrivat::versi($this->foto)]) : null;
    }

    public function simpanFoto(\Illuminate\Http\UploadedFile $file): string
    {
        $path = \App\Support\FotoPrivat::simpan($file, 'foto_siswa', $this->foto);
        $this->update(['foto' => $path]);

        return $path;
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->nama_siswa);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials;
    }
}
