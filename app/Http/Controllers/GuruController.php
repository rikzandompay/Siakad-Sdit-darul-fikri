<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filter by gender
        if ($request->filled('jenis_kelamin') && $request->jenis_kelamin !== '') {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $guru = $query->orderBy('nama_lengkap')->paginate(15)->withQueryString();

        return view('dataguru', compact('guru'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'nullable|string|max:30|unique:users,nip',
            'nama_lengkap' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'no_hp' => 'nullable|string|max:20',
            'status' => 'required|in:Aktif,Pensiun,Pindah',
            'password' => 'required|string|min:6',
        ]);

        $validated['name'] = explode(' ', $validated['nama_lengkap'])[0] ?? $validated['nama_lengkap'];
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('guru.index')->with('success', 'Data guru berhasil ditambahkan!');
    }

    public function update(Request $request, User $guru)
    {
        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('users', 'nip')->ignore($guru->id)],
            'nama_lengkap' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($guru->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($guru->id)],
            'no_hp' => 'nullable|string|max:20',
            'status' => 'required|in:Aktif,Pensiun,Pindah',
            'password' => 'nullable|string|min:6',
        ]);

        $validated['name'] = explode(' ', $validated['nama_lengkap'])[0] ?? $validated['nama_lengkap'];

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $guru->update($validated);

        return redirect()->route('guru.index')->with('success', 'Data guru berhasil diperbarui!');
    }

    public function destroy(User $guru)
    {
        // Prevent deleting admin (sinta)
        if ($guru->isAdmin()) {
            return redirect()->route('guru.index')->with('error', 'Tidak dapat menghapus akun admin!');
        }

        $guru->delete();
        return redirect()->route('guru.index')->with('success', 'Data guru berhasil dihapus!');
    }

    /**
     * Export guru as CSV
     */
    public function exportCsv(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('jenis_kelamin') && $request->jenis_kelamin !== '') {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $data = $query->orderBy('nama_lengkap')->get();
        $filename = 'data_guru_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header Section
            fputcsv($file, ['YAYASAN PENDIDIKAN DARUL FIKRI']);
            fputcsv($file, ['SD IT DARUL FIKRI']);
            fputcsv($file, ['LAPORAN DATA GURU']);
            fputcsv($file, []);
            fputcsv($file, ['Tanggal Cetak', ':', now()->translatedFormat('d F Y')]);
            fputcsv($file, []);

            fputcsv($file, ['No', 'NIP', 'Nama Lengkap', 'Gender', 'Username', 'Email', 'No HP', 'Status']);
            $no = 1;
            foreach ($data as $g) {
                fputcsv($file, [
                    $no++,
                    $g->nip ?? '-',
                    $g->nama_lengkap ?? $g->name,
                    $g->jenis_kelamin ?? '-',
                    $g->username ?? '-',
                    $g->email,
                    $g->no_hp ?? '-',
                    $g->status ?? 'Aktif',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export guru as PDF (HTML-based printable)
     */
    public function exportPdf(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('jenis_kelamin') && $request->jenis_kelamin !== '') {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $data = $query->orderBy('nama_lengkap')->get();

        return view('exports.guru-pdf', compact('data'));
    }
}
