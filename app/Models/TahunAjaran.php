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

    /** Rentang tanggal kalender akademik untuk semester ini (Juli–Desember atau Januari–Juni). */
    public function rentangTanggal(): array
    {
        if (preg_match('/(\d{4})\/(\d{4}).*?(ganjil|genap)/i', (string) $this->nama_semester, $matches)) {
            $tahun1 = (int) $matches[1];
            $tahun2 = (int) $matches[2];
            $isGanjil = strtolower($matches[3]) === 'ganjil';

            return [
                'mulai' => Carbon::create($isGanjil ? $tahun1 : $tahun2, $isGanjil ? 7 : 1, 1, 0, 0, 0, Sekolah::timezone())->startOfDay(),
                'selesai' => Carbon::create($isGanjil ? $tahun1 : $tahun2, $isGanjil ? 12 : 6, $isGanjil ? 31 : 30, 23, 59, 59, Sekolah::timezone())->endOfDay(),
            ];
        }

        $now = Sekolah::now();
        $isGanjil = $now->month >= 7;

        return [
            'mulai' => Carbon::create($now->year, $isGanjil ? 7 : 1, 1, 0, 0, 0, Sekolah::timezone())->startOfDay(),
            'selesai' => Carbon::create($now->year, $isGanjil ? 12 : 6, $isGanjil ? 31 : 30, 23, 59, 59, Sekolah::timezone())->endOfDay(),
        ];
    }
}
