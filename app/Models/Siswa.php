<?php

namespace App\Models;

use App\Support\FotoPrivat;
use App\Support\Sekolah;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * App\Models\Siswa
 *
 * @property int $id
 * @property string $nis
 * @property string $nisn
 * @property string $nama_siswa
 * @property string $tanggal_lahir
 * @property string|null $alamat
 * @property string $jenis_kelamin
 * @property int $kelas_id
 * @property string|null $nama_wali
 * @property string|null $no_hp_wali
 * @property string $status
 * @property string|null $qr_token
 * @property-read Kelas $kelas
 */
class Siswa extends Model
{
    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'nisn',
        'nama_siswa',
        'tanggal_lahir',
        'alamat',
        'jenis_kelamin',
        'kelas_id',
        'nama_wali',
        'no_hp_wali',
        'status',
        'foto',
    ];

    /** Token QR berfungsi seperti kartu presensi; jangan ikut ter-serialisasi ke HTML/JSON. */
    protected $hidden = ['qr_token'];

    /** Awalan isi QR Kartu Pelajar, dibaca aplikasi MyDarfik saat scan sholat. */
    public const QR_PREFIX = 'MYDARFIK:SISWA:';

    protected static function booted(): void
    {
        // Akun wali di aplikasi mengikuti data siswa (NIS + tanggal lahir).
        // Kegagalan sinkron (mis. migrasi belum jalan) dilaporkan, tapi tidak boleh menggagalkan simpan siswa.
        static::saved(function (Siswa $siswa) {
            try {
                Wali::sinkronDariSiswa($siswa);
            } catch (QueryException $e) {
                report($e);
            }
        });
        static::deleting(function (Siswa $siswa) {
            try {
                Wali::lepasSiswa($siswa);
            } catch (QueryException $e) {
                report($e);
            }
        });

        static::creating(function (Siswa $siswa) {
            if (empty($siswa->qr_token)) {
                $siswa->qr_token = static::generateQrToken();
            }
        });
    }

    public static function generateQrToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('qr_token', $token)->exists());

        return $token;
    }

    /** Ganti QR (misalnya kartu hilang); QR lama langsung tidak berlaku. */
    public function regenerateQrToken(): void
    {
        $this->qr_token = static::generateQrToken();
        $this->save();
    }

    public function getQrPayloadAttribute(): string
    {
        return self::QR_PREFIX.$this->qr_token;
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function wali()
    {
        return $this->belongsToMany(Wali::class, 'siswa_wali', 'siswa_id', 'wali_id');
    }

    public function presensiSholat()
    {
        return $this->hasMany(PresensiSholat::class, 'siswa_id');
    }

    public function presensi()
    {
        return $this->hasMany(Presensi::class, 'siswa_id');
    }

    public function nilaiRapot()
    {
        return $this->hasMany(NilaiRapot::class, 'siswa_id');
    }

    /** URL foto siswa (rute terlindungi login), null bila belum ada. */
    public function fotoUrl(): ?string
    {
        return $this->foto ? route('foto.siswa', ['siswa' => $this->id, 'v' => FotoPrivat::versi($this->foto)]) : null;
    }

    /** Foto siswa sebagai data-URI (untuk PDF; berkasnya privat sehingga tidak bisa dimuat lewat URL). */
    public function fotoDataUri(): ?string
    {
        return FotoPrivat::dataUri($this->foto);
    }

    /** QR presensi sholat sebagai SVG (tanpa deklarasi XML); token dibuat bila belum ada. */
    public function qrSvg(int $ukuran = 240): string
    {
        if (! $this->qr_token) {
            $this->regenerateQrToken();
        }
        $writer = new Writer(new ImageRenderer(new RendererStyle($ukuran, 1), new SvgImageBackEnd));

        return preg_replace('/^<\?xml[^>]*>\s*/', '', $writer->writeString($this->qr_payload));
    }

    /**
     * Variabel untuk tampilan kartu pelajar (halaman web atau PDF).
     * PDF memakai berkas lokal / data-URI karena dompdf tidak membawa sesi login.
     */
    /** Gambar kartu identitas (lambang, hiasan, ikon, tanda tangan): jalur berkas untuk PDF, URL untuk web. */
    public static function asetKartu(bool $pdf = false): array
    {
        return collect(['logo' => 'Lgo.png', 'swoosh_tr' => 'images/kartu/kartu_swoosh_tr.png', 'swoosh_bl' => 'images/kartu/kartu_swoosh_bl.png',
            'watermark' => 'images/kartu/kartu_watermark.png', 'ttd' => 'images/kartu/kartu_ttd.png', 'ikon_nis' => 'images/kartu/kartu_ikon_nis.png',
            'ikon_kelas' => 'images/kartu/kartu_ikon_kelas.png', 'ikon_alamat' => 'images/kartu/kartu_ikon_alamat.png'])
            ->map(fn ($jalur) => $pdf ? public_path($jalur) : asset($jalur))->all();
    }

    public function dataKartu(bool $pdf = false): array
    {
        $this->loadMissing('kelas');

        return [
            'siswa' => $this,
            'aset' => static::asetKartu($pdf),
            'kepalaSekolah' => Sekolah::kepalaSekolah(),
            'foto' => $pdf ? $this->fotoDataUri() : $this->fotoUrl(),
            'qrSrc' => 'data:image/svg+xml;base64,'.base64_encode($this->qrSvg()),
            'tahunAjaran' => TahunAjaran::where('status_aktif', 'Y')->value('nama_semester'),
        ];
    }

    public function simpanFoto(UploadedFile $file): string
    {
        $path = FotoPrivat::simpan($file, 'foto_siswa', $this->foto);
        $this->update(['foto' => $path]);

        return $path;
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->nama_siswa);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials;
    }
}
