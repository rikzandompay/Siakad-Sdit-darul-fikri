@extends('layouts.app')
@section('title', 'Pengampu Konseling')
@section('page-title', 'Konseling')

@section('content')
<div>
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
        <a href="{{ route('konseling.index') }}" class="hover:text-blue-600">Konseling</a>
        <span class="material-symbols-outlined text-xs">chevron_right</span>
        <span class="text-blue-600 font-medium">Pengampu</span>
    </div>
    <h2 class="font-bold text-2xl text-blue-600">Pengampu Konseling</h2>
    <p class="text-gray-500 text-sm mt-1">Tetapkan satu guru untuk tiap kelas. Wali murid di aplikasi MyDarfik otomatis terhubung ke pengampu kelas anaknya. Satu guru boleh mengampu beberapa kelas.</p>
</div>

<form method="POST" action="{{ route('konseling.pengampu.update') }}" class="space-y-4">
    @csrf @method('PUT')
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="px-4 md:px-6 py-3 border-b">Kelas</th>
                        <th class="px-4 md:px-6 py-3 border-b hidden md:table-cell">Wali Kelas</th>
                        <th class="px-4 md:px-6 py-3 border-b text-center hidden sm:table-cell">Siswa</th>
                        <th class="px-4 md:px-6 py-3 border-b">Pengampu Konseling</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($kelasList as $kelas)
                        <tr>
                            <td class="px-4 md:px-6 py-3 font-bold text-gray-900">{{ $kelas->nama_kelas }}</td>
                            <td class="px-4 md:px-6 py-3 text-gray-600 hidden md:table-cell">{{ $kelas->waliKelas?->nama_lengkap ?? '-' }}</td>
                            <td class="px-4 md:px-6 py-3 text-center text-gray-600 hidden sm:table-cell">{{ $kelas->siswa_aktif_count }}</td>
                            <td class="px-4 md:px-6 py-3">
                                <label for="pengampu-{{ $kelas->id }}" class="sr-only">Pengampu konseling {{ $kelas->nama_kelas }}</label>
                                <select id="pengampu-{{ $kelas->id }}" name="pengampu[{{ $kelas->id }}]"
                                    class="w-full min-w-48 border rounded-lg text-sm h-10 px-3 {{ $kelas->pengampu_konseling_id ? 'border-gray-200' : 'border-amber-300 bg-amber-50' }}">
                                    <option value="">— Belum ada —</option>
                                    @foreach($guruList as $guru)
                                        <option value="{{ $guru->id }}" @selected($kelas->pengampu_konseling_id === $guru->id)>
                                            {{ $guru->nama_lengkap ?? $guru->name }}{{ $kelas->wali_kelas_id === $guru->id ? ' (wali kelas)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-gray-400">Belum ada kelas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-xs text-gray-500">Jika pengampu diganti, percakapan lama kelas itu ikut berpindah ke pengampu baru.</p>
    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary px-6 py-2.5 rounded-lg text-sm font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">save</span> Simpan Pengampu
        </button>
    </div>
</form>
@endsection
