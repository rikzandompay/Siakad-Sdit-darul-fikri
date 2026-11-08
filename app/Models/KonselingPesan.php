<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class KonselingPesan extends Model
{
    public const WALI = 'wali';

    public const GURU = 'guru';

    protected $table = 'konseling_pesan';

    protected $fillable = ['percakapan_id', 'pengirim', 'guru_id', 'isi', 'foto', 'dibaca_pada', 'diedit_pada'];

    protected function casts(): array
    {
        return ['dibaca_pada' => 'datetime', 'diedit_pada' => 'datetime'];
    }

    public function percakapan()
    {
        return $this->belongsTo(KonselingPercakapan::class, 'percakapan_id');
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /** Pesan guru hanya boleh diubah/dihapus penulisnya. */
    public function milikGuru(User $user): bool
    {
        return $this->pengirim === self::GURU && $this->guru_id === $user->id;
    }

    /** Simpan foto di disk privat; hanya bisa dibuka lewat endpoint yang memeriksa akses. */
    public static function simpanFoto(UploadedFile $file): string
    {
        return $file->store('konseling', 'local');
    }

    public function hapusBerkasFoto(): void
    {
        if ($this->foto && Storage::disk('local')->exists($this->foto)) {
            Storage::disk('local')->delete($this->foto);
        }
    }

    /** Ringkasan untuk daftar percakapan. */
    public function ringkas(): string
    {
        return $this->isi !== null && $this->isi !== '' ? $this->isi : 'Foto';
    }
}
