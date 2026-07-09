@extends('layouts.app')
@section('title', 'Rekap Presensi')
@section('page-title', 'Rekap Presensi')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Rekapitulasi Kehadiran Siswa</h1>
                <p class="text-gray-500 text-sm mt-1">Laporan akumulasi kehadiran siswa berdasarkan rentang waktu</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('presensi.rekap') }}"
                class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Pilih Kelas</label>
                    <select name="kelas_id"
                        class="w-full border border-gray-200 text-sm rounded-lg px-4 py-2.5 bg-gray-50 uppercase shadow-sm mr-2"
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
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Mata Pelajaran</label>
                    <select name="pelajaran_id"
                        class="w-full border border-gray-200 text-sm rounded-lg px-4 py-2.5 bg-gray-50 shadow-sm">
                        <option value="">-- Semua Mata Pelajaran --</option>
                        @foreach ($mapelList as $mapel)
                            <option value="{{ $mapel->id }}"
                                {{ isset($selectedPelajaranId) && $selectedPelajaranId == $mapel->id ? 'selected' : '' }}>
                                {{ $mapel->nama_pelajaran }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Rentang Waktu</label>
                    <select name="rentang"
                        class="w-full border border-gray-200 text-sm rounded-lg px-4 py-2.5 bg-gray-50 uppercase shadow-sm">
                        <option value="hari_ini" {{ $rentang == 'hari_ini' ? 'selected' : '' }}>Hari Ini</option>
                        <option value="minggu_ini" {{ $rentang == 'minggu_ini' ? 'selected' : '' }}>Minggu Ini</option>
                        <option value="bulan_ini" {{ $rentang == 'bulan_ini' ? 'selected' : '' }}>Bulan Ini</option>
                        <option value="semester_ini" {{ $rentang == 'semester_ini' ? 'selected' : '' }}>Semester Ini</option>
                    </select>
                </div>
                <div>
                    <button type="submit"
                        class="w-full bg-blue-500 text-gray-900 rounded-lg px-6 py-2.5 text-sm font-bold shadow-sm hover:bg-blue-500 transition-colors flex items-center justify-center gap-2">
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
                            Data Rekap - {{ $selectedKelas->nama_kelas }}
                            @if(isset($selectedPelajaranId) && $selectedPelajaranId && $mapelList->firstWhere('id', $selectedPelajaranId))
                                ({{ $mapelList->firstWhere('id', $selectedPelajaranId)->nama_pelajaran }})
                            @endif
                        </h3>
                        <div class="text-sm text-gray-500 mt-1">
                            Periode: <strong class="text-gray-900">{{ $periodeLabel }}</strong>
                        </div>
                    </div>
                    
                    <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
                        <div class="flex gap-2 w-full md:w-auto">
                            <a href="{{ route('presensi.rekap.export.pdf', request()->all()) }}" target="_blank"
                                class="flex-1 md:flex-none flex items-center justify-center gap-1.5 bg-red-50 text-red-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-100 transition-colors border border-red-100">
                                <span class="material-symbols-outlined text-sm">picture_as_pdf</span> PDF
                            </a>
                            <a href="{{ route('presensi.rekap.export.csv', request()->all()) }}" target="_blank"
                                class="flex-1 md:flex-none flex items-center justify-center gap-1.5 bg-blue-50 text-blue-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-500 transition-colors border border-blue-200">
                                <span class="material-symbols-outlined text-sm">csv</span> CSV
                            </a>
                        </div>
                        <div class="relative w-full md:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="material-symbols-outlined text-gray-400 text-sm">search</span>
                            </div>
                            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Cari Nama / NIS..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:bg-white focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-blue-500 text-gray-900">
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
                                <tr class="hover:bg-blue-50/50 transition-colors">
                                    <td class="px-6 py-4 text-gray-500">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-600">{{ $data['siswa']->nis }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">{{ $data['siswa']->nama_siswa }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-blue-600 bg-blue-50/30">
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
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-8 text-center text-blue-600">
                <span class="material-symbols-outlined text-4xl mb-3 opacity-50">touch_app</span>
                <h3 class="text-lg font-bold">Pilih Kelas</h3>
                <p class="text-blue-600/70 mt-1">Silakan pilih kelas, bulan, dan tahun pada filter di atas untuk melihat
                    rekap kehadiran siswa.</p>
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
