<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>Login - SDIT Darul Fikri</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-950 via-emerald-900 to-emerald-800 flex items-center justify-center p-4">

    <!-- Decorative Background -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-10 left-10 w-72 h-72 bg-amber-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-20 w-96 h-96 bg-emerald-400/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-emerald-300/5 rounded-full blur-2xl"></div>
    </div>

    <div class="relative w-full max-w-md">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-500 rounded-2xl shadow-2xl shadow-amber-500/30 mb-6">
                <span class="material-symbols-outlined text-emerald-950 text-4xl" style="font-variation-settings: 'FILL' 1;">school</span>
            </div>
            <h1 class="text-3xl font-black text-white tracking-tight">SDIT Darul Fikri</h1>
            <p class="text-emerald-200/60 text-sm mt-2 tracking-wider uppercase">Sistem Informasi Akademik</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/20 p-8">
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">Selamat Datang! 👋</h2>
                <p class="text-gray-500 text-sm mt-1">Silakan login untuk mengakses sistem</p>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined text-red-500 text-sm">error</span>
                    <span class="text-sm font-medium">{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                <!-- Username -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider" for="username">Username</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">person</span>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            value="{{ old('username') }}"
                            required
                            autofocus
                            class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                            placeholder="Masukkan username" />
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider" for="password">Password</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all"
                            placeholder="Masukkan password" />
                    </div>
                </div>

                <!-- Remember & Login -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input name="remember" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                        <span class="text-sm text-gray-600">Ingat saya</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full py-3.5 bg-emerald-900 hover:bg-emerald-800 text-white font-bold rounded-xl shadow-lg shadow-emerald-900/30 transition-all duration-200 active:scale-[0.98] flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">login</span>
                    Masuk ke Sistem
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400">Default: <span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-gray-600">ahmad</span> / <span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-gray-600">guru1234</span></p>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-emerald-200/40 text-xs mt-8">
            &copy; {{ date('Y') }} SDIT Darul Fikri. Semua Hak Dilindungi.
        </p>
    </div>
</body>
</html>
