@extends('layouts.app')
@section('title', 'Rekap Presensi Guru')
@section('page-title', 'Rekap Presensi Guru')

@section('content')
@php $exportQuery = request()->only(['rentang', 'tanggal', 'guru_id']); @endphp

<div>
    <h2 class="font-bold text-2xl text-blue-600">Rekap Presensi Guru</h2>
    <p class="text-gray-500 text-sm mt-1">
        Absen guru dari aplikasi MyDarfik dengan verifikasi lokasi dan wajah. Jam masuk acuan {{ $jamMasuk }}.
        Guru yang belum absen masuk sampai pukul {{ config('presensi_guru.batas_tidak_hadir') ?? '-' }} otomatis tercatat Tidak Hadir.
    </p>
</div>

<form method="GET" action="{{ route('presensi-guru.rekap') }}" class="bg-white p-4 md:p-6 rounded-xl border border-gray-200 shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
    <div>
        <label for="guru_id" class="block text-xs font-bold text-gray-600 uppercase mb-2">Guru</label>
        <select id="guru_id" name="guru_id" class="w-full border border-gray-200 text-sm rounded-lg px-3 h-10 bg-gray-50">
            <option value="">Semua guru (ringkasan)</option>
            @foreach($guruList as $guru)
                <option value="{{ $guru->id }}" @selected($selectedGuru?->id === $guru->id)>{{ $guru->nama_lengkap ?? $guru->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="rentang" class="block text-xs font-bold text-gray-600 uppercase mb-2">Rentang</label>
        <select id="rentang" name="rentang" class="w-full border border-gray-200 text-sm rounded-lg px-3 h-10 bg-gray-50">
            @foreach(['hari_ini' => 'Hari Ini', 'minggu_ini' => 'Minggu Ini', 'bulan_ini' => 'Bulan Ini', 'semester_ini' => 'Semester Ini'] as $value => $label)
                <option value="{{ $value }}" @selected($rentang === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="tanggal" class="block text-xs font-bold text-gray-600 uppercase mb-2">Acuan tanggal</label>
        <input id="tanggal" name="tanggal" type="date" value="{{ $tanggal }}" class="w-full border border-gray-200 text-sm rounded-lg px-3 h-10 bg-gray-50">
    </div>
    <button type="submit" class="h-10 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 flex items-center justify-center gap-2">
        <span class="material-symbols-outlined text-sm">search</span> Tampilkan
    </button>
</form>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-4 md:px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div>
            <h3 class="font-bold text-gray-900">{{ $selectedGuru ? 'Detail harian: '.($selectedGuru->nama_lengkap ?? $selectedGuru->name) : 'Ringkasan per guru' }}</h3>
            <p class="text-sm text-gray-500 mt-0.5">Periode: <strong class="text-gray-900">{{ $periodeLabel }}</strong></p>
        </div>
        <div class="flex gap-2">
            <x-export-button type="pdf" :href="route('presensi-guru.rekap.export.pdf', $exportQuery)" />
            <x-export-button type="csv" :href="route('presensi-guru.rekap.export.csv', $exportQuery)" />
        </div>
    </div>

    <div class="overflow-x-auto">
        @if($selectedGuru && $insidenDetail->isNotEmpty())
            <div class="mx-4 md:mx-6 my-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-bold">Tercatat memakai lokasi palsu ({{ $insidenDetail->count() }}×) — catatan permanen, tidak bisa dihapus</div>
                <ul class="mt-1 list-disc pl-5">
                    @foreach($insidenDetail as $ins)
                        <li>{{ $ins->tanggal->translatedFormat('D, d M Y') }} pukul {{ substr($ins->jam, 0, 5) }} · {{ $ins->tahap }}{{ $ins->jarak_meter !== null ? ' · ±'.number_format($ins->jarak_meter, 0, ',', '.').' m dari sekolah' : '' }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if($selectedGuru)
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-4 md:px-6 py-3 border-b">Tanggal</th>
                        <th class="px-4 md:px-6 py-3 border-b">Status</th>
                        <th class="px-4 md:px-6 py-3 border-b text-center">Masuk</th>
                        <th class="px-4 md:px-6 py-3 border-b text-center">Pulang</th>
                        <th class="px-4 md:px-6 py-3 border-b text-center">Jarak masuk</th>
                        <th class="px-4 md:px-6 py-3 border-b text-center">Kecocokan wajah</th>
                        <th class="px-4 md:px-6 py-3 border-b">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($detail as $row)
                        <tr>
                            <td class="px-4 md:px-6 py-3 font-medium text-gray-900">{{ $row->tanggal->translatedFormat('D, d M Y') }}</td>
                            <td class="px-4 md:px-6 py-3">
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded {{ $row->status === 'Hadir' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">{{ $row->status }}</span>
                                @if($row->terlambat)
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-800 ml-1">Terlambat</span>
                                @endif
                                @if($insidenPerHari->has($row->tanggal->toDateString()))
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-red-600 text-white ml-1">Lokasi palsu {{ $insidenPerHari->get($row->tanggal->toDateString()) }}</span>
                                @endif
                                @if($row->koreksi_mandiri)
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-amber-500 text-white ml-1">Koreksi mandiri</span>
                                @endif
                            </td>
                            <td class="px-4 md:px-6 py-3 text-center">{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-' }}</td>
                            <td class="px-4 md:px-6 py-3 text-center">{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-' }}</td>
                            <td class="px-4 md:px-6 py-3 text-center">{{ $row->masuk_jarak_meter !== null ? $row->masuk_jarak_meter.' m' : '-' }}</td>
                            <td class="px-4 md:px-6 py-3 text-center">{{ $row->masuk_skor_wajah !== null ? $row->masuk_skor_wajah.'%' : '-' }}</td>
                            <td class="px-4 md:px-6 py-3 text-gray-500">{{ $row->keterangan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">Belum ada data absen pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-4 md:px-6 py-3 border-b w-12">No</th>
                        <th class="px-4 md:px-6 py-3 border-b">Nama Guru</th>
                        <th class="px-4 md:px-6 py-3 border-b text-center">Hadir</th>
                        <th class="px-4 md:px-6 py-3 border-b text-center">Terlambat</th>
                        <th class="px-4 md:px-6 py-3 border-b text-center">Tidak Hadir</th>
                        <th class="px-4 md:px-6 py-3 border-b text-center">% Hadir</th>
                        <th class="px-4 md:px-6 py-3 border-b"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($ringkasan as $row)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-4 md:px-6 py-3 text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-4 md:px-6 py-3">
                                <div class="font-bold text-gray-900">{{ $row['guru']->nama_lengkap ?? $row['guru']->name }}</div>
                                <div class="text-xs text-gray-500">{{ $row['guru']->nip ? 'NIP '.$row['guru']->nip : '' }}</div>
                                @if($row['lokasi_palsu'] > 0)
                                    <span class="inline-block mt-1 text-[11px] font-bold px-2 py-0.5 rounded bg-red-600 text-white">Lokasi palsu {{ $row['lokasi_palsu'] }} hari</span>
                                @endif
                                @if($row['koreksi_mandiri'] > 0)
                                    <span class="inline-block mt-1 text-[11px] font-bold px-2 py-0.5 rounded bg-amber-500 text-white">Koreksi mandiri {{ $row['koreksi_mandiri'] }}×</span>
                                @endif
                            </td>
                            <td class="px-4 md:px-6 py-3 text-center font-bold text-green-700">{{ $row['hadir'] }}</td>
                            <td class="px-4 md:px-6 py-3 text-center font-bold text-amber-600">{{ $row['terlambat'] }}</td>
                            <td class="px-4 md:px-6 py-3 text-center font-bold text-red-600">{{ $row['tidak_hadir'] }}</td>
                            <td class="px-4 md:px-6 py-3 text-center font-bold text-gray-700">{{ $row['persen'] }}%</td>
                            <td class="px-4 md:px-6 py-3 text-right">
                                <a href="{{ route('presensi-guru.rekap', ['guru_id' => $row['guru']->id, 'rentang' => $rentang, 'tanggal' => $tanggal]) }}"
                                    class="text-sm font-bold text-blue-600 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">Belum ada guru aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>
</div>

@if(($fotoPengajuan ?? collect())->isNotEmpty() || ($wajahMirip ?? collect())->isNotEmpty())
    <div class="bg-white rounded-xl border border-amber-200 shadow-sm p-4 md:p-6 space-y-4">
        @if($fotoPengajuan->isNotEmpty())
            <div>
                <h3 class="font-bold text-gray-900">Pengajuan foto profil</h3>
                <p class="text-xs text-gray-500 mt-0.5">Foto ini menjadi acuan wajah saat absen. Setujui hanya bila benar wajah guru yang bersangkutan.</p>
            </div>
            @foreach($fotoPengajuan as $g)
                <div class="flex flex-wrap items-center gap-4 border-t border-gray-100 pt-3">
                    <div class="flex items-center gap-2">
                        @if($g->fotoUrl())
                            <img src="{{ $g->fotoUrl() }}" alt="Foto lama {{ $g->nama_lengkap }}" class="w-16 h-16 rounded-lg object-cover border border-gray-200">
                            <span class="material-symbols-outlined text-gray-400">arrow_forward</span>
                        @endif
                        <img src="{{ $g->fotoPengajuanUrl() }}" alt="Foto baru {{ $g->nama_lengkap }}" class="w-16 h-16 rounded-lg object-cover border border-amber-300">
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-gray-900">{{ $g->nama_lengkap ?? $g->name }}</p>
                        <p class="text-xs text-gray-500">Diajukan {{ $g->foto_pengajuan_pada?->timezone(config('presensi_guru.timezone'))->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('presensi-guru.foto.setujui', $g) }}">
                            @csrf
                            <button type="submit" class="btn btn-success px-4 h-10 rounded-lg text-sm">Setujui</button>
                        </form>
                        <form method="POST" action="{{ route('presensi-guru.foto.tolak', $g) }}">
                            @csrf
                            <button type="submit" class="btn btn-alert px-4 h-10 rounded-lg text-sm">Tolak</button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
        @if($wajahMirip->isNotEmpty())
            <div>
                <h3 class="font-bold text-gray-900">Wajah sangat mirip</h3>
                <p class="text-xs text-gray-500 mt-0.5">Sistem tidak bisa membedakan wajah secara pasti untuk pasangan ini (misalnya kembar). Periksa absen mereka secara berkala.</p>
                <ul class="mt-2 space-y-1">
                    @foreach($wajahMirip as $w)
                        <li class="text-sm text-gray-800">{{ $w->guru?->nama_lengkap ?? $w->guru?->name }} dan {{ $w->miripGuru?->nama_lengkap ?? $w->miripGuru?->name }} ({{ round($w->mirip_skor) }}%)</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(($perangkatMenunggu ?? collect())->isNotEmpty() || ($perangkatAktif ?? collect())->isNotEmpty())
            <div class="border-t border-gray-100 pt-3">
                <h3 class="font-bold text-gray-900">Perangkat absen (satu guru satu HP)</h3>
                <p class="text-xs text-gray-500 mt-0.5">Absen hanya bisa dari HP terdaftar supaya tidak bisa dititipkan. Setujui HP baru hanya bila guru benar mengganti perangkat.</p>
            </div>
            @foreach($perangkatMenunggu as $p)
                <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-3">
                    <span class="material-symbols-outlined text-amber-500">smartphone</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-gray-900">{{ $p->guru?->nama_lengkap ?? $p->guru?->name }}</p>
                        <p class="text-xs text-gray-500">{{ $p->nama }} · {{ $p->versi_os }} · diajukan {{ $p->created_at?->timezone(config('presensi_guru.timezone'))->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('presensi-guru.perangkat.setujui', $p) }}">
                            @csrf
                            <button type="submit" class="btn btn-success px-4 h-10 rounded-lg text-sm">Setujui</button>
                        </form>
                        <form method="POST" action="{{ route('presensi-guru.perangkat.tolak', $p) }}">
                            @csrf
                            <button type="submit" class="btn btn-alert px-4 h-10 rounded-lg text-sm">Tolak</button>
                        </form>
                    </div>
                </div>
            @endforeach
            @foreach($perangkatAktif as $p)
                <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 pt-3">
                    <span class="material-symbols-outlined text-emerald-600">verified_user</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-gray-900">{{ $p->guru?->nama_lengkap ?? $p->guru?->name }}</p>
                        <p class="text-xs text-gray-500">{{ $p->nama }} · terakhir dipakai {{ $p->terakhir_dipakai?->timezone(config('presensi_guru.timezone'))->translatedFormat('d M Y, H:i') ?? '-' }}</p>
                    </div>
                    <form method="POST" action="{{ route('presensi-guru.perangkat.cabut', $p) }}" onsubmit="return confirm('Cabut perangkat ini? Guru tidak bisa absen sampai mengajukan HP baru.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline px-4 h-10 rounded-lg text-sm">Cabut</button>
                    </form>
                </div>
            @endforeach
        @endif
    </div>
@endif

@if($selectedGuru)
    @php $wajah = $selectedGuru->wajah; @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <form method="POST" action="{{ route('presensi-guru.manual') }}" class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-4 md:p-6 space-y-4">
            @csrf
            <input type="hidden" name="guru_id" value="{{ $selectedGuru->id }}">
            <input type="hidden" name="rentang" value="{{ $rentang }}">
            <div>
                <h3 class="font-bold text-gray-900">Koreksi absen manual</h3>
                <p class="text-xs text-gray-500 mt-0.5">Untuk guru yang HP-nya bermasalah, wajah gagal terbaca, atau lupa absen. Menimpa catatan pada tanggal itu dan ditandai "diubah oleh admin".</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label for="m-tanggal" class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tanggal</label>
                    <input id="m-tanggal" name="tanggal" type="date" required max="{{ now(config('presensi_guru.timezone'))->toDateString() }}" value="{{ old('tanggal') }}" class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
                </div>
                <div>
                    <label for="m-status" class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Status</label>
                    <select id="m-status" name="status" class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
                        @foreach(\App\Models\PresensiGuru::STATUS as $st)<option value="{{ $st }}" @selected(old('status', 'Hadir') === $st)>{{ $st }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="m-masuk" class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Jam masuk</label>
                    <input id="m-masuk" name="jam_masuk" type="time" value="{{ old('jam_masuk') }}" class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
                </div>
                <div>
                    <label for="m-pulang" class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Jam pulang</label>
                    <input id="m-pulang" name="jam_pulang" type="time" value="{{ old('jam_pulang') }}" class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
                </div>
            </div>
            <div>
                <label for="m-ket" class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Alasan koreksi</label>
                <input id="m-ket" name="keterangan" type="text" required maxlength="200" value="{{ old('keterangan') }}" placeholder="Contoh: HP rusak, hadir sesuai buku tamu" class="w-full border border-gray-200 rounded-lg text-sm h-10 px-3">
            </div>
            <button type="submit" class="btn btn-primary h-10 px-5 rounded-lg text-sm font-bold">Simpan Koreksi</button>
        </form>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 md:p-6">
            <h3 class="font-bold text-gray-900">Data wajah</h3>
            @if($wajah)
                <p class="text-sm text-green-700 font-semibold mt-2 flex items-center gap-1"><span class="material-symbols-outlined text-base">verified_user</span> Terdaftar</p>
                <p class="text-xs text-gray-500 mt-1">Sejak {{ $wajah->didaftarkan_pada->translatedFormat('d M Y, H:i') }}. Template disimpan terenkripsi dan tidak bisa dilihat di web.</p>
                <form method="POST" action="{{ route('presensi-guru.wajah.reset', $selectedGuru) }}" class="mt-4" onsubmit="return confirm('Hapus data wajah? Acuan wajah dibuat ulang dari foto profil saat guru membuka Presensi Guru.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-warning px-4 h-10 rounded-lg text-sm">Reset data wajah</button>
                </form>
            @else
                <p class="text-sm text-amber-700 font-semibold mt-2 flex items-center gap-1"><span class="material-symbols-outlined text-base">face_retouching_off</span> Belum terdaftar</p>
                <p class="text-xs text-gray-500 mt-1">Acuan wajah dibuat otomatis dari foto profil yang disetujui saat guru membuka Presensi Guru di aplikasi.</p>
            @endif
        </div>
    </div>
@endif
@endsection
