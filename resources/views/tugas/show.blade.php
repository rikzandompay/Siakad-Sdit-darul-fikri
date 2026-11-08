@extends('layouts.app')
@section('title', $tugas->judul)
@section('page-title', 'Tugas')

@section('content')
<div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('kelas.index') }}" class="hover:text-blue-600">Kelas &amp; Mapel</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <a href="{{ route('tugas.ruang', [$tugas->kelas_id, $tugas->pelajaran_id]) }}" class="hover:text-blue-600">{{ $tugas->kelas?->nama_kelas }} · {{ $tugas->mataPelajaran?->nama_pelajaran }}</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-blue-600 font-medium">Tugas</span>
        </div>
        <h2 class="font-bold text-2xl text-blue-600 break-words">{{ $tugas->judul }}</h2>
        <p class="text-gray-500 text-sm mt-1">{{ $tugas->kelas?->nama_kelas }} · {{ $tugas->mataPelajaran?->nama_pelajaran }} · {{ $tugas->tenggat ? 'Tenggat '.$tugas->tenggat->translatedFormat('d F Y') : 'Tanpa tenggat' }}</p>
        @if($tugas->deskripsi)<p class="text-sm text-gray-700 mt-3 whitespace-pre-line break-words max-w-3xl">{{ $tugas->deskripsi }}</p>@endif
    </div>
    <div class="flex gap-2 shrink-0">
        <a href="{{ route('tugas.edit', $tugas) }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50"><span class="material-symbols-outlined text-sm">edit</span> Ubah</a>
        <form method="POST" action="{{ route('tugas.destroy', $tugas) }}" onsubmit="return confirm('Hapus tugas ini beserta semua nilainya?')">
            @csrf @method('DELETE')
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-white border border-red-200 rounded-lg text-sm font-bold text-red-600 hover:bg-red-50"><span class="material-symbols-outlined text-sm">delete</span> Hapus</button>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('tugas.nilai', $tugas) }}" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    @csrf
    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-gray-900">Nilai siswa</h3>
        <span class="text-sm text-gray-500">{{ $siswaList->count() }} siswa · skala 0–100</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase tracking-wider font-bold">
                <tr><th class="px-4 md:px-6 py-3 border-b w-12">No</th><th class="px-4 md:px-6 py-3 border-b">Nama</th><th class="px-4 md:px-6 py-3 border-b w-32">Nilai</th><th class="px-4 md:px-6 py-3 border-b">Catatan</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($siswaList as $i => $s)
                    @php $n = $nilai->get($s->id); @endphp
                    <tr>
                        <td class="px-4 md:px-6 py-2 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 md:px-6 py-2 font-medium text-gray-900">{{ $s->nama_siswa }}<input type="hidden" name="nilai[{{ $i }}][siswa_id]" value="{{ $s->id }}"></td>
                        <td class="px-4 md:px-6 py-2">
                            <label for="n-{{ $s->id }}" class="sr-only">Nilai {{ $s->nama_siswa }}</label>
                            <input id="n-{{ $s->id }}" name="nilai[{{ $i }}][nilai]" type="number" min="0" max="100" step="0.01" inputmode="decimal" value="{{ old("nilai.$i.nilai", $n?->nilai) }}" class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
                        </td>
                        <td class="px-4 md:px-6 py-2">
                            <label for="c-{{ $s->id }}" class="sr-only">Catatan {{ $s->nama_siswa }}</label>
                            <input id="c-{{ $s->id }}" name="nilai[{{ $i }}][catatan]" type="text" maxlength="255" value="{{ old("nilai.$i.catatan", $n?->catatan) }}" class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-10 text-center text-gray-400">Tidak ada siswa aktif di kelas ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($siswaList->isNotEmpty())
        <div class="px-4 md:px-6 py-4 border-t border-gray-100 flex justify-end">
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 shadow-sm flex items-center gap-2"><span class="material-symbols-outlined text-sm">save</span> Simpan Nilai</button>
        </div>
    @endif
</form>
@endsection
