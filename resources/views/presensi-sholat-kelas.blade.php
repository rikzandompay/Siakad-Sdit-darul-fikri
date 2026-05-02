@extends('layouts.app')
@section('title', 'Presensi Sholat ' . $jenisSholat)
@section('page-title', 'Presensi Sholat ' . $jenisSholat)

@section('content')
<div class="mb-2">
    <h2 class="font-bold text-2xl text-emerald-900">Presensi Sholat {{ $jenisSholat }}</h2>
    <p class="text-gray-500 text-sm mt-1">
        @if($jenisSholat == 'Zuhur')
            Kelola kehadiran sholat Zuhur berjamaah untuk kelas 3 hingga 6.
        @else
            Kelola kehadiran sholat Dhuha berjamaah untuk seluruh kelas.
        @endif
    </p>
</div>

<!-- Toggle Jenis Sholat -->
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('presensi-sholat.index', ['jenis' => 'Zuhur']) }}"
       class="px-5 py-2.5 rounded-lg text-sm font-bold transition-colors {{ $jenisSholat == 'Zuhur' ? 'bg-emerald-900 text-white shadow-md' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
        <span class="material-symbols-outlined text-sm align-middle mr-1">mosque</span>
        Sholat Zuhur
    </a>
    <a href="{{ route('presensi-sholat.index', ['jenis' => 'Dhuha']) }}"
       class="px-5 py-2.5 rounded-lg text-sm font-bold transition-colors {{ $jenisSholat == 'Dhuha' ? 'bg-emerald-900 text-white shadow-md' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
        <span class="material-symbols-outlined text-sm align-middle mr-1">wb_sunny</span>
        Sholat Dhuha
    </a>
</div>



<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="p-2.5 bg-emerald-50 rounded-lg text-emerald-700">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">groups</span>
            </div>
            <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded">Hadir</span>
        </div>
        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Kehadiran Hari Ini</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $persenHadir }}%</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="p-2.5 bg-blue-50 rounded-lg text-blue-700">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">sick</span>
            </div>
            <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded">Sakit</span>
        </div>
        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Sakit Hari Ini</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $sakitCount }}</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="p-2.5 bg-amber-50 rounded-lg text-amber-700">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">mail</span>
            </div>
            <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded">Izin</span>
        </div>
        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Izin Hari Ini</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $izinCount }}</p>
    </div>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div class="p-2.5 bg-red-50 rounded-lg text-red-700">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person_off</span>
            </div>
            <span class="text-xs font-bold text-red-600 bg-red-50 px-2 py-1 rounded">Alpa</span>
        </div>
        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Alpa Hari Ini</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $alpaCount }}</p>
    </div>
</div>

<!-- Classes Grid -->
<div>
    <h3 class="font-bold text-lg text-gray-900 mb-4">Pilih Kelas untuk Presensi Sholat {{ $jenisSholat }}</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($kelasList as $kelas)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                <div class="h-28 bg-emerald-900 relative overflow-hidden flex items-end p-6">
                    <div>
                        <div class="bg-amber-500 text-emerald-950 font-bold px-2.5 py-0.5 rounded text-[10px] inline-block mb-2 uppercase tracking-wider">
                            {{ $jenisSholat == 'Zuhur' ? 'Setiap Hari' : 'Setiap Hari' }}
                        </div>
                        <h3 class="text-white font-bold text-lg leading-none">{{ $kelas->nama_kelas }}</h3>
                    </div>
                    <span class="material-symbols-outlined absolute -bottom-4 -right-4 text-emerald-800 text-8xl opacity-30">mosque</span>
                </div>
                <div class="p-6">
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Total Siswa</span>
                            <span class="font-bold text-gray-900">{{ $kelas->siswa_count }} Siswa</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Wali Kelas</span>
                            <span class="font-bold text-gray-700 text-xs">{{ $kelas->waliKelas?->nama_lengkap ?? '-' }}</span>
                        </div>
                    </div>
                    <a href="{{ route('presensi-sholat.show', ['kelas' => $kelas->id, 'jenis' => $jenisSholat]) }}"
                        class="block w-full bg-emerald-900 hover:bg-emerald-950 text-white font-bold text-sm py-2.5 rounded-lg transition-colors text-center">
                        <span class="material-symbols-outlined text-sm align-middle mr-1">how_to_reg</span>
                        Mulai Presensi
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 py-20 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 rounded-full bg-emerald-50 flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-4xl text-emerald-400" style="font-variation-settings: 'FILL' 1;">mosque</span>
                </div>
                <h4 class="text-lg font-bold text-gray-800 mb-2">Belum Ada Kelas yang Tersedia</h4>
                <p class="text-gray-400 text-sm max-w-sm leading-relaxed">
                    @if($jenisSholat == 'Zuhur')
                        Pembiasaan sholat Zuhur berjamaah di sekolah diperuntukkan bagi kelas 3 hingga kelas 6. Kelas yang Anda ampu saat ini belum termasuk dalam program ini.
                    @else
                        Anda belum terdaftar sebagai wali kelas yang mengikuti program sholat Dhuha berjamaah.
                    @endif
                </p>
            </div>
        @endforelse
    </div>
</div>
@endsection
