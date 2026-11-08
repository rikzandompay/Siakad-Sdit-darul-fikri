@extends('layouts.app')
@section('title', 'Pengumuman')
@section('page-title', 'Pengumuman')

@section('content')
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
        <h2 class="font-bold text-2xl text-blue-600">Pengumuman Sekolah</h2>
        <p class="text-gray-500 text-sm mt-1">
            @if($isAdmin)
                Pengumuman untuk "Semua" dan "Wali Murid" juga tampil di aplikasi MyDarfik wali murid.
            @else
                Informasi terbaru dari sekolah untuk guru.
            @endif
        </p>
    </div>
    @if($isAdmin)
        <a href="{{ route('pengumuman.create') }}"
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm">add</span>
            Buat Pengumuman
        </a>
    @endif
</div>

<form method="GET" action="{{ route('pengumuman.index') }}" class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
    <div class="{{ $isAdmin ? 'md:col-span-2' : 'md:col-span-3' }}">
        <label for="search" class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Cari</label>
        <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Judul atau isi pengumuman"
            class="w-full bg-white border border-gray-200 rounded-lg text-sm h-10 px-3 focus:ring-blue-500 focus:border-blue-300">
    </div>
    @if($isAdmin)
        <div>
            <label for="sasaran" class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Sasaran</label>
            <select id="sasaran" name="sasaran" class="w-full bg-white border border-gray-200 rounded-lg text-sm h-10 px-3">
                <option value="">Semua sasaran</option>
                @foreach(\App\Models\Pengumuman::SASARAN as $s)
                    <option value="{{ $s }}" @selected($sasaran === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <button type="submit" class="h-10 bg-white border border-gray-200 text-gray-700 font-bold rounded-lg text-sm hover:bg-gray-50 flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-sm">search</span> Tampilkan
    </button>
</form>

<div class="space-y-4">
    @forelse($pengumuman as $p)
        <article class="bg-white rounded-xl border {{ $p->penting ? 'border-amber-300' : 'border-gray-200' }} shadow-sm p-5 md:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        @if($p->penting)
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wide text-amber-800 bg-amber-100 px-2 py-0.5 rounded">
                                <span class="material-symbols-outlined text-[14px]">priority_high</span> Penting
                            </span>
                        @endif
                        <span class="text-[11px] font-bold uppercase tracking-wide text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ $p->sasaran }}</span>
                        <span class="text-xs text-gray-400">{{ $p->created_at->translatedFormat('d M Y, H:i') }} · {{ $p->pembuat?->nama_lengkap ?? 'Sekolah' }}</span>
                    </div>
                    <h3 class="font-bold text-lg text-gray-900 break-words">{{ $p->judul }}</h3>
                </div>
                @if($isAdmin)
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('pengumuman.edit', $p) }}" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Ubah">
                            <span class="material-symbols-outlined text-lg">edit</span>
                        </a>
                        <form method="POST" action="{{ route('pengumuman.destroy', $p) }}" onsubmit="return confirm('Hapus pengumuman ini? Pengumuman juga hilang dari aplikasi wali.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Hapus">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
            <p class="text-sm text-gray-700 mt-3 whitespace-pre-line break-words">{{ $p->isi }}</p>
        </article>
    @empty
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
            <span class="material-symbols-outlined text-5xl text-gray-300">campaign</span>
            <p class="font-medium text-gray-600 mt-2">Belum ada pengumuman</p>
            @if($isAdmin)
                <p class="text-sm text-gray-400 mt-1">Buat pengumuman pertama untuk guru dan wali murid.</p>
            @endif
        </div>
    @endforelse
</div>

{{ $pengumuman->links() }}
@endsection
