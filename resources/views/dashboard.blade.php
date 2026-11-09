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
        $perHari = $jadwalSepekan->groupBy('hari');
        $hariSekolah = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        $hariAwal = in_array($hariIni, $hariSekolah, true) ? $hariIni : 'Senin';
        $totalPresensi = $hadirCount + $sakitCount + $izinCount + $alpaCount;
        $status = [
            ['Hadir', $hadirCount, 'bg-green-700'],
            ['Sakit', $sakitCount, 'bg-purple-600'],
            ['Izin', $izinCount, 'bg-amber-500'],
            ['Alpa', $alpaCount, 'bg-red-600'],
        ];
        $nama = Auth::user()->nama_lengkap ?? Auth::user()->name;

        // Kehadiran 30 hari: cincin komposisi dan peta per hari. Data lama di cache bisa belum punya
        // hitungan sakit/izin/alpa, jadi dianggap 0 agar halaman tidak error.
        $tren = collect($trenKehadiran)->map(fn ($t) => $t + ['sakit' => 0, 'izin' => 0, 'alpa' => 0])->values();
        $adaTren = $tren->isNotEmpty();
        if ($adaTren) {
            $jumlahHari = $tren->count();
            $totalTren = max(1, $tren->sum('total'));
            $rataRata = round($tren->sum('hadir') / $totalTren * 100, 1);
            $persenPerHari = $tren->groupBy(fn ($t) => \App\Support\Sekolah::hari(\Carbon\Carbon::parse($t['tanggal'])))
                ->map(fn ($g) => round($g->sum('hadir') / max(1, $g->sum('total')) * 100, 1));
            $hariTerendah = $jumlahHari >= 5 && $persenPerHari->count() > 1 ? $persenPerHari->sort()->keys()->first() : null;

            $komposisi = collect([
                ['Hadir', $tren->sum('hadir'), 'text-green-700', 'bg-green-700'],
                ['Sakit', $tren->sum('sakit'), 'text-purple-600', 'bg-purple-600'],
                ['Izin', $tren->sum('izin'), 'text-amber-500', 'bg-amber-500'],
                ['Alpa', $tren->sum('alpa'), 'text-red-600', 'bg-red-600'],
            ]);
            // Cincin: keliling lingkaran r=15.9155 adalah 100, sehingga panjang busur = persen.
            $geser = 0;
            $busur = $komposisi->map(function ($k) use ($totalTren, &$geser) {
                $persen = round($k[1] / $totalTren * 100, 2);
                $hasil = ['label' => $k[0], 'jumlah' => $k[1], 'teks' => $k[2], 'latar' => $k[3], 'persen' => $persen, 'geser' => $geser];
                $geser += $persen;

                return $hasil;
            });

            // Peta per hari: kolom = pekan (Senin-Sabtu), dari pekan awal 30 hari sampai pekan ini.
            $perTanggal = $tren->keyBy('tanggal');
            $awalPeta = $now->copy()->subDays(29)->startOfWeek(\Carbon\CarbonInterface::MONDAY);
            $mingguPeta = [];
            for ($m = $awalPeta->copy(); $m->lte($now); $m->addWeek()) {
                $mingguPeta[] = $m->copy();
            }
            $warnaSel = fn ($p) => $p >= 97 ? 'bg-green-700' : ($p >= 94 ? 'bg-green-500' : ($p >= 90 ? 'bg-green-300' : ($p >= 86 ? 'bg-yellow-300' : 'bg-amber-500')));
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
        {{-- Kiri: kehadiran 30 hari di atas, lalu jadwal mengajar --}}
        <div class="min-w-0 space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white" aria-labelledby="judul-tren">
                <div class="flex flex-wrap items-baseline justify-between gap-x-3 px-5 pt-5 md:px-7 md:pt-6">
                    <div>
                        <h2 id="judul-tren" class="text-lg font-bold text-gray-900">Kehadiran siswa 30 hari terakhir</h2>
                        <p class="text-sm text-gray-600">{{ ucfirst($cakupanTren) }}</p>
                    </div>
                </div>
                @if ($adaTren)
                    <div class="px-5 pb-6 pt-5 md:px-7">
                        <div class="flex flex-col items-center gap-6 sm:flex-row sm:gap-10">
                            <div class="relative h-44 w-44 shrink-0" role="img" aria-label="Rata-rata {{ $rataRata }} persen siswa hadir dalam {{ $jumlahHari }} hari presensi">
                                <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90" aria-hidden="true">
                                    <circle cx="18" cy="18" r="15.9155" fill="none" stroke="currentColor" stroke-width="3.4" class="text-gray-100" />
                                    @foreach ($busur as $b)
                                        @if ($b['persen'] > 0)
                                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="currentColor" stroke-width="3.4" class="{{ $b['teks'] }}"
                                                stroke-dasharray="{{ max(0.01, $b['persen'] - 0.4) }} {{ 100 - max(0.01, $b['persen'] - 0.4) }}" stroke-dashoffset="{{ -$b['geser'] }}" />
                                        @endif
                                    @endforeach
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="tabular text-3xl font-extrabold text-gray-900">{{ round($rataRata) }}%</span>
                                    <span class="text-xs font-medium text-gray-600">siswa hadir</span>
                                </div>
                            </div>
                            <div class="w-full min-w-0 flex-1">
                                <ul class="space-y-3.5">
                                    @foreach ($busur as $b)
                                        <li class="flex items-center gap-3 text-sm">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-sm {{ $b['latar'] }}" aria-hidden="true"></span>
                                            <span class="flex-1 font-medium text-gray-800">{{ $b['label'] }}</span>
                                            <span class="tabular font-bold text-gray-900">{{ number_format($b['jumlah'], 0, ',', '.') }}</span>
                                            <span class="tabular w-12 text-right text-gray-600">{{ round($b['persen']) }}%</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-4 text-sm text-gray-600">
                                    {{ $jumlahHari }} hari presensi.
                                    @if ($hariTerendah)
                                        Paling rendah pada hari <span class="font-semibold text-gray-900">{{ $hariTerendah }}</span> (<span class="tabular">{{ $persenPerHari[$hariTerendah] }}%</span>).
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-100 pt-5">
                            <h3 class="text-sm font-semibold text-gray-800">Per hari presensi</h3>
                            <div class="mt-3 overflow-x-auto pb-1" aria-hidden="true">
                                <div class="flex gap-1.5" style="min-width: {{ 2.5 + count($mingguPeta) * 2.25 }}rem">
                                    <div class="flex flex-col gap-1.5 pr-1 pt-5">
                                        @foreach ($hariSekolah as $h)
                                            <span class="flex h-8 items-center text-xs font-medium text-gray-600">{{ substr($h, 0, 3) }}</span>
                                        @endforeach
                                    </div>
                                    @foreach ($mingguPeta as $mi => $senin)
                                        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                            <span class="h-4 whitespace-nowrap text-xs text-gray-600">{{ $mi === 0 || $senin->day <= 7 ? $senin->translatedFormat('j M') : $senin->day }}</span>
                                            @foreach ($hariSekolah as $di => $h)
                                                @php
                                                    $tgl = $senin->copy()->addDays($di - 1);
                                                    $d = $perTanggal->get($tgl->toDateString());
                                                    $akanDatang = $tgl->isAfter($now);
                                                @endphp
                                                <span class="h-8 w-full rounded-md {{ $d ? $warnaSel($d['persen']) : ($akanDatang ? 'bg-transparent' : 'bg-gray-100') }}"
                                                    title="{{ $tgl->translatedFormat('l, j M') }}{{ $d ? ': '.$d['persen'].'% hadir' : '' }}"></span>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2 text-xs text-gray-600" aria-hidden="true">
                                <span>Rendah</span>
                                @foreach (['bg-amber-500', 'bg-yellow-300', 'bg-green-300', 'bg-green-500', 'bg-green-700'] as $w)
                                    <span class="h-2.5 w-5 rounded-sm {{ $w }}"></span>
                                @endforeach
                                <span>Tinggi</span>
                                <span class="ml-3 inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-gray-100 ring-1 ring-gray-200"></span>Tidak ada presensi</span>
                            </div>
                        </div>

                        <div class="sr-only">
                            <table>
                                <caption>Kehadiran siswa per hari presensi</caption>
                                <thead><tr><th scope="col">Tanggal</th><th scope="col">Hadir</th><th scope="col">Sakit</th><th scope="col">Izin</th><th scope="col">Alpa</th><th scope="col">Persen hadir</th></tr></thead>
                                <tbody>
                                    @foreach ($tren as $t)
                                        <tr><td>{{ \Carbon\Carbon::parse($t['tanggal'])->translatedFormat('j F Y') }}</td><td>{{ $t['hadir'] }}</td><td>{{ $t['sakit'] }}</td><td>{{ $t['izin'] }}</td><td>{{ $t['alpa'] }}</td><td>{{ $t['persen'] }}%</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <p class="px-6 pb-10 pt-6 text-sm text-gray-600">Belum ada presensi kelas dalam 30 hari terakhir. Grafik muncul setelah guru mengisi presensi.</p>
                @endif
            </section>

            {{-- Jadwal mengajar: tab hari, dibuka di hari ini --}}
            <section class="rounded-2xl border border-gray-200 bg-white" aria-labelledby="judul-jadwal" x-data="agendaHari" data-awal="{{ $hariAwal }}">
                <div class="flex items-baseline justify-between gap-3 px-5 pt-5 md:px-7 md:pt-6">
                    <h2 id="judul-jadwal" class="text-lg font-bold text-gray-900">Jadwal mengajar</h2>
                    <p class="text-sm text-gray-600">{{ $jadwalSepekan->count() }} sesi pekan ini</p>
                </div>
                @if ($jadwalSepekan->isEmpty())
                    <p class="px-6 pb-10 pt-6 text-sm text-gray-600">Anda belum punya jadwal mengajar.</p>
                @else
                    <div class="px-5 pb-4 pt-4 md:px-7" role="tablist" aria-label="Pilih hari">
                        <div class="grid grid-cols-6 gap-1.5 md:gap-2">
                            @foreach ($hariSekolah as $hari)
                                @php $jumlah = $perHari->get($hari, collect())->count(); @endphp
                                <button type="button" role="tab" data-hari="{{ $hari }}" @click="pilih" :aria-selected="dipilih" :class="kelasTab"
                                    class="flex min-w-0 flex-col items-center rounded-xl px-1 py-2.5 transition-colors md:px-2">
                                    <span class="text-sm font-bold">{{ substr($hari, 0, 3) }}@if ($hari === $hariIni)<span class="sr-only"> (hari ini)</span>@endif</span>
                                    <span class="text-xs font-medium" data-hari="{{ $hari }}" :class="kelasHitungan">{{ $jumlah ? $jumlah.' sesi' : 'Kosong' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @foreach ($hariSekolah as $hari)
                        @php $daftar = $perHari->get($hari, collect())->values(); $hariItu = $hari === $hariIni; @endphp
                        <div role="tabpanel" data-hari="{{ $hari }}" x-show="dipilih" x-cloak class="px-3 pb-4 md:px-5">
                            @forelse ($daftar as $jadwal)
                                @php
                                    $mulai = \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i');
                                    $selesai = \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i');
                                    $menit = \Carbon\Carbon::parse($jadwal->jam_mulai)->diffInMinutes(\Carbon\Carbon::parse($jadwal->jam_selesai));
                                    $berlangsung = $hariItu && $mulai <= $jamSekarang && $jamSekarang < $selesai;
                                @endphp
                                <div class="flex items-center gap-3 rounded-xl px-3 py-3.5 md:gap-4 md:px-4 {{ $berlangsung ? 'bg-blue-50' : '' }}">
                                    <div class="w-12 shrink-0 tabular">
                                        <p class="text-sm font-bold text-gray-900">{{ $mulai }}</p>
                                        <p class="text-xs text-gray-600">{{ $selesai }}</p>
                                    </div>
                                    <span class="h-10 w-[3px] shrink-0 rounded-full {{ $berlangsung ? 'bg-blue-600' : 'bg-blue-200' }}" aria-hidden="true"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="font-bold text-gray-900">{{ $jadwal->mataPelajaran->nama_pelajaran ?? '-' }}</span>
                                            @if ($berlangsung)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Berlangsung</span>@endif
                                        </p>
                                        <p class="text-sm text-gray-600">{{ $jadwal->kelas->nama_kelas ?? '-' }} <span aria-hidden="true">·</span> {{ $menit }} menit</p>
                                    </div>
                                    @if ($hariItu)
                                        <a href="{{ route('presensi.show', $jadwal->kelas_id) }}?jadwal_id={{ $jadwal->id }}" class="btn btn-secondary shrink-0">Isi presensi</a>
                                    @endif
                                </div>
                                @if (! $loop->last)<div class="mx-3 border-t border-gray-100 md:mx-4"></div>@endif
                            @empty
                                <p class="px-4 py-8 text-sm text-gray-600">Tidak mengajar pada hari {{ $hari }}.</p>
                            @endforelse
                        </div>
                    @endforeach
                @endif
            </section>
        </div>

        {{-- Kanan: kondisi sekolah --}}
        <div class="min-w-0 space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6" aria-labelledby="judul-kehadiran">
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

            <section class="rounded-2xl border border-gray-200 bg-white" aria-labelledby="judul-kelas">
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
                <a href="{{ route('kelas.index') }}" class="block border-t border-gray-100 px-5 py-3 text-sm font-semibold text-blue-700 hover:bg-gray-50 md:px-6">Kelola semua kelas</a>
            </section>
        </div>
    </div>
@endsection
