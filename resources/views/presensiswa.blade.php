@extends('layouts.app')
@section('title', 'Presensi ' . $kelas->nama_kelas)
@section('page-title', 'Presensi Siswa')

@section('content')
<!-- Header with Export Buttons -->
<div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('presensi.index') }}" class="hover:text-emerald-700">Presensi</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-emerald-800 font-medium">{{ $kelas->nama_kelas }}</span>
        </div>
        <h2 class="font-bold text-2xl text-emerald-900">Presensi Siswa</h2>
        <p class="text-gray-500 text-sm">Kelola kehadiran harian siswa {{ $kelas->nama_kelas }}</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('presensi.export.pdf', ['kelas' => $kelas->id, 'jadwal_id' => $jadwalId, 'tanggal' => $tanggal, 'rentang' => $rentang]) }}"
           target="_blank"
           class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm text-red-500">picture_as_pdf</span>
            Export PDF
        </a>
        <a href="{{ route('presensi.export.csv', ['kelas' => $kelas->id, 'jadwal_id' => $jadwalId, 'tanggal' => $tanggal, 'rentang' => $rentang]) }}"
           class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm text-emerald-600">table_chart</span>
            Export CSV
        </a>
        @if($jadwalId)
        <button type="submit" form="form-presensi"
           class="flex items-center gap-2 px-5 py-2.5 bg-amber-500 text-white rounded-lg text-sm font-bold hover:bg-amber-600 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm">save</span>
            Simpan Presensi
        </button>
        @endif
    </div>
</div>

<!-- Summary Cards (matching screenshot) -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-emerald-600" style="font-variation-settings: 'FILL' 1;">check_circle</span>
            </div>
            <span class="text-sm font-bold text-emerald-700">Hadir</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $hadirCount }}</p>
        <p class="text-xs text-gray-400 mt-1">Siswa telah terdata hadir</p>
    </div>
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600" style="font-variation-settings: 'FILL' 1;">medical_services</span>
            </div>
            <span class="text-sm font-bold text-blue-700">Sakit</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $sakitCount }}</p>
        <p class="text-xs text-gray-400 mt-1">Dalam pantauan kesehatan</p>
    </div>
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-amber-600" style="font-variation-settings: 'FILL' 1;">mail</span>
            </div>
            <span class="text-sm font-bold text-amber-700">Izin</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $izinCount }}</p>
        <p class="text-xs text-gray-400 mt-1">Disetujui pihak sekolah</p>
    </div>
    <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600" style="font-variation-settings: 'FILL' 1;">cancel</span>
            </div>
            <span class="text-sm font-bold text-red-700">Alfa</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $alpaCount }}</p>
        <p class="text-xs text-gray-400 mt-1">Tanpa keterangan valid</p>
    </div>
</div>

<!-- Filter Bar (matching screenshot: Pilih Kelas, Mata Pelajaran, Tanggal, Rentang Waktu) -->
<div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
    <form method="GET" action="{{ route('presensi.show', $kelas->id) }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Pilih Kelas</label>
            <select onchange="window.location.href='/presensi/'+this.value" class="w-full bg-white border border-gray-200 rounded-lg text-sm h-10 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                @php
                    $guruKelasIds = \App\Models\JadwalPelajaran::where('guru_id', Auth::id())->pluck('kelas_id')->unique();
                @endphp
                @foreach(\App\Models\Kelas::whereIn('id', $guruKelasIds)->orderBy('nama_kelas')->get() as $k)
                    <option value="{{ $k->id }}" {{ $kelas->id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Mata Pelajaran</label>
            <select name="jadwal_id" class="w-full bg-white border border-gray-200 rounded-lg text-sm h-10 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                @foreach($jadwalList as $j)
                    <option value="{{ $j->id }}" {{ $jadwalId == $j->id ? 'selected' : '' }}>
                        {{ $j->mataPelajaran->nama_pelajaran }} ({{ $j->hari }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Tanggal</label>
            <input name="tanggal" type="date" value="{{ $tanggal }}" class="w-full bg-white border border-gray-200 rounded-lg text-sm h-10 px-3 focus:ring-emerald-500 focus:border-emerald-500" />
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Rentang Waktu</label>
            <select name="rentang" class="w-full bg-white border border-gray-200 rounded-lg text-sm h-10 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="hari_ini" {{ $rentang == 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                <option value="minggu_ini" {{ $rentang == 'minggu_ini' ? 'selected' : '' }}>Minggu Ini</option>
                <option value="bulan_ini" {{ $rentang == 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                <option value="semester_ini" {{ $rentang == 'semester_ini' ? 'selected' : '' }}>Semester Ini</option>
            </select>
        </div>
        <div>
            <button type="submit" class="w-full h-10 bg-emerald-900 text-white font-bold px-6 rounded-lg hover:bg-emerald-800 text-sm transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">filter_list</span>
                Filter Data
            </button>
        </div>
    </form>
</div>

@if($jadwalId && $siswa->count() > 0)
<!-- Attendance Form -->
<form id="form-presensi" method="POST" action="{{ route('presensi.store') }}">
    @csrf
    <input type="hidden" name="jadwal_id" value="{{ $jadwalId }}">
    <input type="hidden" name="tanggal" value="{{ $tanggal }}">

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-700">list_alt</span>
                <h3 class="font-bold text-gray-900">Daftar Kehadiran Siswa</h3>
            </div>
            <span class="text-sm text-gray-500">{{ $siswa->count() }} Siswa</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-6 py-4 border-b">No</th>
                        <th class="px-6 py-4 border-b">NIS</th>
                        <th class="px-6 py-4 border-b">Nama Lengkap</th>
                        <th class="px-6 py-4 text-center border-b">L/P</th>
                        <th class="px-6 py-4 text-center border-b">Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($siswa as $index => $s)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-400">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $s->nis }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full {{ $s->jenis_kelamin == 'L' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center font-bold text-xs">
                                        {{ $s->initials }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $s->nama_siswa }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $s->jenis_kelamin == 'L' ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700' }}">{{ $s->jenis_kelamin }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <input type="hidden" name="presensi[{{ $index }}][siswa_id]" value="{{ $s->id }}">
                                <div class="flex justify-center gap-1">
                                    @php $currentStatus = $existingPresensi[$s->id] ?? 'H'; @endphp
                                    @foreach(['H' => 'HADIR', 'S' => 'SAKIT', 'I' => 'IZIN', 'A' => 'ALPA'] as $code => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="presensi[{{ $index }}][status]" value="{{ $code }}" class="hidden peer"
                                                {{ $currentStatus == $code ? 'checked' : '' }}>
                                            <span class="block px-4 py-2 rounded-lg text-[11px] font-bold transition-all border
                                                peer-checked:border-transparent peer-checked:text-white peer-checked:shadow-md
                                                @if($code == 'H') peer-checked:bg-emerald-600 border-gray-200 text-gray-500 hover:bg-emerald-50
                                                @elseif($code == 'S') peer-checked:bg-blue-600 border-gray-200 text-gray-500 hover:bg-blue-50
                                                @elseif($code == 'I') peer-checked:bg-amber-500 border-gray-200 text-gray-500 hover:bg-amber-50
                                                @else peer-checked:bg-red-600 border-gray-200 text-gray-500 hover:bg-red-50
                                                @endif">
                                                {{ $label }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end mt-4">
        <button type="submit" class="px-8 py-3 bg-amber-500 text-white font-bold rounded-xl hover:bg-amber-600 transition-colors shadow-lg shadow-amber-500/20 flex items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            Simpan Presensi
        </button>
    </div>
</form>
@elseif($siswa->count() == 0)
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center text-gray-400">
    <span class="material-symbols-outlined text-5xl mb-3">person_off</span>
    <p class="font-medium">Tidak ada siswa aktif di kelas ini</p>
</div>
@else
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center text-gray-400">
    <span class="material-symbols-outlined text-5xl mb-3">calendar_today</span>
    <p class="font-medium">Silakan pilih mata pelajaran terlebih dahulu</p>
    <p class="text-sm mt-1">Pilih jadwal pelajaran di filter di atas</p>
</div>
@endif
@endsection
