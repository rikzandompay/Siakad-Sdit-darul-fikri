@extends('layouts.app')
@section('title', 'Akun Wali Murid')
@section('page-title', 'Akun Wali')

@section('content')
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h2 class="font-bold text-2xl text-blue-600">Akun Wali Murid</h2>
            <p class="text-gray-500 text-sm mt-1">Dibuat otomatis dari Data Siswa. Wali login di aplikasi MyDarfik dengan
                <strong>NIS siswa</strong> dan <strong>tanggal lahir siswa</strong> format YY-MM-DD (mis. 16-01-25).</p>
        </div>
        <form method="POST" action="{{ route('wali.sinkron') }}"
            onsubmit="return confirm('Sinkronkan akun wali dengan data siswa sekarang?')">
            @csrf
            <button type="submit" class="btn btn-secondary inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm">
                <span class="material-symbols-outlined text-sm">sync</span> Sinkronkan dari Data Siswa
            </button>
        </form>
    </div>

    <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-lg px-4 py-3 flex items-start gap-2">
        <span class="material-symbols-outlined text-base">info</span>
        <span>Siswa baru otomatis punya akun saat datanya disimpan. Untuk mengganti NIS atau tanggal lahir, ubah di <a
                href="{{ route('siswa.index') }}" class="font-bold underline">Data Siswa</a>; akunnya ikut berubah. Akun
            nonaktif otomatis untuk siswa yang lulus atau pindah.</span>
    </div>

    @if ($siswaTanpaNamaWali->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 text-amber-900 text-sm rounded-lg px-4 py-3">
            <p class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-base">warning</span>
                {{ $siswaTanpaNamaWali->count() }} siswa aktif belum punya akun karena tanggal lahir kosong</p>
            <p class="mt-1 text-amber-800">
                {{ $siswaTanpaNamaWali->take(8)->map(fn($s) => $s->nama_siswa . ' (' . ($s->kelas?->nama_kelas ?? '-') . ')')->join(', ') }}{{ $siswaTanpaNamaWali->count() > 8 ? ', dan ' . ($siswaTanpaNamaWali->count() - 8) . ' lainnya' : '' }}.
                Lengkapi tanggal lahir di Data Siswa.</p>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <form method="GET" action="{{ route('wali.index') }}" class="p-4 border-b border-gray-100">
            <div class="relative md:w-80">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">search</span>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Cari nama wali, NIS, atau nama siswa" aria-label="Cari akun wali"
                    class="w-full pl-9 pr-3 h-10 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-4 md:px-6 py-3 border-b">Login: NIS</th>
                        <th class="px-4 md:px-6 py-3 border-b">Nama wali</th>
                        <th class="px-4 md:px-6 py-3 border-b">Siswa</th>
                        <th class="px-4 md:px-6 py-3 border-b hidden md:table-cell">Terakhir login</th>
                        <th class="px-4 md:px-6 py-3 border-b text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($waliList as $w)
                        <tr>
                            <td class="px-4 md:px-6 py-3 font-mono font-semibold text-gray-900">{{ $w->username }}</td>
                            <td class="px-4 md:px-6 py-3 text-gray-800">{{ $w->nama }}@if ($w->no_hp)
                                    <div class="text-xs text-gray-500">{{ $w->no_hp }}</div>
                                @endif
                            </td>
                            <td class="px-4 md:px-6 py-3 text-gray-700">
                                @foreach ($w->siswa as $s)
                                    <div>{{ $s->nama_siswa }} <span
                                            class="text-xs text-gray-400">({{ $s->kelas?->nama_kelas ?? '-' }})</span>
                                    </div>
                                @endforeach
                            </td>
                            <td class="px-4 md:px-6 py-3 text-gray-500 hidden md:table-cell">
                                {{ $w->terakhir_login_pada?->diffForHumans() ?? 'Belum pernah' }}</td>
                            <td class="px-4 md:px-6 py-3 text-center">
                                <span
                                    class="text-[11px] font-bold px-2 py-0.5 rounded {{ $w->isAktif() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $w->status }}</span>
                                @if ($terkunci->has($w->id))
                                    <form method="POST" action="{{ route('wali.buka-kunci', $w) }}" class="mt-1.5"
                                        onsubmit="return confirm('Buka kunci login untuk NIS {{ $w->username }}? Pastikan yang meminta memang wali siswa ini.')">
                                        @csrf
                                        <p class="text-[11px] text-red-700 font-semibold">Terkunci
                                            {{ \App\Support\KunciLogin::teks($terkunci[$w->id]) }}</p>
                                        <button type="submit"
                                            class="btn btn-warning mt-1 px-2.5 py-1 rounded-md text-xs">Buka kunci</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">Belum ada akun wali. Akun
                                    terbentuk otomatis saat data siswa (dengan nama wali) disimpan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $waliList->links() }}
    @endsection
