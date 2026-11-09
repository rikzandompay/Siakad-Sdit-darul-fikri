@extends('layouts.app')
@section('title', 'Presensi ' . $kelas->nama_kelas)
@section('page-title', 'Presensi Siswa')

@section('content')
<!-- Header with Export Buttons -->
<div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('presensi.index') }}" class="hover:text-blue-600">Presensi</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-blue-600 font-medium">{{ $kelas->nama_kelas }}</span>
        </div>
        <h2 class="font-bold text-2xl text-blue-600">Presensi Siswa</h2>
        <p class="text-gray-500 text-sm">Kelola kehadiran harian siswa {{ $kelas->nama_kelas }}</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <x-export-button type="pdf" :href="route('presensi.export.pdf', ['kelas' => $kelas->id, 'jadwal_id' => $jadwalId, 'tanggal' => $tanggal])" />
        <x-export-button type="csv" :href="route('presensi.export.csv', ['kelas' => $kelas->id, 'jadwal_id' => $jadwalId, 'tanggal' => $tanggal])" />
        @if($jadwalId)
        <button type="submit" form="form-presensi"
           class="btn btn-secondary flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold">
            <span class="material-symbols-outlined text-sm">save</span>
            Simpan Presensi
        </button>
        @endif
    </div>
</div>

<!-- Summary Cards (matching screenshot) -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="p-5 rounded-xl border border-blue-200/80 shadow-sm hover:shadow-md transition-all duration-200"
        style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-green-100/80 flex items-center justify-center">
                <span class="material-symbols-outlined text-green-700" style="font-variation-settings: 'FILL' 1;">check_circle</span>
            </div>
            <span class="text-sm font-bold text-green-800">Hadir</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $hadirCount }}</p>
        <p class="text-xs text-gray-700 mt-1 font-medium">Siswa telah terdata hadir</p>
    </div>
    <div class="p-5 rounded-xl border border-blue-200/80 shadow-sm hover:shadow-md transition-all duration-200"
        style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100/80 flex items-center justify-center">
                <span class="material-symbols-outlined text-purple-700" style="font-variation-settings: 'FILL' 1;">medical_services</span>
            </div>
            <span class="text-sm font-bold text-purple-800">Sakit</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $sakitCount }}</p>
        <p class="text-xs text-gray-700 mt-1 font-medium">Dalam pantauan kesehatan</p>
    </div>
    <div class="p-5 rounded-xl border border-blue-200/80 shadow-sm hover:shadow-md transition-all duration-200"
        style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100/80 flex items-center justify-center">
                <span class="material-symbols-outlined text-amber-700" style="font-variation-settings: 'FILL' 1;">mail</span>
            </div>
            <span class="text-sm font-bold text-amber-800">Izin</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $izinCount }}</p>
        <p class="text-xs text-gray-700 mt-1 font-medium">Disetujui pihak sekolah</p>
    </div>
    <div class="p-5 rounded-xl border border-blue-200/80 shadow-sm hover:shadow-md transition-all duration-200"
        style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-lg bg-red-100/80 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-700" style="font-variation-settings: 'FILL' 1;">cancel</span>
            </div>
            <span class="text-sm font-bold text-red-800">Alpa</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $alpaCount }}</p>
        <p class="text-xs text-gray-700 mt-1 font-medium">Tanpa keterangan valid</p>
    </div>
</div>

<!-- Filter Bar: Pilih Kelas, Tanggal, Mata Pelajaran (auto-filter by day) -->
<div class="p-4 rounded-xl border border-blue-200/80 shadow-sm"
    style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
    <form method="GET" action="{{ route('presensi.show', $kelas->id) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end" id="filterForm">
        <div>
            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1.5">Pilih Kelas</label>
            <select onchange="window.location.href='/presensi/'+this.value+'?tanggal={{ $tanggal }}'" class="w-full bg-white/90 border border-blue-200 rounded-lg text-sm h-10 px-3 focus:ring-blue-500 focus:border-blue-400">
                @foreach($kelasList as $k)
                    <option value="{{ $k->id }}" {{ $kelas->id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1.5">Tanggal</label>
            <input name="tanggal" id="inputTanggal" type="date" value="{{ $tanggal }}" onchange="filterJadwalByDay(this.value)"
                class="w-full bg-white/90 border border-blue-200 rounded-lg text-sm h-10 px-3 focus:ring-blue-500 focus:border-blue-400" />
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-700 uppercase mb-1.5">Mata Pelajaran</label>
            <select name="jadwal_id" id="selectJadwal" class="w-full bg-white/90 border border-blue-200 rounded-lg text-sm h-10 px-3 focus:ring-blue-500 focus:border-blue-400">
                @foreach($jadwalList as $j)
                    <option value="{{ $j->id }}" data-hari="{{ $j->hari }}" {{ $jadwalId == $j->id ? 'selected' : '' }}>
                        {{ $j->mataPelajaran->nama_pelajaran }} ({{ $j->hari }})
                    </option>
                @endforeach
            </select>
            <p id="noJadwalMsg" class="text-xs text-amber-700 mt-1 hidden font-semibold">⚠ Tidak ada jadwal pada hari ini</p>
        </div>
        <div>
            <button type="submit" class="btn btn-secondary w-full h-10 font-bold px-6 rounded-lg text-sm flex items-center justify-center gap-2 shadow-xs">
                <span class="material-symbols-outlined text-sm">filter_list</span>
                Filter Data
            </button>
        </div>
    </form>
</div>

@if($jadwalId && $siswa->count() > 0)
<!-- Attendance Form -->
<form id="form-presensi" method="POST" action="{{ route('presensi.store') }}">
    @csrf
    <input type="hidden" name="jadwal_id" value="{{ $jadwalId }}">
    <input type="hidden" name="tanggal" value="{{ $tanggal }}">

    <div class="rounded-xl border border-blue-200/80 shadow-sm overflow-hidden"
        style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
        <div class="px-6 py-4 border-b border-blue-100/80 flex justify-between items-center bg-white/70">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-blue-700">list_alt</span>
                <h3 class="font-bold text-gray-900">Daftar Kehadiran Siswa</h3>
            </div>
            <span class="text-sm text-gray-700 font-medium">{{ $siswa->count() }} Siswa</span>
        </div>
        <div class="overflow-x-auto bg-white/90">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-6 py-4 border-b">No</th>
                        <th class="px-6 py-4 border-b">NIS</th>
                        <th class="px-6 py-4 border-b">Nama Lengkap</th>
                        <th class="px-6 py-4 text-center border-b">L/P</th>
                        <th class="px-6 py-4 text-center border-b">Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($siswa as $index => $s)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-400">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $s->nis }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 shrink-0 rounded-full {{ $s->jenis_kelamin == 'L' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center font-bold text-xs">
                                        {{ $s->initials }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $s->nama_siswa }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $s->jenis_kelamin == 'L' ? 'bg-blue-50 text-blue-600' : 'bg-pink-50 text-pink-700' }}">{{ $s->jenis_kelamin }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <input type="hidden" name="presensi[{{ $index }}][siswa_id]" value="{{ $s->id }}">
                                <div class="flex justify-center gap-1">
                                    @php $currentStatus = $existingPresensi[$s->id] ?? 'H'; @endphp
                                    @foreach(['H' => 'HADIR', 'S' => 'SAKIT', 'I' => 'IZIN', 'A' => 'ALPA'] as $code => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="presensi[{{ $index }}][status]" value="{{ $code }}" class="hidden peer"
                                                {{ $currentStatus == $code ? 'checked' : '' }}>
                                            <span class="block px-4 py-2 rounded-lg text-[11px] font-bold transition-all border
                                                peer-checked:border-transparent peer-checked:text-white peer-checked:shadow-md
                                                @if($code == 'H') peer-checked:bg-green-700 border-gray-200 text-gray-600 hover:bg-green-50
                                                @elseif($code == 'S') peer-checked:bg-purple-600 border-gray-200 text-gray-600 hover:bg-purple-50
                                                @elseif($code == 'I') peer-checked:bg-amber-700 border-gray-200 text-gray-600 hover:bg-amber-50
                                                @else peer-checked:bg-red-600 border-gray-200 text-gray-600 hover:bg-red-50
                                                @endif">
                                                {{ $label }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end mt-4">
        <button type="submit" class="btn btn-primary px-8 py-3 font-bold rounded-xl flex items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            Simpan Presensi
        </button>
    </div>
</form>
@elseif($siswa->count() == 0)
<div class="rounded-xl border border-blue-200/80 shadow-sm p-12 text-center text-gray-500"
    style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
    <span class="material-symbols-outlined text-5xl mb-3 text-blue-300">person_off</span>
    <p class="font-medium">Tidak ada siswa aktif di kelas ini</p>
</div>
@else
<div class="rounded-xl border border-blue-200/80 shadow-sm p-12 text-center text-gray-500"
    style="background: linear-gradient(180deg, #ffffff 0%, #aadaf5 100%);">
    <span class="material-symbols-outlined text-5xl mb-3 text-blue-300">calendar_today</span>
    <p class="font-medium text-gray-800">Silakan pilih mata pelajaran terlebih dahulu</p>
    <p class="text-sm mt-1 text-gray-600">Pilih jadwal pelajaran di filter di atas</p>
</div>
@endif
@endsection

@push('scripts')
<script>
    // Map nama hari Indonesia ke index JS (0=Minggu, 1=Senin, ...)
    const hariMap = {
        'Minggu': 0, 'Senin': 1, 'Selasa': 2, 'Rabu': 3,
        'Kamis': 4, 'Jumat': 5, 'Sabtu': 6
    };

    function filterJadwalByDay(dateValue) {
        if (!dateValue) return;

        // Ambil nama hari dari tanggal yang dipilih
        const date = new Date(dateValue + 'T00:00:00');
        const dayIndex = date.getDay(); // 0=Minggu, 1=Senin, dst
        const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const selectedDay = dayNames[dayIndex];

        const select = document.getElementById('selectJadwal');
        const options = select.querySelectorAll('option');
        const msg = document.getElementById('noJadwalMsg');

        let firstMatch = null;
        let hasMatch = false;

        options.forEach(opt => {
            const hariOpt = opt.getAttribute('data-hari');
            if (hariOpt === selectedDay) {
                opt.style.display = '';
                if (!firstMatch) {
                    firstMatch = opt.value;
                }
                hasMatch = true;
            } else {
                opt.style.display = 'none';
            }
        });

        // Auto-pilih option pertama yang cocok
        if (firstMatch) {
            select.value = firstMatch;
        }

        // Tampilkan/sembunyikan pesan peringatan
        if (!hasMatch) {
            msg.classList.remove('hidden');
            select.disabled = false; // tetap bisa submit tapi semua options tersembunyi
        } else {
            msg.classList.add('hidden');
        }
    }

    // Jalankan filter saat halaman load berdasarkan tanggal yang sudah ada
    document.addEventListener('DOMContentLoaded', function() {
        const tanggalInput = document.getElementById('inputTanggal');
        if (tanggalInput && tanggalInput.value) {
            filterJadwalByDay(tanggalInput.value);
        }
    });
</script>
@endpush
