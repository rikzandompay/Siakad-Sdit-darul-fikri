@extends('layouts.app')
@section('title', 'Data Siswa')
@section('page-title', 'Data Siswa')

@section('content')
<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div class="space-y-1">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <span>Akademik</span>
            <span class="material-symbols-outlined text-xs">chevron_right</span>
            <span class="text-emerald-800 font-medium">Data Siswa</span>
        </div>
        <h2 class="font-bold text-2xl text-emerald-900">Manajemen Data Siswa</h2>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('siswa.export.pdf', request()->query()) }}" target="_blank"
           class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm text-red-500">picture_as_pdf</span>
            Export PDF
        </a>
        <a href="{{ route('siswa.export.csv', request()->query()) }}"
           class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm text-emerald-600">table_chart</span>
            Export CSV
        </a>
        <button onclick="document.getElementById('modal-tambah').classList.remove('hidden')"
            class="flex items-center gap-2 px-5 py-2.5 bg-amber-500 text-white rounded-lg font-bold text-sm hover:bg-amber-600 transition-colors shadow-sm">
            <span class="material-symbols-outlined text-sm">person_add</span>
            Tambah Siswa
        </button>
    </div>
</div>

<!-- Filters -->
<div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
    <form id="filter-form" method="GET" action="{{ route('siswa.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Cari</label>
            <input name="search" value="{{ request('search') }}" type="text" placeholder="Nama / NIS..."
                class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Kelas</label>
            <select name="kelas_id" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $k)
                    <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Gender</label>
            <select name="jenis_kelamin" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                <option value="">Semua</option>
                <option value="L" {{ request('jenis_kelamin') === 'L' ? 'selected' : '' }}>Laki-laki</option>
                <option value="P" {{ request('jenis_kelamin') === 'P' ? 'selected' : '' }}>Perempuan</option>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1.5">Status</label>
            <select name="status" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                <option value="">Semua</option>
                <option value="Aktif" {{ request('status') === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="Lulus" {{ request('status') === 'Lulus' ? 'selected' : '' }}>Lulus</option>
                <option value="Pindah" {{ request('status') === 'Pindah' ? 'selected' : '' }}>Pindah</option>
            </select>
        </div>
        <div>
            <button type="submit" class="w-full bg-emerald-900 text-white px-4 py-2.5 rounded-lg font-bold text-sm hover:bg-emerald-800 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-sm">filter_list</span> Filter
            </button>
        </div>
    </form>
</div>

<!-- Active Filters Info -->
@if(request()->hasAny(['search', 'kelas_id', 'jenis_kelamin', 'status']))
<div class="flex items-center gap-2 flex-wrap">
    <span class="text-xs text-gray-500 font-medium">Filter aktif:</span>
    @if(request('search'))
        <span class="px-2 py-1 bg-emerald-100 text-emerald-800 text-[11px] rounded-full font-medium">Cari: "{{ request('search') }}"</span>
    @endif
    @if(request('kelas_id'))
        <span class="px-2 py-1 bg-amber-100 text-amber-800 text-[11px] rounded-full font-medium">{{ $kelasList->find(request('kelas_id'))->nama_kelas ?? '' }}</span>
    @endif
    @if(request('jenis_kelamin'))
        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-[11px] rounded-full font-medium">{{ request('jenis_kelamin') == 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
    @endif
    @if(request('status'))
        <span class="px-2 py-1 bg-purple-100 text-purple-800 text-[11px] rounded-full font-medium">{{ request('status') }}</span>
    @endif
    <a href="{{ route('siswa.index') }}" class="text-[11px] text-red-500 hover:underline font-medium ml-2">✕ Reset Filter</a>
</div>
@endif

<!-- Table -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-8 py-5 border-b border-gray-100 flex justify-between items-center">
        <h3 class="font-bold text-lg text-gray-900">Daftar Siswa</h3>
        <span class="text-sm text-gray-500">Menampilkan {{ $siswa->firstItem() ?? 0 }}-{{ $siswa->lastItem() ?? 0 }} dari {{ $siswa->total() }} siswa</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b">No</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b">NIS</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b">Nama Siswa</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b text-center">L/P</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b">Kelas</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b">Wali</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b text-center">Status</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider border-b text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($siswa as $index => $s)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-sm text-gray-400">{{ $siswa->firstItem() + $index }}</td>
                        <td class="px-6 py-4 text-sm font-mono text-gray-700">{{ $s->nis }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full {{ $s->jenis_kelamin == 'L' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center text-xs font-bold">
                                    {{ $s->initials }}
                                </div>
                                <span class="font-medium text-gray-900">{{ $s->nama_siswa }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-center">
                            <span class="px-2 py-0.5 rounded text-xs font-bold {{ $s->jenis_kelamin == 'L' ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700' }}">
                                {{ $s->jenis_kelamin }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $s->kelas?->nama_kelas ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $s->nama_wali ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase
                                {{ $s->status == 'Aktif' ? 'bg-emerald-100 text-emerald-800' : ($s->status == 'Lulus' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $s->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="openEditModal({{ json_encode($s) }})"
                                    class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <form method="POST" action="{{ route('siswa.destroy', $s->id) }}" onsubmit="return confirm('Yakin ingin menghapus data siswa ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            <span class="material-symbols-outlined text-4xl mb-2">person_off</span>
                            <p class="font-medium">Tidak ada data siswa yang cocok</p>
                            @if(request()->hasAny(['search', 'kelas_id', 'jenis_kelamin', 'status']))
                                <a href="{{ route('siswa.index') }}" class="text-emerald-600 text-sm hover:underline mt-2 inline-block">Reset filter</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($siswa->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $siswa->links() }}
        </div>
    @endif
</div>

<!-- Modal Tambah Siswa -->
<div id="modal-tambah" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-900">Tambah Siswa Baru</h3>
            <button onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="{{ route('siswa.store') }}" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">NIS *</label>
                    <input name="nis" required type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Jenis Kelamin *</label>
                    <select name="jenis_kelamin" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nama Siswa *</label>
                <input name="nama_siswa" required type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Kelas *</label>
                <select name="kelas_id" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nama Wali</label>
                    <input name="nama_wali" type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">No HP Wali</label>
                    <input name="no_hp_wali" type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Status</label>
                <select name="status" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="Aktif">Aktif</option>
                    <option value="Lulus">Lulus</option>
                    <option value="Pindah">Pindah</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-900 text-white rounded-lg text-sm font-bold hover:bg-emerald-800">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Siswa -->
<div id="modal-edit" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-900">Edit Data Siswa</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="form-edit" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">NIS *</label>
                    <input id="edit-nis" name="nis" required type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Jenis Kelamin *</label>
                    <select id="edit-jk" name="jenis_kelamin" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nama Siswa *</label>
                <input id="edit-nama" name="nama_siswa" required type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Kelas *</label>
                <select id="edit-kelas" name="kelas_id" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nama Wali</label>
                    <input id="edit-wali" name="nama_wali" type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">No HP Wali</label>
                    <input id="edit-hp" name="no_hp_wali" type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Status</label>
                <select id="edit-status" name="status" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                    <option value="Aktif">Aktif</option>
                    <option value="Lulus">Lulus</option>
                    <option value="Pindah">Pindah</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-900 text-white rounded-lg text-sm font-bold hover:bg-emerald-800">Perbarui</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditModal(siswa) {
    document.getElementById('form-edit').action = '/siswa/' + siswa.id;
    document.getElementById('edit-nis').value = siswa.nis;
    document.getElementById('edit-nama').value = siswa.nama_siswa;
    document.getElementById('edit-jk').value = siswa.jenis_kelamin;
    document.getElementById('edit-kelas').value = siswa.kelas_id;
    document.getElementById('edit-wali').value = siswa.nama_wali || '';
    document.getElementById('edit-hp').value = siswa.no_hp_wali || '';
    document.getElementById('edit-status').value = siswa.status;
    document.getElementById('modal-edit').classList.remove('hidden');
}
</script>
@endpush
