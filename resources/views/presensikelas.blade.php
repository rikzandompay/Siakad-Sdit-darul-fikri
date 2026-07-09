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
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="p-2.5 bg-blue-50 rounded-lg text-blue-600">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">groups</span>
            </div>
            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded">Hadir</span>
        </div>
        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Kehadiran Hari Ini</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $persenHadir }}%</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="p-2.5 bg-amber-50 rounded-lg text-amber-600">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">sick</span>
            </div>
            <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2 py-1 rounded">Sakit</span>
        </div>
        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Sakit Hari Ini</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $sakitCount }}</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="p-2.5 bg-purple-50 rounded-lg text-purple-600">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person_pin</span>
            </div>
            <span class="text-xs font-bold text-purple-700 bg-purple-50 px-2 py-1 rounded">Total</span>
        </div>
        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Siswa Aktif</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $totalSiswaAktif }} Siswa</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="p-2.5 bg-red-50 rounded-lg text-red-600">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person_off</span>
            </div>
            <span class="text-xs font-bold text-red-700 bg-red-50 px-2 py-1 rounded">Alpa</span>
        </div>
        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Alpa Hari Ini</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $alpaCount }}</p>
    </div>
</div>

<!-- Classes Grid -->
<div>
    <h3 class="font-bold text-lg text-gray-900 mb-4">Pilih Kelas untuk Presensi</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($kelasList as $kelas)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                <div class="h-28 bg-blue-500 relative overflow-hidden flex items-end p-6">
                    <div>
                        <div class="bg-amber-500 text-blue-950 font-bold px-2.5 py-0.5 rounded text-[10px] inline-block mb-2 uppercase tracking-wider">
                            {{ $kelas->siswa_aktif_count > 0 ? 'Aktif' : 'Kosong' }}
                        </div>
                        <h3 class="text-white font-bold text-lg leading-none">{{ $kelas->nama_kelas }}</h3>
                    </div>
                    <span class="material-symbols-outlined absolute -bottom-4 -right-4 text-blue-600 text-8xl opacity-30">school</span>
                </div>
                <div class="p-6">
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Total Siswa</span>
                            <span class="font-bold text-gray-900">{{ $kelas->siswa_aktif_count }} Siswa</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Wali Kelas</span>
                            <span class="font-bold text-gray-700 text-xs">{{ $kelas->waliKelas?->nama_lengkap ?? '-' }}</span>
                        </div>
                    </div>
                    <a href="{{ route('presensi.show', $kelas->id) }}"
                        class="block w-full bg-blue-500 hover:bg-blue-950 text-gray-900 font-bold text-sm py-2.5 rounded-lg transition-colors text-center">
                        <span class="material-symbols-outlined text-sm align-middle mr-1">how_to_reg</span>
                        Mulai Presensi
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
