@extends('layouts.app')
@php $baru = ! $pengumuman->exists; @endphp
@section('title', $baru ? 'Buat Pengumuman' : 'Ubah Pengumuman')
@section('page-title', 'Pengumuman')

@section('content')
<div>
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
        <a href="{{ route('pengumuman.index') }}" class="hover:text-blue-600">Pengumuman</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-blue-600 font-medium">{{ $baru ? 'Buat' : 'Ubah' }}</span>
    </div>
    <h2 class="font-bold text-2xl text-blue-600">{{ $baru ? 'Buat Pengumuman' : 'Ubah Pengumuman' }}</h2>
</div>

<form method="POST" action="{{ $baru ? route('pengumuman.store') : route('pengumuman.update', $pengumuman) }}"
    class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 md:p-6 space-y-5 max-w-3xl">
    @csrf
    @unless($baru) @method('PUT') @endunless

    <div>
        <label for="judul" class="block text-sm font-bold text-gray-700 mb-1.5">Judul</label>
        <input id="judul" name="judul" type="text" required maxlength="150" value="{{ old('judul', $pengumuman->judul) }}"
            class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3 focus:ring-blue-500 focus:border-blue-300">
    </div>

    <div>
        <label for="isi" class="block text-sm font-bold text-gray-700 mb-1.5">Isi pengumuman</label>
        <textarea id="isi" name="isi" rows="8" required maxlength="5000"
            class="w-full border border-gray-200 rounded-lg text-sm p-3 focus:ring-blue-500 focus:border-blue-300">{{ old('isi', $pengumuman->isi) }}</textarea>
    </div>

    <fieldset>
        <legend class="block text-sm font-bold text-gray-700 mb-2">Ditujukan untuk</legend>
        <div class="flex flex-wrap gap-2">
            @foreach(\App\Models\Pengumuman::SASARAN as $s)
                <label class="cursor-pointer">
                    <input type="radio" name="sasaran" value="{{ $s }}" class="peer sr-only" @checked(old('sasaran', $pengumuman->sasaran) === $s)>
                    <span class="block px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50
                        peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400">{{ $s }}</span>
                </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-2">"Semua" tampil untuk guru di web dan wali murid di aplikasi. "Wali Murid" hanya tampil di aplikasi.</p>
    </fieldset>

    <label class="flex items-start gap-3 cursor-pointer">
        <input type="hidden" name="penting" value="0">
        <input type="checkbox" name="penting" value="1" class="mt-0.5 rounded border-gray-300 text-amber-500 focus:ring-amber-400"
            @checked(old('penting', $pengumuman->penting))>
        <span class="text-sm">
            <span class="font-bold text-gray-700">Tandai penting</span>
            <span class="block text-gray-500">Ditampilkan paling atas dengan penanda "Penting".</span>
        </span>
    </label>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
        <a href="{{ route('pengumuman.index') }}" class="px-5 py-2.5 text-center rounded-lg border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50">Batal</a>
        <button type="submit" class="btn btn-primary px-6 py-2.5 rounded-lg text-sm font-bold">
            {{ $baru ? 'Terbitkan' : 'Simpan Perubahan' }}
        </button>
    </div>
</form>
@endsection
