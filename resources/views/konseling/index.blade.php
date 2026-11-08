@extends('layouts.app')
@section('title', 'Konseling')
@section('page-title', 'Konseling')

@section('content')
@php $isAdmin = Auth::user()->isAdmin(); @endphp

<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
        <h2 class="font-bold text-2xl text-blue-600">Konseling Wali Murid</h2>
        <p class="text-gray-500 text-sm mt-1">
            @if($kelasDiampu->isNotEmpty())
                Anda pengampu konseling untuk {{ $kelasDiampu->pluck('nama_kelas')->join(', ', ' dan ') }}. Wali murid mengirim pesan dari aplikasi MyDarfik.
            @elseif($isAdmin)
                Isi percakapan bersifat privat antara wali murid dan pengampu, sehingga tidak tampil di sini. Anda bisa mengatur pengampu tiap kelas.
            @else
                Anda belum ditetapkan sebagai pengampu konseling kelas mana pun.
            @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($isAdmin)
            <a href="{{ route('konseling.pengampu') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-bold hover:bg-gray-50">
                <span class="material-symbols-outlined text-sm">manage_accounts</span>
                Atur Pengampu
            </a>
        @endif
        @if($kelasDiampu->isNotEmpty())
            <a href="{{ route('konseling.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 shadow-sm">
                <span class="material-symbols-outlined text-sm">add_comment</span>
                Mulai Percakapan
            </a>
        @endif
    </div>
</div>

@if($isAdmin && $kelasTanpaPengampu > 0)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg px-4 py-3 flex items-start gap-2">
        <span class="material-symbols-outlined text-base">warning</span>
        <span>{{ $kelasTanpaPengampu }} kelas belum punya pengampu konseling. Wali murid di kelas tersebut belum bisa memulai konseling.
            <a href="{{ route('konseling.pengampu') }}" class="font-bold underline">Atur sekarang</a></span>
    </div>
@endif

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex gap-1 bg-gray-100 rounded-lg p-1 w-fit">
            @foreach(['Aktif' => 'Aktif', 'Selesai' => 'Selesai', 'semua' => 'Semua'] as $value => $label)
                <a href="{{ route('konseling.index', array_filter(['status' => $value, 'search' => $search])) }}"
                    class="px-4 py-1.5 rounded-md text-sm font-semibold {{ $status === $value ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">{{ $label }}</a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('konseling.index') }}" class="relative md:w-72">
            <input type="hidden" name="status" value="{{ $status }}">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">search</span>
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari siswa atau topik" aria-label="Cari siswa atau topik"
                class="w-full pl-9 pr-3 h-10 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none">
        </form>
    </div>

    <ul class="divide-y divide-gray-100">
        @forelse($percakapan as $p)
            <li>
                <a href="{{ route('konseling.show', $p) }}" class="flex items-start gap-4 px-4 md:px-6 py-4 hover:bg-blue-50/40 transition-colors">
                    <div class="w-10 h-10 shrink-0 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                        {{ $p->siswa?->initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-bold text-gray-900 truncate">{{ $p->topik }}</p>
                            <span class="text-xs text-gray-400 shrink-0">{{ $p->pesan_terakhir_pada?->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Wali {{ $p->siswa?->nama_siswa }} · {{ $p->siswa?->kelas?->nama_kelas ?? '-' }}
                            @if($isAdmin)
                                · Pengampu: {{ $p->siswa?->kelas?->pengampuKonseling?->nama_lengkap ?? 'belum ada' }}
                            @endif
                        </p>
                        @if($p->pesanTerakhir)
                            <p class="text-sm text-gray-600 mt-1 truncate">
                                <span class="font-semibold">{{ $p->pesanTerakhir->pengirim === 'wali' ? 'Wali' : 'Guru' }}:</span>
                                {{ $p->pesanTerakhir->ringkas() }}
                            </p>
                        @endif
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        @if($p->pesan_wali_belum_dibaca_count > 0)
                            <span class="min-w-5 h-5 px-1.5 rounded-full bg-red-600 text-white text-[11px] font-bold flex items-center justify-center">{{ $p->pesan_wali_belum_dibaca_count }}</span>
                        @endif
                        @if($p->status === 'Selesai')
                            <span class="text-[11px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded">Selesai</span>
                        @endif
                    </div>
                </a>
            </li>
        @empty
            <li class="p-12 text-center">
                <span class="material-symbols-outlined text-5xl text-gray-300">forum</span>
                <p class="font-medium text-gray-600 mt-2">Belum ada percakapan {{ $status === 'semua' ? '' : strtolower($status) }}</p>
                <p class="text-sm text-gray-400 mt-1">Percakapan muncul saat wali murid mengirim pesan dari aplikasi, atau saat Anda memulai percakapan.</p>
            </li>
        @endforelse
    </ul>
</div>

{{ $percakapan->links() }}
@endsection
