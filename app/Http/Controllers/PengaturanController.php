<?php

namespace App\Http\Controllers;

use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PengaturanController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tahunAjaranList = Cache::remember('tahun_ajaran_list', 300, fn () => TahunAjaran::orderByDesc('id')->get());
        $mapelList = Cache::remember('mapel_list_all', 300, fn () => MataPelajaran::orderBy('nama_pelajaran')->get());

        return view('pengaturan', compact('user', 'tahunAjaranList', 'mapelList'));
    }

    // ── Profil ──
    public function updateProfil(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            // NIP juga dipakai untuk login aplikasi, jadi tidak boleh sama dengan NIP atau username akun lain.
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('users', 'nip')->ignore($user->id), Rule::unique('users', 'username')->ignore($user->id)],
            'no_hp' => 'nullable|string|max:20',
        ], [
            'nip.unique' => 'NIP sudah dipakai sebagai NIP atau username akun lain.',
        ]);

        $user->update($validated);
        $user->update(['name' => explode(' ', $validated['nama_lengkap'])[0]]);

        return redirect()->route('pengaturan.index')->with('success', 'Profil berhasil diperbarui!');
    }

    public function updateFoto(Request $request)
    {
        $request->validate(['foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120']);
        $aktif = Auth::user()->unggahFoto($request->file('foto'));

        return redirect()->route('pengaturan.index')->with('success', $aktif
            ? 'Foto profil diperbarui. Foto yang sama dipakai di aplikasi dan Kartu Guru.'
            : 'Foto terkirim dan menunggu persetujuan kepala sekolah.');
    }

    /** Admin menghapus fotonya sendiri; guru hanya bisa membatalkan pengajuan yang menunggu. */
    public function hapusFoto()
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            $user->hapusFoto();

            return redirect()->route('pengaturan.index')->with('success', 'Foto profil dihapus.');
        }

        if (! $user->foto_pengajuan) {
            return redirect()->route('pengaturan.index')->withErrors(['foto' => 'Foto profil hanya bisa diganti lewat pengajuan foto baru.']);
        }
        $user->batalkanFotoPengajuan();

        return redirect()->route('pengaturan.index')->with('success', 'Pengajuan foto dibatalkan.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_+=\-]).+$/',
            ],
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.regex' => 'Password harus mengandung huruf besar, huruf kecil, angka, dan simbol.',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama tidak sesuai.']);
        }

        $user->forceFill(['password' => Hash::make($request->password), 'wajib_ganti_sandi' => false])->save();

        return redirect()->route('pengaturan.index')->with('success', 'Password berhasil diperbarui!');
    }

    // ── Tahun Ajaran ──
    public function storeTahunAjaran(Request $request)
    {
        $request->validate([
            'tahun' => 'required|string|max:20',
            'semester' => 'required|in:Ganjil,Genap',
            'status_aktif' => 'required|in:Y,N',
        ]);

        $namaSemester = $request->tahun.' - '.$request->semester;

        $ta = TahunAjaran::create([
            'nama_semester' => $namaSemester,
            'status_aktif' => $request->status_aktif,
        ]);

        if ($request->status_aktif == 'Y') {
            $ta->setSemesterAktif();
        }

        $this->clearTahunAjaranCache();

        return redirect()->route('pengaturan.index')->with('success', 'Tahun ajaran berhasil ditambahkan!');
    }

    public function updateTahunAjaran(Request $request, TahunAjaran $tahunAjaran)
    {
        $request->validate([
            'tahun' => 'required|string|max:20',
            'semester' => 'required|in:Ganjil,Genap',
            'status_aktif' => 'required|in:Y,N',
        ]);

        $namaSemester = $request->tahun.' - '.$request->semester;

        $tahunAjaran->update([
            'nama_semester' => $namaSemester,
            'status_aktif' => $request->status_aktif,
        ]);

        if ($request->status_aktif == 'Y') {
            $tahunAjaran->setSemesterAktif();
        }

        $this->clearTahunAjaranCache();

        return redirect()->route('pengaturan.index')->with('success', 'Tahun ajaran berhasil diperbarui!');
    }

    public function setAktifTahunAjaran(TahunAjaran $tahunAjaran)
    {
        $tahunAjaran->setSemesterAktif();
        $this->clearTahunAjaranCache();

        return redirect()->route('pengaturan.index')->with('success', 'Tahun ajaran aktif berhasil diubah!');
    }

    public function destroyTahunAjaran(TahunAjaran $tahunAjaran)
    {
        $tahunAjaran->delete();
        $this->clearTahunAjaranCache();

        return redirect()->route('pengaturan.index')->with('success', 'Tahun ajaran berhasil dihapus!');
    }

    // ── Mata Pelajaran ──
    public function storeMapel(Request $request)
    {
        $validated = $request->validate([
            'kode_pelajaran' => 'required|string|max:20|unique:mata_pelajaran,kode_pelajaran',
            'nama_pelajaran' => 'required|string|max:100',
        ]);

        MataPelajaran::create($validated);
        $this->clearMapelCache();

        return redirect()->route('pengaturan.index')->with('success', 'Mata pelajaran berhasil ditambahkan!');
    }

    public function updateMapel(Request $request, MataPelajaran $mapel)
    {
        $validated = $request->validate([
            'kode_pelajaran' => 'required|string|max:20|unique:mata_pelajaran,kode_pelajaran,'.$mapel->id,
            'nama_pelajaran' => 'required|string|max:100',
        ]);

        $mapel->update($validated);
        $this->clearMapelCache();

        return redirect()->route('pengaturan.index')->with('success', 'Mata pelajaran berhasil diperbarui!');
    }

    public function destroyMapel(MataPelajaran $mapel)
    {
        $mapel->delete();
        $this->clearMapelCache();

        return redirect()->route('pengaturan.index')->with('success', 'Mata pelajaran berhasil dihapus!');
    }

    private function clearTahunAjaranCache(): void
    {
        Cache::forget('tahun_ajaran_list');
        Cache::forget('tahun_aktif');
    }

    private function clearMapelCache(): void
    {
        Cache::forget('mapel_list_all');
    }
}
