@extends('layouts.app')
@php $baru = ! $tugas->exists; @endphp
@section('title', $baru ? 'Buat Tugas' : 'Ubah Tugas')
@section('page-title', 'Tugas')

@section('content')
<div>
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
        <a href="{{ route('kelas.index') }}" class="hover:text-blue-600">Kelas &amp; Mapel</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <a href="{{ route('tugas.ruang', [$kelas, $mapel]) }}" class="hover:text-blue-600">{{ $kelas->nama_kelas }} · {{ $mapel->nama_pelajaran }}</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-blue-600 font-medium">{{ $baru ? 'Buat' : 'Ubah' }}</span>
    </div>
    <h2 class="font-bold text-2xl text-blue-600">{{ $baru ? 'Buat Tugas' : 'Ubah Tugas' }}</h2>
    <p class="text-gray-500 text-sm mt-1">{{ $mapel->nama_pelajaran }} · {{ $kelas->nama_kelas }}</p>
</div>

    <form method="POST" action="{{ $baru ? route('tugas.store', [$kelas, $mapel]) : route('tugas.update', $tugas) }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 md:p-6 space-y-5 max-w-3xl">
        @csrf
        @unless($baru) @method('PUT') @endunless
        <div>
            <label for="judul" class="block text-sm font-bold text-gray-700 mb-1.5">Judul</label>
            <input id="judul" name="judul" type="text" required maxlength="150" value="{{ old('judul', $tugas->judul) }}" class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3">
        </div>
        <div>
            <label for="deskripsi" class="block text-sm font-bold text-gray-700 mb-1.5">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="5" maxlength="3000" class="w-full border border-gray-200 rounded-lg text-sm p-3">{{ old('deskripsi', $tugas->deskripsi) }}</textarea>
        </div>
        <div class="sm:w-60">
            <label for="tenggat" class="block text-sm font-bold text-gray-700 mb-1.5">Tenggat</label>
            <input id="tenggat" name="tenggat" type="date" value="{{ old('tenggat', $tugas->tenggat?->format('Y-m-d')) }}" class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3">
        </div>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <a href="{{ route('tugas.ruang', [$kelas, $mapel]) }}" class="px-5 py-2.5 text-center rounded-lg border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50">Batal</a>
            <button type="submit" class="btn btn-primary px-6 py-2.5 rounded-lg text-sm font-bold">{{ $baru ? 'Bagikan Tugas' : 'Simpan' }}</button>
        </div>
    </form>
@endsection
