@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    @php
        $now = \App\Support\Sekolah::now();
        $hariIni = \App\Support\Sekolah::hari($now);
        $jamSekarang = $now->format('H:i');
        $sapaan = match (true) {
            $now->hour < 11 => 'Selamat pagi',
            $now->hour < 15 => 'Selamat siang',
            $now->hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };
        $jadwalHariIni = $jadwalSepekan->where('hari', $hariIni)->values();
        $perHari = $jadwalSepekan->groupBy('hari');
        $totalPresensi = $hadirCount + $sakitCount + $izinCount + $alpaCount;
        $status = [
            ['Hadir', $hadirCount, 'bg-green-700', 'text-green-700'],
            ['Sakit', $sakitCount, 'bg-purple-600', 'text-purple-700'],
            ['Izin', $izinCount, 'bg-amber-500', 'text-amber-700'],
            ['Alpa', $alpaCount, 'bg-red-600', 'text-red-700'],
        ];
        $nama = Auth::user()->nama_lengkap ?? Auth::user()->name;
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
        <div>
            <p class="text-sm text-gray-600">{{ $hariIni }}, {{ $now->translatedFormat('j F Y') }}</p>
            <h1 class="text-2xl font-bold text-gray-900">{{ $sapaan }}, {{ $nama }}</h1>
        </div>
        <p class="text-sm text-gray-600">Tahun ajaran <span class="font-semibold text-gray-900">{{ $tahunAktif->nama_semester ?? 'belum diatur' }}</span></p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- Kiri: pekerjaan hari ini --}}
        <div class="min-w-0 space-y-6">
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white" aria-labelledby="judul-hari-ini">
                <div class="flex items-baseline justify-between gap-3 border-b border-gray-200 px-5 py-4 md:px-6">
                    <h2 id="judul-hari-ini" class="text-lg font-bold text-gray-900">Mengajar hari ini</h2>
                    <p class="text-sm text-gray-600">{{ $jadwalHariIni->count() }} sesi</p>
                </div>
                @forelse ($jadwalHariIni as $jadwal)
                    @php
                        $mulai = \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i');
                        $selesai = \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i');
                        $berlangsung = $mulai <= $jamSekarang && $jamSekarang < $selesai;
                    @endphp
                    <div class="flex items-center gap-4 px-5 py-4 md:gap-5 md:px-6 {{ $berlangsung ? 'bg-blue-50' : '' }} {{ ! $loop->last ? 'border-b border-gray-100' : '' }}">
                        <div class="w-14 shrink-0 tabular">
                            <p class="text-sm font-bold text-gray-900">{{ $mulai }}</p>
                            <p class="text-xs text-gray-600">{{ $selesai }}</p>
                        </div>
                        <span class="hidden h-3 w-3 shrink-0 rounded-full sm:block {{ $berlangsung ? 'bg-amber-400 ring-2 ring-blue-800' : 'bg-blue-200' }}" aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">{{ $jadwal->mataPelajaran->nama_pelajaran ?? '-' }}</p>
                            <p class="truncate text-sm text-gray-600">{{ $jadwal->kelas->nama_kelas ?? '-' }}@if ($berlangsung), sedang berlangsung @endif</p>
                        </div>
                        <a href="{{ route('presensi.show', $jadwal->kelas_id) }}?jadwal_id={{ $jadwal->id }}"
                            class="shrink-0 rounded-lg border border-blue-800 px-3.5 py-2 text-sm font-semibold text-blue-800 transition-colors hover:bg-blue-800 hover:text-white">
                            Isi presensi
                        </a>
                    </div>
                @empty
                    <p class="px-6 py-10 text-sm text-gray-600">
                        @if ($jadwalSepekan->isEmpty())
                            Anda belum punya jadwal mengajar.
                        @else
                            Tidak ada jadwal mengajar hari ini. Jadwal berikutnya ada di bagian sepekan di bawah.
                        @endif
                    </p>
                @endforelse
            </section>

            <section class="rounded-xl border border-gray-200 bg-white" aria-labelledby="judul-sepekan">
                <div class="flex items-baseline justify-between gap-3 border-b border-gray-200 px-5 py-4 md:px-6">
                    <h2 id="judul-sepekan" class="text-lg font-bold text-gray-900">Jadwal sepekan</h2>
                    <p class="text-sm text-gray-600">{{ $jadwalSepekan->count() }} sesi</p>
                </div>
                @if ($perHari->isEmpty())
                    <p class="px-6 py-10 text-sm text-gray-600">Tidak ada jadwal mengajar dalam sepekan.</p>
                @else
                    <dl class="divide-y divide-gray-100">
                        @foreach ($perHari as $hari => $daftar)
                            <div class="grid gap-x-6 gap-y-1 px-5 py-3.5 md:grid-cols-[6rem_minmax(0,1fr)] md:px-6">
                                <dt class="text-sm font-semibold {{ $hari === $hariIni ? 'text-blue-800' : 'text-gray-900' }}">{{ $hari }}@if ($hari === $hariIni)<span class="sr-only"> (hari ini)</span>@endif</dt>
                                <dd class="space-y-1">
                                    @foreach ($daftar as $j)
                                        <p class="text-sm text-gray-700">
                                            <span class="tabular font-medium text-gray-900">{{ \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') }}</span>
                                            <span class="mx-1 text-gray-400" aria-hidden="true">·</span>{{ $j->mataPelajaran->nama_pelajaran ?? '-' }}, {{ $j->kelas->nama_kelas ?? '-' }}
                                        </p>
                                    @endforeach
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>
        </div>

        {{-- Kanan: kondisi sekolah --}}
        <div class="min-w-0 space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white p-5 md:p-6" aria-labelledby="judul-kehadiran">
                <h2 id="judul-kehadiran" class="text-lg font-bold text-gray-900">Kehadiran siswa hari ini</h2>
                @if ($totalPresensi === 0)
                    <p class="mt-3 text-sm text-gray-600">Belum ada presensi kelas yang diisi hari ini.</p>
                @else
                    <p class="mt-3 flex items-baseline gap-2">
                        <span class="tabular text-4xl font-extrabold text-green-700">{{ $persenHadir }}%</span>
                        <span class="text-sm text-gray-600">siswa hadir</span>
                    </p>
                    <div class="mt-4 flex h-2 gap-0.5 overflow-hidden rounded-full" role="img"
                        aria-label="Hadir {{ $hadirCount }}, sakit {{ $sakitCount }}, izin {{ $izinCount }}, alpa {{ $alpaCount }}">
                        @foreach ($status as [$label, $jumlah, $warna])
                            @if ($jumlah > 0)
                                <span class="{{ $warna }}" style="flex: {{ $jumlah }}"></span>
                            @endif
                        @endforeach
                    </div>
                    <dl class="mt-4 grid grid-cols-4 gap-2">
                        @foreach ($status as [$label, $jumlah, $warna, $teks])
                            <div>
                                <dt class="flex items-center gap-1.5 text-xs text-gray-600"><span class="h-2 w-2 rounded-sm {{ $warna }}" aria-hidden="true"></span>{{ $label }}</dt>
                                <dd class="tabular text-lg font-bold text-gray-900">{{ $jumlah }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>

            <section class="rounded-xl border border-gray-200 bg-white" aria-labelledby="judul-kelas">
                <div class="border-b border-gray-200 px-5 py-4 md:px-6">
                    <h2 id="judul-kelas" class="text-lg font-bold text-gray-900">Kelas</h2>
                    <p class="text-sm text-gray-600">{{ $totalSiswa }} siswa aktif di {{ $totalKelas }} kelas</p>
                </div>
                <ul class="divide-y divide-gray-100">
                    @forelse ($kelasList as $kelas)
                        <li>
                            <a href="{{ route('kelas.show', $kelas->id) }}" class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-gray-50 md:px-6">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $kelas->nama_kelas }}</p>
                                    <p class="truncate text-xs text-gray-600">{{ $kelas->waliKelas?->nama_lengkap ?? 'Belum ada wali kelas' }}</p>
                                </div>
                                <p class="tabular shrink-0 text-sm text-gray-700"><span class="font-bold text-gray-900">{{ $kelas->siswa_aktif_count }}</span> siswa</p>
                            </a>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-sm text-gray-600">Belum ada kelas.</li>
                    @endforelse
                </ul>
                <a href="{{ route('kelas.index') }}" class="block border-t border-gray-100 px-5 py-3 text-sm font-semibold text-blue-800 hover:bg-gray-50 md:px-6">Kelola semua kelas</a>
            </section>
        </div>
    </div>
@endsection
