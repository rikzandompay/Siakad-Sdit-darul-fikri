@extends('layouts.app')
@section('title', 'Konseling - '.$percakapan->topik)
@section('page-title', 'Konseling')

@section('content')
@php
    $siswa = $percakapan->siswa;
    $pengampu = $siswa?->kelas?->pengampuKonseling;
    $selesai = $percakapan->status === 'Selesai';
@endphp

<div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
    <div class="min-w-0">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('konseling.index') }}" class="hover:text-blue-600">Konseling</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-blue-600 font-medium truncate">{{ $siswa?->nama_siswa }}</span>
        </div>
        <h2 class="font-bold text-2xl text-blue-600 break-words">{{ $percakapan->topik }}</h2>
        <p class="text-gray-500 text-sm mt-1">
            Wali dari {{ $siswa?->nama_siswa }} ({{ $siswa?->kelas?->nama_kelas ?? '-' }})
            @if($siswa?->nama_wali) · {{ $siswa->nama_wali }} @endif
            @if($siswa?->no_hp_wali) · {{ $siswa->no_hp_wali }} @endif
        </p>
        <p class="text-gray-500 text-sm">Pengampu konseling: <span class="font-semibold text-gray-700">{{ $pengampu?->nama_lengkap ?? 'belum ditetapkan' }}</span></p>
    </div>
    <form method="POST" action="{{ route('konseling.status', $percakapan) }}">
        @csrf @method('PUT')
        <input type="hidden" name="status" value="{{ $selesai ? 'Aktif' : 'Selesai' }}">
        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold border {{ $selesai ? 'border-blue-200 text-blue-700 bg-white hover:bg-blue-50' : 'border-gray-200 text-gray-700 bg-white hover:bg-gray-50' }}">
            <span class="material-symbols-outlined text-sm">{{ $selesai ? 'restart_alt' : 'task_alt' }}</span>
            {{ $selesai ? 'Buka Kembali' : 'Tandai Selesai' }}
        </button>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div id="thread" class="p-4 md:p-6 space-y-4 max-h-[60vh] overflow-y-auto bg-gray-50/60">
        @foreach($percakapan->pesan as $pesan)
            @php $dariGuru = $pesan->pengirim === 'guru'; @endphp
            @if($loop->first || ! $pesan->created_at->isSameDay($percakapan->pesan[$loop->index - 1]->created_at))
                <div class="text-center">
                    <span class="text-[11px] font-semibold text-gray-500 bg-white border border-gray-200 rounded-full px-3 py-1">{{ $pesan->created_at->translatedFormat('l, d F Y') }}</span>
                </div>
            @endif
            <div class="flex {{ $dariGuru ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[85%] md:max-w-[70%]">
                    <p class="text-[11px] text-gray-500 mb-1 {{ $dariGuru ? 'text-right' : '' }}">
                        {{ $dariGuru ? ($pesan->guru?->nama_lengkap ?? 'Guru') : 'Wali '.$siswa?->nama_siswa }}
                    </p>
                    <div class="rounded-2xl px-4 py-2.5 text-sm break-words {{ $dariGuru ? 'bg-blue-600 text-white rounded-br-md' : 'bg-white border border-gray-200 text-gray-800 rounded-bl-md' }}">
                        @if($pesan->foto)
                            <a href="{{ route('konseling.pesan.foto', [$percakapan, $pesan]) }}" target="_blank" rel="noopener">
                                <img src="{{ route('konseling.pesan.foto', [$percakapan, $pesan]) }}" alt="Foto lampiran" loading="lazy" class="rounded-lg max-h-64 mb-1">
                            </a>
                        @endif
                        @if($pesan->isi)<span class="whitespace-pre-line">{{ $pesan->isi }}</span>@endif
                    </div>
                    @if($pesan->milikGuru(Auth::user()))
                        <details class="mt-1 text-right">
                            <summary class="text-[11px] text-gray-500 cursor-pointer hover:text-blue-600 list-none">Ubah atau hapus</summary>
                            <div class="mt-2 bg-white border border-gray-200 rounded-lg p-3 text-left space-y-2">
                                <form method="POST" action="{{ route('konseling.pesan.update', [$percakapan, $pesan]) }}" class="space-y-2">
                                    @csrf @method('PUT')
                                    <label for="isi-{{ $pesan->id }}" class="sr-only">Isi pesan</label>
                                    <textarea id="isi-{{ $pesan->id }}" name="isi" rows="2" maxlength="2000" class="w-full border border-gray-200 rounded-lg text-sm p-2">{{ $pesan->isi }}</textarea>
                                    <button type="submit" class="btn btn-primary px-3 py-1.5 rounded-lg text-xs font-bold">Simpan perubahan</button>
                                </form>
                                <form method="POST" action="{{ route('konseling.pesan.destroy', [$percakapan, $pesan]) }}" onsubmit="return confirm('Hapus pesan ini? Wali murid tidak akan melihatnya lagi.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-200 text-red-600 text-xs font-bold">Hapus pesan</button>
                                </form>
                            </div>
                        </details>
                    @endif
                    <p class="text-[11px] text-gray-400 mt-1 {{ $dariGuru ? 'text-right' : '' }}">{{ $pesan->created_at->format('H:i') }}{{ $pesan->diedit_pada ? ' · diedit' : '' }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('konseling.reply', $percakapan) }}" enctype="multipart/form-data" class="border-t border-gray-100 p-3 md:p-4 flex items-end gap-2 flex-wrap">
        @csrf
        <label for="isi" class="sr-only">Balas pesan</label>
        <textarea id="isi" name="isi" rows="2" maxlength="2000" placeholder="{{ $selesai ? 'Membalas akan membuka kembali percakapan ini' : 'Tulis balasan untuk wali murid' }}"
            class="flex-1 border border-gray-200 rounded-xl text-sm p-3 resize-y focus:ring-blue-500 focus:border-blue-300">{{ old('isi') }}</textarea>
        <label class="h-11 px-3 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold flex items-center gap-1.5 cursor-pointer hover:bg-gray-50">
            <span class="material-symbols-outlined text-sm">image</span>
            <span class="max-w-[8rem] truncate" id="nama-foto">Foto</span>
            <input type="file" name="foto" accept="image/png,image/jpeg,image/webp" class="sr-only" onchange="document.getElementById('nama-foto').textContent = this.files[0] ? this.files[0].name : 'Foto'">
        </label>
        <button type="submit" class="btn btn-primary h-11 px-5 rounded-xl text-sm font-bold flex items-center gap-1.5">
            <span class="material-symbols-outlined text-sm">send</span>
            Kirim
        </button>
    @error('foto') <p class="w-full text-xs text-red-600">{{ $message }}</p> @enderror
    @error('isi') <p class="w-full text-xs text-red-600">{{ $message }}</p> @enderror
    </form>
</div>
@endsection

@push('scripts')
<script>
    const thread = document.getElementById('thread');
    if (thread) thread.scrollTop = thread.scrollHeight;
</script>
@endpush
