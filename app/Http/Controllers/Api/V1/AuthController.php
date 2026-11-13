<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Wali\WaliController;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use App\Support\BatasLoginGuru;
use App\Support\KunciLogin;
use App\Support\Sekolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Login aplikasi MyDarfik.
 * - Guru & Staf: username atau NIP + kata sandi (akun web yang sama).
 * - Wali murid : NIS siswa + tanggal lahir siswa YY-MM-DD (akun dibuat otomatis dari data siswa).
 * Guru: salah 5 kali dari IP yang sama mengunci 5 menit untuk IP itu; 30 salah per 15 menit dari
 *       semua IP mengunci akun (BatasLoginGuru).
 * Wali: kunci bertingkat sampai 24 jam (KunciLogin) dan maksimal 30 salah per IP per jam.
 */
class AuthController extends Controller
{
    /** Login wali salah per IP per jam, dijumlah dari semua NIS. */
    private const MAKS_GAGAL_WALI_PER_IP = 30;

    private function tolakTerkunci(int $detik, string $awalan = ''): never
    {
        throw ValidationException::withMessages([
            'username' => $awalan.'Terlalu banyak percobaan. Coba lagi dalam '.KunciLogin::teks($detik).'.',
        ])->status(429);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'peran' => ['required', Rule::in(['guru', 'wali'])],
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:100',
        ]);

        $wali = $data['peran'] === 'wali';
        $kunci = 'api-login:'.$data['peran'].':'.mb_strtolower(trim($data['username']));
        $kunciIp = 'api-login-wali-ip:'.$request->ip();

        if ($wali) {
            // Tanggal lahir mudah ditebak: kunci akun bertingkat (lihat KunciLogin) dan batasi
            // jumlah salah per IP agar satu orang tidak bisa menyisir banyak NIS sekaligus.
            if ($detik = KunciLogin::sisa($kunci)) {
                $this->tolakTerkunci($detik);
            }
            if (RateLimiter::tooManyAttempts($kunciIp, self::MAKS_GAGAL_WALI_PER_IP)) {
                $this->tolakTerkunci(RateLimiter::availableIn($kunciIp));
            }
        } elseif ($detik = BatasLoginGuru::sisa($data['username'], $request->ip())) {
            $this->tolakTerkunci($detik);
        }

        $akun = $wali
            ? $this->cekWali($data['username'], $data['password'])
            : $this->cekGuru($data['username'], $data['password']);

        if (! $akun) {
            if ($wali) {
                RateLimiter::hit($kunciIp, 3600);
                if ($lama = KunciLogin::gagal($kunci)) {
                    $this->tolakTerkunci($lama, 'NIS atau kata sandi tidak cocok. ');
                }
            } else {
                BatasLoginGuru::gagal($data['username'], $request->ip());
            }
            throw ValidationException::withMessages([
                'username' => $wali
                    ? 'NIS atau kata sandi (tanggal lahir YY-MM-DD) tidak cocok.'
                    : 'Username/NIP atau kata sandi salah.',
            ]);
        }

        $wali ? KunciLogin::bersihkan($kunci) : BatasLoginGuru::berhasil($data['username'], $request->ip());
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
            'sandi_lama' => 'required|string|max:255',
            'sandi_baru' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ], ['sandi_baru.regex' => 'Kata sandi harus mengandung huruf besar, huruf kecil, dan angka.']);

        // Wali: sandi lama bisa berupa tanggal lahir siswa dalam format apa pun.
        $cocok = $user instanceof Wali ? $user->cocokSandi($request->input('sandi_lama')) : Hash::check($request->input('sandi_lama'), $user->password);
        if (! $cocok) {
            throw ValidationException::withMessages(['sandi_lama' => 'Kata sandi lama tidak sesuai.']);
        }
        if (Hash::check($request->input('sandi_baru'), $user->password)) {
            throw ValidationException::withMessages(['sandi_baru' => 'Kata sandi baru harus berbeda dari kata sandi lama.']);
        }

        $user->forceFill(['password' => $request->input('sandi_baru'), 'wajib_ganti_sandi' => false])->save();
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
            'message' => $aktif ? 'Foto profil berhasil diperbarui dan menjadi acuan wajah saat absen.' : 'Foto terkirim dan menunggu persetujuan kepala sekolah.',
            'foto_url' => $user->fotoUrl(),
            'menunggu' => ! $aktif,
        ]);
    }

    private function cekGuru(string $username, string $password): ?User
    {
        // Username persis lebih dulu, baru NIP, agar NIP yang kebetulan sama dengan username
        // guru lain tidak membuat login salah akun.
        $user = User::where('status', 'Aktif')->where('username', $username)->first()
            ?? User::where('status', 'Aktif')->where('nip', $username)->first();

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
                'wajib_ganti_sandi' => (bool) $akun->wajib_ganti_sandi,
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
            'wajib_ganti_sandi' => (bool) $akun->wajib_ganti_sandi,
        ];
    }
}
