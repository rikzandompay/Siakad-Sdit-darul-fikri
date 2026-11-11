<?php

namespace App\Console\Commands;

use App\Jobs\BuatAcuanWajah;
use App\Models\User;
use App\Models\WajahGuru;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/** Membuat ulang acuan wajah dari foto profil di server (mis. setelah pindah dari acuan buatan HP). */
class BuatAcuanWajahCommand extends Command
{
    protected $signature = 'wajah:buat-acuan {guru? : username guru} {--semua : semua guru yang punya foto profil}';

    protected $description = 'Buat ulang acuan wajah presensi dari foto profil guru (dihitung di server)';

    public function handle(): int
    {
        if (! $this->argument('guru') && ! $this->option('semua')) {
            $this->error('Sebutkan username guru atau pakai --semua.');

            return self::FAILURE;
        }

        $guru = User::whereNotNull('foto_profil')
            ->when($this->argument('guru'), fn ($q, $u) => $q->where('username', $u))
            ->orderBy('id')->get();

        foreach ($guru as $g) {
            WajahGuru::where('guru_id', $g->id)->delete();
            BuatAcuanWajah::dispatchSync($g->id);
            $nama = $g->nama_lengkap ?? $g->username;
            WajahGuru::where('guru_id', $g->id)->exists()
                ? $this->line("✓ {$nama}")
                : $this->warn("✗ {$nama}: ".(Cache::get(BuatAcuanWajah::kunciGagal($g->id)) ?? 'gagal'));
        }
        $this->info($guru->count().' guru diproses.');

        return self::SUCCESS;
    }
}
