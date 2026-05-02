<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>@yield('title', 'SDIT Darul Fikri') - Sistem Informasi</title>
    <!-- Preconnect to CDNs for faster loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#004532",
                        "primary-container": "#065f46",
                        "on-primary": "#ffffff",
                        "on-primary-container": "#8bd6b7",
                        "secondary": "#9b4500",
                        "secondary-container": "#fd8a42",
                        "on-secondary": "#ffffff",
                        "on-secondary-container": "#682c00",
                        "tertiary": "#652925",
                        "tertiary-container": "#823f3a",
                        "error": "#ba1a1a",
                        "error-container": "#ffdad6",
                        "background": "#f7faf6",
                        "on-background": "#181c1a",
                        "surface": "#f7faf6",
                        "on-surface": "#181c1a",
                        "surface-variant": "#e0e3df",
                        "on-surface-variant": "#3f4944",
                        "outline": "#6f7973",
                        "outline-variant": "#bec9c2",
                        "surface-container": "#ecefeb",
                        "surface-container-low": "#f1f4f0",
                        "surface-container-high": "#e6e9e5",
                        "surface-container-highest": "#e0e3df",
                        "surface-container-lowest": "#ffffff",
                        "surface-dim": "#d8dbd7",
                        "surface-bright": "#f7faf6",
                        "inverse-surface": "#2d312f",
                        "inverse-on-surface": "#eef2ed",
                        "inverse-primary": "#8bd6b6",
                        "surface-tint": "#1b6b51",
                    },
                    spacing: {
                        "stack-sm": "0.5rem",
                        "margin-page": "2rem",
                        "stack-lg": "1.5rem",
                        "gutter": "1.5rem",
                        "stack-md": "1rem",
                        "container-max": "1280px",
                        "sidebar-width": "260px"
                    },
                    fontFamily: {
                        "inter": ["Inter"],
                    },
                    fontSize: {
                        "body-lg": ["18px", { lineHeight: "28px", fontWeight: "400" }],
                        "h2": ["24px", { lineHeight: "32px", letterSpacing: "-0.01em", fontWeight: "600" }],
                        "h3": ["20px", { lineHeight: "28px", fontWeight: "600" }],
                        "body-sm": ["14px", { lineHeight: "20px", fontWeight: "400" }],
                        "button": ["14px", { lineHeight: "20px", fontWeight: "600" }],
                        "h1": ["30px", { lineHeight: "36px", letterSpacing: "-0.02em", fontWeight: "700" }],
                        "body-md": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                        "label-md": ["14px", { lineHeight: "20px", letterSpacing: "0.05em", fontWeight: "500" }]
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }
        body { font-family: 'Inter', sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="bg-background text-on-background antialiased font-inter text-base">

    <!-- SideNavBar -->
    <aside class="fixed left-0 top-0 h-screen w-64 border-r border-emerald-900 bg-emerald-950 shadow-xl flex flex-col py-6 z-50">
        <div class="px-6 mb-8 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-500 flex items-center justify-center">
                <span class="material-symbols-outlined text-emerald-950" style="font-variation-settings: 'FILL' 1;">school</span>
            </div>
            <div>
                <h1 class="text-white font-bold text-lg leading-tight">Darul Fikri</h1>
                <p class="text-emerald-100/50 text-xs uppercase tracking-wider">School System</p>
            </div>
        </div>

        <nav class="flex-1 space-y-1">
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('dashboard') ? 'bg-emerald-900/50 text-amber-500 border-l-4 border-amber-500' : 'text-emerald-100/70 hover:text-white hover:bg-emerald-900/30' }}"
                href="{{ route('dashboard') }}">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-medium text-sm">Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('siswa.*') ? 'bg-emerald-900/50 text-amber-500 border-l-4 border-amber-500' : 'text-emerald-100/70 hover:text-white hover:bg-emerald-900/30' }}"
                href="{{ route('siswa.index') }}">
                <span class="material-symbols-outlined">group</span>
                <span class="font-medium text-sm">Data Siswa</span>
            </a>
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('kelas.*') ? 'bg-emerald-900/50 text-amber-500 border-l-4 border-amber-500' : 'text-emerald-100/70 hover:text-white hover:bg-emerald-900/30' }}"
                href="{{ route('kelas.index') }}">
                <span class="material-symbols-outlined">class</span>
                <span class="font-medium text-sm">Kelas & Mapel</span>
            </a>
            <!-- Presensi Dropdown Menu -->
            <div x-data="{ open: {{ request()->routeIs('presensi.*') || request()->routeIs('presensi-sholat.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between px-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi.*') || request()->routeIs('presensi-sholat.*') ? 'bg-emerald-900/50 text-amber-500 border-l-4 border-amber-500' : 'text-emerald-100/70 hover:text-white hover:bg-emerald-900/30' }}">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined">how_to_reg</span>
                        <span class="font-medium text-sm">Presensi</span>
                    </div>
                    <span class="material-symbols-outlined text-sm transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                </button>
                
                <div x-show="open" x-collapse x-cloak class="bg-emerald-950/50">
                    <a href="{{ route('presensi.index') }}" class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi.index') || request()->routeIs('presensi.show') ? 'text-white' : 'text-emerald-100/60 hover:text-white hover:bg-emerald-900/20' }}">
                        <div class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('presensi.index') || request()->routeIs('presensi.show') ? 'bg-amber-500' : 'bg-emerald-700' }}"></div>
                        <span class="text-sm">Input Presensi</span>
                    </a>
                    <a href="{{ route('presensi.rekap') }}" class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi.rekap') ? 'text-white' : 'text-emerald-100/60 hover:text-white hover:bg-emerald-900/20' }}">
                        <div class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('presensi.rekap') ? 'bg-amber-500' : 'bg-emerald-700' }}"></div>
                        <span class="text-sm">Rekap Absensi Siswa</span>
                    </a>
                    <a href="{{ route('presensi-sholat.index', ['jenis' => 'Zuhur']) }}" class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi-sholat.*') && request()->get('jenis', 'Zuhur') == 'Zuhur' ? 'text-white' : 'text-emerald-100/60 hover:text-white hover:bg-emerald-900/20' }}">
                        <div class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('presensi-sholat.*') && request()->get('jenis', 'Zuhur') == 'Zuhur' ? 'bg-amber-500' : 'bg-emerald-700' }}"></div>
                        <span class="text-sm">Sholat Zuhur</span>
                    </a>
                    <a href="{{ route('presensi-sholat.index', ['jenis' => 'Dhuha']) }}" class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi-sholat.index') && request()->get('jenis') == 'Dhuha' || request()->routeIs('presensi-sholat.show') && request()->get('jenis') == 'Dhuha' ? 'text-white' : 'text-emerald-100/60 hover:text-white hover:bg-emerald-900/20' }}">
                        <div class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('presensi-sholat.index') && request()->get('jenis') == 'Dhuha' || request()->routeIs('presensi-sholat.show') && request()->get('jenis') == 'Dhuha' ? 'bg-amber-500' : 'bg-emerald-700' }}"></div>
                        <span class="text-sm">Sholat Dhuha</span>
                    </a>
                    <a href="{{ route('presensi-sholat.rekap') }}" class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('presensi-sholat.rekap') ? 'text-white' : 'text-emerald-100/60 hover:text-white hover:bg-emerald-900/20' }}">
                        <div class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('presensi-sholat.rekap') ? 'bg-amber-500' : 'bg-emerald-700' }}"></div>
                        <span class="text-sm">Rekap Absensi Sholat</span>
                    </a>
                </div>
            </div>
            <!-- Nilai Dropdown Menu -->
            <div x-data="{ open: {{ request()->routeIs('nilai.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full flex items-center justify-between px-6 py-3 transition-colors duration-200 {{ request()->routeIs('nilai.*') ? 'bg-emerald-900/50 text-amber-500 border-l-4 border-amber-500' : 'text-emerald-100/70 hover:text-white hover:bg-emerald-900/30' }}">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined">description</span>
                        <span class="font-medium text-sm">Nilai Rapot</span>
                    </div>
                    <span class="material-symbols-outlined text-sm transition-transform duration-200" :class="open ? 'rotate-180' : ''">expand_more</span>
                </button>
                
                <div x-show="open" x-collapse x-cloak class="bg-emerald-950/50">
                    <a href="{{ route('nilai.index') }}" class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('nilai.index') || request()->routeIs('nilai.show') ? 'text-white' : 'text-emerald-100/60 hover:text-white hover:bg-emerald-900/20' }}">
                        <div class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('nilai.index') || request()->routeIs('nilai.show') ? 'bg-amber-500' : 'bg-emerald-700' }}"></div>
                        <span class="text-sm">Input Nilai</span>
                    </a>
                    <a href="{{ route('nilai.rekap') }}" class="flex items-center gap-3 pl-12 pr-6 py-3 transition-colors duration-200 {{ request()->routeIs('nilai.rekap') ? 'text-white' : 'text-emerald-100/60 hover:text-white hover:bg-emerald-900/20' }}">
                        <div class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('nilai.rekap') ? 'bg-amber-500' : 'bg-emerald-700' }}"></div>
                        <span class="text-sm">Rekap Nilai Siswa</span>
                    </a>
                </div>
            </div>
            <a class="flex items-center gap-3 px-6 py-3 transition-colors duration-200
                {{ request()->routeIs('pengaturan.*') ? 'bg-emerald-900/50 text-amber-500 border-l-4 border-amber-500' : 'text-emerald-100/70 hover:text-white hover:bg-emerald-900/30' }}"
                href="{{ route('pengaturan.index') }}">
                <span class="material-symbols-outlined">settings</span>
                <span class="font-medium text-sm">Pengaturan</span>
            </a>
        </nav>

        <div class="px-6 mt-auto">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center gap-3 text-emerald-100/70 hover:text-white py-3 transition-colors duration-200 w-full">
                    <span class="material-symbols-outlined">logout</span>
                    <span class="font-medium text-sm">Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- TopNavBar -->
    <header class="fixed top-0 right-0 w-[calc(100%-16rem)] h-16 bg-white/80 backdrop-blur-md border-b border-gray-200 flex items-center justify-between px-8 z-40 shadow-sm">
        <div class="flex items-center gap-4">
            <h2 class="text-emerald-800 font-bold text-sm">@yield('page-title', 'Dashboard')</h2>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3 pl-6 border-l border-gray-200">
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-900">{{ Auth::user()->nama_lengkap ?? Auth::user()->name }}</p>
                    <p class="text-xs text-gray-500">Guru</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-800 font-bold border-2 border-emerald-50">
                    {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="ml-64 mt-16 p-margin-page min-h-screen">
        <div class="max-w-container-max mx-auto space-y-stack-lg">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div id="flash-success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-6 py-4 rounded-xl flex items-center gap-3 shadow-sm">
                    <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                    <span class="font-medium text-sm">{{ session('success') }}</span>
                    <button onclick="document.getElementById('flash-success').remove()" class="ml-auto text-emerald-400 hover:text-emerald-600">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
            @endif

            @if($errors->any())
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
