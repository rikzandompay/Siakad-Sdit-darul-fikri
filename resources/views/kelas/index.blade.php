@extends('layouts.app')
@section('title', 'Kelas & Mapel')
@section('page-title', 'Kelas & Mata Pelajaran')

@section('content')
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div class="space-y-1">
        <h2 class="font-bold text-2xl text-emerald-900">Manajemen Kelas</h2>
        <p class="text-sm text-gray-500">Kelola data kelas dan jadwal pelajaran</p>
    </div>
    <button onclick="document.getElementById('modal-tambah-kelas').classList.remove('hidden')"
        class="flex items-center gap-2 px-5 py-2.5 bg-amber-500 text-white rounded-lg font-bold text-sm hover:bg-amber-600 transition-colors shadow-sm">
        <span class="material-symbols-outlined text-sm">add</span>
        Tambah Kelas
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
    @foreach($kelasList as $kelas)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
            <div class="h-28 bg-emerald-900 relative overflow-hidden flex items-end p-6">
                <div>
                    <h3 class="text-white font-bold text-lg leading-none">{{ $kelas->nama_kelas }}</h3>
                    <p class="text-emerald-200/70 text-xs mt-1">{{ $kelas->waliKelas?->nama_lengkap ?? 'Belum ada wali' }}</p>
                </div>
                <span class="material-symbols-outlined absolute -bottom-4 -right-4 text-emerald-800 text-8xl opacity-30">class</span>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Total Siswa</span>
                    <span class="font-bold text-gray-900">{{ $kelas->siswa_count }} Siswa</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('kelas.show', $kelas->id) }}"
                        class="bg-emerald-900 hover:bg-emerald-950 text-white font-bold text-sm py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">visibility</span>
                        Detail
                    </a>
                    <a href="{{ route('presensi.show', $kelas->id) }}"
                        class="bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">how_to_reg</span>
                        Presensi
                    </a>
                </div>
                <div class="flex gap-2">
                    <button onclick="openEditKelas({{ json_encode($kelas) }})" class="flex-1 text-xs text-gray-500 hover:text-emerald-700 py-1 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">Edit</button>
                    <form method="POST" action="{{ route('kelas.destroy', $kelas->id) }}" onsubmit="return confirm('Yakin hapus kelas ini?')" class="flex-1">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full text-xs text-gray-500 hover:text-red-600 py-1 border border-gray-200 rounded-lg hover:bg-red-50 transition-colors">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Modal Tambah Kelas -->
<div id="modal-tambah-kelas" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-900">Tambah Kelas Baru</h3>
            <button onclick="document.getElementById('modal-tambah-kelas').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="{{ route('kelas.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nama Kelas *</label>
                <input name="nama_kelas" required type="text" placeholder="contoh: Kelas 1-A" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Wali Kelas</label>
                <select name="wali_kelas_id" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">-- Pilih Wali Kelas --</option>
                    @foreach($guruList as $guru)
                        <option value="{{ $guru->id }}">{{ $guru->nama_lengkap ?? $guru->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-tambah-kelas').classList.add('hidden')" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-900 text-white rounded-lg text-sm font-bold hover:bg-emerald-800">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kelas -->
<div id="modal-edit-kelas" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-900">Edit Kelas</h3>
            <button onclick="document.getElementById('modal-edit-kelas').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="form-edit-kelas" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nama Kelas *</label>
                <input id="edit-kelas-nama" name="nama_kelas" required type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Wali Kelas</label>
                <select id="edit-kelas-wali" name="wali_kelas_id" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="">-- Pilih Wali Kelas --</option>
                    @foreach($guruList as $guru)
                        <option value="{{ $guru->id }}">{{ $guru->nama_lengkap ?? $guru->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-edit-kelas').classList.add('hidden')" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-900 text-white rounded-lg text-sm font-bold hover:bg-emerald-800">Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditKelas(kelas) {
    document.getElementById('form-edit-kelas').action = '/kelas/' + kelas.id;
    document.getElementById('edit-kelas-nama').value = kelas.nama_kelas;
    document.getElementById('edit-kelas-wali').value = kelas.wali_kelas_id || '';
    document.getElementById('modal-edit-kelas').classList.remove('hidden');
}
</script>
@endpush
