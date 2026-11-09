@extends('layouts.app')
@section('title', 'Presensi')
@section('page-title', 'Presensi & Kehadiran')

@section('content')
    <div class="mb-2">
        <h2 class="font-bold text-2xl text-blue-600">Presensi & Nilai Siswa</h2>
        <p class="text-gray-500 text-sm mt-1">Kelola kehadiran harian seluruh siswa berdasarkan jenjang kelas.</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="p-6 rounded-xl border border-blue-200/80 shadow-sm hover:shadow-md transition-all duration-200"
            style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-blue-100/80 rounded-lg text-blue-700">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">groups</span>
                </div>
                <span class="text-xs font-bold text-blue-800 bg-blue-100/80 px-2 py-1 rounded">Hadir</span>
            </div>
            <p class="text-xs text-gray-700 uppercase font-bold tracking-wider">Kehadiran Hari Ini</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $persenHadir }}%</p>
        </div>
        <div class="p-6 rounded-xl border border-blue-200/80 shadow-sm hover:shadow-md transition-all duration-200"
            style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-amber-100/80 rounded-lg text-amber-800">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">sick</span>
                </div>
                <span class="text-xs font-bold text-amber-900 bg-amber-100/80 px-2 py-1 rounded">Sakit</span>
            </div>
            <p class="text-xs text-gray-700 uppercase font-bold tracking-wider">Sakit Hari Ini</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $sakitCount }}</p>
        </div>
        <div class="p-6 rounded-xl border border-blue-200/80 shadow-sm hover:shadow-md transition-all duration-200"
            style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-purple-100/80 rounded-lg text-purple-800">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person_pin</span>
                </div>
                <span class="text-xs font-bold text-purple-900 bg-purple-100/80 px-2 py-1 rounded">Total</span>
            </div>
            <p class="text-xs text-gray-700 uppercase font-bold tracking-wider">Total Siswa Aktif</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalSiswaAktif }} Siswa</p>
        </div>
        <div class="p-6 rounded-xl border border-blue-200/80 shadow-sm hover:shadow-md transition-all duration-200"
            style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
            <div class="flex items-center justify-between mb-4">
                <div class="p-2.5 bg-red-100/80 rounded-lg text-red-800">
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person_off</span>
                </div>
                <span class="text-xs font-bold text-red-900 bg-red-100/80 px-2 py-1 rounded">Alpa</span>
            </div>
            <p class="text-xs text-gray-700 uppercase font-bold tracking-wider">Alpa Hari Ini</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $alpaCount }}</p>
        </div>
    </div>

    <!-- Classes Grid -->
    <div>
        <h3 class="font-bold text-lg text-gray-900 mb-4">Pilih Kelas untuk Presensi</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($kelasList as $kelas)
                <div
                    class="rounded-xl border border-blue-200/80 shadow-sm overflow-hidden hover:shadow-md transition-all duration-200"
                    style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
                    <div class="h-28 bg-white/70 border-b border-blue-100/80 relative overflow-hidden flex items-end p-6">
                        <div class="relative z-10">
                            <div
                                class="bg-blue-600 text-white font-bold px-2.5 py-0.5 rounded text-[10px] inline-block mb-2 uppercase tracking-wider shadow-2xs">
                                {{ $kelas->siswa_aktif_count > 0 ? 'Aktif' : 'Kosong' }}
                            </div>
                            <h3 class="text-blue-700 font-bold text-lg leading-none">{{ $kelas->nama_kelas }}</h3>
                        </div>
                        <span
                            class="material-symbols-outlined absolute -bottom-4 -right-4 text-blue-200/50 text-8xl" aria-hidden="true">school</span>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700 font-medium">Total Siswa</span>
                                <span class="font-bold text-gray-900">{{ $kelas->siswa_aktif_count }} Siswa</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700 font-medium">Wali Kelas</span>
                                <span
                                    class="font-bold text-gray-800 text-xs">{{ $kelas->waliKelas?->nama_lengkap ?? '-' }}</span>
                            </div>
                        </div>
                        <a href="{{ route('presensi.show', $kelas->id) }}"
                            class="btn btn-primary w-full font-bold text-sm py-2.5 rounded-lg transition-colors text-center flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">how_to_reg</span>
                            Mulai Presensi
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 py-16 text-center">
                    <span class="material-symbols-outlined text-5xl text-gray-300">how_to_reg</span>
                    <h4 class="text-lg font-bold text-gray-800 mt-3">Belum ada kelas untuk diisi</h4>
                    <p class="text-gray-600 text-sm max-w-md mx-auto mt-1">Presensi kelas hanya diisi oleh wali kelas. Anda
                        belum terdaftar sebagai wali kelas mana pun. Rekap presensi kelas yang Anda ajar tetap bisa dilihat
                        di menu Rekap.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
