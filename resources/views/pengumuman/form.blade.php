@extends('layouts.app')
@php $baru = ! $pengumuman->exists; @endphp
@section('title', $baru ? 'Buat Pengumuman' : 'Ubah Pengumuman')
@section('page-title', 'Pengumuman')

@section('content')
    <div>
        <x-breadcrumb :items="[['label' => 'Pengumuman', 'url' => route('pengumuman.index')], ['label' => '{{ $baru ? 'Buat' : 'Ubah' }}']]" :class="'mb-1'" />
        <h2 class="font-bold text-2xl text-blue-600">{{ $baru ? 'Buat Pengumuman' : 'Ubah Pengumuman' }}</h2>
        <p class="text-gray-500 text-sm mt-1">
            Pengumuman dicetak sebagai surat resmi sekolah dengan kop dan tanda tangan kepala sekolah.
            @if($baru) Nomor surat dibuat otomatis saat diterbitkan. @else Nomor surat: <span class="font-semibold text-gray-700">{{ $pengumuman->nomor_surat ?? 'belum ada (dibuat saat disimpan)' }}</span> @endif
        </p>
    </div>

    <form method="POST" action="{{ $baru ? route('pengumuman.store') : route('pengumuman.update', $pengumuman) }}"
        class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 md:p-6 space-y-5 max-w-3xl">
        @csrf
        @unless ($baru)
            @method('PUT')
        @endunless

        <div>
            <label for="judul" class="block text-sm font-bold text-gray-700 mb-1.5">Judul surat / kegiatan</label>
            <input id="judul" name="judul" type="text" required maxlength="150"
                value="{{ old('judul', $pengumuman->judul) }}" placeholder="Contoh: Libur Semester, Seminar Parenting"
                class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3 focus:ring-blue-500 focus:border-blue-300">
            <p class="text-xs text-gray-500 mt-1">Tampil sebagai "TENTANG …" di tengah surat.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="tanggal_surat" class="block text-sm font-bold text-gray-700 mb-1.5">Tanggal surat</label>
                <input id="tanggal_surat" name="tanggal_surat" type="date"
                    value="{{ old('tanggal_surat', optional($pengumuman->tanggal_surat)->toDateString() ?? now()->toDateString()) }}"
                    class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3 focus:ring-blue-500 focus:border-blue-300">
            </div>
            <div>
                <label for="tempat" class="block text-sm font-bold text-gray-700 mb-1.5">Tempat</label>
                <input id="tempat" name="tempat" type="text" maxlength="100"
                    value="{{ old('tempat', $pengumuman->tempat ?? \App\Models\PengaturanSekolah::ambil('kota', 'Kab. Kubu Raya')) }}"
                    class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3 focus:ring-blue-500 focus:border-blue-300">
                <p class="text-xs text-gray-500 mt-1">Tampil di kiri atas tanda tangan.</p>
            </div>
        </div>

        <div>
            <label for="isi" class="block text-sm font-bold text-gray-700 mb-1.5">Isi pengumuman</label>
            <textarea id="isi" name="isi" rows="8" required maxlength="5000" placeholder="Tulis isi surat pengumuman…"
                class="w-full border border-gray-200 rounded-lg text-sm p-3 focus:ring-blue-500 focus:border-blue-300">{{ old('isi', $pengumuman->isi) }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Baris baru akan dipertahankan di PDF.</p>
        </div>

        <fieldset class="border border-gray-200 rounded-lg p-4">
            <legend class="px-1 text-sm font-bold text-gray-700">Jadwal kegiatan <span class="font-normal text-gray-400">(opsional)</span></legend>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="jadwal_hari_tanggal" class="block text-xs font-semibold text-gray-600 mb-1">Hari/Tanggal</label>
                    <input id="jadwal_hari_tanggal" name="jadwal_hari_tanggal" type="text" maxlength="150"
                        value="{{ old('jadwal_hari_tanggal', $pengumuman->jadwal_hari_tanggal) }}" placeholder="Senin, 1 Desember 2026"
                        class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
                </div>
                <div>
                    <label for="jadwal_waktu" class="block text-xs font-semibold text-gray-600 mb-1">Waktu</label>
                    <input id="jadwal_waktu" name="jadwal_waktu" type="text" maxlength="100"
                        value="{{ old('jadwal_waktu', $pengumuman->jadwal_waktu) }}" placeholder="08.00 s.d 11.00 WIB"
                        class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
                </div>
                <div>
                    <label for="jadwal_tempat" class="block text-xs font-semibold text-gray-600 mb-1">Tempat</label>
                    <input id="jadwal_tempat" name="jadwal_tempat" type="text" maxlength="150"
                        value="{{ old('jadwal_tempat', $pengumuman->jadwal_tempat) }}" placeholder="Aula SDIT Darul Fikri"
                        class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend class="block text-sm font-bold text-gray-700 mb-2">Ditujukan untuk</legend>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Models\Pengumuman::SASARAN as $s)
                    <label class="cursor-pointer">
                        <input type="radio" name="sasaran" value="{{ $s }}" class="peer sr-only"
                            @checked(old('sasaran', $pengumuman->sasaran) === $s)>
                        <span
                            class="block px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50
                        peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400">{{ $s }}</span>
                    </label>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-2">"Semua" tampil untuk guru di web dan wali murid di aplikasi. "Wali Murid"
                hanya tampil di aplikasi.</p>
        </fieldset>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="hidden" name="penting" value="0">
            <input type="checkbox" name="penting" value="1"
                class="mt-0.5 rounded border-gray-300 text-amber-500 focus:ring-amber-400" @checked(old('penting', $pengumuman->penting))>
            <span class="text-sm">
                <span class="font-bold text-gray-700">Tandai penting</span>
                <span class="block text-gray-500">Ditampilkan paling atas dengan penanda "Penting".</span>
            </span>
        </label>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
            <a href="{{ route('pengumuman.index') }}"
                class="btn btn-tertiary px-5 py-2.5 text-center rounded-lg text-sm">Batal</a>
            <button type="submit" class="btn btn-primary px-6 py-2.5 rounded-lg text-sm font-bold">
                {{ $baru ? 'Terbitkan' : 'Simpan Perubahan' }}
            </button>
        </div>
    </form>
@endsection
