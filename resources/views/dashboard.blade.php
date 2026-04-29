@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <p class="text-sm text-gray-500">Selamat datang kembali,</p>
            <h2 class="font-bold text-2xl text-emerald-900">{{ Auth::user()->nama_lengkap ?? Auth::user()->name }} 👋</h2>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500 uppercase tracking-wider font-bold">Tahun Ajaran</p>
            <p class="text-sm font-bold text-emerald-800">{{ $tahunAktif->nama_semester ?? 'Belum diatur' }}</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-gutter">
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-emerald-50 flex items-center justify-center">
                    <span class="material-symbols-outlined text-emerald-700"
                        style="font-variation-settings: 'FILL' 1;">groups</span>
                </div>
                <span class="px-2 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded uppercase">Total</span>
            </div>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Siswa Aktif</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalSiswa }}</p>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-amber-50 flex items-center justify-center">
                    <span class="material-symbols-outlined text-amber-700"
                        style="font-variation-settings: 'FILL' 1;">school</span>
                </div>
                <span class="px-2 py-1 bg-amber-50 text-amber-700 text-[10px] font-bold rounded uppercase">Kelas</span>
            </div>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Kelas</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalKelas }}</p>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-700"
                        style="font-variation-settings: 'FILL' 1;">how_to_reg</span>
                </div>
                <span class="px-2 py-1 bg-blue-50 text-blue-700 text-[10px] font-bold rounded uppercase">Hari Ini</span>
            </div>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Kehadiran Hari Ini</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $persenHadir }}%</p>
        </div>

        <div class="bg-emerald-900 p-6 rounded-xl shadow-sm relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-xs text-emerald-100/70 uppercase font-bold tracking-wider">Presensi Hari Ini</p>
                <div class="flex items-center gap-4 mt-3">
                    <div class="text-center">
                        <p class="text-xl font-bold text-white">{{ $hadirCount }}</p>
                        <p class="text-[10px] text-emerald-200 uppercase">Hadir</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-blue-300">{{ $sakitCount }}</p>
                        <p class="text-[10px] text-emerald-200 uppercase">Sakit</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-amber-300">{{ $izinCount }}</p>
                        <p class="text-[10px] text-emerald-200 uppercase">Izin</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-red-300">{{ $alpaCount }}</p>
                        <p class="text-[10px] text-emerald-200 uppercase">Alpa</p>
                    </div>
                </div>
            </div>
            <span
                class="material-symbols-outlined absolute -bottom-4 -right-4 text-emerald-800 text-8xl opacity-30">monitoring</span>
        </div>
    </div>

    <!-- Jadwal Sepekan -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-900">Jadwal Mengajar Sepekan</h3>
            <span
                class="px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-medium rounded-full border border-emerald-200">
                {{ $jadwalSepekan->count() }} Sesi
            </span>
        </div>
        @if ($jadwalSepekan->count() > 0)
            <div class="divide-y divide-gray-100">
                @php $currentHari = ''; @endphp
                @foreach ($jadwalSepekan as $jadwal)
                    @if ($jadwal->hari !== $currentHari)
                        <div class="px-8 py-2 bg-gray-50 border-t border-gray-100 mt-2 first:mt-0 first:border-0">
                            <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider">{{ $jadwal->hari }}</p>
                        </div>
                        @php $currentHari = $jadwal->hari; @endphp
                    @endif
                    <div class="flex items-center gap-6 px-8 py-4 hover:bg-gray-50 transition-colors">
                        <div class="w-20 text-center">
                            <p class="text-sm font-bold text-emerald-800">
                                {{ \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') }}</p>
                            <p class="text-xs text-gray-400">
                                {{ \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') }}</p>
                        </div>
                        <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ $jadwal->mataPelajaran->nama_pelajaran ?? '-' }}</p>
                            <p class="text-xs text-gray-500">{{ $jadwal->kelas->nama_kelas ?? '-' }}</p>
                        </div>
                        <a href="{{ route('presensi.show', $jadwal->kelas_id) }}?jadwal_id={{ $jadwal->id }}"
                            class="px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg hover:bg-emerald-100 transition-colors">
                            Mulai Presensi
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-8 py-12 text-center text-gray-400">
                <span class="material-symbols-outlined text-4xl mb-2">event_available</span>
                <p class="text-sm">Tidak ada jadwal mengajar dalam sepekan</p>
            </div>
        @endif
    </div>

    <!-- Daftar Kelas -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-gray-900">Daftar Kelas</h3>
            <a href="{{ route('kelas.index') }}" class="text-sm text-emerald-700 font-medium hover:underline">Lihat Semua
                →</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
            @foreach ($kelasList as $kelas)
                <a href="{{ route('kelas.show', $kelas->id) }}"
                    class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow block">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-lg bg-emerald-900 flex items-center justify-center">
                            <span class="material-symbols-outlined text-white">class</span>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $kelas->nama_kelas }}</h4>
                            <p class="text-xs text-gray-500">{{ $kelas->waliKelas?->nama_lengkap ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Siswa Aktif</span>
                        <span class="font-bold text-emerald-700">{{ $kelas->siswa_count }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endsection
