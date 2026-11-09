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
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
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
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            background: linear-gradient(to bottom, #ffffff 30%, #aadaf5 100%);
            background-attachment: fixed;
        }
    </style>
    @stack('styles')
</head>

<body class="text-on-background antialiased font-inter text-base max-md:overflow-x-hidden" x-data="shell">
    @include('partials.analytics-body')

    <a href="#konten"
        class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[60] focus:rounded-lg focus:bg-blue-800 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">Lewati ke konten</a>

    <!-- Sidebar -->
    <aside
        class="fixed left-0 top-0 h-screen w-64 border-r border-gray-200 flex flex-col py-6 z-50 transition-transform duration-300 ease-in-out max-md:-translate-x-full"
        style="background: linear-gradient(to bottom, #ffffff 0%, #aadaf5 100%);"
        :class="asideClass">
        <div class="px-6 mb-8 flex items-center gap-3 shrink-0">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center overflow-hidden p-0">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <h1 class="text-gray-900 font-bold text-lg leading-tight">Darul Fikri</h1>
                <p class="text-gray-600 text-xs font-medium">Sistem Informasi Akademik</p>
            </div>
        </div>

        <nav class="flex-1 min-h-0 overflow-y-auto overscroll-contain space-y-1" aria-label="Menu utama">
            <x-nav-item :href="route('dashboard')" icon="dashboard" :active="request()->routeIs('dashboard')">Dashboard</x-nav-item>
            @if (Auth::user()->isAdmin())
                <x-nav-item :href="route('guru.index')" icon="badge" :active="request()->routeIs('guru.*')">Data Guru</x-nav-item>
            @endif
            <x-nav-item :href="route('siswa.index')" icon="group" :active="request()->routeIs('siswa.*')">Data Siswa</x-nav-item>
            @if (Auth::user()->isAdmin())
                <x-nav-item :href="route('wali.index')" icon="family_restroom" :active="request()->routeIs('wali.*')">Akun Wali</x-nav-item>
            @endif
            <x-nav-item :href="route('kelas.index')" icon="class" :active="request()->routeIs('kelas.*', 'tugas.*')">Kelas &amp; Mapel</x-nav-item>

            <x-nav-group icon="how_to_reg" label="Presensi" :active="request()->routeIs('presensi.*', 'presensi-sholat.*', 'presensi-guru.*')">
                <x-nav-sub :href="route('presensi.index')" :active="request()->routeIs('presensi.index', 'presensi.show')">Input Presensi</x-nav-sub>
                <x-nav-sub :href="route('presensi.rekap')" :active="request()->routeIs('presensi.rekap')">Rekap Absensi Siswa</x-nav-sub>
                @foreach (\App\Models\PresensiSholat::JENIS as $jenisMenu)
                    @php
                        $sholatAktif = request()->routeIs('presensi-sholat.index', 'presensi-sholat.show')
                            && \App\Models\PresensiSholat::normalizeJenis(request()->get('jenis')) === $jenisMenu;
                    @endphp
                    <x-nav-sub :href="route('presensi-sholat.index', ['jenis' => $jenisMenu])" :active="$sholatAktif">Sholat {{ $jenisMenu }}</x-nav-sub>
                @endforeach
                <x-nav-sub :href="route('presensi-sholat.rekap')" :active="request()->routeIs('presensi-sholat.rekap')">Rekap Absensi Sholat</x-nav-sub>
                @if (Auth::user()->isAdmin())
                    <x-nav-sub :href="route('presensi-guru.rekap')" :active="request()->routeIs('presensi-guru.*')">Rekap Presensi Guru</x-nav-sub>
                @endif
            </x-nav-group>

            <x-nav-group icon="description" label="Nilai Rapot" :active="request()->routeIs('nilai.*')">
                <x-nav-sub :href="route('nilai.index')" :active="request()->routeIs('nilai.index', 'nilai.show')">Input Nilai</x-nav-sub>
                <x-nav-sub :href="route('nilai.rekap')" :active="request()->routeIs('nilai.rekap')">Rekap Nilai Siswa</x-nav-sub>
            </x-nav-group>

            <x-nav-item :href="route('izin.index')" icon="mail" :active="request()->routeIs('izin.*')"
                :badge="$izinMenunggu ?? 0" :badge-title="($izinMenunggu ?? 0).' izin menunggu'">Izin Siswa</x-nav-item>
            <x-nav-item :href="route('pengumuman.index')" icon="campaign" :active="request()->routeIs('pengumuman.*')">Pengumuman</x-nav-item>
            <x-nav-item :href="route('konseling.index')" icon="forum" :active="request()->routeIs('konseling.*')"
                :badge="$konselingBelumDibaca ?? 0" :badge-title="($konselingBelumDibaca ?? 0).' pesan wali belum dibaca'">Konseling</x-nav-item>
            <x-nav-item :href="route('pengaturan.index')" icon="settings" :active="request()->routeIs('pengaturan.*')">Pengaturan</x-nav-item>
        </nav>

        <div class="px-6 pt-4 shrink-0">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white/70 py-2.5 text-sm font-semibold text-red-700 transition-colors hover:bg-red-700 hover:text-white">
                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">logout</span>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- TopNavBar -->
    <header
        class="fixed top-0 right-0 w-full md:w-[calc(100%-16rem)] h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:px-8 z-40 transition-transform duration-300 ease-in-out"
        :class="shiftClass">
        <div class="flex items-center gap-4">
            <!-- Mobile Hamburger Toggle -->
            <button type="button" @click="toggleSidebar"
                class="md:hidden text-blue-800 p-2 -ml-2 rounded-lg hover:bg-gray-100 flex items-center justify-center" aria-label="Buka menu">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <p class="truncate text-sm font-semibold text-gray-900">@yield('page-title', 'Dashboard')</p>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900">
                        {{ Auth::user()->nama_lengkap ?? Auth::user()->name }}</p>
                    <p class="text-xs text-gray-600">{{ Auth::user()->isAdmin() ? 'Kepala Sekolah' : 'Guru' }}</p>
                </div>
                <a href="{{ route('pengaturan.index') }}" title="Profil saya"
                    class="w-10 h-10 shrink-0 rounded-full bg-blue-800 flex items-center justify-center text-sm text-white font-bold overflow-hidden">
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
    <main id="konten" tabindex="-1" class="ml-0 md:ml-64 mt-16 p-4 md:p-margin-page min-h-screen transition-transform duration-300 ease-in-out"
        :class="shiftClass">
        <div class="max-w-container-max mx-auto space-y-stack-lg">
            {{-- Flash Messages --}}
            @if (session('success'))
                <div id="flash-success"
                    class="bg-green-50 border border-green-200 text-green-900 px-5 py-3.5 rounded-xl flex items-center gap-3" role="status">
                    <span class="material-symbols-outlined text-green-700" aria-hidden="true">check_circle</span>
                    <span class="font-medium text-sm">{{ session('success') }}</span>
                    <button onclick="document.getElementById('flash-success').remove()"
                        class="ml-auto text-green-800 hover:text-green-950" aria-label="Tutup pesan">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-xl" role="alert">
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
