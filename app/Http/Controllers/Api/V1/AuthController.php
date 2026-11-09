<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Wali\WaliController;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use App\Support\Sekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Login aplikasi MyDarfik.
 * - Guru & Staf: username atau NIP + kata sandi (akun web yang sama).
 * - Wali murid : NIS siswa + tanggal lahir siswa YY-MM-DD (akun dibuat otomatis dari data siswa).
 * Salah 5 kali untuk akun yang sama mengunci login akun itu 5 menit (dari HP mana pun).
 */
class AuthController extends Controller
{
    private const MAKS_PERCOBAAN = 5;

    private const KUNCI_DETIK = 300;

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'peran' => ['required', Rule::in(['guru', 'wali'])],
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:100',
        ]);

        $kunci = 'api-login:'.$data['peran'].':'.mb_strtolower(trim($data['username']));
        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN)) {
            $detik = RateLimiter::availableIn($kunci);
            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan. Coba lagi dalam '.ceil($detik / 60).' menit.',
            ])->status(429);
        }

        $akun = $data['peran'] === 'guru'
            ? $this->cekGuru($data['username'], $data['password'])
            : $this->cekWali($data['username'], $data['password']);

        if (! $akun) {
            RateLimiter::hit($kunci, self::KUNCI_DETIK);
            throw ValidationException::withMessages([
                'username' => $data['peran'] === 'guru'
                    ? 'Username/NIP atau kata sandi salah.'
                    : 'NIS atau tanggal lahir (YY-MM-DD) tidak cocok.',
            ]);
        }

        RateLimiter::clear($kunci);
        if ($akun instanceof Wali) {
            $akun->forceFill(['terakhir_login_pada' => now()])->save();
        }

        $token = $akun->createToken($data['device_name'] ?? 'MyDarfik', [$data['peran']]);

        return response()->json([
            'token' => $token->plainTextToken,
            'peran' => $data['peran'],
            'akun' => $this->profil($akun),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['peran' => $request->user()->tokenCan('guru') ? 'guru' : 'wali', 'akun' => $this->profil($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function ubahSandi(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate([
            'sandi_lama' => 'required|string',
            'sandi_baru' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ], ['sandi_baru.regex' => 'Kata sandi harus mengandung huruf besar, huruf kecil, dan angka.']);

        if (! Hash::check($request->input('sandi_lama'), $user->password)) {
            throw ValidationException::withMessages(['sandi_lama' => 'Kata sandi lama tidak sesuai.']);
        }

        $user->update(['password' => $request->input('sandi_baru')]);
        // Keluarkan perangkat lain, pertahankan sesi saat ini.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Kata sandi diperbarui.']);
    }

    public function uploadFotoGuru(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $aktif = $user->unggahFoto($request->file('foto'));

        return response()->json([
            'message' => $aktif ? 'Foto profil berhasil diperbarui. Daftarkan wajah Anda lagi sebelum absen.' : 'Foto terkirim dan menunggu persetujuan kepala sekolah.',
            'foto_url' => $user->fotoUrl(),
            'menunggu' => ! $aktif,
        ]);
    }

    private function cekGuru(string $username, string $password): ?User
    {
        $user = User::where('status', 'Aktif')
            ->where(fn ($q) => $q->where('username', $username)->orWhere('nip', $username))
            ->first();

        return $user && Hash::check($password, $user->password) ? $user : null;
    }

    private function cekWali(string $nis, string $tanggalLahir): ?Wali
    {
        $wali = Wali::where('username', trim($nis))->where('status', 'Aktif')->first();

        return $wali && $wali->cocokSandi($tanggalLahir) ? $wali : null;
    }

    /** Data kartu & profil yang ditampilkan aplikasi. */
    private function profil(User|Wali $akun): array
    {
        $tahun = TahunAjaran::where('status_aktif', 'Y')->value('nama_semester');

        if ($akun instanceof Wali) {
            $akun->loadMissing('siswa.kelas.waliKelas');

            return [
                'id' => $akun->id,
                'nama' => $akun->nama,
                'no_hp' => $akun->no_hp,
                'tahun_ajaran' => $tahun,
                'anak' => $akun->siswa->sortBy('nama_siswa')->map(fn ($s) => WaliController::dataAnak($s))->values(),
            ];
        }

        $waliKelas = Kelas::where('wali_kelas_id', $akun->id)->orderBy('nama_kelas')->pluck('nama_kelas');
        $mapel = $akun->jadwalPelajaran()->with('mataPelajaran')->get()
            ->pluck('mataPelajaran.nama_pelajaran')->filter()->unique()->sort()->values();

        return [
            'id' => $akun->id,
            'nama' => $akun->nama_lengkap ?? $akun->name,
            'username' => $akun->username,
            'nip' => $akun->nip,
            'no_hp' => $akun->no_hp,
            'jenis_kelamin' => $akun->jenis_kelamin,
            'foto_url' => $akun->fotoUrl(),
            'foto_menunggu' => (bool) $akun->foto_pengajuan,
            'inisial' => Sekolah::inisial($akun->nama_lengkap ?? $akun->name),
            'jabatan' => $akun->isAdmin() ? 'Kepala Sekolah' : 'Guru',
            'is_admin' => $akun->isAdmin(),
            'mata_pelajaran' => $mapel,
            'wali_kelas' => $waliKelas,
            'sekolah' => Sekolah::NAMA,
            'tahun_ajaran' => $tahun,
        ];
    }
}
