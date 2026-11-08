<!DOCTYPE html>
<html class="light" lang="id">

<head>
    @include('partials.analytics')
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}?v=2">
    <title>@yield('title', 'SDIT Darul Fikri') - Sistem Informasi</title>

    {{-- DNS Prefetch & Preconnect for external resources --}}
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Vite-compiled Tailwind CSS (replaces CDN Tailwind JS) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Load fonts non-render-blocking with media="print" trick + onload swap --}}
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
            rel="stylesheet">
    </noscript>

    {{-- Material Symbols - non-render-blocking to improve first paint --}}
    <link rel="preload"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=swap"
        as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link
            href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=swap"
            rel="stylesheet">
    </noscript>

    {{-- Alpine.js is bundled locally through Vite in resources/js/app.js --}}

    <style>
        [x-cloak] {
            display: none !important;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            background: linear-gradient(to bottom, #ffffff 30%, #aadaf5 100%);
            background-attachment: fixed;
        }
    </style>
    @stack('styles')
</head>

<body class="text-on-background antialiased font-inter text-base max-md:overflow-x-hidden" x-data="shell">
    @include('partials.analytics-body')

    <!-- SideNavBar -->
    <aside
        class="fixed left-0 top-0 h-screen w-64 border-r border-gray-200 shadow-xl flex flex-col py-6 z-50 transition-transform duration-300 ease-in-out max-md:-translate-x-full"
        style="background: linear-gradient(to bottom, #ffffff 0%, #aadaf5 100%);"
        :class="asideClass">
        <div class="px-6 mb-8 flex items-center gap-3 shrink-0">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center overflow-hidden p-0">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <h1 class="text-gray-900 font-bold text-lg leading-tight">Darul Fikri</h1>
                <p class="text-gray-500 text-[10px] uppercase tracking-wider font-semibold">SIAKAD System</p>
            </div>
        </div>

        <nav class="flex-1 min-h-0 overflow-y-auto overscroll-contain space-y-1">
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('dashboard') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}"
                href="{{ route('dashboard') }}">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-medium text-sm">Dashboard</span>
            </a>
            @if (Auth::user()->isAdmin())
                <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('guru.*') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}"
                    href="{{ route('guru.index') }}">
                    <span class="material-symbols-outlined">badge</span>
                    <span class="font-medium text-sm">Data Guru</span>
                </a>
            @endif
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('siswa.*') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}"
                href="{{ route('siswa.index') }}">
                <span class="material-symbols-outlined">group</span>
                <span class="font-medium text-sm">Data Siswa</span>
            </a>
            @if (Auth::user()->isAdmin())
                <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('wali.*') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}"
                    href="{{ route('wali.index') }}">
                    <span class="material-symbols-outlined">family_restroom</span>
                    <span class="font-medium text-sm">Akun Wali</span>
                </a>
            @endif
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('kelas.*', 'tugas.*') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}"
                href="{{ route('kelas.index') }}">
                <span class="material-symbols-outlined">class</span>
                <span class="font-medium text-sm">Kelas & Mapel</span>
            </a>
            <!-- Presensi Dropdown Menu -->
            <div x-data="menuGroup" data-open="{{ request()->routeIs('presensi.*', 'presensi-sholat.*', 'presensi-guru.*') ? 'true' : 'false' }}">
                <button type="button" @click="toggle"
                    class="w-full flex items-center justify-between px-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi.*', 'presensi-sholat.*', 'presensi-guru.*') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined">how_to_reg</span>
                        <span class="font-medium text-sm">Presensi</span>
                    </div>
                    <span class="material-symbols-outlined text-sm transition-transform duration-200"
                        :class="chevronClass">expand_more</span>
                </button>

                <div x-show="open" x-cloak class="bg-gray-50 border-y border-gray-100">
                    <a href="{{ route('presensi.index') }}"
                        class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi.index') || request()->routeIs('presensi.show') ? 'text-blue-700 font-medium' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                        <div
                            class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('presensi.index') || request()->routeIs('presensi.show') ? 'bg-blue-600' : 'bg-gray-300' }}">
                        </div>
                        <span class="text-sm">Input Presensi</span>
                    </a>
                    <a href="{{ route('presensi.rekap') }}"
                        class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi.rekap') ? 'text-blue-700 font-medium' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                        <div
                            class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('presensi.rekap') ? 'bg-blue-600' : 'bg-gray-300' }}">
                        </div>
                        <span class="text-sm">Rekap Absensi Siswa</span>
                    </a>
                    @foreach(\App\Models\PresensiSholat::JENIS as $jenisMenu)
                        @php
                            $sholatAktif = request()->routeIs('presensi-sholat.index', 'presensi-sholat.show')
                                && \App\Models\PresensiSholat::normalizeJenis(request()->get('jenis')) === $jenisMenu;
                        @endphp
                        <a href="{{ route('presensi-sholat.index', ['jenis' => $jenisMenu]) }}"
                            class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ $sholatAktif ? 'text-blue-700 font-medium' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                            <div class="w-1.5 h-1.5 rounded-full {{ $sholatAktif ? 'bg-blue-600' : 'bg-gray-300' }}"></div>
                            <span class="text-sm">Sholat {{ $jenisMenu }}</span>
                        </a>
                    @endforeach
                    <a href="{{ route('presensi-sholat.rekap') }}"
                        class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi-sholat.rekap') ? 'text-blue-700 font-medium' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                        <div
                            class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('presensi-sholat.rekap') ? 'bg-blue-600' : 'bg-gray-300' }}">
                        </div>
                        <span class="text-sm">Rekap Absensi Sholat</span>
                    </a>
                    @if (Auth::user()->isAdmin())
                        <a href="{{ route('presensi-guru.rekap') }}"
                            class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi-guru.*') ? 'text-blue-700 font-medium' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                            <div class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('presensi-guru.*') ? 'bg-blue-600' : 'bg-gray-300' }}"></div>
                            <span class="text-sm">Rekap Presensi Guru</span>
                        </a>
                    @endif
                </div>
            </div>
            <!-- Nilai Dropdown Menu -->
            <div x-data="menuGroup" data-open="{{ request()->routeIs('nilai.*') ? 'true' : 'false' }}">
                <button type="button" @click="toggle"
                    class="w-full flex items-center justify-between px-6 py-3 transition-colors duration-200 {{ request()->routeIs('nilai.*') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined">description</span>
                        <span class="font-medium text-sm">Nilai Rapot</span>
                    </div>
                    <span class="material-symbols-outlined text-sm transition-transform duration-200"
                        :class="chevronClass">expand_more</span>
                </button>

                <div x-show="open" x-cloak class="bg-gray-50 border-y border-gray-100">
                    <a href="{{ route('nilai.index') }}"
                        class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('nilai.index') || request()->routeIs('nilai.show') ? 'text-blue-700 font-medium' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                        <div
                            class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('nilai.index') || request()->routeIs('nilai.show') ? 'bg-blue-600' : 'bg-gray-300' }}">
                        </div>
                        <span class="text-sm">Input Nilai</span>
                    </a>
                    <a href="{{ route('nilai.rekap') }}"
                        class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('nilai.rekap') ? 'text-blue-700 font-medium' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-100' }}">
                        <div
                            class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('nilai.rekap') ? 'bg-blue-600' : 'bg-gray-300' }}">
                        </div>
                        <span class="text-sm">Rekap Nilai Siswa</span>
                    </a>
                </div>
            </div>
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('izin.*') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}"
                href="{{ route('izin.index') }}">
                <span class="material-symbols-outlined">mail</span>
                <span class="font-medium text-sm">Izin Siswa</span>
                @if (($izinMenunggu ?? 0) > 0)
                    <span class="ml-auto min-w-5 h-5 px-1.5 rounded-full bg-red-600 text-white text-[11px] font-bold flex items-center justify-center"
                        title="{{ $izinMenunggu }} izin menunggu">{{ $izinMenunggu > 99 ? '99+' : $izinMenunggu }}</span>
                @endif
            </a>
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('pengumuman.*') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}"
                href="{{ route('pengumuman.index') }}">
                <span class="material-symbols-outlined">campaign</span>
                <span class="font-medium text-sm">Pengumuman</span>
            </a>
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('konseling.*') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}"
                href="{{ route('konseling.index') }}">
                <span class="material-symbols-outlined">forum</span>
                <span class="font-medium text-sm">Konseling</span>
                @if (($konselingBelumDibaca ?? 0) > 0)
                    <span class="ml-auto min-w-5 h-5 px-1.5 rounded-full bg-red-600 text-white text-[11px] font-bold flex items-center justify-center"
                        title="{{ $konselingBelumDibaca }} pesan wali belum dibaca">{{ $konselingBelumDibaca > 99 ? '99+' : $konselingBelumDibaca }}</span>
                @endif
            </a>
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('pengaturan.*') ? 'bg-white/60 text-blue-700 border-l-4 border-blue-600' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}"
                href="{{ route('pengaturan.index') }}">
                <span class="material-symbols-outlined">settings</span>
                <span class="font-medium text-sm">Pengaturan</span>
            </a>
        </nav>

        <div class="px-6 pt-4 shrink-0">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex items-center justify-center gap-3 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white py-3 rounded-lg transition-colors duration-200 w-full border border-red-100">
                    <span class="material-symbols-outlined">logout</span>
                    <span class="font-medium text-sm">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- TopNavBar -->
    <header
        class="fixed top-0 right-0 w-full md:w-[calc(100%-16rem)] h-16 bg-white/80 backdrop-blur-md border-b border-gray-200 flex items-center justify-between px-4 md:px-8 z-40 shadow-sm transition-transform duration-300 ease-in-out"
        :class="shiftClass">
        <div class="flex items-center gap-4">
            <!-- Mobile Hamburger Toggle -->
            <button type="button" @click="toggleSidebar"
                class="md:hidden text-blue-800 p-2 -ml-2 rounded-lg hover:bg-white/60 focus:outline-none flex items-center justify-center">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h2 class="text-blue-800 font-bold text-sm truncate">@yield('page-title', 'Dashboard')</h2>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900">
                        {{ Auth::user()->nama_lengkap ?? Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ Auth::user()->isAdmin() ? 'Kepala Sekolah' : 'Guru' }}</p>
                </div>
                <a href="{{ route('pengaturan.index') }}" title="Profil saya"
                    class="w-10 h-10 shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-blue-800 font-bold border-2 border-blue-50 overflow-hidden">
                    @if (Auth::user()->fotoUrl())
                        <img src="{{ Auth::user()->fotoUrl() }}" alt="Foto {{ Auth::user()->nama_lengkap ?? Auth::user()->name }}" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr(Auth::user()->nama_lengkap ?? Auth::user()->name, 0, 2)) }}
                    @endif
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="ml-0 md:ml-64 mt-16 p-4 md:p-margin-page min-h-screen transition-transform duration-300 ease-in-out"
        :class="shiftClass">
        <div class="max-w-container-max mx-auto space-y-stack-lg">
            {{-- Flash Messages --}}
            @if (session('success'))
                <div id="flash-success"
                    class="bg-white/60 border border-blue-200 text-blue-800 px-6 py-4 rounded-xl flex items-center gap-3 shadow-sm">
                    <span class="material-symbols-outlined text-blue-600">check_circle</span>
                    <span class="font-medium text-sm">{{ session('success') }}</span>
                    <button onclick="document.getElementById('flash-success').remove()"
                        class="ml-auto text-blue-400 hover:text-blue-600">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-xl shadow-sm">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="material-symbols-outlined text-red-600">error</span>
                        <span class="font-medium text-sm">Terdapat kesalahan:</span>
                    </div>
                    <ul class="list-disc list-inside text-sm space-y-1 ml-8">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    @stack('scripts')
    <script>
        // Auto-dismiss flash messages after 5 seconds
        setTimeout(() => {
            const flash = document.getElementById('flash-success');
            if (flash) flash.style.display = 'none';
        }, 5000);
    </script>
</body>

</html>
