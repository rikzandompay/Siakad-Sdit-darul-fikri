<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Wali;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi endpoint API ke peran tertentu (guru | admin | wali) dan akun yang masih aktif.
 * Token guru hanya untuk model User, token wali hanya untuk model Wali.
 */
class EnsureApiRole
{
    public function handle(Request $request, Closure $next, string $peran): Response
    {
        $user = $request->user();

        $cocok = match ($peran) {
            'guru' => $user instanceof User && $user->status === 'Aktif' && $user->tokenCan('guru'),
            'admin' => $user instanceof User && $user->status === 'Aktif' && $user->tokenCan('guru') && $user->isAdmin(),
            'wali' => $user instanceof Wali && $user->isAktif() && $user->tokenCan('wali'),
            default => false,
        };

        if (! $cocok) {
            return response()->json(['message' => 'Akses tidak diizinkan untuk akun ini.'], 403);
        }

        return $next($request);
    }
}
