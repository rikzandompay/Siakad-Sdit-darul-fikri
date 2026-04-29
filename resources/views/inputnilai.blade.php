@extends('layouts.app')
@section('title', 'Input Nilai Rapot')
@section('page-title', 'Nilai Rapot')

@section('content')
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div class="space-y-1">
        <p class="text-amber-600 font-bold text-xs uppercase tracking-wider">Evaluasi Belajar</p>
        <h2 class="font-bold text-2xl text-emerald-900">Input Nilai Siswa</h2>
    </div>
    @if($siswaList->count() > 0 && $kelasId && $pelajaranId)
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('nilai.export.pdf', ['tahun_ajaran_id' => $tahunAjaranId, 'kelas_id' => $kelasId, 'pelajaran_id' => $pelajaranId]) }}"
           target="_blank"
           class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm text-red-500">picture_as_pdf</span>
            Export PDF
        </a>
        <a href="{{ route('nilai.export.csv', ['tahun_ajaran_id' => $tahunAjaranId, 'kelas_id' => $kelasId, 'pelajaran_id' => $pelajaranId]) }}"
           class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm text-emerald-600">table_chart</span>
            Export CSV
        </a>
    </div>
    @endif
</div>

<!-- Filter Section -->
<div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
    <form method="GET" action="{{ route('nilai.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Tahun Ajaran</label>
            <select name="tahun_ajaran_id" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20">
                @foreach($tahunAjaranList as $ta)
                    <option value="{{ $ta->id }}" {{ $tahunAjaranId == $ta->id ? 'selected' : '' }}>
                        {{ $ta->nama_semester }} {{ $ta->status_aktif == 'Y' ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Kelas</label>
            <select name="kelas_id" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20" onchange="this.form.submit()">
                <option value="">-- Pilih Kelas --</option>
                @foreach($kelasList as $k)
                    <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Mata Pelajaran</label>
            <select name="pelajaran_id" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20">
                <option value="">-- Pilih Mapel --</option>
                @foreach($mapelList as $m)
                    <option value="{{ $m->id }}" {{ $pelajaranId == $m->id ? 'selected' : '' }}>{{ $m->nama_pelajaran }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="w-full bg-emerald-900 text-white px-4 py-2.5 rounded-lg font-bold text-sm hover:bg-emerald-800 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">filter_list</span>
                Tampilkan Siswa
            </button>
        </div>
    </form>
</div>

@if($siswaList->count() > 0)
<!-- Info Bar -->
@if($selectedKelas && $selectedMapel)
<div class="flex items-center gap-2 flex-wrap">
    <span class="text-xs text-gray-500 font-medium">Menampilkan:</span>
    <span class="px-2 py-1 bg-emerald-100 text-emerald-800 text-[11px] rounded-full font-medium">{{ $selectedKelas->nama_kelas }}</span>
    <span class="px-2 py-1 bg-amber-100 text-amber-800 text-[11px] rounded-full font-medium">{{ $selectedMapel->nama_pelajaran }}</span>
</div>
@endif

<!-- Grade Input Table -->
<form method="POST" action="{{ route('nilai.store') }}">
    @csrf
    <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunAjaranId }}">
    <input type="hidden" name="pelajaran_id" value="{{ $pelajaranId }}">

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-700">list_alt</span>
                <h3 class="font-bold text-emerald-900">Daftar Nilai Siswa</h3>
            </div>
            <span class="text-sm text-gray-500">{{ $siswaList->count() }} Siswa</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase w-12 border-b">No</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase border-b">Nama Siswa</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center border-b">Tugas (30%)</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center border-b">UTS (30%)</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center border-b">UAS (40%)</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center border-b bg-emerald-50/50">Nilai Akhir</th>
                        <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase text-center border-b">Predikat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($siswaList as $index => $s)
                        @php
                            $existing = $nilaiMap[$s->id] ?? null;
                            $nilaiTugas = $existing['nilai_tugas'] ?? 0;
                            $nilaiUts = $existing['nilai_uts'] ?? 0;
                            $nilaiUas = $existing['nilai_uas'] ?? 0;
                            $nilaiAkhir = $existing['nilai_akhir'] ?? 0;
                            $predikat = \App\Models\NilaiRapot::getPredikat($nilaiAkhir);
                        @endphp
                        <tr class="hover:bg-gray-50/30 transition-colors" id="row-{{ $index }}">
                            <td class="px-6 py-4 text-sm text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-6 py-4">
                                <input type="hidden" name="nilai[{{ $index }}][siswa_id]" value="{{ $s->id }}">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full {{ $s->jenis_kelamin == 'L' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center text-xs font-bold">{{ $s->initials }}</div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $s->nama_siswa }}</p>
                                        <p class="text-[11px] text-gray-400 tracking-wider">NIS: {{ $s->nis }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <input name="nilai[{{ $index }}][nilai_tugas]" type="number" min="0" max="100" step="0.01"
                                    value="{{ $nilaiTugas }}"
                                    onchange="calculateNilai({{ $index }})" oninput="calculateNilai({{ $index }})"
                                    class="nilai-tugas w-20 mx-auto block text-center bg-transparent border border-gray-200 rounded-lg p-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-200" />
                            </td>
                            <td class="px-6 py-4">
                                <input name="nilai[{{ $index }}][nilai_uts]" type="number" min="0" max="100" step="0.01"
                                    value="{{ $nilaiUts }}"
                                    onchange="calculateNilai({{ $index }})" oninput="calculateNilai({{ $index }})"
                                    class="nilai-uts w-20 mx-auto block text-center bg-transparent border border-gray-200 rounded-lg p-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-200" />
                            </td>
                            <td class="px-6 py-4">
                                <input name="nilai[{{ $index }}][nilai_uas]" type="number" min="0" max="100" step="0.01"
                                    value="{{ $nilaiUas }}"
                                    onchange="calculateNilai({{ $index }})" oninput="calculateNilai({{ $index }})"
                                    class="nilai-uas w-20 mx-auto block text-center bg-transparent border border-gray-200 rounded-lg p-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-200" />
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-emerald-900 bg-emerald-50/30 text-lg" id="na-{{ $index }}">{{ number_format($nilaiAkhir, 1) }}</td>
                            <td class="px-6 py-4 text-center" id="predikat-{{ $index }}">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                                    {{ $predikat == 'A' ? 'bg-emerald-100 text-emerald-800' : ($predikat == 'B' ? 'bg-amber-100 text-amber-800' : ($predikat == 'C' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')) }}">
                                    {{ $predikat }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-8">
        <div class="bg-emerald-50 p-5 rounded-xl border border-emerald-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-500 flex items-center justify-center text-white">
                <span class="material-symbols-outlined">trending_up</span>
            </div>
            <div>
                <p class="text-xs text-emerald-800 opacity-70">Rata-rata Kelas</p>
                <p class="text-xl font-bold text-emerald-900" id="stat-avg">{{ $stats['rata_rata'] }}</p>
            </div>
        </div>
        <div class="bg-amber-50 p-5 rounded-xl border border-amber-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-amber-500 flex items-center justify-center text-white">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">star</span>
            </div>
            <div>
                <p class="text-xs text-amber-800 opacity-70">Nilai Tertinggi</p>
                <p class="text-xl font-bold text-amber-900">{{ $stats['nilai_tertinggi'] }}</p>
            </div>
        </div>
        <div class="bg-red-50 p-5 rounded-xl border border-red-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-red-500 flex items-center justify-center text-white">
                <span class="material-symbols-outlined">warning</span>
            </div>
            <div>
                <p class="text-xs text-red-800 opacity-70">Perlu Remedial (&lt;75)</p>
                <p class="text-xl font-bold text-red-900">{{ $stats['perlu_remedial'] }}</p>
            </div>
        </div>
        <div class="bg-blue-50 p-5 rounded-xl border border-blue-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center text-white">
                <span class="material-symbols-outlined">check_circle</span>
            </div>
            <div>
                <p class="text-xs text-blue-800 opacity-70">Tuntas (≥75)</p>
                <p class="text-xl font-bold text-blue-900">{{ $stats['tuntas'] }}</p>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end mt-8">
        <button type="submit" class="px-10 py-3 bg-amber-500 text-white font-bold rounded-xl hover:bg-amber-600 transition-colors shadow-lg shadow-amber-500/20 flex items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            Simpan Nilai
        </button>
    </div>
</form>
@elseif($kelasId && $pelajaranId)
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center text-gray-400">
    <span class="material-symbols-outlined text-5xl mb-3">school</span>
    <p class="font-medium">Tidak ada siswa aktif di kelas ini</p>
</div>
@else
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center text-gray-400">
    <span class="material-symbols-outlined text-5xl mb-3">grade</span>
    <p class="font-medium">Pilih Tahun Ajaran, Kelas, dan Mata Pelajaran</p>
    <p class="text-sm mt-1">Data nilai akan ditampilkan setelah filter diterapkan</p>
</div>
@endif
@endsection

@push('scripts')
<script>
function calculateNilai(rowIndex) {
    const row = document.getElementById('row-' + rowIndex);
    const tugas = parseFloat(row.querySelector('.nilai-tugas').value) || 0;
    const uts = parseFloat(row.querySelector('.nilai-uts').value) || 0;
    const uas = parseFloat(row.querySelector('.nilai-uas').value) || 0;
    const nilaiAkhir = (tugas * 0.3) + (uts * 0.3) + (uas * 0.4);

    document.getElementById('na-' + rowIndex).textContent = nilaiAkhir.toFixed(1);

    let predikat = 'D', colorBg = 'bg-red-100', colorText = 'text-red-800';
    if (nilaiAkhir >= 85) { predikat = 'A'; colorBg = 'bg-emerald-100'; colorText = 'text-emerald-800'; }
    else if (nilaiAkhir >= 75) { predikat = 'B'; colorBg = 'bg-amber-100'; colorText = 'text-amber-800'; }
    else if (nilaiAkhir >= 65) { predikat = 'C'; colorBg = 'bg-orange-100'; colorText = 'text-orange-800'; }

    document.getElementById('predikat-' + rowIndex).innerHTML =
        `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ${colorBg} ${colorText}">${predikat}</span>`;
}
</script>
@endpush
