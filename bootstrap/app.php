<?php

use App\Http\Middleware\EnsureApiRole;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Explicit proxy addresses only; configure TRUSTED_PROXIES for deployment ingress.
        $trustedProxies = array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '127.0.0.1,::1')))));
        $middleware->trustProxies(at: $trustedProxies);
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'is_admin' => IsAdmin::class,
            'ability' => CheckForAnyAbility::class,
            'abilities' => CheckAbilities::class,
            'peran' => EnsureApiRole::class,
            'wali.kelas' => \App\Http\Middleware\EnsureWaliKelas::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API aplikasi MyDarfik selalu menjawab JSON, termasuk saat error.
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
    })->create();
