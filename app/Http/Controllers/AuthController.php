<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $throttleKey = strtolower($credentials['username']).'|'.$request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors(['username' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit.'])->onlyInput('username');
        }

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password'], 'status' => 'Aktif'], $request->boolean('remember'))) {
            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
