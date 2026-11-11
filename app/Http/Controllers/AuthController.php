<?php

namespace App\Http\Controllers;

use App\Support\BatasLoginGuru;
use App\Support\KunciLogin;
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

        if ($detik = BatasLoginGuru::sisa($credentials['username'], $request->ip())) {
            return back()->withErrors(['username' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam '.KunciLogin::teks($detik).'.'])->onlyInput('username');
        }

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password'], 'status' => 'Aktif'], $request->boolean('remember'))) {
            BatasLoginGuru::berhasil($credentials['username'], $request->ip());
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        BatasLoginGuru::gagal($credentials['username'], $request->ip());

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
