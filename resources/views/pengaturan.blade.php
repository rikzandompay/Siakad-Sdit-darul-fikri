@extends('layouts.app')
@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')

@section('content')
<h2 class="font-bold text-2xl text-emerald-900">Pengaturan Sistem</h2>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Profil Guru -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-700">person</span>
                Profil Guru
            </h3>
        </div>
        <form method="POST" action="{{ route('pengaturan.profil.update') }}" class="p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nama Lengkap</label>
                <input name="nama_lengkap" value="{{ $user->nama_lengkap }}" type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Email</label>
                <input name="email" value="{{ $user->email }}" type="email" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">NIP</label>
                    <input name="nip" value="{{ $user->nip }}" type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">No HP</label>
                    <input name="no_hp" value="{{ $user->no_hp }}" type="text" class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="px-6 py-2.5 bg-emerald-900 text-white rounded-lg text-sm font-bold hover:bg-emerald-800">Simpan Profil</button>
            </div>
        </form>
    </div>

    <!-- Ganti Password -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600">lock</span>
                Ganti Password
            </h3>
        </div>
        <form method="POST" action="{{ route('pengaturan.password.update') }}" class="p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Password Lama</label>
                <input name="current_password" type="password" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Password Baru</label>
                <input name="password" type="password" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Konfirmasi Password Baru</label>
                <input name="password_confirmation" type="password" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" />
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="px-6 py-2.5 bg-amber-500 text-white rounded-lg text-sm font-bold hover:bg-amber-600">Ganti Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Tahun Ajaran -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
        <h3 class="font-bold text-gray-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-600">calendar_today</span>
            Tahun Ajaran
        </h3>
        <button onclick="document.getElementById('modal-ta').classList.remove('hidden')"
            class="px-4 py-2 bg-emerald-900 text-white rounded-lg text-xs font-bold hover:bg-emerald-800">+ Tambah</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase tracking-wider font-bold">
                <tr>
                    <th class="px-6 py-3">No</th>
                    <th class="px-6 py-3">Nama Semester</th>
                    <th class="px-6 py-3 text-center">Status</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($tahunAjaranList as $index => $ta)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 text-sm text-gray-400">{{ $index + 1 }}</td>
                        <td class="px-6 py-3 font-medium text-gray-900">{{ $ta->nama_semester }}</td>
                        <td class="px-6 py-3 text-center">
                            @if($ta->status_aktif == 'Y')
                                <span class="px-2 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full">AKTIF</span>
                            @else
                                <form method="POST" action="{{ route('pengaturan.tahun-ajaran.aktif', $ta->id) }}" class="inline">
                                    @csrf @method('PUT')
                                    <button type="submit" class="px-2 py-1 bg-gray-100 text-gray-500 text-[10px] font-bold rounded-full hover:bg-emerald-100 hover:text-emerald-800 transition-colors">
                                        Set Aktif
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center">
                            <button onclick="document.getElementById('modal-ta-edit-{{ $ta->id }}').classList.remove('hidden')" class="text-gray-400 hover:text-blue-600 transition-colors mr-2">
                                <span class="material-symbols-outlined text-sm">edit</span>
                            </button>
                            @if($ta->status_aktif != 'Y')
                                <form method="POST" action="{{ route('pengaturan.tahun-ajaran.destroy', $ta->id) }}" onsubmit="return confirm('Hapus tahun ajaran ini?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors">
                                        <span class="material-symbols-outlined text-sm">delete</span>
                                    </button>
                                </form>
                            @endif

                            <!-- Modal Edit Tahun Ajaran -->
                            <div id="modal-ta-edit-{{ $ta->id }}" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm text-left">
                                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
                                    <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
                                        <h3 class="font-bold text-lg text-gray-900">Edit Tahun Ajaran</h3>
                                        <button onclick="document.getElementById('modal-ta-edit-{{ $ta->id }}').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                                            <span class="material-symbols-outlined">close</span>
                                        </button>
                                    </div>
                                    <form method="POST" action="{{ route('pengaturan.tahun-ajaran.update', $ta->id) }}" class="p-6 space-y-4">
                                        @csrf @method('PUT')
                                        @php
                                            $parts = explode(' - ', $ta->nama_semester);
                                            $tahunVal = $parts[0] ?? '';
                                            $semVal = $parts[1] ?? 'Ganjil';
                                        @endphp
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tahun *</label>
                                            <input name="tahun" value="{{ $tahunVal }}" required type="text" placeholder="contoh: 2024/2025"
                                                class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Semester *</label>
                                            <select name="semester" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
                                                <option value="Ganjil" {{ $semVal == 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                                                <option value="Genap" {{ $semVal == 'Genap' ? 'selected' : '' }}>Genap</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Status Aktif *</label>
                                            <select name="status_aktif" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
                                                <option value="N" {{ $ta->status_aktif == 'N' ? 'selected' : '' }}>Tidak Aktif</option>
                                                <option value="Y" {{ $ta->status_aktif == 'Y' ? 'selected' : '' }}>Aktif</option>
                                            </select>
                                        </div>
                                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                            <button type="button" onclick="document.getElementById('modal-ta-edit-{{ $ta->id }}').classList.add('hidden')" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600">Batal</button>
                                            <button type="submit" class="px-6 py-2.5 bg-emerald-900 text-white rounded-lg text-sm font-bold">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Mata Pelajaran -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
        <h3 class="font-bold text-gray-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-purple-600">menu_book</span>
            Mata Pelajaran
        </h3>
        <button onclick="document.getElementById('modal-mapel').classList.remove('hidden')"
            class="px-4 py-2 bg-emerald-900 text-white rounded-lg text-xs font-bold hover:bg-emerald-800">+ Tambah</button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-[11px] text-gray-500 uppercase tracking-wider font-bold">
                <tr>
                    <th class="px-6 py-3">No</th>
                    <th class="px-6 py-3">Kode</th>
                    <th class="px-6 py-3">Nama Mata Pelajaran</th>
                    <th class="px-6 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($mapelList as $index => $m)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 text-sm text-gray-400">{{ $index + 1 }}</td>
                        <td class="px-6 py-3 text-sm font-mono font-bold text-gray-600">{{ $m->kode_pelajaran }}</td>
                        <td class="px-6 py-3 font-medium text-gray-900">{{ $m->nama_pelajaran }}</td>
                        <td class="px-6 py-3 text-center">
                            <form method="POST" action="{{ route('pengaturan.mapel.destroy', $m->id) }}" onsubmit="return confirm('Hapus mata pelajaran ini?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Tahun Ajaran -->
<div id="modal-ta" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-900">Tambah Tahun Ajaran</h3>
            <button onclick="document.getElementById('modal-ta').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="{{ route('pengaturan.tahun-ajaran.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tahun *</label>
                <input name="tahun" required type="text" placeholder="contoh: 2024/2025"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Semester *</label>
                <select name="semester" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
                    <option value="Ganjil">Ganjil</option>
                    <option value="Genap">Genap</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Status Aktif *</label>
                <select name="status_aktif" required class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm">
                    <option value="N" selected>Tidak Aktif</option>
                    <option value="Y">Aktif</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-ta').classList.add('hidden')" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-900 text-white rounded-lg text-sm font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Mapel -->
<div id="modal-mapel" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-900">Tambah Mata Pelajaran</h3>
            <button onclick="document.getElementById('modal-mapel').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="{{ route('pengaturan.mapel.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Kode Pelajaran *</label>
                <input name="kode_pelajaran" required type="text" placeholder="contoh: MTK"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nama Pelajaran *</label>
                <input name="nama_pelajaran" required type="text" placeholder="contoh: Matematika"
                    class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm" />
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modal-mapel').classList.add('hidden')" class="px-6 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-600">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-900 text-white rounded-lg text-sm font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
