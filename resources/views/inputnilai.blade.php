@extends('layouts.app')
@section('title', 'Input Nilai Rapot')
@section('page-title', 'Nilai Rapot')

@section('content')
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div class="space-y-1">
            <p class="text-amber-600 font-bold text-xs uppercase tracking-wider">Evaluasi Belajar</p>
            <h2 class="font-bold text-2xl text-blue-600">Input Nilai Siswa</h2>
        </div>
        @if ($siswaList->count() > 0 && $kelasId && $pelajaranId)
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('nilai.export.pdf', ['tahun_ajaran_id' => $tahunAjaranId, 'kelas_id' => $kelasId, 'pelajaran_id' => $pelajaranId]) }}"
                    target="_blank"
                    class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-sm text-red-500">picture_as_pdf</span>
                    Export PDF
                </a>
                <a href="{{ route('nilai.export.csv', ['tahun_ajaran_id' => $tahunAjaranId, 'kelas_id' => $kelasId, 'pelajaran_id' => $pelajaranId]) }}"
                    class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-sm text-blue-600">table_chart</span>
                    Export CSV
                </a>
            </div>
        @endif
    </div>

    <!-- Filter Section -->
    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm mt-6">
        <form method="GET" action="{{ route('nilai.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Tahun Ajaran</label>
                <select name="tahun_ajaran_id"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500/20">
                    @foreach ($tahunAjaranList as $ta)
                        <option value="{{ $ta->id }}" {{ $tahunAjaranId == $ta->id ? 'selected' : '' }}>
                            {{ $ta->nama_semester }} {{ $ta->status_aktif == 'Y' ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Kelas</label>
                <select name="kelas_id"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500/20"
                    onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    @foreach ($kelasList as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Mata Pelajaran</label>
                <select name="pelajaran_id"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500/20">
                    <option value="">-- Pilih Mapel --</option>
                    @foreach ($mapelList as $m)
                        <option value="{{ $m->id }}" {{ $pelajaranId == $m->id ? 'selected' : '' }}>
                            {{ $m->nama_pelajaran }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit"
                    class="w-full bg-blue-500 text-gray-900 px-4 py-2.5 rounded-lg font-bold text-sm hover:bg-blue-500 transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">filter_list</span>
                    Tampilkan Siswa
                </button>
            </div>
        </form>
    </div>

    @if ($siswaList->count() > 0)
        <!-- Info Bar -->
        @if ($selectedKelas && $selectedMapel)
            <div class="flex items-center gap-2 flex-wrap mt-6">
                <span class="text-xs text-gray-500 font-medium">Menampilkan:</span>
                <span
                    class="px-2 py-1 bg-blue-100 text-blue-700 text-[11px] rounded-full font-medium">{{ $selectedKelas->nama_kelas }}</span>
                <span
                    class="px-2 py-1 bg-amber-100 text-amber-800 text-[11px] rounded-full font-medium">{{ $selectedMapel->nama_pelajaran }}</span>
            </div>
        @endif

        <!-- Grade Input Table - Spreadsheet Style -->
        <form method="POST" action="{{ route('nilai.store') }}" id="nilaiForm">
            @csrf
            <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
            <input type="hidden" name="pelajaran_id" value="{{ $pelajaranId }}">

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mt-4">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-900 to-blue-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-white">table</span>
                        <h3 class="font-bold text-white">Spreadsheet Penilaian Rapot</h3>
                    </div>
                    <span class="text-sm text-blue-600">{{ $siswaList->count() }} Siswa</span>
                </div>

                <!-- Horizontal Scroll Container -->
                <div class="overflow-x-auto max-h-[70vh] sticky-header-container">
                    <table class="border-collapse min-w-max" id="nilaiTable">
                        <thead class="sticky top-0 z-20">
                            <!-- Header Row 1 - Main Categories -->
                            <tr class="bg-gray-100">
                                <th rowspan="2"
                                    class="sticky left-0 z-30 bg-gray-100 px-4 py-3 text-xs font-bold text-gray-600 uppercase border-b-2 border-r-2 border-gray-300 min-w-[180px] text-center">
                                    Nama Siswa
                                </th>
                                <!-- Formatif Header - Colspan 25 (5 cols × 4 BAB + 1 rata-rata per BAB) -->
                                <th colspan="24"
                                    class="px-3 py-3 text-xs font-bold text-white uppercase border-b-2 border-r-2 border-gray-300 text-center bg-gradient-to-r from-blue-600 to-blue-500">
                                    FORMATIF (50%)
                                </th>
                                <th rowspan="2"
                                    class="px-3 py-2 text-xs font-bold text-white uppercase border-b-2 border-r-2 border-gray-300 text-center bg-gradient-to-r from-blue-600 to-blue-500 min-w-[60px]">
                                    Formatif Total
                                </th>
                                <th rowspan="2"
                                    class="px-3 py-2 text-xs font-bold text-white uppercase border-b-2 border-r-2 border-gray-300 text-center bg-gradient-to-r from-purple-600 to-purple-500 min-w-[60px]">
                                    SAS (30%)
                                </th>
                                <th rowspan="2"
                                    class="px-3 py-2 text-xs font-bold text-white uppercase border-b-2 border-r-2 border-gray-300 text-center bg-gradient-to-r from-amber-500 to-amber-400 min-w-[60px]">
                                    Kehadiran
                                </th>
                                <th colspan="2"
                                    class="px-3 py-2 text-xs font-bold text-white uppercase border-b-2 border-r-2 border-gray-300 text-center bg-gradient-to-r from-red-500 to-red-400">
                                    Pengurang
                                </th>
                                <th rowspan="2"
                                    class="px-3 py-2 text-xs font-bold text-white uppercase border-b-2 border-gray-300 text-center bg-gradient-to-r from-gray-800 to-gray-700 min-w-[80px]">
                                    Nilai Rapor
                                </th>
                                <th rowspan="2"
                                    class="px-3 py-2 text-xs font-bold text-gray-700 uppercase border-b-2 border-gray-300 text-center bg-gray-100 min-w-[70px]">
                                    Predikat
                                </th>
                            </tr>

                            <!-- Header Row 2 - BAB Sub-headers -->
                            <tr class="bg-gray-50">
                                <!-- BAB 1 -->
                                <th colspan="5"
                                    class="px-2 py-2 text-[10px] font-bold text-blue-600 uppercase border-b border-r border-gray-300 text-center bg-blue-50">
                                    BAB 1
                                </th>
                                <th
                                    class="px-1 py-2 text-[9px] font-bold text-blue-700 uppercase border-b border-r border-gray-300 text-center bg-blue-100 min-w-[50px]">
                                    Rata²
                                </th>
                                <!-- BAB 2 -->
                                <th colspan="5"
                                    class="px-2 py-2 text-[10px] font-bold text-blue-600 uppercase border-b border-r border-gray-300 text-center bg-blue-50">
                                    BAB 2
                                </th>
                                <th
                                    class="px-1 py-2 text-[9px] font-bold text-blue-700 uppercase border-b border-r border-gray-300 text-center bg-blue-100 min-w-[50px]">
                                    Rata²
                                </th>
                                <!-- BAB 3 -->
                                <th colspan="5"
                                    class="px-2 py-2 text-[10px] font-bold text-blue-600 uppercase border-b border-r border-gray-300 text-center bg-blue-50">
                                    BAB 3
                                </th>
                                <th
                                    class="px-1 py-2 text-[9px] font-bold text-blue-700 uppercase border-b border-r border-gray-300 text-center bg-blue-100 min-w-[50px]">
                                    Rata²
                                </th>
                                <!-- BAB 4 -->
                                <th colspan="5"
                                    class="px-2 py-2 text-[10px] font-bold text-blue-600 uppercase border-b border-r border-gray-300 text-center bg-blue-50">
                                    BAB 4
                                </th>
                                <th
                                    class="px-1 py-2 text-[9px] font-bold text-blue-700 uppercase border-b border-r border-gray-300 text-center bg-blue-100 min-w-[50px]">
                                    Rata²
                                </th>
                                <!-- Pengurang Sub-headers -->
                                <th
                                    class="px-2 py-2 text-[9px] font-bold text-red-700 uppercase border-b border-r border-gray-300 text-center bg-red-50 min-w-[50px]">
                                    Tidak/Telat
                                </th>
                                <th
                                    class="px-2 py-2 text-[9px] font-bold text-red-700 uppercase border-b border-r border-gray-300 text-center bg-red-50 min-w-[50px]">
                                    Menyontek
                                </th>
                            </tr>

                            <!-- Header Row 3 - Component Labels -->
                            <tr class="bg-gray-100">
                                <th class="sticky left-0 z-30 bg-gray-100 border-b border-r border-gray-300"></th>
                                @for ($bab = 1; $bab <= 4; $bab++)
                                    <th
                                        class="px-1 py-1 text-[8px] font-bold text-gray-600 uppercase border-b border-r border-gray-200 text-center min-w-[40px]">
                                        TP 1</th>
                                    <th
                                        class="px-1 py-1 text-[8px] font-bold text-gray-600 uppercase border-b border-r border-gray-200 text-center min-w-[40px]">
                                        TP 2</th>
                                    <th
                                        class="px-1 py-1 text-[8px] font-bold text-gray-600 uppercase border-b border-r border-gray-200 text-center min-w-[40px]">
                                        TP 3</th>
                                    <th
                                        class="px-1 py-1 text-[8px] font-bold text-gray-600 uppercase border-b border-r border-gray-200 text-center min-w-[40px]">
                                        TP 4</th>
                                    <th
                                        class="px-1 py-1 text-[8px] font-bold text-gray-600 uppercase border-b border-r border-gray-200 text-center min-w-[40px]">
                                        UH</th>
                                    <th
                                        class="px-1 py-1 text-[8px] font-bold text-blue-600 uppercase border-b border-r border-gray-200 text-center bg-blue-50 min-w-[50px]">
                                        BAB {{ $bab }}</th>
                                @endfor
                                <th
                                    class="px-1 py-1 text-[8px] font-bold text-blue-600 uppercase border-b border-r border-gray-200 text-center bg-blue-50">
                                    Total</th>
                                <th
                                    class="px-1 py-1 text-[8px] font-bold text-purple-600 uppercase border-b border-r border-gray-200 text-center bg-purple-50">
                                    SAS</th>
                                <th
                                    class="px-1 py-1 text-[8px] font-bold text-amber-600 uppercase border-b border-r border-gray-200 text-center bg-amber-50">
                                    Poin</th>
                                <th
                                    class="px-1 py-1 text-[8px] font-bold text-red-600 uppercase border-b border-r border-gray-200 text-center bg-red-50">
                                    -</th>
                                <th
                                    class="px-1 py-1 text-[8px] font-bold text-red-600 uppercase border-b border-r border-gray-200 text-center bg-red-50">
                                    -</th>
                                <th
                                    class="px-1 py-1 text-[8px] font-bold text-gray-700 uppercase border-b border-gray-200 text-center bg-gray-50">
                                    Akhir</th>
                                <th
                                    class="px-1 py-1 text-[8px] font-bold text-gray-600 uppercase border-b border-gray-200 text-center bg-gray-100">
                                    Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($siswaList as $index => $s)
                                @php
                                    $existing = $nilaiMap[$s->id] ?? null;
                                    $formatifData =
                                        $existing['formatif_data'] ?? \App\Models\NilaiRapot::getDefaultFormatifData();
                                    $sas = $existing['sas'] ?? 0;
                                    $kehadiran = $existing['kehadiran'] ?? 0;
                                    $pengurangTidaktelat = $existing['pengurang_tidaktelat'] ?? 0;
                                    $pengurangMenyontek = $existing['pengurang_menyontek'] ?? 0;
                                    $nilaiRapor = $existing['nilai_rapor'] ?? 0;
                                    $predikat = \App\Models\NilaiRapot::getPredikat($nilaiRapor);
                                @endphp
                                <tr class="hover:bg-blue-50/30 transition-colors" id="row-{{ $index }}">
                                    <!-- Sticky Nama Siswa Column -->
                                    <td class="sticky left-0 z-10 bg-white px-3 py-2 border-r-2 border-gray-300">
                                        <input type="hidden" name="nilai[{{ $index }}][siswa_id]"
                                            value="{{ $s->id }}">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-7 h-7 rounded-full {{ $s->jenis_kelamin == 'L' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center text-[10px] font-bold flex-shrink-0">
                                                {{ $s->initials }}</div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900 text-xs truncate">{{ $s->nama_siswa }}
                                                </p>
                                                <p class="text-[10px] text-gray-400">{{ $s->nis }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Formatif Inputs - 4 BABs -->
                                    @for ($bab = 1; $bab <= 4; $bab++)
                                        @php $babKey = "bab{$bab}"; @endphp
                                        <td class="px-1 py-1 border-r border-gray-100">
                                            <input type="number"
                                                name="nilai[{{ $index }}][formatif][{{ $babKey }}][tp1]"
                                                value="{{ $formatifData[$babKey]['tp1'] ?? 0 }}" min="0"
                                                max="100" step="0.01"
                                                class="formatif-input w-full text-center text-xs px-1 py-1 border border-gray-200 rounded focus:border-blue-200 focus:ring-1 focus:ring-blue-200"
                                                data-row="{{ $index }}" data-bab="{{ $bab }}"
                                                data-component="tp1" onchange="calculateAll({{ $index }})">
                                        </td>
                                        <td class="px-1 py-1 border-r border-gray-100">
                                            <input type="number"
                                                name="nilai[{{ $index }}][formatif][{{ $babKey }}][tp2]"
                                                value="{{ $formatifData[$babKey]['tp2'] ?? 0 }}" min="0"
                                                max="100" step="0.01"
                                                class="formatif-input w-full text-center text-xs px-1 py-1 border border-gray-200 rounded focus:border-blue-200 focus:ring-1 focus:ring-blue-200"
                                                data-row="{{ $index }}" data-bab="{{ $bab }}"
                                                data-component="tp2" onchange="calculateAll({{ $index }})">
                                        </td>
                                        <td class="px-1 py-1 border-r border-gray-100">
                                            <input type="number"
                                                name="nilai[{{ $index }}][formatif][{{ $babKey }}][tp3]"
                                                value="{{ $formatifData[$babKey]['tp3'] ?? 0 }}" min="0"
                                                max="100" step="0.01"
                                                class="formatif-input w-full text-center text-xs px-1 py-1 border border-gray-200 rounded focus:border-blue-200 focus:ring-1 focus:ring-blue-200"
                                                data-row="{{ $index }}" data-bab="{{ $bab }}"
                                                data-component="tp3" onchange="calculateAll({{ $index }})">
                                        </td>
                                        <td class="px-1 py-1 border-r border-gray-100">
                                            <input type="number"
                                                name="nilai[{{ $index }}][formatif][{{ $babKey }}][tp4]"
                                                value="{{ $formatifData[$babKey]['tp4'] ?? 0 }}" min="0"
                                                max="100" step="0.01"
                                                class="formatif-input w-full text-center text-xs px-1 py-1 border border-gray-200 rounded focus:border-blue-200 focus:ring-1 focus:ring-blue-200"
                                                data-row="{{ $index }}" data-bab="{{ $bab }}"
                                                data-component="tp4" onchange="calculateAll({{ $index }})">
                                        </td>
                                        <td class="px-1 py-1 border-r border-gray-100">
                                            <input type="number"
                                                name="nilai[{{ $index }}][formatif][{{ $babKey }}][uh]"
                                                value="{{ $formatifData[$babKey]['uh'] ?? 0 }}" min="0"
                                                max="100" step="0.01"
                                                class="formatif-input w-full text-center text-xs px-1 py-1 border border-gray-200 rounded focus:border-blue-200 focus:ring-1 focus:ring-blue-200"
                                                data-row="{{ $index }}" data-bab="{{ $bab }}"
                                                data-component="uh" onchange="calculateAll({{ $index }})">
                                        </td>
                                        <td class="px-1 py-1 border-r border-gray-200 bg-blue-50 text-center">
                                            <span class="font-bold text-blue-600 text-xs bab-avg"
                                                id="bab-avg-{{ $index }}-{{ $bab }}">0.0</span>
                                        </td>
                                    @endfor

                                    <!-- Formatif Total (calculated) -->
                                    <td class="px-1 py-1 border-r border-gray-200 bg-blue-50 text-center">
                                        <span class="font-bold text-blue-600 text-sm formatif-total"
                                            id="formatif-total-{{ $index }}">0.0</span>
                                    </td>

                                    <!-- SAS Input -->
                                    <td class="px-1 py-1 border-r border-gray-200 bg-purple-50">
                                        <input type="number" name="nilai[{{ $index }}][sas]"
                                            value="{{ $sas }}" min="0" max="100" step="0.01"
                                            class="sas-input w-full text-center text-xs px-1 py-1 border border-purple-200 rounded bg-white focus:border-purple-500 focus:ring-1 focus:ring-purple-200"
                                            data-row="{{ $index }}" onchange="calculateAll({{ $index }})">
                                    </td>

                                    <!-- Kehadiran Input (max 20) -->
                                    <td class="px-1 py-1 border-r border-gray-200 bg-amber-50">
                                        <input type="number" name="nilai[{{ $index }}][kehadiran]"
                                            value="{{ $kehadiran }}" min="0" max="20" step="0.01"
                                            class="kehadiran-input w-full text-center text-xs px-1 py-1 border border-amber-200 rounded bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-200"
                                            data-row="{{ $index }}" onchange="calculateAll({{ $index }})">
                                    </td>

                                    <!-- Pengurang Inputs -->
                                    <td class="px-1 py-1 border-r border-gray-100 bg-red-50">
                                        <input type="number" name="nilai[{{ $index }}][pengurang_tidaktelat]"
                                            value="{{ $pengurangTidaktelat }}" min="0" max="100"
                                            step="0.01"
                                            class="pengurang-input w-full text-center text-xs px-1 py-1 border border-red-200 rounded bg-white focus:border-red-500 focus:ring-1 focus:ring-red-200"
                                            data-row="{{ $index }}" onchange="calculateAll({{ $index }})">
                                    </td>
                                    <td class="px-1 py-1 border-r border-gray-200 bg-red-50">
                                        <input type="number" name="nilai[{{ $index }}][pengurang_menyontek]"
                                            value="{{ $pengurangMenyontek }}" min="0" max="100"
                                            step="0.01"
                                            class="pengurang-input w-full text-center text-xs px-1 py-1 border border-red-200 rounded bg-white focus:border-red-500 focus:ring-1 focus:ring-red-200"
                                            data-row="{{ $index }}" onchange="calculateAll({{ $index }})">
                                    </td>

                                    <!-- Nilai Rapor (calculated) -->
                                    <td
                                        class="px-2 py-2 bg-gradient-to-r from-gray-100 to-gray-50 text-center border-r border-gray-200">
                                        <span class="font-bold text-gray-800 text-base nilai-rapor"
                                            id="nilai-rapor-{{ $index }}">{{ number_format($nilaiRapor, 1) }}</span>
                                    </td>

                                    <!-- Predikat -->
                                    <td class="px-2 py-2 text-center bg-gray-50">
                                        <span
                                            class="predikat-badge inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold
                                    {{ $predikat == 'A' ? 'bg-emerald-100 text-emerald-700' : ($predikat == 'B' ? 'bg-amber-100 text-amber-800' : ($predikat == 'C' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')) }}"
                                            id="predikat-{{ $index }}">
                                            {{ $predikat }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6">
                <div class="p-4 rounded-xl text-white shadow-lg"
                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                            style="background: rgba(255,255,255,0.2);">
                            <span class="material-symbols-outlined text-xl">trending_up</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-medium uppercase" style="color: rgba(255,255,255,0.85);">Rata-rata
                                Kelas</p>
                            <p class="text-2xl font-bold" id="stat-avg">{{ $stats['rata_rata'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 rounded-xl text-white shadow-lg"
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                            style="background: rgba(255,255,255,0.2);">
                            <span class="material-symbols-outlined text-xl">star</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-medium uppercase" style="color: rgba(255,255,255,0.85);">Nilai
                                Tertinggi</p>
                            <p class="text-2xl font-bold">{{ $stats['nilai_tertinggi'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 rounded-xl text-white shadow-lg"
                    style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                            style="background: rgba(255,255,255,0.2);">
                            <span class="material-symbols-outlined text-xl">warning</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-medium uppercase" style="color: rgba(255,255,255,0.85);">Perlu
                                Remedial</p>
                            <p class="text-2xl font-bold">{{ $stats['perlu_remedial'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 rounded-xl text-white shadow-lg"
                    style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                            style="background: rgba(255,255,255,0.2);">
                            <span class="material-symbols-outlined text-xl">check_circle</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-medium uppercase" style="color: rgba(255,255,255,0.85);">Tuntas
                                (≥75)</p>
                            <p class="text-2xl font-bold">{{ $stats['tuntas'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end mt-6">
                <button type="submit"
                    class="px-8 py-3 text-white font-bold rounded-xl transition-all shadow-lg flex items-center gap-2 transform hover:scale-105"
                    style="background: linear-gradient(to right, #f59e0b, #d97706); box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);">
                    <span class="material-symbols-outlined">save</span>
                    Simpan Rekap Nilai
                </button>
            </div>
        </form>
    @elseif($kelasId && $pelajaranId)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center text-gray-400 mt-6">
            <span class="material-symbols-outlined text-5xl mb-3">school</span>
            <p class="font-medium">Tidak ada siswa aktif di kelas ini</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center text-gray-400 mt-6">
            <span class="material-symbols-outlined text-5xl mb-3">grade</span>
            <p class="font-medium">Pilih Tahun Ajaran, Kelas, dan Mata Pelajaran</p>
            <p class="text-sm mt-1">Data nilai akan ditampilkan setelah filter diterapkan</p>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        // Initialize all calculations on page load
        document.addEventListener('DOMContentLoaded', function() {
            @if ($siswaList->count() > 0)
                @foreach ($siswaList as $index => $s)
                    calculateAll({{ $index }});
                @endforeach
                updateClassStats();
            @endif
        });

        // Calculate BAB average
        function calculateBabAverage(rowIndex, bab) {
            const row = document.getElementById('row-' + rowIndex);
            const inputs = row.querySelectorAll(`[data-bab="${bab}"]`);

            let sum = 0;
            inputs.forEach(input => {
                sum += parseFloat(input.value) || 0;
            });

            return sum / 5;
        }

        // Calculate Formatif Total (average of all 4 BABs)
        function calculateFormatifTotal(rowIndex) {
            let totalBabAvg = 0;
            for (let bab = 1; bab <= 4; bab++) {
                totalBabAvg += calculateBabAverage(rowIndex, bab);
            }
            return totalBabAvg / 4;
        }

        // Main calculation function
        function calculateAll(rowIndex) {
            const row = document.getElementById('row-' + rowIndex);

            // Calculate and display BAB averages
            let formatifTotal = 0;
            for (let bab = 1; bab <= 4; bab++) {
                const babAvg = calculateBabAverage(rowIndex, bab);
                document.getElementById(`bab-avg-${rowIndex}-${bab}`).textContent = babAvg.toFixed(1);
                formatifTotal += babAvg;
            }

            // Formatif Total (average of all BABs)
            formatifTotal = formatifTotal / 4;
            document.getElementById(`formatif-total-${rowIndex}`).textContent = formatifTotal.toFixed(1);

            // Get other values
            const sas = parseFloat(row.querySelector('.sas-input').value) || 0;
            const kehadiran = parseFloat(row.querySelector('.kehadiran-input').value) || 0;
            const pengurangInputs = row.querySelectorAll('.pengurang-input');
            let totalPengurang = 0;
            pengurangInputs.forEach(input => {
                totalPengurang += parseFloat(input.value) || 0;
            });

            // Calculate Nilai Rapor
            // Formula: (Formatif Total × 0.50) + (SAS × 0.30) + Kehadiran - Total Pengurang
            let nilaiRapor = (formatifTotal * 0.50) + (sas * 0.30) + kehadiran - totalPengurang;
            nilaiRapor = Math.max(0, Math.min(100, nilaiRapor)); // Clamp between 0-100

            document.getElementById(`nilai-rapor-${rowIndex}`).textContent = nilaiRapor.toFixed(1);

            // Update Predikat
            updatePredikat(rowIndex, nilaiRapor);

            // Update class stats
            updateClassStats();
        }

        function updatePredikat(rowIndex, nilaiRapor) {
            let predikat = 'D',
                bgClass = 'bg-red-100',
                textClass = 'text-red-800';
            if (nilaiRapor >= 85) {
                predikat = 'A';
                bgClass = 'bg-emerald-100';
                textClass = 'text-emerald-700';
            } else if (nilaiRapor >= 75) {
                predikat = 'B';
                bgClass = 'bg-amber-100';
                textClass = 'text-amber-800';
            } else if (nilaiRapor >= 65) {
                predikat = 'C';
                bgClass = 'bg-orange-100';
                textClass = 'text-orange-800';
            } else if (nilaiRapor >= 55) {
                predikat = 'D';
                bgClass = 'bg-red-100';
                textClass = 'text-red-800';
            } else if (nilaiRapor < 45) {
                predikat = 'E';
                bgClass = 'bg-red-100';
                textClass = 'text-red-800';
            }
            document.getElementById(`predikat-${rowIndex}`).className =
                `predikat-badge inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold ${bgClass} ${textClass}`;
            document.getElementById(`predikat-${rowIndex}`).textContent = predikat;
        }

        function updateClassStats() {
            const rows = document.querySelectorAll('tbody tr[id^="row-"]');
            let total = 0,
                count = 0,
                max = 0,
                remedial = 0,
                tuntas = 0;

            rows.forEach(row => {
                const rowIndex = row.id.replace('row-', '');
                const nilaiRapor = parseFloat(document.getElementById(`nilai-rapor-${rowIndex}`).textContent) || 0;

                if (nilaiRapor > 0) {
                    total += nilaiRapor;
                    count++;
                    if (nilaiRapor > max) max = nilaiRapor;
                    if (nilaiRapor < 75) remedial++;
                    else tuntas++;
                }
            });

            const avg = count > 0 ? (total / count).toFixed(1) : 0;
            document.getElementById('stat-avg').textContent = avg;
        }

        // Keyboard navigation for spreadsheet-like experience
        document.addEventListener('keydown', function(e) {
            if (e.target.classList.contains('formatif-input') ||
                e.target.classList.contains('sas-input') ||
                e.target.classList.contains('kehadiran-input') ||
                e.target.classList.contains('pengurang-input')) {

                const row = e.target.closest('tr');
                const inputs = Array.from(row.querySelectorAll('input[type="number"]'));
                const currentIndex = inputs.indexOf(e.target);

                if (e.key === 'ArrowRight' && currentIndex < inputs.length - 1) {
                    e.preventDefault();
                    inputs[currentIndex + 1].focus();
                } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
                    e.preventDefault();
                    inputs[currentIndex - 1].focus();
                } else if (e.key === 'Tab' && !e.shiftKey && currentIndex === inputs.length - 1) {
                    // Move to next row's first input
                    const nextRow = row.nextElementSibling;
                    if (nextRow) {
                        const nextInputs = nextRow.querySelectorAll('input[type="number"]');
                        if (nextInputs.length > 0) {
                            e.preventDefault();
                            nextInputs[0].focus();
                        }
                    }
                }
            }
        });
    </script>
@endpush
