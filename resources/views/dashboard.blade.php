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
        $nama = Auth::user()->nama_lengkap ?? Auth::user()->name;
        $totalPresensi = $hadirCount + $sakitCount + $izinCount + $alpaCount;
        $status = [
            ['Hadir', $hadirCount, 'bg-green-700'],
            ['Sakit', $sakitCount, 'bg-purple-600'],
            ['Izin', $izinCount, 'bg-amber-500'],
            ['Alpa', $alpaCount, 'bg-red-600'],
        ];
        $jadwalHariIni = $jadwalSepekan->where('hari', $hariIni)->values();

        // Kehadiran per kelas: kelas dengan persen hadir terendah ditaruh paling atas.
        $kelasTren = collect($trenKehadiran)
            ->map(fn($k) => $k + ['persen' => $k['total'] > 0 ? round(($k['hadir'] / $k['total']) * 100, 1) : 0])
            ->sortBy('persen')
            ->values();
        $adaTren = $kelasTren->isNotEmpty();
        if ($adaTren) {
            $totalSemua = max(1, $kelasTren->sum('total'));
            $rataRata = round(($kelasTren->sum('hadir') / $totalSemua) * 100, 1);
            $terendah = $kelasTren->count() > 1 ? $kelasTren->first() : null;
            $komposisi = [
                ['hadir', 'Hadir', 'bg-green-700'],
                ['sakit', 'Sakit', 'bg-purple-600'],
                ['izin', 'Izin', 'bg-amber-500'],
                ['alpa', 'Alpa', 'bg-red-600'],
            ];
        }
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
        <div>
            <p class="text-sm text-gray-600">{{ $hariIni }}, {{ $now->translatedFormat('j F Y') }}</p>
            <h1 class="text-2xl font-bold text-gray-900">{{ $sapaan }}, {{ $nama }}</h1>
        </div>
        <p class="text-sm text-gray-600">Tahun ajaran <span
                class="font-semibold text-gray-900">{{ $tahunAktif->nama_semester ?? 'belum diatur' }}</span></p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- Kiri: kehadiran per kelas, lalu jadwal mengajar hari ini --}}
        <div class="min-w-0 space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white" aria-labelledby="judul-kelas-tren">
                <div
                    class="flex flex-wrap items-baseline justify-between gap-x-3 border-b border-gray-200 px-5 py-4 md:px-6">
                    <h2 id="judul-kelas-tren" class="text-lg font-bold text-gray-900">Kehadiran per kelas, 30 hari terakhir
                    </h2>
                    <p class="text-sm text-gray-600">{{ ucfirst($cakupanTren) }}</p>
                </div>
                @if ($adaTren)
                    <div class="px-5 pb-5 pt-4 md:px-6">
                        <p class="text-sm text-gray-700">
                            Rata-rata <span class="tabular font-bold text-gray-900">{{ $rataRata }}%</span> hadir di
                            {{ $kelasTren->count() }} kelas.
                            @if ($terendah)
                                Terendah: <span class="font-semibold text-gray-900">{{ $terendah['nama_kelas'] }}</span>
                                (<span class="tabular">{{ $terendah['persen'] }}%</span>).
                            @endif
                        </p>

                        {{-- Grafik Batang Vertikal (Vertical Bar Chart) --}}
                        <div class="mt-5 rounded-lg border border-gray-200 bg-white p-4 sm:p-5" role="region"
                            aria-label="Grafik persentase kehadiran per kelas">
                            <h3 class="mb-5 text-center text-sm font-bold text-gray-900">Kehadiran per Kelas (30 Hari
                                Terakhir)</h3>

                            {{-- Area Grafik dengan Sumbu Y & Garis Kisi Horizontal --}}
                            <div class="overflow-x-auto pb-1">
                                <div class="min-w-[420px] sm:min-w-0">
                                    <div class="relative flex h-60">
                                        {{-- Sumbu Y (Skala 0% sampai 100%) --}}
                                        <div class="flex w-12 shrink-0 flex-col justify-between py-0 pr-2 text-right select-none"
                                            aria-hidden="true">
                                            <span class="tabular text-xs font-medium leading-none text-gray-600">100%</span>
                                            <span class="tabular text-xs font-medium leading-none text-gray-600">80%</span>
                                            <span class="tabular text-xs font-medium leading-none text-gray-600">60%</span>
                                            <span class="tabular text-xs font-medium leading-none text-gray-600">40%</span>
                                            <span class="tabular text-xs font-medium leading-none text-gray-600">20%</span>
                                            <span class="tabular text-xs font-medium leading-none text-gray-600">0%</span>
                                        </div>

                                        {{-- Plot Area dengan Garis Kisi --}}
                                        <div class="relative h-full flex-1 border-b border-l border-gray-300">
                                            {{-- Garis Kisi Horizontal --}}
                                            <div class="pointer-events-none absolute inset-0 flex flex-col justify-between select-none"
                                                aria-hidden="true">
                                                <div class="w-full border-t border-gray-200"></div>
                                                <div class="w-full border-t border-gray-200"></div>
                                                <div class="w-full border-t border-gray-200"></div>
                                                <div class="w-full border-t border-gray-200"></div>
                                                <div class="w-full border-t border-gray-200"></div>
                                                <div class="w-full"></div>
                                            </div>

                                            {{-- Kolom Batang Vertikal --}}
                                            <div
                                                class="relative z-10 flex h-full items-end justify-around gap-2 px-3 sm:px-6">
                                                @foreach ($kelasTren as $k)
                                                    <div
                                                        class="group relative flex h-full flex-1 max-w-[56px] flex-col items-center justify-end">
                                                        {{-- Tooltip Saat Diarahkan / Difokuskan --}}
                                                        <div
                                                            class="pointer-events-none absolute -top-11 z-30 hidden flex-col items-center transition-opacity duration-150 group-hover:flex group-focus-within:flex">
                                                            <div
                                                                class="whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white shadow-md tabular">
                                                                <p class="font-bold">{{ $k['nama_kelas'] }}</p>
                                                                <p>{{ $k['persen'] }}%
                                                                    ({{ $k['hadir'] }}/{{ $k['total'] }} hadir)</p>
                                                            </div>
                                                            <div
                                                                class="-mt-0.5 h-1.5 w-2 border-t-4 border-r-4 border-l-4 border-transparent border-t-gray-900">
                                                            </div>
                                                        </div>

                                                        {{-- Label Persentase di Atas Batang --}}
                                                        <span
                                                            class="tabular mb-1 text-[11px] font-bold leading-none text-gray-800 transition-transform group-hover:-translate-y-0.5"
                                                            aria-hidden="true">
                                                            {{ $k['persen'] }}%
                                                        </span>

                                                        {{-- Batang Vertikal --}}
                                                        <div tabindex="0" role="graphics-symbol"
                                                            aria-label="{{ $k['nama_kelas'] }}: {{ $k['persen'] }}% kehadiran ({{ $k['hadir'] }} hadir dari {{ $k['total'] }} total presensi)"
                                                            class="w-full cursor-pointer rounded-t-xs bg-blue-600 transition-all duration-300 hover:bg-blue-700 focus:bg-blue-700 focus:outline-2 focus:outline-offset-1 focus:outline-blue-600"
                                                            style="height: {{ max(4, $k['persen']) }}%; min-height: 4px;">
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Sumbu X: Nama Kelas di Bawah Tiap Batang --}}
                                    <div class="flex pl-12 pt-2">
                                        <div class="flex flex-1 justify-around gap-2 px-3 sm:px-6" aria-hidden="true">
                                            @foreach ($kelasTren as $k)
                                                <div class="flex-1 max-w-[56px] text-center">
                                                    <span class="block truncate text-xs font-semibold text-gray-700"
                                                        title="{{ $k['nama_kelas'] }}">
                                                        {{ $k['nama_kelas'] }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Legenda di Bagian Bawah --}}
                            <div class="mt-4 flex items-center justify-center gap-2 border-t border-gray-100 pt-3"
                                aria-hidden="true">
                                <span class="h-3 w-3 rounded-xs bg-blue-600"></span>
                                <span class="text-xs font-semibold text-gray-700">Persentase Kehadiran Siswa</span>
                            </div>
                        </div>

                        <dl class="mt-5 flex flex-wrap gap-x-6 gap-y-2 border-t border-gray-100 pt-4" aria-hidden="true">
                            @foreach ($komposisi as [$kunci, $label, $warna])
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="h-2.5 w-2.5 rounded-sm {{ $warna }}"></span>
                                    <dt class="text-gray-600">{{ $label }}</dt>
                                    <dd class="tabular font-bold text-gray-900">{{ $kelasTren->sum($kunci) }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <div class="sr-only">
                            <table>
                                <caption>Kehadiran siswa per kelas selama 30 hari terakhir</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Kelas</th>
                                        <th scope="col">Hadir</th>
                                        <th scope="col">Sakit</th>
                                        <th scope="col">Izin</th>
                                        <th scope="col">Alpa</th>
                                        <th scope="col">Persen hadir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($kelasTren as $k)
                                        <tr>
                                            <td>{{ $k['nama_kelas'] }}</td>
                                            <td>{{ $k['hadir'] }}</td>
                                            <td>{{ $k['sakit'] }}</td>
                                            <td>{{ $k['izin'] }}</td>
                                            <td>{{ $k['alpa'] }}</td>
                                            <td>{{ $k['persen'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <p class="px-6 py-10 text-sm text-gray-600">Belum ada presensi kelas dalam 30 hari terakhir. Grafik
                        muncul setelah guru mengisi presensi.</p>
                @endif
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white" aria-labelledby="judul-hari-ini">
                <div class="flex items-baseline justify-between gap-3 border-b border-gray-200 px-5 py-4 md:px-6">
                    <h2 id="judul-hari-ini" class="text-lg font-bold text-gray-900">Jadwal mengajar hari ini</h2>
                    <p class="text-sm text-gray-600">{{ $jadwalHariIni->count() }} sesi</p>
                </div>
                @forelse ($jadwalHariIni as $jadwal)
                    @php
                        $mulai = \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i');
                        $selesai = \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i');
                        $berlangsung = $mulai <= $jamSekarang && $jamSekarang < $selesai;
                    @endphp
                    <div
                        class="flex items-center gap-4 px-5 py-4 md:gap-5 md:px-6 {{ $berlangsung ? 'bg-blue-50' : '' }} {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                        <div class="w-14 shrink-0 tabular">
                            <p class="text-sm font-bold text-gray-900">{{ $mulai }}</p>
                            <p class="text-xs text-gray-600">{{ $selesai }}</p>
                        </div>
                        <span
                            class="hidden h-3 w-3 shrink-0 rounded-full sm:block {{ $berlangsung ? 'bg-amber-400 ring-2 ring-blue-600' : 'bg-blue-200' }}"
                            aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-gray-900">
                                {{ $jadwal->mataPelajaran->nama_pelajaran ?? '-' }}</p>
                            <p class="truncate text-sm text-gray-600">{{ $jadwal->kelas->nama_kelas ?? '-' }}@if ($berlangsung)
                                    , sedang berlangsung
                                @endif
                            </p>
                        </div>
                        @if ($jadwal->kelas?->wali_kelas_id === Auth::id())
                            <a href="{{ route('presensi.show', $jadwal->kelas_id) }}?jadwal_id={{ $jadwal->id }}"
                                class="btn btn-secondary shrink-0">Isi presensi</a>
                        @endif
                    </div>
                    @empty
                        <p class="px-6 py-10 text-sm text-gray-600">
                            {{ $jadwalSepekan->isEmpty() ? 'Anda belum punya jadwal mengajar.' : 'Tidak ada jadwal mengajar hari ini.' }}
                        </p>
                    @endforelse
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
                            @foreach ($status as [$label, $jumlah, $warna])
                                <div>
                                    <dt class="flex items-center gap-1.5 text-xs text-gray-600"><span
                                            class="h-2 w-2 rounded-sm {{ $warna }}"
                                            aria-hidden="true"></span>{{ $label }}</dt>
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
                                <a href="{{ route('kelas.show', $kelas->id) }}"
                                    class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-gray-50 md:px-6">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-gray-900">{{ $kelas->nama_kelas }}</p>
                                        <p class="truncate text-xs text-gray-600">
                                            {{ $kelas->waliKelas?->nama_lengkap ?? 'Belum ada wali kelas' }}</p>
                                    </div>
                                    <p class="tabular shrink-0 text-sm text-gray-700"><span
                                            class="font-bold text-gray-900">{{ $kelas->siswa_aktif_count }}</span> siswa</p>
                                </a>
                            </li>
                        @empty
                            <li class="px-6 py-8 text-sm text-gray-600">Belum ada kelas.</li>
                        @endforelse
                    </ul>
                    <a href="{{ route('kelas.index') }}"
                        class="block border-t border-gray-100 px-5 py-3 text-sm font-semibold text-blue-700 hover:bg-gray-50 md:px-6">Kelola
                        semua kelas</a>
                </section>
            </div>
        </div>
    @endsection
