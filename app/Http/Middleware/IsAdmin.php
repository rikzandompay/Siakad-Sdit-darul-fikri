<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            // Jika request AJAX/JSON, kembalikan 403 JSON
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke fitur ini.'], 403);
            }
            // Jika request biasa, abort 403 dengan pesan
            abort(403, 'Akses Ditolak: Fitur ini hanya untuk Kepala Sekolah.');
        }

        return $next($request);
    }
}
