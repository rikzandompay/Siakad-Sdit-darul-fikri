@extends('layouts.app')
@section('title', 'Tugas '.$mapel->nama_pelajaran.' - '.$kelas->nama_kelas)
@section('page-title', 'Ruang Tugas')

@section('content')
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div class="min-w-0">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('kelas.index') }}" class="hover:text-blue-600">Kelas &amp; Mapel</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <a href="{{ route('kelas.show', $kelas) }}" class="hover:text-blue-600">{{ $kelas->nama_kelas }}</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-blue-600 font-medium">{{ $mapel->nama_pelajaran }}</span>
        </div>
        <h2 class="font-bold text-2xl text-blue-600">Ruang Tugas {{ $mapel->nama_pelajaran }}</h2>
        <p class="text-gray-500 text-sm mt-1">{{ $kelas->nama_kelas }} · {{ $jumlahSiswa }} siswa. Tugas di sini tampil di aplikasi MyDarfik wali murid.</p>
    </div>
    <a href="{{ route('tugas.create', [$kelas, $mapel]) }}" class="btn btn-primary inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold">
        <span class="material-symbols-outlined text-sm">add</span> Buat Tugas
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <ul class="divide-y divide-gray-100">
        @forelse($tugasList as $t)
            @php $lewat = $t->tenggat && $t->tenggat->isPast() && ! $t->tenggat->isToday(); @endphp
            <li>
                <a href="{{ route('tugas.show', $t) }}" class="flex items-start justify-between gap-4 px-4 md:px-6 py-4 hover:bg-blue-50/40">
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 truncate">{{ $t->judul }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">Dibuat {{ $t->guru?->nama_lengkap ?? '-' }} · {{ $t->created_at->translatedFormat('d M Y') }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-xs {{ $lewat ? 'text-red-600 font-semibold' : 'text-gray-500' }}">{{ $t->tenggat ? 'Tenggat '.$t->tenggat->translatedFormat('d M Y') : 'Tanpa tenggat' }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t->dinilai_count }}/{{ $jumlahSiswa }} dinilai</p>
                    </div>
                </a>
            </li>
        @empty
            <li class="p-12 text-center">
                <span class="material-symbols-outlined text-5xl text-gray-300">assignment</span>
                <p class="font-medium text-gray-600 mt-2">Belum ada tugas {{ $mapel->nama_pelajaran }} untuk {{ $kelas->nama_kelas }}</p>
                <p class="text-sm text-gray-400 mt-1">Klik "Buat Tugas" untuk membagikan tugas pertama.</p>
            </li>
        @endforelse
    </ul>
</div>
{{ $tugasList->links() }}
@endsection
