<?php

namespace App\Providers;

use App\Models\KonselingPesan;
use App\Models\PengajuanIzin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Batas request API MyDarfik. Limiter bernama punya penghitung sendiri, jadi
        // tidak saling menambah dengan limiter grup (berbeda dengan throttle:x,y biasa).
        $kunci = fn (Request $request) => $request->user()?->getMorphClass().':'.($request->user()?->getKey() ?? $request->ip());
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(120)->by($kunci($r)));
        RateLimiter::for('api-login', fn (Request $r) => Limit::perMinute(20)->by($r->ip()));
        RateLimiter::for('api-absen', fn (Request $r) => Limit::perMinute(10)->by($kunci($r)));
        RateLimiter::for('api-wajah', fn (Request $r) => Limit::perMinute(5)->by($kunci($r)));
        RateLimiter::for('api-scan', fn (Request $r) => Limit::perMinute(240)->by($kunci($r)));
        RateLimiter::for('api-sandi', fn (Request $r) => Limit::perMinute(10)->by($kunci($r)));
        RateLimiter::for('api-foto', fn (Request $r) => Limit::perMinute(20)->by($kunci($r)));

        // Badge sidebar: pesan wali yang belum dibaca di kelas yang diampu user.
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            if (! $user) {
                return;
            }

            try {
                $badge = [
                    'konselingBelumDibaca' => KonselingPesan::where('pengirim', KonselingPesan::WALI)
                        ->whereNull('dibaca_pada')
                        ->whereHas('percakapan.siswa.kelas', fn ($q) => $q->where('pengampu_konseling_id', $user->id))
                        ->count(),
                    'izinMenunggu' => PengajuanIzin::terlihatOleh($user)->where('status', PengajuanIzin::MENUNGGU)->count(),
                ];
            } catch (QueryException) {
                // Migrasi belum dijalankan: jangan sampai seluruh halaman error karena badge.
                $badge = ['konselingBelumDibaca' => 0, 'izinMenunggu' => 0];
            }

            $view->with($badge);
        });
    }
}
