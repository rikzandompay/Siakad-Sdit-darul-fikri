@extends('layouts.app')
@section('title', 'Izin Siswa')
@section('page-title', 'Izin Siswa')

@section('content')
<div>
    <h2 class="font-bold text-2xl text-blue-600">Izin Siswa</h2>
    <p class="text-gray-500 text-sm mt-1">Diajukan wali murid lewat aplikasi MyDarfik. Izin yang disetujui otomatis tercatat di presensi kelas (Sakit atau Izin) pada hari sekolah dalam rentang izin.</p>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex gap-1 bg-gray-100 rounded-lg p-1 w-fit">
            @foreach(['Menunggu' => 'Menunggu', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak', 'semua' => 'Semua'] as $value => $label)
                <a href="{{ route('izin.index', array_filter(['status' => $value, 'search' => $search])) }}"
                    class="px-3 md:px-4 py-1.5 rounded-md text-sm font-semibold {{ $status === $value ? 'bg-white text-blue-700 shadow-sm' : 'text-gray-500 hover:text-gray-800' }}">
                    {{ $label }}@if($value === 'Menunggu' && $jumlahMenunggu > 0) <span class="ml-1 text-[11px] bg-red-600 text-white rounded-full px-1.5">{{ $jumlahMenunggu }}</span>@endif
                </a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('izin.index') }}" class="relative md:w-72">
            <input type="hidden" name="status" value="{{ $status }}">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">search</span>
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama siswa" aria-label="Cari nama siswa"
                class="w-full pl-9 pr-3 h-10 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none">
        </form>
    </div>

    <ul class="divide-y divide-gray-100">
        @forelse($izinList as $izin)
            <li class="p-4 md:p-6">
                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-bold text-gray-900">{{ $izin->siswa?->nama_siswa }}</span>
                            <span class="text-xs text-gray-500">{{ $izin->siswa?->kelas?->nama_kelas ?? '-' }}</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded {{ $izin->jenis === 'Sakit' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">{{ $izin->jenis }}</span>
                            @if($izin->status !== 'Menunggu')
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded {{ $izin->status === 'Disetujui' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700' }}">{{ $izin->status }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $izin->tanggal_mulai->translatedFormat('d M Y') }}@if(! $izin->tanggal_mulai->isSameDay($izin->tanggal_selesai)) – {{ $izin->tanggal_selesai->translatedFormat('d M Y') }}@endif
                            · {{ $izin->jumlahHari() }} hari
                            @if($izin->wali) · diajukan {{ $izin->wali->nama }} @endif
                        </p>
                        <p class="text-sm text-gray-800 mt-2 whitespace-pre-line break-words">{{ $izin->alasan }}</p>
                        @if($izin->status !== 'Menunggu')
                            <p class="text-xs text-gray-500 mt-2">Ditinjau {{ $izin->peninjau?->nama_lengkap ?? '-' }} · {{ $izin->ditinjau_pada?->diffForHumans() }}@if($izin->catatan_peninjau) — “{{ $izin->catatan_peninjau }}”@endif</p>
                        @endif
                    </div>

                    @if($izin->status === 'Menunggu')
                        <div class="flex flex-col sm:flex-row gap-2 md:w-[28rem] shrink-0">
                            <form method="POST" action="{{ route('izin.approve', $izin) }}" class="sm:flex-none">
                                @csrf
                                <button type="submit" class="btn btn-primary w-full h-10 px-4 rounded-lg text-sm font-bold flex items-center justify-center gap-1.5 whitespace-nowrap">
                                    <span class="material-symbols-outlined text-sm">check</span> Setujui
                                </button>
                            </form>
                            <form method="POST" action="{{ route('izin.reject', $izin) }}" class="sm:flex-1 flex gap-1.5">
                                @csrf
                                <label for="catatan-{{ $izin->id }}" class="sr-only">Alasan penolakan</label>
                                <input id="catatan-{{ $izin->id }}" name="catatan" type="text" required maxlength="255" placeholder="Alasan jika ditolak"
                                    class="min-w-0 flex-1 border border-gray-200 rounded-lg text-sm h-10 px-3">
                                <button type="submit" class="btn btn-danger h-10 px-3 rounded-lg text-sm font-bold">Tolak</button>
                            </form>
                        </div>
                    @endif
                </div>
            </li>
        @empty
            <li class="p-12 text-center">
                <span class="material-symbols-outlined text-5xl text-gray-300">mail</span>
                <p class="font-medium text-gray-600 mt-2">Tidak ada izin {{ $status === 'semua' ? '' : strtolower($status) }}</p>
            </li>
        @endforelse
    </ul>
</div>
{{ $izinList->links() }}
@endsection
