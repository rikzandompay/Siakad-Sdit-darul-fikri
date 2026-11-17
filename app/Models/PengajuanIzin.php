<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PengajuanIzin extends Model
{
    public const MENUNGGU = 'Menunggu';

    public const DISETUJUI = 'Disetujui';

    public const DITOLAK = 'Ditolak';

    public const JENIS = ['Sakit', 'Izin'];

    /** Kode di tabel presensi untuk tiap jenis izin. */
    public const KODE_PRESENSI = ['Sakit' => 'S', 'Izin' => 'I'];

    protected $table = 'pengajuan_izin';

    protected $fillable = [
        'siswa_id', 'wali_id', 'jenis', 'tanggal_mulai', 'tanggal_selesai', 'alasan',
        'status', 'ditinjau_oleh', 'ditinjau_pada', 'catatan_peninjau', 'lampiran',
    ];

    protected function casts(): array
    {
        return ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date', 'ditinjau_pada' => 'datetime'];
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function wali()
    {
        return $this->belongsTo(Wali::class, 'wali_id');
    }

    public function peninjau()
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    /** Admin melihat semua; guru hanya izin siswa di kelas yang ia walikan. */
    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->whereHas('siswa.kelas', fn ($q) => $q->where('wali_kelas_id', $user->id));
    }

    public function bisaDitinjauOleh(User $user): bool
    {
        return $user->isAdmin() || $this->siswa?->kelas?->wali_kelas_id === $user->id;
    }

    /**
     * Setujui izin dan tulis status S/I ke presensi kelas pada setiap jadwal di hari sekolah
     * (bukan hari libur) dalam rentang izin. Mengembalikan jumlah presensi yang ditulis.
     */
    public function setujui(User $peninjau, ?string $catatan = null): int
    {
        return DB::transaction(function () use ($peninjau, $catatan) {
            $this->update([
                'status' => self::DISETUJUI, 'ditinjau_oleh' => $peninjau->id,
                'ditinjau_pada' => now(), 'catatan_peninjau' => $catatan,
            ]);

            $kelasId = $this->siswa->kelas_id;
            if (! $kelasId) {
                return 0;
            }
            $hariNama = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
            $jadwalPerHari = JadwalPelajaran::where('kelas_id', $kelasId)->get(['id', 'hari'])->groupBy('hari');
            $now = now();
            $rows = [];
            for ($d = Carbon::parse($this->tanggal_mulai); $d->lte($this->tanggal_selesai); $d->addDay()) {
                $hari = $hariNama[$d->dayOfWeekIso] ?? null;
                if (! $hari || HariLibur::isLibur($d)) {
                    continue;
                }
                foreach ($jadwalPerHari->get($hari, []) as $jadwal) {
                    $rows[] = [
                        'jadwal_id' => $jadwal->id, 'siswa_id' => $this->siswa_id, 'tanggal' => $d->toDateString(),
                        'status_kehadiran' => self::KODE_PRESENSI[$this->jenis], 'keterangan' => "{$this->jenis} (disetujui)",
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                }
            }
            if ($rows) {
                Presensi::upsert($rows, ['jadwal_id', 'siswa_id', 'tanggal'], ['status_kehadiran', 'keterangan', 'updated_at']);
            }

            return count($rows);
        });
    }

    public function tolak(User $peninjau, string $catatan): void
    {
        $this->update([
            'status' => self::DITOLAK, 'ditinjau_oleh' => $peninjau->id,
            'ditinjau_pada' => now(), 'catatan_peninjau' => $catatan,
        ]);
    }

    public function jumlahHari(): int
    {
        return Carbon::parse($this->tanggal_mulai)->diffInDays($this->tanggal_selesai) + 1;
    }
}
