<?php

namespace App\Observers;

use App\Models\KonselingPesan;
use App\Models\PengajuanIzin;
use App\Models\Pengumuman;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use App\Models\Wali;
use App\Notifications\IzinDiajukan;
use App\Notifications\IzinDitinjau;
use App\Notifications\PengumumanBaru;
use App\Notifications\PesanKonselingBaru;
use App\Notifications\TugasBaru;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

/**
 * Kirim notifikasi aplikasi saat data dibuat/diubah, baik dari web maupun API.
 * Didaftarkan untuk beberapa model di AppServiceProvider; tiap model punya cabangnya sendiri.
 */
class NotifikasiObserver implements ShouldHandleEventsAfterCommit
{
    public function created(object $model): void
    {
        match (true) {
            $model instanceof KonselingPesan => $this->pesanKonseling($model),
            $model instanceof Pengumuman => $this->pengumuman($model),
            $model instanceof PengajuanIzin => $this->izinDiajukan($model),
            $model instanceof Tugas => Notification::send(static::waliKelas($model->kelas_id), new TugasBaru($model)),
            default => null,
        };
    }

    public function updated(object $model): void
    {
        if ($model instanceof PengajuanIzin && $model->wasChanged('status')
            && $model->getOriginal('status') === PengajuanIzin::MENUNGGU && $model->status !== PengajuanIzin::MENUNGGU) {
            $penerima = Wali::whereKey($model->wali_id)->where('status', 'Aktif')->get();
            if ($penerima->isEmpty()) {
                $penerima = static::waliAktif()->whereHas('siswa', fn ($q) => $q->whereKey($model->siswa_id))->get();
            }
            Notification::send($penerima, new IzinDitinjau($model));
        }
    }

    private function pesanKonseling(KonselingPesan $pesan): void
    {
        $siswa = $pesan->percakapan?->siswa;
        if (! $siswa) {
            return;
        }

        // Percakapan privat: hanya pengampu konseling kelas dan wali siswa, admin tidak ikut.
        $penerima = $pesan->pengirim === KonselingPesan::WALI
            ? User::whereKey($siswa->kelas?->pengampu_konseling_id)->where('status', 'Aktif')->get()
            : static::waliAktif()->whereHas('siswa', fn ($q) => $q->whereKey($siswa->id))->get();

        Notification::send($penerima, new PesanKonselingBaru($pesan));
    }

    private function pengumuman(Pengumuman $p): void
    {
        $notif = new PengumumanBaru($p);
        if (in_array($p->sasaran, [Pengumuman::SEMUA, Pengumuman::GURU], true)) {
            Notification::send(User::where('status', 'Aktif')->whereKeyNot($p->dibuat_oleh)->get(), $notif);
        }
        if (in_array($p->sasaran, [Pengumuman::SEMUA, Pengumuman::WALI], true)) {
            Notification::send(static::waliAktif()->get(), $notif);
        }
    }

    private function izinDiajukan(PengajuanIzin $izin): void
    {
        $waliKelasId = Siswa::find($izin->siswa_id)?->kelas?->wali_kelas_id;
        $penerima = $waliKelasId
            ? User::whereKey($waliKelasId)->where('status', 'Aktif')->get()
            : collect();

        // Kelas tanpa wali kelas (atau wali kelas nonaktif): kepala sekolah/admin yang meninjau.
        if ($penerima->isEmpty()) {
            $penerima = User::where('status', 'Aktif')->get()->filter->isAdmin();
        }

        Notification::send($penerima, new IzinDiajukan($izin));
    }

    /** Wali aktif dari siswa aktif di kelas. */
    public static function waliKelas(?int $kelasId)
    {
        return static::waliAktif()
            ->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelasId)->where('status', 'Aktif'))
            ->get();
    }

    private static function waliAktif(): Builder
    {
        return Wali::where('status', 'Aktif');
    }
}
