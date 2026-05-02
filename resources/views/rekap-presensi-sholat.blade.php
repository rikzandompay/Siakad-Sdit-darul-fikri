@extends('layouts.app')
@section('title', 'Rekap Presensi Sholat')
@section('page-title', 'Rekap Presensi Sholat')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Rekapitulasi Kehadiran Sholat Siswa</h1>
                <p class="text-gray-500 text-sm mt-1">Laporan akumulasi kehadiran sholat siswa per bulan</p>
            </div>
        </div>

        <!-- Toggle Jenis Sholat -->
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('presensi-sholat.rekap', array_merge(request()->query(), ['jenis' => 'Zuhur'])) }}"
               class="px-5 py-2.5 rounded-lg text-sm font-bold transition-colors {{ $jenisSholat == 'Zuhur' ? 'bg-emerald-900 text-white shadow-md' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                <span class="material-symbols-outlined text-sm align-middle mr-1">mosque</span>
                Sholat Zuhur
            </a>
            <a href="{{ route('presensi-sholat.rekap', array_merge(request()->query(), ['jenis' => 'Dhuha'])) }}"
               class="px-5 py-2.5 rounded-lg text-sm font-bold transition-colors {{ $jenisSholat == 'Dhuha' ? 'bg-emerald-900 text-white shadow-md' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                <span class="material-symbols-outlined text-sm align-middle mr-1">wb_sunny</span>
                Sholat Dhuha
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('presensi-sholat.rekap') }}"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <input type="hidden" name="jenis" value="{{ $jenisSholat }}">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Pilih Kelas</label>
                    <select name="kelas_id"
                        class="w-full border border-gray-200 text-sm rounded-lg px-4 py-2.5 bg-gray-50 uppercase shadow-sm"
                        onchange="this.form.submit()" required>
                        <option value="">-- Pilih Kelas --</option>
                        @foreach ($kelasList as $kelas)
                            <option value="{{ $kelas->id }}"
                                {{ $selectedKelas && $selectedKelas->id == $kelas->id ? 'selected' : '' }}>
                                {{ $kelas->nama_kelas }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Pilih Bulan</label>
                    <select name="bulan"
                        class="w-full border border-gray-200 text-sm rounded-lg px-4 py-2.5 bg-gray-50 uppercase shadow-sm">
                        @foreach (['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $key => $namaBulan)
                            <option value="{{ $key + 1 }}" {{ $bulan == $key + 1 ? 'selected' : '' }}>
                                {{ $namaBulan }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Pilih Tahun</label>
                    <select name="tahun"
                        class="w-full border border-gray-200 text-sm rounded-lg px-4 py-2.5 bg-gray-50 uppercase shadow-sm">
                        @for ($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="w-full bg-emerald-900 text-white rounded-lg px-6 py-2.5 text-sm font-bold shadow-sm hover:bg-emerald-800 transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">search</span>
                        Tampilkan Rekap
                    </button>
                </div>
            </form>
        </div>

        @if ($selectedKelas)
            <!-- Rekap Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div>
                        <h3 class="font-bold text-gray-900">
                            Data Rekap - {{ $selectedKelas->nama_kelas }} (Sholat {{ $jenisSholat }})
                        </h3>
                        <div class="text-sm text-gray-500 mt-1">
                            Periode: <strong
                                class="text-gray-900">{{ \Carbon\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y') }}</strong>
                        </div>
                    </div>
                    
                    <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
                        <div class="flex gap-2 w-full md:w-auto">
                            <a href="{{ route('presensi-sholat.rekap.export.pdf', request()->all()) }}" target="_blank"
                                class="flex-1 md:flex-none flex items-center justify-center gap-1.5 bg-red-50 text-red-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-100 transition-colors border border-red-100">
                                <span class="material-symbols-outlined text-sm">picture_as_pdf</span> PDF
                            </a>
                            <a href="{{ route('presensi-sholat.rekap.export.csv', request()->all()) }}" target="_blank"
                                class="flex-1 md:flex-none flex items-center justify-center gap-1.5 bg-emerald-50 text-emerald-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-100 transition-colors border border-emerald-100">
                                <span class="material-symbols-outlined text-sm">csv</span> CSV
                            </a>
                        </div>
                        <div class="relative w-full md:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-gray-400 text-sm">search</span>
                            </div>
                            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Cari Nama / NIS..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-emerald-500 transition-all outline-none">
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-emerald-900 text-white">
                            <tr>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider w-16">No</th>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider">NIS</th>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider">Nama Lengkap</th>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider text-center" title="Hadir">Hadir
                                </th>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider text-center" title="Sakit">Sakit
                                </th>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider text-center" title="Izin">Izin
                                </th>
                                <th class="px-6 py-4 font-bold uppercase tracking-wider text-center" title="Alfa">Alfa
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($rekapData as $data)
                                <tr class="hover:bg-emerald-50/50 transition-colors">
                                    <td class="px-6 py-4 text-gray-500">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-600">{{ $data['siswa']->nis }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">{{ $data['siswa']->nama_siswa }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-emerald-600 bg-emerald-50/30">
                                        {{ $data['summary']['H'] }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-amber-500 bg-amber-50/30">
                                        {{ $data['summary']['S'] }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-blue-600 bg-blue-50/30">
                                        {{ $data['summary']['I'] }}
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-red-500 bg-red-50/30">
                                        {{ $data['summary']['A'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="inline-flex flex-col items-center justify-center space-y-3">
                                            <div
                                                class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                                                <span
                                                    class="material-symbols-outlined text-3xl text-gray-400">group_off</span>
                                            </div>
                                            <p class="font-medium text-gray-500">Belum ada siswa di kelas ini</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-8 text-center text-emerald-800">
                <span class="material-symbols-outlined text-4xl mb-3 opacity-50">touch_app</span>
                <h3 class="text-lg font-bold">Pilih Kelas</h3>
                <p class="text-emerald-700/70 mt-1">Silakan pilih kelas, bulan, dan tahun pada filter di atas untuk melihat
                    rekap kehadiran sholat siswa.</p>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    function filterTable() {
        // Declare variables
        var input, filter, table, tr, tdNis, tdNama, i, txtValueNis, txtValueNama;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        table = document.querySelector("table tbody");
        tr = table.getElementsByTagName("tr");

        // Loop through all table rows, and hide those who don't match the search query
        for (i = 0; i < tr.length; i++) {
            // First row might be the "empty table" message, ignore if it has colspan
            if (tr[i].getElementsByTagName("td")[0].hasAttribute("colspan")) continue;

            tdNis = tr[i].getElementsByTagName("td")[1];
            tdNama = tr[i].getElementsByTagName("td")[2];
            
            if (tdNis || tdNama) {
                txtValueNis = tdNis.textContent || tdNis.innerText;
                txtValueNama = tdNama.textContent || tdNama.innerText;
                if (txtValueNis.toUpperCase().indexOf(filter) > -1 || txtValueNama.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
</script>
@endpush
