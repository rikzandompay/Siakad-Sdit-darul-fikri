@extends('layouts.app')
@section('title', 'Mulai Percakapan Konseling')
@section('page-title', 'Konseling')

@section('content')
<div>
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
        <a href="{{ route('konseling.index') }}" class="hover:text-blue-600">Konseling</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-blue-600 font-medium">Mulai percakapan</span>
    </div>
    <h2 class="font-bold text-2xl text-blue-600">Mulai Percakapan</h2>
    <p class="text-gray-500 text-sm mt-1">Pesan dikirim ke wali murid lewat aplikasi MyDarfik.</p>
</div>

@if($siswaList->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center text-gray-500">
        Tidak ada siswa aktif di kelas yang Anda ampu konselingnya.
    </div>
@else
    <form method="POST" action="{{ route('konseling.store') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 md:p-6 space-y-5 max-w-3xl">
        @csrf
        <div>
            <label for="siswa_id" class="block text-sm font-bold text-gray-700 mb-1.5">Wali dari siswa</label>
            <select id="siswa_id" name="siswa_id" required class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3">
                <option value="">Pilih siswa</option>
                @foreach($siswaList->groupBy(fn ($s) => $s->kelas?->nama_kelas ?? 'Tanpa kelas') as $namaKelas => $list)
                    <optgroup label="{{ $namaKelas }}">
                        @foreach($list as $s)
                            <option value="{{ $s->id }}" @selected(old('siswa_id') == $s->id)>{{ $s->nama_siswa }}{{ $s->nama_wali ? ' — wali: '.$s->nama_wali : '' }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div>
            <label for="topik" class="block text-sm font-bold text-gray-700 mb-1.5">Topik</label>
            <input id="topik" name="topik" type="text" required maxlength="150" value="{{ old('topik') }}" placeholder="Contoh: Perkembangan belajar Matematika"
                class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3">
        </div>
        <div>
            <label for="isi" class="block text-sm font-bold text-gray-700 mb-1.5">Pesan pertama</label>
            <textarea id="isi" name="isi" rows="5" required maxlength="2000" class="w-full border border-gray-200 rounded-lg text-sm p-3">{{ old('isi') }}</textarea>
        </div>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <a href="{{ route('konseling.index') }}" class="px-5 py-2.5 text-center rounded-lg border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50">Batal</a>
            <button type="submit" class="btn btn-primary px-6 py-2.5 rounded-lg text-sm font-bold">Kirim</button>
        </div>
    </form>
@endif
@endsection
