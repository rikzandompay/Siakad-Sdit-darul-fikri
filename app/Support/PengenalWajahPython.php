<?php

namespace App\Support;

use Symfony\Component\Process\Process;

/**
 * Menjalankan scripts/wajah/embedding.py (YuNet + MobileFaceNet yang sama dengan aplikasi).
 * Python dan paketnya dipasang terpisah di server, mis. venv /opt/siakad-wajah
 * (lihat config presensi_guru.python_wajah / env GURU_WAJAH_PYTHON).
 */
class PengenalWajahPython implements PengenalWajah
{
    public function dariFoto(string $pathFoto): array
    {
        $proses = new Process([config('presensi_guru.python_wajah'), '-I', base_path('scripts/wajah/embedding.py'), $pathFoto]);
        $proses->setTimeout(90);
        $proses->run();

        $hasil = json_decode(trim($proses->getOutput()), true);
        if (! $proses->isSuccessful() || ! is_array($hasil)) {
            throw new \RuntimeException('Pembuat acuan wajah gagal dijalankan: '.mb_substr(trim($proses->getErrorOutput()), -500));
        }
        if (isset($hasil['error'])) {
            throw new PengenalWajahGagal((string) $hasil['error'], (string) ($hasil['pesan'] ?? 'Wajah pada foto profil tidak terbaca.'));
        }
        $embedding = $hasil['embedding'] ?? null;
        if (! is_array($embedding) || count($embedding) < 64) {
            throw new \RuntimeException('Keluaran pembuat acuan wajah tidak valid.');
        }

        return array_map('floatval', $embedding);
    }
}
