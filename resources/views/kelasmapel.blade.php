@extends('layouts.app')
@section('title', $kelas->nama_kelas)
@section('page-title', 'Detail Kelas')

@section('content')
<!-- Breadcrumbs -->
<div class="flex items-center justify-between">
    <div class="space-y-1">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('kelas.index') }}" class="hover:text-emerald-700">Kelas & Mapel</a>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-emerald-800 font-medium">{{ $kelas->nama_kelas }}</span>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('kelas.index') }}" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <h2 class="font-bold text-2xl text-emerald-900">Jadwal Pelajaran {{ $kelas->nama_kelas }}</h2>
        </div>
    </div>
</div>

<!-- Header Info -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-gutter">
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-3">
        <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
            <span class="material-symbols-outlined text-emerald-700">person</span>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Wali Kelas</p>
            <p class="font-bold text-gray-900">{{ $kelas->waliKelas?->nama_lengkap ?? '-' }}</p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-3">
        <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
            <span class="material-symbols-outlined text-amber-700">groups</span>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Jumlah Siswa</p>
            <p class="font-bold text-gray-900">{{ $kelas->siswa->count() }} Siswa</p>
        </div>
    </div>
    <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-3">
        <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
            <span class="material-symbols-outlined text-blue-700">book</span>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Mata Pelajaran</p>
            <p class="font-bold text-gray-900">{{ $kelas->jadwalPelajaran->unique('pelajaran_id')->count() }} Subjek</p>
        </div>
    </div>
    <div class="bg-emerald-900 p-6 rounded-xl shadow-sm space-y-3 relative overflow-hidden">
        <div class="relative z-10">
            <p class="text-xs text-emerald-100/70 uppercase font-bold tracking-wider">Total Jadwal</p>
            <p class="font-bold text-white text-xl">{{ $kelas->jadwalPelajaran->count() }} Sesi/Minggu</p>
        </div>
        <span class="material-symbols-outlined absolute -bottom-4 -right-4 text-emerald-800 text-8xl opacity-30">calendar_today</span>
    </div>
</div>

<!-- Schedule Table -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center">
        <h3 class="font-bold text-lg text-gray-900">Rincian Jadwal Pelajaran Mingguan</h3>
        <div class="flex items-center gap-3">
            <a href="{{ route('jadwal.export.csv', $kelas->id) }}" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-sm">download</span>
                CSV
            </a>
            <a href="{{ route('jadwal.export.pdf', $kelas->id) }}" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-red-600 rounded-lg text-sm font-bold hover:bg-red-50 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                PDF
            </a>
            <button onclick="document.getElementById('modal-tambah-jadwal').classList.remove('hidden')"
                class="flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg font-bold text-sm hover:bg-amber-600 transition-colors shadow-sm">
                <span class="material-symbols-outlined text-sm">add</span>
                Tambah Jadwal
            </button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b">Hari</th>
                    <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b">Waktu</th>
                    <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b">Mata Pelajaran</th>
                    <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b">Guru Pengampu</th>
                    <th class="px-8 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php $currentHari = ''; @endphp
                @forelse($kelas->jadwalPelajaran as $jadwal)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-8 py-4 font-semibold text-emerald-800">
                            @if($jadwal->hari !== $currentHari)
                                {{ $jadwal->hari }}
                                @php $currentHari = $jadwal->hari; @endphp
                            @endif
                        </td>
                        <td class="px-8 py-4 text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') }}
                        </td>
                        <td class="px-8 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                                <span class="font-medium text-gray-900">{{ $jadwal->mataPelajaran->nama_pelajaran }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-4 text-sm text-gray-700">{{ $jadwal->guru->nama_lengkap ?? $jadwal->guru->name }}</td>
                        <td class="px-8 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="openEditModal({{ json_encode($jadwal) }})"
                                    class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <form method="POST" action="{{ route('jadwal.destroy', $jadwal->id) }}" onsubmit="return confirm('Hapus jadwal ini?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                        <span class="material-symbols-outlined text-sm">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-8 py-12 text-center text-gray-400">Belum ada jadwal pelajaran</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Jadwal -->
<div id="modal-tambah-jadwal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-900">Tambah Jadwal Pelajaran</h3>
            <button onclick="document.getElementById('modal-tambah-jadwal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="{{ route('jadwal.store') }}" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Mata Pelajaran *</label>
                <select name="pelajaran_id" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
                    @foreach(\App\Models\MataPelajaran::orderBy('nama_pelajaran')->get() as $mapel)
                        <option value="{{ $mapel->id }}">{{ $mapel->nama_pelajaran }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Guru Pengampu *</label>
                <select name="guru_id" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
                    @foreach(\App\Models\User::orderBy('nama_lengkap')->get() as $guru)
                        <option value="{{ $guru->id }}">{{ $guru->nama_lengkap ?? $guru->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hari *</label>
                <select name="hari" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
                    @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $h)
                        <option value="{{ $h }}">{{ $h }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Jam Mulai *</label>
                    <input name="jam_mulai" type="time" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Jam Selesai *</label>
                    <input name="jam_selesai" type="time" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm" />
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-tambah-jadwal').classList.add('hidden')" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-900 text-white rounded-lg text-sm font-bold">Simpan</button>
            </div>
        </form>
    </div>
<!-- Modal Edit Jadwal -->
<div id="modal-edit-jadwal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-900">Edit Jadwal Pelajaran</h3>
            <button onclick="document.getElementById('modal-edit-jadwal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="form-edit-jadwal" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            <input type="hidden" name="kelas_id" value="{{ $kelas->id }}">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Mata Pelajaran *</label>
                <select id="edit-pelajaran-id" name="pelajaran_id" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
                    @foreach(\App\Models\MataPelajaran::orderBy('nama_pelajaran')->get() as $mapel)
                        <option value="{{ $mapel->id }}">{{ $mapel->nama_pelajaran }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Guru Pengampu *</label>
                <select id="edit-guru-id" name="guru_id" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
                    @foreach(\App\Models\User::orderBy('nama_lengkap')->get() as $guru)
                        <option value="{{ $guru->id }}">{{ $guru->nama_lengkap ?? $guru->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hari *</label>
                <select id="edit-hari" name="hari" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
                    @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $h)
                        <option value="{{ $h }}">{{ $h }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Jam Mulai *</label>
                    <input id="edit-jam-mulai" name="jam_mulai" type="time" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Jam Selesai *</label>
                    <input id="edit-jam-selesai" name="jam_selesai" type="time" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm" />
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-edit-jadwal').classList.add('hidden')" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-900 text-white rounded-lg text-sm font-bold hover:bg-emerald-800">Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditModal(jadwal) {
    document.getElementById('form-edit-jadwal').action = '/jadwal/' + jadwal.id;
    document.getElementById('edit-pelajaran-id').value = jadwal.pelajaran_id;
    document.getElementById('edit-guru-id').value = jadwal.guru_id;
    document.getElementById('edit-hari').value = jadwal.hari;
    
    // Format the date strings to extract just HH:mm
    if (jadwal.jam_mulai) {
        document.getElementById('edit-jam-mulai').value = jadwal.jam_mulai.substring(0, 5);
    }
    if (jadwal.jam_selesai) {
        document.getElementById('edit-jam-selesai').value = jadwal.jam_selesai.substring(0, 5);
    }
    
    document.getElementById('modal-edit-jadwal').classList.remove('hidden');
}
</script>
@endpush
