@extends('layouts.app')
@section('title', 'Rekap Nilai Siswa')
@section('page-title', 'Rekap Nilai Siswa')

@section('content')
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div class="space-y-1">
        <p class="text-amber-600 font-bold text-xs uppercase tracking-wider">Rekapitulasi</p>
        <h2 class="font-bold text-2xl text-emerald-900">Rekap Nilai Semua Mapel</h2>
    </div>
    @if($siswaList->count() > 0 && $kelasId && $tahunAjaranId)
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('nilai.rekap.export.pdf', ['tahun_ajaran_id' => $tahunAjaranId, 'kelas_id' => $kelasId]) }}"
           target="_blank"
           class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm text-red-500">picture_as_pdf</span>
            Export PDF
        </a>
        <a href="{{ route('nilai.rekap.export.csv', ['tahun_ajaran_id' => $tahunAjaranId, 'kelas_id' => $kelasId]) }}"
           class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm text-emerald-600">table_chart</span>
            Export CSV
        </a>
    </div>
    @endif
</div>

<!-- Filter Section -->
<div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm mt-6">
    <form method="GET" action="{{ route('nilai.rekap') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Tahun Ajaran</label>
            <select name="tahun_ajaran_id" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20">
                @foreach($tahunAjaranList as $ta)
                    <option value="{{ $ta->id }}" {{ $tahunAjaranId == $ta->id ? 'selected' : '' }}>
                        {{ $ta->nama_semester }} {{ $ta->status_aktif == 'Y' ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Kelas</label>
            <select name="kelas_id" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20">
                <option value="">-- Pilih Kelas --</option>
                @foreach($kelasList as $k)
                    <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="w-full bg-emerald-900 text-white px-4 py-2.5 rounded-lg font-bold text-sm hover:bg-emerald-800 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">filter_list</span>
                Tampilkan Rekap
            </button>
        </div>
    </form>
</div>

@if($siswaList->count() > 0 && $mapelList->count() > 0)
<!-- Info Bar -->
@if($selectedKelas && $tahunAjaranAktif)
<div class="flex items-center gap-2 flex-wrap mt-6">
    <span class="text-xs text-gray-500 font-medium">Menampilkan:</span>
    <span class="px-2 py-1 bg-emerald-100 text-emerald-800 text-[11px] rounded-full font-medium">{{ $selectedKelas->nama_kelas }}</span>
    <span class="px-2 py-1 bg-amber-100 text-amber-800 text-[11px] rounded-full font-medium">{{ $tahunAjaranAktif->nama_semester }}</span>
</div>
@endif

<!-- Grade Recap Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mt-6">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-emerald-700">list_alt</span>
            <h3 class="font-bold text-emerald-900">Rekapitulasi Nilai Siswa per Mata Pelajaran</h3>
        </div>
        <span class="text-sm text-gray-500">{{ $siswaList->count() }} Siswa</span>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-max">
            <thead>
                <tr class="bg-gray-50/50">
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase w-12 border-b">No</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase border-b">Nama Siswa / NIS</th>
                    @foreach($mapelList as $m)
                        <th class="px-4 py-4 text-xs font-bold text-gray-500 uppercase text-center border-b max-w-[120px] truncate" title="{{ $m->nama_pelajaran }}">
                            {{ substr($m->nama_pelajaran, 0, 15) }}{{ strlen($m->nama_pelajaran) > 15 ? '...' : '' }}
                        </th>
                    @endforeach
                    <th class="px-6 py-4 text-xs font-bold text-emerald-700 uppercase text-center border-b bg-emerald-50/50">Rata-rata</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($siswaList as $index => $s)
                    <tr class="hover:bg-gray-50/30 transition-colors">
                        <td class="px-6 py-4 text-sm text-gray-400">{{ $index + 1 }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full {{ $s->jenis_kelamin == 'L' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex flex-shrink-0 items-center justify-center text-xs font-bold">{{ $s->initials }}</div>
                                <div>
                                    <p class="font-medium text-gray-900 whitespace-nowrap">{{ $s->nama_siswa }}</p>
                                    <p class="text-[11px] text-gray-400 tracking-wider">NIS: {{ $s->nis }}</p>
                                </div>
                            </div>
                        </td>
                        @foreach($mapelList as $m)
                            @php
                                $nilai = $nilaiMap[$s->id][$m->id] ?? null;
                            @endphp
                            <td class="px-4 py-4 text-center">
                                @if($nilai !== null)
                                    <span class="font-medium {{ $nilai >= 75 ? 'text-gray-700' : 'text-red-500' }}">
                                        {{ number_format($nilai, 1) }}
                                    </span>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                        @endforeach
                        @php
                            $rataRata = $rataRataSiswa[$s->id] ?? 0;
                        @endphp
                        <td class="px-6 py-4 text-center font-bold text-emerald-900 bg-emerald-50/30 text-lg">
                            {{ $rataRata > 0 ? number_format($rataRata, 1) : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@elseif($kelasId)
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center text-gray-400 mt-6">
    <span class="material-symbols-outlined text-5xl mb-3">school</span>
    <p class="font-medium">Tidak ada siswa atau mata pelajaran aktif untuk kelas ini</p>
</div>
@else
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center text-gray-400 mt-6">
    <span class="material-symbols-outlined text-5xl mb-3">analytics</span>
    <p class="font-medium">Pilih Tahun Ajaran dan Kelas</p>
    <p class="text-sm mt-1">Rekap nilai semua mata pelajaran akan ditampilkan setelah filter diterapkan</p>
</div>
@endif

@endsection
