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
        $hariSekolah = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        $totalPresensi = $hadirCount + $sakitCount + $izinCount + $alpaCount;
        $status = [
            ['Hadir', $hadirCount, 'bg-green-700'],
            ['Sakit', $sakitCount, 'bg-purple-600'],
            ['Izin', $izinCount, 'bg-amber-500'],
            ['Alpa', $alpaCount, 'bg-red-600'],
        ];
        $nama = Auth::user()->nama_lengkap ?? Auth::user()->name;

        // Grafik batang per hari presensi. Tiap batang setinggi 100% dan terbagi menurut status,
        // supaya komposisi hadir, sakit, izin, dan alpa terlihat walau datanya baru beberapa hari.
        // Data lama di cache bisa belum punya hitungan sakit/izin/alpa: anggap 0 agar halaman tidak error.
        $tren = collect($trenKehadiran)->map(fn ($t) => $t + ['sakit' => 0, 'izin' => 0, 'alpa' => 0])->values();
        $adaTren = $tren->isNotEmpty();
        if ($adaTren) {
            $jumlahHari = $tren->count();
            $labelSetiapBatang = $jumlahHari <= 12;
            $rataRata = round($tren->sum('hadir') / max(1, $tren->sum('total')) * 100, 1);
            $persenPerHari = $tren->groupBy(fn ($t) => \App\Support\Sekolah::hari(\Carbon\Carbon::parse($t['tanggal'])))
                ->map(fn ($g) => round($g->sum('hadir') / max(1, $g->sum('total')) * 100, 1));
            $hariTerendah = $jumlahHari >= 5 && $persenPerHari->count() > 1 ? $persenPerHari->sort()->keys()->first() : null;
            $jumlahStatus = [
                ['Hadir', $tren->sum('hadir'), 'bg-green-700'],
                ['Sakit', $tren->sum('sakit'), 'bg-purple-600'],
                ['Izin', $tren->sum('izin'), 'bg-amber-500'],
                ['Alpa', $tren->sum('alpa'), 'bg-red-600'],
            ];
        }
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
        <div>
            <p class="text-sm text-gray-600">{{ $hariIni }}, {{ $now->translatedFormat('j F Y') }}</p>
            <h1 class="text-2xl font-bold text-gray-900">{{ $sapaan }}, {{ $nama }}</h1>
        </div>
        <p class="text-sm text-gray-600">Tahun ajaran <span class="font-semibold text-gray-900">{{ $tahunAktif->nama_semester ?? 'belum diatur' }}</span></p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- Kiri: grafik di atas, lalu pekerjaan hari ini --}}
        <div class="min-w-0 space-y-6">
            <section class="rounded-xl border border-gray-200 bg-white" aria-labelledby="judul-tren">
                <div class="flex flex-wrap items-baseline justify-between gap-x-3 border-b border-gray-200 px-5 py-4 md:px-6">
                    <h2 id="judul-tren" class="text-lg font-bold text-gray-900">Kehadiran siswa 30 hari terakhir</h2>
                    <p class="text-sm text-gray-600">{{ ucfirst($cakupanTren) }}</p>
                </div>
                @if ($adaTren)
                    <div class="px-5 pb-5 pt-4 md:px-6">
                        <p class="text-sm text-gray-700">
                            Rata-rata <span class="tabular font-bold text-gray-900">{{ $rataRata }}%</span> hadir dalam {{ $jumlahHari }} hari presensi.
                            @if ($hariTerendah)
                                Paling rendah pada hari <span class="font-bold text-gray-900">{{ $hariTerendah }}</span> (<span class="tabular">{{ $persenPerHari[$hariTerendah] }}%</span>).
                            @endif
                        </p>

                        <div class="mt-4 grid grid-cols-[auto_minmax(0,1fr)] gap-x-3" aria-hidden="true">
                            <div class="tabular relative mt-5 h-44 text-right text-xs text-gray-600">
                                <span class="absolute right-0 top-0 -translate-y-1/2">100%</span>
                                <span class="absolute right-0 top-1/2 -translate-y-1/2">50%</span>
                                <span class="absolute right-0 top-full -translate-y-1/2">0%</span>
                            </div>
                            <div class="min-w-0">
                                <div class="relative">
                                    <div class="pointer-events-none absolute inset-x-0 bottom-0 top-5 border-y border-gray-200">
                                        <div class="absolute inset-x-0 top-1/2 border-t border-gray-200"></div>
                                    </div>
                                    <div class="relative flex items-end {{ $labelSetiapBatang ? 'justify-start gap-2 md:gap-3' : 'justify-between gap-0.5' }}">
                                        @foreach ($tren as $t)
                                            @php $tgl = \Carbon\Carbon::parse($t['tanggal']); @endphp
                                            <div class="flex min-w-0 flex-1 flex-col items-center {{ $labelSetiapBatang ? 'max-w-20' : '' }}"
                                                title="{{ $tgl->translatedFormat('j M') }}: hadir {{ $t['hadir'] }}, sakit {{ $t['sakit'] }}, izin {{ $t['izin'] }}, alpa {{ $t['alpa'] }} ({{ $t['persen'] }}%)">
                                                <span class="tabular mb-1 h-4 text-xs font-bold text-gray-900">@if ($labelSetiapBatang || $loop->last){{ round($t['persen']) }}%@endif</span>
                                                <div class="flex h-44 w-full flex-col-reverse overflow-hidden rounded-t-md bg-gray-100">
                                                    @foreach ([['hadir', 'bg-green-700'], ['sakit', 'bg-purple-600'], ['izin', 'bg-amber-500'], ['alpa', 'bg-red-600']] as [$kunci, $warna])
                                                        @if ($t[$kunci] > 0)
                                                            <span class="{{ $warna }}" style="height: {{ round($t[$kunci] / max(1, $t['total']) * 100, 2) }}%"></span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="mt-2 flex items-start text-xs text-gray-600 {{ $labelSetiapBatang ? 'justify-start gap-2 md:gap-3' : 'justify-between' }}">
                                    @if ($labelSetiapBatang)
                                        @foreach ($tren as $t)
                                            <span class="min-w-0 max-w-20 flex-1 text-center">{{ \Carbon\Carbon::parse($t['tanggal'])->translatedFormat('j M') }}</span>
                                        @endforeach
                                    @else
                                        <span>{{ \Carbon\Carbon::parse($tren->first()['tanggal'])->translatedFormat('j M') }}</span>
                                        <span>{{ \Carbon\Carbon::parse($tren->last()['tanggal'])->translatedFormat('j M') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 border-t border-gray-100 pt-4" aria-hidden="true">
                            @foreach ($jumlahStatus as [$label, $jumlah, $warna])
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="h-2.5 w-2.5 rounded-sm {{ $warna }}"></span>
                                    <dt class="text-gray-600">{{ $label }}</dt>
                                    <dd class="tabular font-bold text-gray-900">{{ $jumlah }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <div class="sr-only"><table>
                            <caption>Kehadiran siswa per hari presensi</caption>
                            <thead><tr><th scope="col">Tanggal</th><th scope="col">Hadir</th><th scope="col">Sakit</th><th scope="col">Izin</th><th scope="col">Alpa</th><th scope="col">Persen hadir</th></tr></thead>
                            <tbody>
                                @foreach ($tren as $t)
                                    <tr><td>{{ \Carbon\Carbon::parse($t['tanggal'])->translatedFormat('j F Y') }}</td><td>{{ $t['hadir'] }}</td><td>{{ $t['sakit'] }}</td><td>{{ $t['izin'] }}</td><td>{{ $t['alpa'] }}</td><td>{{ $t['persen'] }}%</td></tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    </div>
                @else
                    <p class="px-6 py-10 text-sm text-gray-600">Belum ada presensi kelas dalam 30 hari terakhir. Grafik muncul setelah guru mengisi presensi.</p>
                @endif
            </section>

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
                        <a href="{{ route('presensi.show', $jadwal->kelas_id) }}?jadwal_id={{ $jadwal->id }}" class="btn btn-secondary shrink-0">Isi presensi</a>
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

    {{-- Jadwal sepekan: satu kolom per hari, selebar halaman --}}
    <section class="rounded-xl border border-gray-200 bg-white" aria-labelledby="judul-sepekan">
        <div class="flex items-baseline justify-between gap-3 border-b border-gray-200 px-5 py-4 md:px-6">
            <h2 id="judul-sepekan" class="text-lg font-bold text-gray-900">Jadwal mengajar sepekan</h2>
            <p class="text-sm text-gray-600">{{ $jadwalSepekan->count() }} sesi</p>
        </div>
        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 md:p-5">
            @foreach ($hariSekolah as $hari)
                @php $hariIniKolom = $hari === $hariIni; $daftar = $perHari->get($hari, collect()); @endphp
                <div class="min-w-0 rounded-lg border {{ $hariIniKolom ? 'border-blue-800' : 'border-gray-200' }} bg-white">
                    <div class="flex items-center justify-between gap-2 rounded-t-md px-3 py-2 {{ $hariIniKolom ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-900' }}">
                        <h3 class="text-sm font-bold">{{ $hari }}</h3>
                        @if ($hariIniKolom)<span class="text-xs font-medium text-blue-100">Hari ini</span>@endif
                    </div>
                    <div class="space-y-2 p-2">
                        @forelse ($daftar as $j)
                            <div class="rounded-md px-3 py-2 {{ $hariIniKolom ? 'bg-blue-50' : 'bg-gray-50' }}">
                                <p class="tabular text-xs font-semibold text-gray-600">{{ \Carbon\Carbon::parse($j->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($j->jam_selesai)->format('H:i') }}</p>
                                <p class="truncate text-sm font-bold text-gray-900">{{ $j->mataPelajaran->nama_pelajaran ?? '-' }}</p>
                                <p class="truncate text-xs text-gray-600">{{ $j->kelas->nama_kelas ?? '-' }}</p>
                            </div>
                        @empty
                            <p class="px-3 py-3 text-xs text-gray-600">Tidak mengajar</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
