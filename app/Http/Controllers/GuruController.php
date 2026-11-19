<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Sekolah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        $guru = $this->queryFilter($request)->paginate(15)->withQueryString();

        return view('dataguru', compact('guru'));
    }

    public function store(Request $request)
    {
        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);

        $validated = $request->validate([
            // NIP juga dipakai untuk login aplikasi: tidak boleh sama dengan NIP atau username akun lain.
            'nip' => ['nullable', 'string', 'max:30', 'unique:users,nip', 'unique:users,username'],
            'nama_lengkap' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'username' => ['required', 'string', 'max:50', 'unique:users,username', 'unique:users,nip', Rule::notIn(array_map('strtolower', config('admin.usernames', ['admin'])))],
            'email' => 'required|email|max:255|unique:users,email',
            'no_hp' => 'nullable|string|max:20',
            'status' => 'required|in:Aktif,Pensiun,Pindah',
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ], [
            'password.regex' => 'Password harus mengandung huruf besar, huruf kecil, dan angka.',
        ]);

        $validated['name'] = explode(' ', $validated['nama_lengkap'])[0] ?? $validated['nama_lengkap'];
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated)->forceFill(['wajib_ganti_sandi' => true])->save();
        Cache::forget('guru_list_all');

        return redirect()->route('guru.index')->with('success', 'Data guru berhasil ditambahkan!');
    }

    public function update(Request $request, User $guru)
    {
        $request->merge(['username' => strtolower(trim((string) $request->input('username')))]);
        $adminUsernames = array_map('strtolower', config('admin.usernames', ['admin']));

        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('users', 'nip')->ignore($guru->id), Rule::unique('users', 'username')->ignore($guru->id)],
            'nama_lengkap' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'username' => array_filter([
                'required', 'string', 'max:50',
                Rule::unique('users', 'username')->ignore($guru->id),
                Rule::unique('users', 'nip')->ignore($guru->id),
                $guru->isAdmin() ? Rule::in([strtolower($guru->username)]) : Rule::notIn($adminUsernames),
            ]),
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($guru->id)],
            'no_hp' => 'nullable|string|max:20',
            'status' => $guru->isAdmin() ? 'required|in:Aktif' : 'required|in:Aktif,Pensiun,Pindah',
            'password' => ['nullable', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ], [
            'password.regex' => 'Password harus mengandung huruf besar, huruf kecil, dan angka.',
        ]);

        $validated['name'] = explode(' ', $validated['nama_lengkap'])[0] ?? $validated['nama_lengkap'];

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
            // Sandi direset admin: guru wajib menggantinya lagi.
            $guru->wajib_ganti_sandi = true;
        } else {
            unset($validated['password']);
        }

        $guru->update($validated);
        Cache::forget('guru_list_all');

        // Sandi direset atau akun dinonaktifkan: cabut token aplikasi di semua perangkat.
        if ($guru->wasChanged(['password', 'status'])) {
            $guru->tokens()->delete();
        }

        return redirect()->route('guru.index')->with('success', 'Data guru berhasil diperbarui!');
    }

    public function destroy(User $guru)
    {
        // Prevent deleting admin (sinta)
        if ($guru->isAdmin()) {
            return redirect()->route('guru.index')->with('error', 'Tidak dapat menghapus akun admin!');
        }

        $guru->delete();
        Cache::forget('guru_list_all');

        return redirect()->route('guru.index')->with('success', 'Data guru berhasil dihapus!');
    }

    /**
     * Export guru as CSV
     */
    public function exportCsv(Request $request)
    {
        $data = $this->queryFilter($request)->get();
        $filename = 'data_guru_'.Sekolah::now()->format('Ymd_His').'.csv';

        return $this->streamCsv($filename, function ($file) use ($data) {
            $this->putKopCsv($file, 'LAPORAN DATA GURU', [
                ['Tanggal Cetak', ':', Sekolah::now()->translatedFormat('d F Y')],
            ]);

            fputcsv($file, ['No', 'NIP', 'Nama Lengkap', 'Gender', 'Username', 'Email', 'No HP', 'Status']);
            foreach ($data as $i => $g) {
                $this->putSafeCsv($file, [
                    $i + 1,
                    $g->nip ?? '-',
                    $g->nama_lengkap ?? $g->name,
                    $g->jenis_kelamin ?? '-',
                    $g->username ?? '-',
                    $g->email,
                    $g->no_hp ?? '-',
                    $g->status ?? 'Aktif',
                ]);
            }
        });
    }

    /**
     * Export guru as PDF (HTML-based printable)
     */
    public function exportPdf(Request $request)
    {
        $data = $this->queryFilter($request)->get();

        return view('exports.guru-pdf', compact('data'));
    }

    private function queryFilter(Request $request): Builder
    {
        return User::query()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($sub) => $sub
                ->where('nama_lengkap', 'like', "%{$request->search}%")
                ->orWhere('nip', 'like', "%{$request->search}%")
                ->orWhere('name', 'like', "%{$request->search}%")))
            ->when($request->filled('jenis_kelamin'), fn ($q) => $q->where('jenis_kelamin', $request->jenis_kelamin))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('nama_lengkap');
    }
}
