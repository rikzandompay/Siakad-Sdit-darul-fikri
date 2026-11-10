@extends('layouts.app')
@section('title', 'Pengaturan Sekolah')
@section('page-title', 'Pengaturan')

@section('content')
<div>
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
        <a href="{{ route('pengaturan.index') }}" class="hover:text-blue-600">Pengaturan</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-blue-600 font-medium">Lokasi, Jam Masuk &amp; Hari Libur</span>
    </div>
    <h2 class="font-bold text-2xl text-blue-600">Lokasi, Jam Masuk &amp; Hari Libur</h2>
    <p class="text-gray-500 text-sm mt-1">Dipakai aplikasi MyDarfik untuk memeriksa lokasi saat guru absen, dan menentukan hari yang tidak dihitung Tidak Hadir otomatis.</p>
</div>

<form method="POST" action="{{ route('pengaturan-sekolah.update') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 md:p-6 space-y-5 max-w-3xl">
    @csrf @method('PUT')
    <h3 class="font-bold text-gray-900 flex items-center gap-2"><span class="material-symbols-outlined text-blue-600">location_on</span> Area absen guru</h3>

    @if($latitude === null)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg px-4 py-3">
            Lokasi sekolah belum diatur, jadi aplikasi belum bisa memeriksa area absen guru.
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="latitude" class="block text-sm font-bold text-gray-700 mb-1.5">Latitude</label>
            <input id="latitude" name="latitude" type="number" step="any" inputmode="decimal" value="{{ old('latitude', $latitude) }}" placeholder="-0.0263"
                class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3">
        </div>
        <div>
            <label for="longitude" class="block text-sm font-bold text-gray-700 mb-1.5">Longitude</label>
            <input id="longitude" name="longitude" type="number" step="any" inputmode="decimal" value="{{ old('longitude', $longitude) }}" placeholder="109.3425"
                class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3">
        </div>
    </div>
    <p class="text-xs text-gray-500 -mt-2">Cara mudah: buka Google Maps, tekan lama di titik gerbang sekolah, lalu salin dua angka koordinatnya.
        @if($latitude !== null)
            <a href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}" target="_blank" rel="noopener" class="font-bold text-blue-600 hover:underline">Lihat titik tersimpan di peta</a>.
        @endif
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="radius_meter" class="block text-sm font-bold text-gray-700 mb-1.5">Radius area (meter)</label>
            <input id="radius_meter" name="radius_meter" type="number" min="20" max="2000" required value="{{ old('radius_meter', $radius) }}"
                class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3">
            <p class="text-xs text-gray-500 mt-1">Guru harus berada dalam jarak ini dari titik sekolah. Beri ruang untuk akurasi GPS (±10–30 m).</p>
        </div>
        <div>
            <label for="jam_masuk" class="block text-sm font-bold text-gray-700 mb-1.5">Jam masuk guru</label>
            <input id="jam_masuk" name="jam_masuk" type="time" required value="{{ old('jam_masuk', $jamMasuk) }}"
                class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3">
            <p class="text-xs text-gray-500 mt-1">Absen masuk setelah jam ini ditandai Terlambat.</p>
        </div>
        <div>
            <label for="batas_masuk" class="block text-sm font-bold text-gray-700 mb-1.5">Batas akhir absen masuk</label>
            <input id="batas_masuk" name="batas_masuk" type="time" value="{{ old('batas_masuk', $batasMasuk) }}"
                class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3">
            <p class="text-xs text-gray-500 mt-1">Absen masuk ditutup setelah jam ini (kosongkan jika tidak dibatasi).</p>
        </div>
        <div>
            <label for="jam_pulang" class="block text-sm font-bold text-gray-700 mb-1.5">Jam pulang guru</label>
            <input id="jam_pulang" name="jam_pulang" type="time" required value="{{ old('jam_pulang', $jamPulang) }}"
                class="w-full border border-gray-200 rounded-lg text-sm h-11 px-3">
            <p class="text-xs text-gray-500 mt-1">Jam keluar guru. Harus setelah jam masuk. Tampil di aplikasi sebagai jam pulang.</p>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary px-6 py-2.5 rounded-lg text-sm font-bold">Simpan</button>
    </div>
</form>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 md:p-6 max-w-3xl space-y-5">
    <h3 class="font-bold text-gray-900 flex items-center gap-2"><span class="material-symbols-outlined text-blue-600">event_busy</span> Hari libur</h3>
    <p class="text-sm text-gray-500 -mt-3">Pada tanggal ini siswa dan guru tidak otomatis dicatat Tidak Hadir, dan izin siswa tidak ditulis ke presensi. Senin–Sabtu saja yang dianggap hari sekolah.</p>

    <form method="POST" action="{{ route('pengaturan-sekolah.libur.store') }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 items-end">
        @csrf
        <div class="sm:col-span-2">
            <label for="tanggal" class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Mulai</label>
            <input id="tanggal" name="tanggal" type="date" required class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
        </div>
        <div class="sm:col-span-2">
            <label for="tanggal_akhir" class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Sampai (opsional)</label>
            <input id="tanggal_akhir" name="tanggal_akhir" type="date" class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
        </div>
        <div class="sm:col-span-2">
            <label for="keterangan" class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Keterangan</label>
            <input id="keterangan" name="keterangan" type="text" required maxlength="150" placeholder="Libur semester" class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
        </div>
        <button type="submit" class="btn btn-secondary sm:col-span-6 sm:w-fit h-10 px-5 rounded-lg text-sm font-bold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> Tambah hari libur
        </button>
    </form>

    <ul class="divide-y divide-gray-100 border border-gray-100 rounded-lg">
        @forelse($libur as $l)
            <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900">{{ $l->tanggal->translatedFormat('l, d F Y') }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $l->keterangan }}</p>
                </div>
                <form method="POST" action="{{ route('pengaturan-sekolah.libur.destroy', $l) }}" onsubmit="return confirm('Hapus hari libur ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" aria-label="Hapus {{ $l->keterangan }}">
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </form>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-gray-400">Belum ada hari libur.</li>
        @endforelse
    </ul>
</div>
@endsection
