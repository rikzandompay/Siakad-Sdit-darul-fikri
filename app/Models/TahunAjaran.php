<?php

namespace App\Models;

use App\Support\Sekolah;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';

    protected $fillable = ['nama_semester', 'status_aktif'];

    public function nilaiRapot()
    {
        return $this->hasMany(NilaiRapot::class, 'tahun_ajaran_id');
    }

    public static function getAktif()
    {
        return static::where('status_aktif', 'Y')->first();
    }

    public function setSemesterAktif(): bool
    {
        static::where('id', '!=', $this->id)->where('status_aktif', 'Y')->update(['status_aktif' => 'N']);
        $this->status_aktif = 'Y';

        return $this->save();
    }

    /** Rentang tanggal semester ini (Juli–Desember untuk Ganjil, Januari–Juni untuk Genap). */
    public function rentangTanggal(): array
    {
        if (preg_match('/(\d{4})\/(\d{4}).*?(ganjil|genap)/i', (string) $this->nama_semester, $m)) {
            $ganjil = strtolower($m[3]) === 'ganjil';
            $tahun = (int) ($ganjil ? $m[1] : $m[2]);

            return [
                'mulai' => Carbon::create($tahun, $ganjil ? 7 : 1, 1, 0, 0, 0, Sekolah::timezone())->startOfDay(),
                'selesai' => Carbon::create($tahun, $ganjil ? 12 : 6, $ganjil ? 31 : 30, 23, 59, 59, Sekolah::timezone())->endOfDay(),
            ];
        }

        $now = Sekolah::now();
        $ganjil = $now->month >= 7;

        return [
            'mulai' => Carbon::create($now->year, $ganjil ? 7 : 1, 1, 0, 0, 0, Sekolah::timezone())->startOfDay(),
            'selesai' => Carbon::create($now->year, $ganjil ? 12 : 6, $ganjil ? 31 : 30, 23, 59, 59, Sekolah::timezone())->endOfDay(),
        ];
    }
}
